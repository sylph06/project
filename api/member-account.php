<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../google_auth.php';

$user = current_user();
if (!$user) out(['ok' => false, 'error' => 'Please sign in again.'], 401);
if (($user['role'] ?? '') !== 'KK Member'
    || $user['legacy_id'] === null || (string)$user['legacy_id'] === '') {
    out(['ok' => false, 'error' => 'This action is available only to KK member accounts.'], 403);
}
if (!empty($user['must_change_password'])) {
    out(['ok' => false, 'error' => 'Change your temporary password before binding Google.'], 403);
}
if (!google_oauth_configured() || !google_oauth_schema_ready()) {
    out(['ok' => false, 'error' => 'Google account binding is not configured. Contact the administrator.'], 503);
}

$memberId = (string)$user['legacy_id'];
$submittedTicket = $in['googleRegistrationTicket'] ?? null;
$replaceProfileEmail = ($in['replaceProfileEmail'] ?? false) === true;
$currentPassword = $in['currentPassword'] ?? null;
$identity = google_auth_registration_ticket(
    $_SESSION['google_registration_ticket'] ?? null,
    $submittedTicket,
    null,
    'member_self_link',
    $memberId
);
if ($identity === null) {
    out(['ok' => false, 'error' => 'Verify the Google account from this Settings page, then try again.'], 403);
}

$pdo = db();
try {
    $pdo->beginTransaction();
    $accountQuery = $pdo->prepare('SELECT id, legacy_id, is_active, password, role, google_sub FROM users WHERE id = ? FOR UPDATE');
    $accountQuery->execute([(int)$user['id']]);
    $account = $accountQuery->fetch(PDO::FETCH_ASSOC);
    if (!$account || !(int)$account['is_active'] || (string)$account['legacy_id'] !== $memberId) {
        throw new DomainException('Your active KK login could not be matched to the member profile.');
    }
    if (kk_effective_role($account) !== 'KK Member') {
        throw new DomainException('Google binding is available only to KK member accounts. SK officials must use password sign-in.');
    }

    $members = google_auth_load_members($pdo, true);
    $memberIndex = null;
    foreach ($members as $index => $candidate) {
        if (is_array($candidate) && (string)($candidate['id'] ?? '') === $memberId) {
            $memberIndex = $index;
            break;
        }
    }
    if ($memberIndex === null || ($members[$memberIndex]['status'] ?? 'Active') !== 'Active') {
        throw new DomainException('Only an active KK member can bind a Google account.');
    }

    $member = $members[$memberIndex];
    $oldProfileEmail = trim((string)($member['email'] ?? ''));
    $emailBindingState = google_auth_profile_email_binding_state($oldProfileEmail, $identity['email']);
    if ($emailBindingState === 'invalid_google_email') {
        throw new DomainException('Use a Google account with a verified, valid email address.');
    }
    $profileEmailNeedsChange = $emailBindingState !== 'already_matches';
    if ($emailBindingState === 'replace_profile_email' && !$replaceProfileEmail) {
        throw new DomainException('This Google account (' . $identity['email'] . ') doesn\'t match your profile email (' .
            $oldProfileEmail . '). Choose "Use this Google email instead" and re-enter your password to continue.');
    }
    if ($replaceProfileEmail && $emailBindingState !== 'replace_profile_email') {
        throw new DomainException('A profile email replacement is not needed for this Google account.');
    }
    if ($emailBindingState === 'replace_profile_email') {
        if (!is_string($currentPassword) || $currentPassword === '' || strlen($currentPassword) > 4096
            || !password_verify($currentPassword, (string)$account['password'])) {
            usleep(600000);
            throw new DomainException('Your current password is incorrect.');
        }
    }
    if (!google_auth_changed_email_is_unique($members, $memberId, $identity['email'])) {
        throw new DomainException('This Google email is already assigned to another KK member.');
    }

    $subjectQuery = $pdo->prepare('SELECT id FROM users WHERE google_sub = ? AND id <> ? FOR UPDATE');
    $subjectQuery->execute([$identity['subject'], (int)$account['id']]);
    if ($subjectQuery->fetchColumn() !== false) {
        throw new DomainException('This Google account is already linked to another member.');
    }
    $subjectQuery = $pdo->prepare('SELECT user_id FROM google_account_links WHERE google_subject = ? FOR UPDATE');
    $subjectQuery->execute([$identity['subject']]);
    if ($subjectQuery->fetchColumn() !== false) {
        throw new DomainException('This Google account is already linked to a login.');
    }
    $emailQuery = $pdo->prepare('SELECT user_id FROM google_account_links WHERE google_email = ? FOR UPDATE');
    $emailQuery->execute([$identity['email']]);
    foreach ($emailQuery->fetchAll(PDO::FETCH_COLUMN) as $emailOwner) {
        if ((int)$emailOwner !== (int)$account['id']) {
            throw new DomainException('This Google email is already bound to another login.');
        }
    }
    if (!empty($account['google_sub'])) {
        throw new DomainException('A Google account is already bound to this KK login.');
    }
    $accountLinkQuery = $pdo->prepare('SELECT 1 FROM google_account_links WHERE user_id = ? FOR UPDATE');
    $accountLinkQuery->execute([(int)$account['id']]);
    if ($accountLinkQuery->fetchColumn()) {
        throw new DomainException('A Google account is already bound to this KK login.');
    }

    if ($profileEmailNeedsChange) {
        $member['email'] = $identity['email'];
        $members[$memberIndex] = $member;
        google_auth_save_member($pdo, $member, $user['username'], $members);
        sk_audit($pdo, $user, $memberId, 'google_profile_email_updated', [
            'email' => $oldProfileEmail,
        ], [
            'email' => $identity['email'],
            'reason' => 'Google account binding',
        ]);
    }
    $pdo->prepare('INSERT INTO google_account_links (user_id, google_subject, google_email) VALUES (?, ?, ?)')
        ->execute([(int)$account['id'], $identity['subject'], $identity['email']]);
    $pdo->prepare('UPDATE users SET google_sub = ? WHERE id = ?')
        ->execute([$identity['subject'], (int)$account['id']]);
    if (google_oauth_link_requests_schema_ready($pdo)) {
        $pdo->prepare('DELETE FROM google_account_link_requests WHERE user_id = ?')
            ->execute([(int)$account['id']]);
    }
    sk_audit($pdo, $user, $memberId, 'google_account_linked_self_service', null, [
        'google_email' => $identity['email'],
        'profile_email_updated' => $profileEmailNeedsChange,
    ]);
    $pdo->commit();
    unset($_SESSION['google_registration_ticket']);
    out(['ok' => true, 'email' => $identity['email'], 'profileEmailUpdated' => $profileEmailNeedsChange]);
} catch (DomainException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $exception->getMessage()], 409);
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ((string)$exception->getCode() === '23000') {
        out(['ok' => false, 'error' => 'This Google account or KK login is already linked.'], 409);
    }
    error_log('Self-service Google account binding failed: ' . $exception->getMessage());
    out(['ok' => false, 'error' => 'Could not bind the Google account. Please try again.'], 500);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Self-service Google account binding failed: ' . $exception->getMessage());
    out(['ok' => false, 'error' => 'Could not bind the Google account. Please try again.'], 500);
}

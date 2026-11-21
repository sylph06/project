<?php
/* =========================================================
   api/accounts.php — member account management and username recovery
   ========================================================= */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../member_account_logic.php';
require_once __DIR__ . '/../google_auth.php';

$action = $_GET['action'] ?? '';
$user = require_officer();

if (!in_array($action, [
    'accounts_unlinked', 'account_link', 'account_link_google_request',
    'account_google_links_pending', 'account_link_google_approve', 'account_link_google_reject',
    'account_create', 'account_reset', 'account_reset_member', 'account_username_member', 'account_deactivate',
], true)) {
    out(['ok' => false, 'error' => 'Unknown action.'], 400);
}

$googleLinkApprovalActions = ['account_google_links_pending', 'account_link_google_approve', 'account_link_google_reject'];
if ($action === 'account_link_google_request') {
    if (!can_request_google_member_links()) {
        out(['ok' => false, 'error' => 'Only an SK Kagawad, Chairperson, or Secretary can request a Google link.'], 403);
    }
} elseif (in_array($action, $googleLinkApprovalActions, true)) {
    if (!can_manage_sk_roles()) {
        out(['ok' => false, 'error' => 'Only the SK Chairperson or Secretary can review Google link requests.'], 403);
    }
} elseif (!can_manage_sk_roles()) {
    out(['ok' => false, 'error' => 'Only the SK Chairperson or SK Secretary can manage member accounts.'], 403);
}
$pdo = db();
if (in_array($action, [
    'account_link_google_request', 'account_google_links_pending',
    'account_link_google_approve', 'account_link_google_reject',
], true) && !google_oauth_link_requests_schema_ready($pdo)) {
    out(['ok' => false, 'error' => 'Google link approvals are not set up yet. Contact the administrator.'], 503);
}
if (in_array($action, ['account_link_google_request', 'account_link_google_approve'], true)
    && !google_oauth_schema_ready($pdo)) {
    out(['ok' => false, 'error' => 'Google sign-in is not configured. Contact the administrator.'], 503);
}
if ($action === 'accounts_unlinked') {
    $rows = $pdo->query('SELECT id, username, full_name, role, is_active FROM users WHERE legacy_id IS NULL ORDER BY full_name')
        ->fetchAll(PDO::FETCH_ASSOC);
    $linked = $pdo->query('SELECT legacy_id FROM users WHERE legacy_id IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);
    out(['ok' => true, 'accounts' => $rows, 'linkedLegacyIds' => $linked]);
}
if ($action === 'account_google_links_pending') {
    $requests = $pdo->query('SELECT r.request_id, r.legacy_id, r.google_email, r.requested_at,
            m.full_name AS member_name, requester.username AS requested_by
        FROM google_account_link_requests r
        INNER JOIN users m ON m.id = r.user_id AND m.legacy_id = r.legacy_id
        INNER JOIN users requester ON requester.id = r.requested_by
        ORDER BY r.requested_at, r.request_id')->fetchAll(PDO::FETCH_ASSOC);
    out(['ok' => true, 'requests' => $requests]);
}

$pdo->beginTransaction();
try {
    if ($action === 'account_link_google_request') {
        $legacyId = trim((string)($in['legacyId'] ?? ''));
        if ($legacyId === '') throw new RuntimeException('A valid KK member profile is required.');
        if (!google_oauth_configured()) {
            throw new RuntimeException('Google sign-in is not configured. Contact the administrator.');
        }
        $googleIdentity = google_auth_registration_ticket(
            $_SESSION['google_registration_ticket'] ?? null,
            $in['googleRegistrationTicket'] ?? null,
            null,
            'member_link',
            $legacyId
        );
        if ($googleIdentity === null) {
            throw new RuntimeException('Google verification expired. Start the link process again.');
        }

        $members = google_auth_load_members($pdo, true);
        $memberIndex = null;
        foreach ($members as $index => $candidate) {
            if (is_array($candidate) && (string)($candidate['id'] ?? '') === $legacyId) {
                $memberIndex = $index;
                break;
            }
        }
        if ($memberIndex === null || ($members[$memberIndex]['status'] ?? 'Active') !== 'Active') {
            throw new RuntimeException('Only an active KK member can be linked to Google.');
        }
        if (!google_auth_changed_email_is_unique($members, $legacyId, $googleIdentity['email'])) {
            throw new RuntimeException('That Google email is already assigned to another member profile.');
        }

        $accountQuery = $pdo->prepare('SELECT id, username FROM users
            WHERE legacy_id = ? AND is_active = 1 FOR UPDATE');
        $accountQuery->execute([$legacyId]);
        $account = $accountQuery->fetch(PDO::FETCH_ASSOC);
        if (!$account) throw new RuntimeException('Create this member’s login account before linking Google.');
        $accountId = (int)$account['id'];

        $subjectQuery = $pdo->prepare('SELECT user_id FROM google_account_links WHERE google_subject = ? FOR UPDATE');
        $subjectQuery->execute([$googleIdentity['subject']]);
        $subjectOwner = $subjectQuery->fetchColumn();
        if ($subjectOwner !== false) throw new RuntimeException('This Google account is already linked to a login.');
        $accountGoogleQuery = $pdo->prepare('SELECT google_subject FROM google_account_links WHERE user_id = ? FOR UPDATE');
        $accountGoogleQuery->execute([$accountId]);
        $existingSubject = $accountGoogleQuery->fetchColumn();
        if ($existingSubject !== false) throw new RuntimeException('This member already has a Google account linked.');

        $requestId = bin2hex(random_bytes(16));
        $pdo->prepare('INSERT INTO google_account_link_requests
            (request_id, user_id, legacy_id, google_subject, google_email, requested_by)
            VALUES (?, ?, ?, ?, ?, ?)')->execute([
                $requestId, $accountId, $legacyId, $googleIdentity['subject'],
                $googleIdentity['email'], (int)$user['id'],
            ]);
        sk_audit($pdo, $user, $legacyId, 'google_account_link_requested', null, [
            'request_id' => $requestId,
            'account_id' => $accountId,
            'email_verified' => true,
        ]);
        $pdo->commit();
        unset($_SESSION['google_registration_ticket']);
        out(['ok' => true, 'requestId' => $requestId]);
    }

    if ($action === 'account_link_google_approve' || $action === 'account_link_google_reject') {
        $requestId = strtolower(trim((string)($in['requestId'] ?? '')));
        if (!preg_match('/^[a-f0-9]{32}$/', $requestId)) {
            throw new RuntimeException('A valid Google link request is required.');
        }
        $requestQuery = $pdo->prepare('SELECT * FROM google_account_link_requests WHERE request_id = ? FOR UPDATE');
        $requestQuery->execute([$requestId]);
        $request = $requestQuery->fetch(PDO::FETCH_ASSOC);
        if (!$request) throw new RuntimeException('This Google link request is no longer pending.');

        $legacyId = (string)$request['legacy_id'];
        if ($action === 'account_link_google_reject') {
            $pdo->prepare('DELETE FROM google_account_link_requests WHERE request_id = ?')->execute([$requestId]);
            sk_audit($pdo, $user, $legacyId, 'google_account_link_rejected', [
                'request_id' => $requestId,
                'requested_by' => (int)$request['requested_by'],
            ], null);
            $pdo->commit();
            out(['ok' => true]);
        }

        $members = google_auth_load_members($pdo, true);
        $memberIndex = null;
        foreach ($members as $index => $candidate) {
            if (is_array($candidate) && (string)($candidate['id'] ?? '') === $legacyId) {
                $memberIndex = $index;
                break;
            }
        }
        if ($memberIndex === null || ($members[$memberIndex]['status'] ?? 'Active') !== 'Active') {
            throw new RuntimeException('Only an active KK member can be linked to Google.');
        }
        if (!google_auth_changed_email_is_unique($members, $legacyId, $request['google_email'])) {
            throw new RuntimeException('That Google email is already assigned to another member profile.');
        }
        $accountQuery = $pdo->prepare('SELECT id FROM users
            WHERE id = ? AND legacy_id = ? AND is_active = 1 FOR UPDATE');
        $accountQuery->execute([(int)$request['user_id'], $legacyId]);
        $accountId = $accountQuery->fetchColumn();
        if ($accountId === false) throw new RuntimeException('This member no longer has an active login account.');

        $subjectQuery = $pdo->prepare('SELECT user_id FROM google_account_links WHERE google_subject = ? FOR UPDATE');
        $subjectQuery->execute([$request['google_subject']]);
        if ($subjectQuery->fetchColumn() !== false) {
            throw new RuntimeException('This Google account is already linked to a login.');
        }
        $accountGoogleQuery = $pdo->prepare('SELECT google_subject FROM google_account_links WHERE user_id = ? FOR UPDATE');
        $accountGoogleQuery->execute([(int)$accountId]);
        if ($accountGoogleQuery->fetchColumn() !== false) {
            throw new RuntimeException('This member already has a Google account linked.');
        }

        $pdo->prepare('INSERT INTO google_account_links (user_id, google_subject, google_email)
            VALUES (?, ?, ?)')->execute([(int)$accountId, $request['google_subject'], $request['google_email']]);
        $members[$memberIndex]['email'] = $request['google_email'];
        if (USE_MEMBERS_TABLE) {
            kk_member_upsert($pdo, $members[$memberIndex], $user['username']);
        } else {
            $memberJson = json_encode($members, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
            $pdo->prepare("UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = 'kkMembers'")
                ->execute([$memberJson, $user['username']]);
        }
        $pdo->prepare('DELETE FROM google_account_link_requests WHERE request_id = ?')->execute([$requestId]);
        sk_audit($pdo, $user, $legacyId, 'google_account_link_approved', [
            'request_id' => $requestId,
            'requested_by' => (int)$request['requested_by'],
        ], [
            'account_id' => (int)$accountId,
            'email_verified' => true,
        ]);
        $pdo->commit();
        out(['ok' => true, 'email' => $request['google_email']]);
    }

    if ($action === 'account_link') {
        $accountId = filter_var($in['accountId'] ?? null, FILTER_VALIDATE_INT);
        $legacyId = (string)($in['legacyId'] ?? '');
        $member = $legacyId === '' ? null : sk_member_for_role_change($pdo, $legacyId, true);
        if (!$accountId || !$member || ($member['status'] ?? 'Active') !== 'Active') {
            throw new RuntimeException('Choose an active KK member and a valid unlinked account.');
        }
        $account = $pdo->prepare('SELECT id, username, legacy_id, is_active, must_change_password FROM users WHERE id = ? FOR UPDATE');
        $account->execute([$accountId]);
        $target = $account->fetch(PDO::FETCH_ASSOC);
        if (!$target || $target['legacy_id'] !== null || !(int)$target['is_active']) {
            throw new RuntimeException('That account is already linked or inactive.');
        }
        $pdo->prepare('UPDATE users SET legacy_id = ? WHERE id = ?')->execute([$legacyId, $accountId]);
        sk_audit($pdo, $user, $legacyId, 'account_linked', ['account_id' => $accountId, 'username' => $target['username'], 'legacy_id' => null],
            ['account_id' => $accountId, 'username' => $target['username'], 'legacy_id' => $legacyId]);
        $pdo->commit();
        out(['ok' => true]);
    }

    if ($action === 'account_create') {
        $legacyId = (string)($in['legacyId'] ?? '');
        $member = $legacyId === '' ? null : sk_member_for_role_change($pdo, $legacyId, true);
        if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
            throw new RuntimeException('Only an active KK member can receive an account.');
        }
        $username = trim((string)($in['username'] ?? ''));
        if (!preg_match('/^[A-Za-z0-9._-]{4,50}$/', $username)) {
            throw new RuntimeException('Username must be 4 to 50 letters, numbers, dots, underscores, or hyphens.');
        }
        $consent = !empty($in['guardianConsent']);
        $birthDate = (string)($member['birthDate'] ?? '');
        if ($birthDate !== '') {
            $birth = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
            if ($birth && $birth->diff(new DateTimeImmutable('today'))->y < 18 && !$consent) {
                throw new RuntimeException('Guardian consent is required to create an account for a member under 18.');
            }
        }
        $exists = $pdo->prepare('SELECT id FROM users WHERE legacy_id = ? OR username = ? FOR UPDATE');
        $exists->execute([$legacyId, $username]);
        if ($exists->fetchColumn() !== false) throw new RuntimeException('A member can have only one account, and usernames must be unique.');
        $name = trim(implode(' ', array_filter([
            $member['firstName'] ?? '', $member['middleName'] ?? '', $member['lastName'] ?? '', $member['suffix'] ?? '',
        ], function ($part) { return (string)$part !== ''; })));
        $unusablePassword = bin2hex(random_bytes(32));
        $version = $consent ? trim((string)($in['consentVersion'] ?? '')) : null;
        if ($consent && $version === '') throw new RuntimeException('Consent version is required when guardian consent is recorded.');
        $pdo->prepare('INSERT INTO users
            (username, password, full_name, role, is_active, legacy_id, must_change_password,
             guardian_consent, consent_at, consent_version, created_by)
            VALUES (?, ?, ?, ?, 1, ?, 0, ?, ?, ?, ?)')
            ->execute([
                $username, password_hash($unusablePassword, PASSWORD_DEFAULT), $name, 'KK Member', $legacyId,
                $consent ? 1 : 0, $consent ? date('Y-m-d H:i:s') : null, $version, $user['id'],
            ]);
        $newId = (int)$pdo->lastInsertId();
        sk_audit($pdo, $user, $legacyId, 'account_created', null, [
            'account_id' => $newId, 'username' => $username, 'google_sign_in_required' => true,
            'guardian_consent' => $consent, 'consent_version' => $version,
        ]);
        $pdo->commit();
        out(['ok' => true, 'accountId' => $newId, 'googleSignInRequired' => true]);
    }

    $member = null;
    if ($action === 'account_username_member') {
        $legacyId = trim((string)($in['legacyId'] ?? ''));
        if ($legacyId === '') throw new RuntimeException('A valid KK member profile is required.');
        $member = sk_member_for_role_change($pdo, $legacyId, true);
        if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
            throw new RuntimeException('Only an active KK member can have a login username retrieved.');
        }
        $account = $pdo->prepare('SELECT id, username FROM users WHERE legacy_id = ? AND is_active = 1 FOR UPDATE');
        $account->execute([$legacyId]);
        $target = $account->fetch(PDO::FETCH_ASSOC);
        if (!$target) throw new RuntimeException('This member has no active login account.');
        sk_audit($pdo, $user, $legacyId, 'account_username_retrieved', null, [
            'account_id' => (int)$target['id'],
        ]);
        $pdo->commit();
        out(['ok' => true, 'username' => $target['username']]);
    }

    if ($action === 'account_reset_member') {
        $legacyId = trim((string)($in['legacyId'] ?? ''));
        if ($legacyId === '') throw new RuntimeException('A valid KK member profile is required.');
        $member = sk_member_for_role_change($pdo, $legacyId, true);
        if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
            throw new RuntimeException('Only an active KK member can have a login password reset.');
        }
        $account = $pdo->prepare('SELECT id, username, legacy_id, role, is_active, must_change_password
            FROM users WHERE legacy_id = ? AND is_active = 1 FOR UPDATE');
        $account->execute([$legacyId]);
        $target = $account->fetch(PDO::FETCH_ASSOC);
        if (!$target) throw new RuntimeException('This member has no active login account to reset.');
        $targetId = (int)$target['id'];
    } else {
        $targetId = filter_var($in['accountId'] ?? null, FILTER_VALIDATE_INT);
        if (!$targetId) throw new RuntimeException('A valid account id is required.');
        $account = $pdo->prepare('SELECT id, username, legacy_id, role, is_active, must_change_password FROM users WHERE id = ? FOR UPDATE');
        $account->execute([$targetId]);
        $target = $account->fetch(PDO::FETCH_ASSOC);
        if (!$target) throw new RuntimeException('The account no longer exists.');
    }
    if ($action === 'account_reset' || $action === 'account_reset_member') {
        if ($member === null && $target['legacy_id'] !== null) {
            $member = sk_member_for_role_change($pdo, (string)$target['legacy_id'], true);
        }
        $googleLoginStatus = google_auth_password_login_status($pdo, $target);
        if ($googleLoginStatus !== 'allowed') {
            throw new RuntimeException($googleLoginStatus === 'setup_missing'
                ? 'Google sign-in is required for KK members, but its database setup is incomplete.'
                : 'This KK account uses Google sign-in. Have the member sign in with their verified profile email instead.');
        }
        $temporaryPassword = $member === null
            ? bin2hex(random_bytes(12))
            : kk_member_temporary_password($member['lastName'] ?? '');
        $pdo->prepare('UPDATE users SET password = ?, must_change_password = 1 WHERE id = ?')
            ->execute([password_hash($temporaryPassword, PASSWORD_DEFAULT), $targetId]);
        sk_audit($pdo, $user, $target['legacy_id'], 'account_password_reset', ['must_change_password' => (bool)$target['must_change_password']],
            ['account_id' => $targetId, 'username' => $target['username'], 'must_change_password' => true]);
        $pdo->commit();
        out(['ok' => true, 'temporaryPassword' => $temporaryPassword]);
    }
    if ($targetId === $user['id']) throw new RuntimeException('You cannot deactivate your own account.');
    $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = ?')->execute([$targetId]);
    sk_audit($pdo, $user, $target['legacy_id'], 'account_deactivated',
        ['account_id' => $targetId, 'is_active' => (bool)$target['is_active']],
        ['account_id' => $targetId, 'is_active' => false]);
    $pdo->commit();
    out(['ok' => true]);
} catch (RuntimeException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($ex instanceof PDOException && (string)$ex->getCode() === '23000') {
        $message = $action === 'account_link_google_request'
            ? 'A Google link is already linked or pending for this member or account.'
            : 'This Google account or profile email is already linked.';
        out(['ok' => false, 'error' => $message], 409);
    }
    out(['ok' => false, 'error' => $ex->getMessage()], 400);
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Member account operation failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Could not update the member account.'], 500);
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Member account operation failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Could not update the member account.'], 500);
}


<?php
require_once __DIR__ . '/google_auth.php';
header('Cache-Control: no-store');

function google_registration_callback() {
    $currentState = $_GET['state'] ?? '';
    $registrationOAuth = $_SESSION['google_registration_oauth'] ?? null;
    unset($_SESSION['google_registration_oauth']);
    if (!is_array($registrationOAuth) || !is_string($currentState)
        || !is_string($registrationOAuth['state'] ?? null)
        || !hash_equals($registrationOAuth['state'], $currentState)
        || !is_int($registrationOAuth['created_at'] ?? null)
        || $registrationOAuth['created_at'] < time() - 600) {
        google_registration_popup_result(false, 'The Google verification expired. Return to Settings and try again.');
    }
    $purpose = $registrationOAuth['purpose'] ?? '';
    $memberId = $registrationOAuth['member_id'] ?? null;
    if ($purpose !== 'member_self_link' || !is_string($memberId) || $memberId === '') {
        google_registration_popup_result(false, 'This Google verification request is invalid.');
    }

    $user = current_user();
    if (!$user || ($user['role'] ?? '') !== 'KK Member'
        || (int)$user['id'] !== (int)($registrationOAuth['operator_id'] ?? 0)
        || (string)($user['legacy_id'] ?? '') !== $memberId || !empty($user['must_change_password'])) {
        google_registration_popup_result(false, 'Your sign-in session is no longer active. Sign in again and retry.');
    }
    if ($purpose === 'member_self_link') {
        $member = sk_member_for_role_change(db(), $memberId);
        if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
            google_registration_popup_result(false, 'Only an active KK member can bind a Google account.');
        }
    }
    if (isset($_GET['error'])) {
        google_registration_popup_result(false, 'Google account verification was cancelled.');
    }
    $code = $_GET['code'] ?? '';
    if (!is_string($code) || $code === '' || strlen($code) > 4096) {
        google_registration_popup_result(false, 'Google could not verify this account. Return to the form and try again.');
    }

    try {
        $profile = google_oauth_fetch_profile($code);
    } catch (Throwable $exception) {
        error_log('Google registration verification failed: ' . get_class($exception));
        google_registration_popup_result(false, 'Google account verification failed. Please try again.');
    }
    $subject = $profile['sub'] ?? null;
    $email = google_auth_normalize_email($profile['email'] ?? null);
    if (!is_string($subject) || $subject === '' || strlen($subject) > 255
        || ($profile['email_verified'] ?? false) !== true || $email === null) {
        google_registration_popup_result(false, 'Use a Google account with a verified, valid email address.');
    }
    try {
        $statement = db()->prepare('SELECT id FROM users WHERE google_sub = ?');
        $statement->execute([$subject]);
        $subjectOwner = $statement->fetchColumn();
        if ($subjectOwner === false) {
            $statement = db()->prepare('SELECT user_id FROM google_account_links WHERE google_subject = ?');
            $statement->execute([$subject]);
            $subjectOwner = $statement->fetchColumn();
        }
        if ($subjectOwner !== false) {
            google_registration_popup_result(false, 'This Google account is already linked to an existing login.');
        }
    } catch (Throwable $exception) {
        error_log('Google registration account check failed: ' . get_class($exception));
        google_registration_popup_result(false, 'Google account verification could not be completed.');
    }

    $ticket = bin2hex(random_bytes(32));
    $_SESSION['google_registration_ticket'] = [
        'token' => $ticket,
        'subject' => $subject,
        'email' => $email,
        'expires_at' => time() + 600,
        'purpose' => $purpose,
        'member_id' => $memberId,
    ];
    google_registration_popup_result(true, 'Google account verified.', $ticket, $email, $purpose, $memberId);
}

function google_callback_fail($reason, $destination = null) {
    $url = 'login.php?google_error=' . rawurlencode($reason);
    if (is_string($destination) && strpos($destination, 'announcements.php?view=public') === 0) {
        $url .= '&next=announcements.php';
        if (preg_match('/[?&]eventId=([A-Za-z0-9_-]{1,60})/', $destination, $matches)) {
            $url .= '&eventId=' . rawurlencode($matches[1]);
        }
    }
    header('Location: ' . $url);
    exit;
}

if (isset($_GET['state'], $_SESSION['google_registration_oauth']['state'])
    && is_string($_GET['state'])
    && hash_equals((string)$_SESSION['google_registration_oauth']['state'], $_GET['state'])) {
    if (!google_oauth_configured() || !google_oauth_schema_ready()) {
        unset($_SESSION['google_registration_oauth']);
        google_registration_popup_result(false, 'Google sign-in setup is incomplete. Contact the administrator.');
    }
    google_registration_callback();
}

if (!google_oauth_configured()) google_callback_fail('not_configured');
if (!google_oauth_schema_ready()) google_callback_fail('setup_incomplete');

$state = $_GET['state'] ?? '';
$expectedState = $_SESSION['google_oauth_state'] ?? '';
$createdAt = (int)($_SESSION['google_oauth_created_at'] ?? 0);
$destination = $_SESSION['google_oauth_destination'] ?? null;
unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_created_at'], $_SESSION['google_oauth_destination']);
if (!is_string($state) || !is_string($expectedState) || $expectedState === ''
    || !hash_equals($expectedState, $state) || $createdAt < time() - 600) {
    google_callback_fail('invalid_state', $destination);
}
if (isset($_GET['error'])) google_callback_fail('google_cancelled', $destination);

$code = $_GET['code'] ?? '';
if (!is_string($code) || $code === '' || strlen($code) > 4096) {
    google_callback_fail('google_failed', $destination);
}

try {
    $profile = google_oauth_fetch_profile($code);
} catch (Throwable $exception) {
    error_log('Google sign-in request failed: ' . get_class($exception));
    google_callback_fail('google_failed', $destination);
}

$googleSubject = $profile['sub'] ?? null;
$emailVerified = $profile['email_verified'] ?? false;
$email = google_auth_normalize_email($profile['email'] ?? null);
if (!is_string($googleSubject) || $googleSubject === '' || strlen($googleSubject) > 255) {
    google_callback_fail('google_failed', $destination);
}
if ($emailVerified !== true) google_callback_fail('email_not_verified', $destination);
if ($email === null) google_callback_fail('email_invalid', $destination);

$pdo = db();
$pdo->beginTransaction();
try {
    $accountQuery = $pdo->prepare("SELECT id, username, full_name, role, is_active, legacy_id, must_change_password, google_sub
        FROM users WHERE google_sub = ? AND is_active = 1 FOR UPDATE");
    $accountQuery->execute([$googleSubject]);
    $account = $accountQuery->fetch(PDO::FETCH_ASSOC);
    if (!$account) {
        $legacyLink = $pdo->prepare('SELECT user_id FROM google_account_links WHERE google_subject = ? FOR UPDATE');
        $legacyLink->execute([$googleSubject]);
        $legacyUserId = $legacyLink->fetchColumn();
        if ($legacyUserId !== false) {
            $accountQuery = $pdo->prepare('SELECT id, username, full_name, role, is_active, legacy_id, must_change_password, google_sub
                FROM users WHERE id = ? AND is_active = 1 FOR UPDATE');
            $accountQuery->execute([(int)$legacyUserId]);
            $account = $accountQuery->fetch(PDO::FETCH_ASSOC);
            if ($account && !empty($account['google_sub'])
                && !hash_equals((string)$account['google_sub'], $googleSubject)) {
                $pdo->rollBack();
                google_callback_fail('google_link_conflict', $destination);
            }
            if ($account && empty($account['google_sub'])) {
                $pdo->prepare('UPDATE users SET google_sub = ? WHERE id = ?')
                    ->execute([$googleSubject, (int)$account['id']]);
            }
        }
    }
    if (!$account || !google_auth_account_is_member_linked($account)) {
        $pdo->rollBack();
        google_callback_fail('google_account_not_bound', $destination);
    }
    if (kk_effective_role($account) !== 'KK Member') {
        $pdo->rollBack();
        google_callback_fail('official_password_required', $destination);
    }
    $memberId = (string)$account['legacy_id'];
    $members = google_auth_load_members($pdo, true);
    $memberIsActive = false;
    foreach ($members as $member) {
        if (is_array($member) && (string)($member['id'] ?? '') === $memberId
            && ($member['status'] ?? 'Active') === 'Active') {
            $memberIsActive = true;
            break;
        }
    }
    if (!$memberIsActive) {
        $pdo->rollBack();
        google_callback_fail('member_inactive', $destination);
    }
    $linkedAccountQuery = $pdo->prepare('SELECT google_subject, google_email FROM google_account_links WHERE user_id = ? FOR UPDATE');
    $linkedAccountQuery->execute([(int)$account['id']]);
    $existingLink = $linkedAccountQuery->fetch(PDO::FETCH_ASSOC);
    if (!$existingLink || !hash_equals((string)$existingLink['google_subject'], $googleSubject)) {
        $pdo->rollBack();
        google_callback_fail('google_account_not_bound', $destination);
    }
    if (!hash_equals((string)$account['google_sub'], $googleSubject)) {
        $pdo->prepare('UPDATE users SET google_sub = ? WHERE id = ?')
            ->execute([$googleSubject, (int)$account['id']]);
    }
    $googleEmailChanged = google_auth_normalize_email($existingLink['google_email'] ?? null) !== $email;
    $googleEmailUpdateAllowed = google_auth_changed_email_is_unique($members, $memberId, $email);
    if ($googleEmailUpdateAllowed) {
        $emailOwnerQuery = $pdo->prepare('SELECT user_id FROM google_account_links
            WHERE google_email = ? AND user_id <> ? FOR UPDATE');
        $emailOwnerQuery->execute([$email, (int)$account['id']]);
        $googleEmailUpdateAllowed = $emailOwnerQuery->fetchColumn() === false;
    }
    if ($googleEmailChanged && $googleEmailUpdateAllowed) {
        $pdo->prepare('UPDATE google_account_links SET google_email = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?')
            ->execute([$email, (int)$account['id']]);
    }

    $actor = ['id' => (int)$account['id'], 'username' => (string)$account['username']];
    sk_audit($pdo, $actor, $memberId, 'google_login', null, [
        'provider' => 'Google',
        'google_email' => $email,
        'email_verified' => true,
    ]);
    if ($googleEmailChanged && $googleEmailUpdateAllowed) {
        sk_audit($pdo, $actor, $memberId, 'google_account_email_updated', [
            'google_email' => $existingLink['google_email'] ?? null,
        ], ['google_email' => $email]);
    } elseif ($googleEmailChanged) {
        sk_audit($pdo, $actor, $memberId, 'google_account_email_change_not_applied', null, [
            'google_email' => $email,
            'reason' => 'The email is already assigned to another member or login; Google subject authentication was retained.',
        ]);
    }
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Google member account linking failed: ' . get_class($exception));
    google_callback_fail('google_failed', $destination);
}

session_regenerate_id(true);
unset($_SESSION['public']);
$_SESSION['user'] = ['id' => (int)$account['id']];
$_SESSION['google_authenticated_at'] = time();
$_SESSION['google_authenticated_user_id'] = (int)$account['id'];
$resetNoticeTable = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_password_reset_notices'");
$resetNoticeTable->execute();
$resetNotice = null;
if ((int)$resetNoticeTable->fetchColumn() === 1) {
    $resetNoticeQuery = $pdo->prepare('SELECT reset_by_name, reset_at FROM user_password_reset_notices WHERE user_id = ?');
    $resetNoticeQuery->execute([(int)$account['id']]);
    $resetNotice = $resetNoticeQuery->fetch(PDO::FETCH_ASSOC);
}
if ($resetNotice) {
    $pdo->prepare('DELETE FROM user_password_reset_notices WHERE user_id = ?')->execute([(int)$account['id']]);
    $_SESSION['password_reset_notice'] = 'Your password was reset by ' . $resetNotice['reset_by_name']
        . ' on ' . $resetNotice['reset_at'] . '.';
}
$signedInUser = current_user();
if (!empty($signedInUser['must_change_password']) || $resetNotice) {
    $target = 'settings.php';
} elseif (is_string($destination) && strpos($destination, 'announcements.php?view=public') === 0) {
    $target = $destination;
} else {
    $target = ($signedInUser['role'] ?? 'KK Member') === 'KK Member'
        ? 'member-portal.php' : 'dashboard.php';
}
header('Location: ' . $target);
exit;

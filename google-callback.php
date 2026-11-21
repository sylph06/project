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
        google_registration_popup_result(false, 'The Google verification expired. Return to the form and try again.');
    }
    $purpose = $registrationOAuth['purpose'] ?? 'registration';
    $memberId = $registrationOAuth['member_id'] ?? null;
    if (!in_array($purpose, ['registration', 'member_link'], true)
        || ($purpose === 'member_link' && (!is_string($memberId) || $memberId === ''))) {
        google_registration_popup_result(false, 'This Google verification request is invalid.');
    }

    $user = current_user();
    $operatorCanContinue = $purpose === 'member_link'
        ? can_request_google_member_links()
        : can_write_app_data();
    if (!$user || (int)$user['id'] !== (int)($registrationOAuth['operator_id'] ?? 0)
        || !$operatorCanContinue || !empty($user['must_change_password'])) {
        google_registration_popup_result(false, 'The SK profiling session is no longer active. Sign in again and retry.');
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
        $statement = db()->prepare('SELECT user_id FROM google_account_links WHERE google_subject = ?');
        $statement->execute([$subject]);
        $subjectOwner = $statement->fetchColumn();
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
        'member_id' => $purpose === 'member_link' ? $memberId : null,
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
    $members = google_auth_load_members($pdo, true);
    $match = google_auth_match_member_email($members, $email);
    if ($match['status'] !== 'matched') {
        $pdo->rollBack();
        $errors = [
            'not_found' => 'member_not_found',
            'ambiguous' => 'email_ambiguous',
            'inactive' => 'member_inactive',
            'invalid_email' => 'email_invalid',
        ];
        google_callback_fail($errors[$match['status']] ?? 'google_failed', $destination);
    }
    $member = $match['member'];
    $memberId = (string)($member['id'] ?? '');
    if ($memberId === '') throw new RuntimeException('The matched member has no profile id.');

    $accountQuery = $pdo->prepare("SELECT id, username, full_name, role, is_active, legacy_id, must_change_password
        FROM users WHERE legacy_id = ? AND legacy_id IS NOT NULL AND is_active = 1 FOR UPDATE");
    $accountQuery->execute([$memberId]);
    $account = $accountQuery->fetch(PDO::FETCH_ASSOC);
    if (!$account) {
        $pdo->rollBack();
        google_callback_fail('member_account_missing', $destination);
    }
    if (!google_auth_account_is_member_linked($account)) {
        $pdo->rollBack();
        google_callback_fail('member_account_missing', $destination);
    }

    $linkedSubjectQuery = $pdo->prepare('SELECT user_id FROM google_account_links WHERE google_subject = ? FOR UPDATE');
    $linkedSubjectQuery->execute([$googleSubject]);
    $subjectOwner = $linkedSubjectQuery->fetchColumn();
    if ($subjectOwner !== false && (int)$subjectOwner !== (int)$account['id']) {
        $pdo->rollBack();
        google_callback_fail('google_link_conflict', $destination);
    }

    $linkedAccountQuery = $pdo->prepare('SELECT google_subject FROM google_account_links WHERE user_id = ? FOR UPDATE');
    $linkedAccountQuery->execute([(int)$account['id']]);
    $existingSubject = $linkedAccountQuery->fetchColumn();
    if ($existingSubject !== false && !hash_equals((string)$existingSubject, $googleSubject)) {
        $pdo->rollBack();
        google_callback_fail('account_already_linked', $destination);
    }

    $isFirstLink = $existingSubject === false;
    if ($isFirstLink) {
        $pdo->prepare('INSERT INTO google_account_links (user_id, google_subject, google_email)
            VALUES (?, ?, ?)')->execute([(int)$account['id'], $googleSubject, $email]);
    } else {
        $pdo->prepare('UPDATE google_account_links SET google_email = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?')
            ->execute([$email, (int)$account['id']]);
    }
    $pdo->prepare('UPDATE users SET must_change_password = 0 WHERE id = ?')->execute([(int)$account['id']]);

    $actor = [
        'id' => (int)$account['id'],
        'username' => (string)$account['username'],
    ];
    sk_audit($pdo, $actor, $memberId, $isFirstLink ? 'google_account_linked' : 'google_login', null, [
        'provider' => 'Google',
        'email_verified' => true,
    ]);
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Google member account linking failed: ' . get_class($exception));
    google_callback_fail('google_failed', $destination);
}

session_regenerate_id(true);
unset($_SESSION['public']);
$_SESSION['user'] = ['id' => (int)$account['id']];
$signedInUser = current_user();
$target = is_string($destination) && strpos($destination, 'announcements.php?view=public') === 0
    ? $destination : (($signedInUser['role'] ?? 'KK Member') === 'KK Member'
        ? 'member-portal.php' : 'dashboard.php');
header('Location: ' . $target);
exit;

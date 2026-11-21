<?php
require_once __DIR__ . '/google_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST only.');
}

$user = current_user();
if (!$user || !can_write_app_data() || !empty($user['must_change_password'])) {
    http_response_code(403);
    google_registration_popup_result(false, 'Sign in as an SK official to verify the member Google account.');
}
if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
    http_response_code(403);
    google_registration_popup_result(false, 'The profiling session expired. Reload the page and try again.');
}
if (!google_oauth_configured() || !google_oauth_schema_ready()) {
    http_response_code(503);
    google_registration_popup_result(false, 'Google sign-in setup is incomplete. Contact the administrator.');
}

$purpose = (string)($_POST['purpose'] ?? 'registration');
if ($purpose === 'member_link' && !google_oauth_link_requests_schema_ready()) {
    http_response_code(503);
    google_registration_popup_result(false, 'Google link approvals are not set up yet. Contact the administrator.');
}
$memberId = null;
if ($purpose === 'member_link') {
    if (!can_request_google_member_links()) {
        http_response_code(403);
        google_registration_popup_result(false, 'Only an SK Kagawad, Chairperson, or Secretary can request a Google link.');
    }
    $memberId = trim((string)($_POST['memberId'] ?? ''));
    $member = $memberId === '' ? null : sk_member_for_role_change(db(), $memberId);
    if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
        http_response_code(404);
        google_registration_popup_result(false, 'Choose an active KK member before linking Google.');
    }
    $accountQuery = db()->prepare('SELECT id FROM users WHERE legacy_id = ? AND is_active = 1');
    $accountQuery->execute([$memberId]);
    if ($accountQuery->fetchColumn() === false) {
        http_response_code(409);
        google_registration_popup_result(false, 'Create this member login account before linking Google.');
    }
} elseif ($purpose !== 'registration') {
    http_response_code(400);
    google_registration_popup_result(false, 'This Google verification request is invalid.');
}

$state = bin2hex(random_bytes(32));
$_SESSION['google_registration_oauth'] = [
    'state' => $state,
    'created_at' => time(),
    'operator_id' => (int)$user['id'],
    'purpose' => $purpose,
    'member_id' => $memberId,
];
unset($_SESSION['google_registration_ticket']);

$params = [
    'client_id' => GOOGLE_OAUTH_CLIENT_ID,
    'redirect_uri' => GOOGLE_OAUTH_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid email',
    'state' => $state,
    'prompt' => 'select_account',
];
header('Cache-Control: no-store');
header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986));
exit;

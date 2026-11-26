<?php
require_once __DIR__ . '/google_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST only.');
}

$user = current_user();
if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
    http_response_code(403);
    google_registration_popup_result(false, 'Your session expired. Reload Settings and try again.');
}
if (!$user || ($user['role'] ?? '') !== 'KK Member'
    || $user['legacy_id'] === null || (string)$user['legacy_id'] === ''
    || !empty($user['must_change_password'])) {
    http_response_code(403);
    google_registration_popup_result(false, 'Sign in to your active KK member account before binding Google.');
}
if (!google_oauth_configured() || !google_oauth_schema_ready()) {
    http_response_code(503);
    google_registration_popup_result(false, 'Google sign-in setup is incomplete. Contact the administrator.');
}
$memberId = (string)$user['legacy_id'];
$member = sk_member_for_role_change(db(), $memberId);
if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
    http_response_code(409);
    google_registration_popup_result(false, 'Only an active KK member can bind a Google account.');
}
$accountQuery = db()->prepare('SELECT id, role, is_active, legacy_id FROM users WHERE id = ? AND legacy_id = ? AND is_active = 1');
$accountQuery->execute([(int)$user['id'], $memberId]);
$bindingAccount = $accountQuery->fetch(PDO::FETCH_ASSOC);
if (!$bindingAccount || kk_effective_role($bindingAccount) !== 'KK Member') {
    http_response_code(409);
    google_registration_popup_result(false, 'This active KK account could not be matched to its member profile.');
}
$linkedAccountQuery = db()->prepare('SELECT 1 FROM google_account_links WHERE user_id = ?');
$linkedAccountQuery->execute([(int)$user['id']]);
if ($linkedAccountQuery->fetchColumn()) {
    http_response_code(409);
    google_registration_popup_result(false, 'A Google account is already bound to this KK login.');
}

$state = bin2hex(random_bytes(32));
$_SESSION['google_registration_oauth'] = [
    'state' => $state,
    'created_at' => time(),
    'operator_id' => (int)$user['id'],
    'purpose' => 'member_self_link',
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

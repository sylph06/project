<?php
require_once __DIR__ . '/google_auth.php';

if (!google_oauth_configured()) {
    header('Location: login.php?google_error=not_configured');
    exit;
}
if (!google_oauth_schema_ready()) {
    header('Location: login.php?google_error=setup_incomplete');
    exit;
}

$destination = 'member-portal.php';
if (($_GET['next'] ?? '') === 'announcements.php') {
    $destination = 'announcements.php?view=public';
    $eventId = (string)($_GET['eventId'] ?? '');
    if (preg_match('/^[A-Za-z0-9_-]{1,60}$/', $eventId)) {
        $destination .= '&eventId=' . rawurlencode($eventId);
    }
}

$_SESSION['google_oauth_state'] = bin2hex(random_bytes(32));
$_SESSION['google_oauth_created_at'] = time();
$_SESSION['google_oauth_destination'] = $destination;

$params = [
    'client_id' => GOOGLE_OAUTH_CLIENT_ID,
    'redirect_uri' => GOOGLE_OAUTH_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid email',
    'state' => $_SESSION['google_oauth_state'],
    'prompt' => 'select_account',
];
header('Cache-Control: no-store');
header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986));
exit;

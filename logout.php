<?php
require_once __DIR__ . '/config.php';
$user = current_user();
if ($user && in_array($user['role'] ?? '', APP_WRITE_ROLES, true)) {
    require_once __DIR__ . '/role_store.php';
    sk_audit(db(), $user, null, 'logout', null, ['event' => 'logout']);
}
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;

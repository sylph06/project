<?php
require_once __DIR__ . '/../role_store.php';

$checks = 0;
$assert = function ($condition, $message) use (&$checks) {
    $checks++;
    if (!$condition) throw new RuntimeException($message);
};

$today = '2026-11-16';
$roles = [
    ['id' => 'current', 'kkId' => 'member-1', 'status' => 'Active', 'termStart' => '2026-01-01', 'termEnd' => '2027-01-01'],
    ['id' => 'future', 'kkId' => 'member-2', 'status' => 'Active', 'termStart' => '2027-01-01'],
    ['id' => 'ended', 'kkId' => 'member-3', 'status' => 'Active', 'termEnd' => '2026-11-15'],
    ['id' => 'inactive', 'kkId' => 'member-4', 'status' => 'Inactive'],
    ['id' => 'archived', 'kkId' => 'member-5', 'status' => 'Archived'],
];

$assert(sk_role_is_current($roles[0], $today), 'A role within its term must be current.');
$assert(!sk_role_is_current($roles[1], $today), 'A future role must not be current.');
$assert(!sk_role_is_current($roles[2], $today), 'An ended role must not be current.');
$assert(!sk_role_is_current($roles[3], $today), 'An inactive role must not be current.');
$assert(!sk_role_is_current($roles[4], $today), 'An archived role must not be current.');
$assert(sk_role_member_has_current_assignment($roles, 'member-1', null, $today), 'A current role must block a second current assignment.');
$assert(!sk_role_member_has_current_assignment($roles, 'member-1', 'current', $today), 'A role update must ignore the role being updated.');
$assert(!sk_role_member_has_current_assignment($roles, 'member-2', null, $today), 'A future role must not block a current assignment.');
$assert(!sk_role_member_has_current_assignment($roles, 'missing', null, $today), 'A member without a current role must be assignable.');

$hadUserAgent = array_key_exists('HTTP_USER_AGENT', $_SERVER);
$originalUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
unset($_SERVER['HTTP_USER_AGENT']);
$assert(sk_audit_user_agent() === null, 'Audit entries without a browser user agent must store no device value.');
$_SERVER['HTTP_USER_AGENT'] = "Browser/1.0\r\nMobile";
$assert(sk_audit_user_agent() === 'Browser/1.0Mobile', 'User-agent control characters must be removed.');
$_SERVER['HTTP_USER_AGENT'] = str_repeat('A', 513);
$assert(strlen(sk_audit_user_agent()) === 512, 'User-agent values must be capped to the audit column size.');
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/123.0.0.0 Safari/537.36 Edg/123.0.0.0';
$assert(sk_audit_device_type(sk_audit_user_agent()) === 'Desktop', 'Desktop user agents must be categorized as desktop.');
$assert(sk_audit_browser(sk_audit_user_agent()) === 'Microsoft Edge 123', 'Edge must be detected before Chromium in its user agent.');
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 13; Pixel) AppleWebKit/537.36 Chrome/123.0.0.0 Mobile Safari/537.36';
$assert(sk_audit_device_type(sk_audit_user_agent()) === 'Mobile', 'Mobile Android user agents must be categorized as mobile.');
$assert(sk_audit_browser(sk_audit_user_agent()) === 'Chrome 123', 'Chrome versions must be detected from the user agent.');
if ($hadUserAgent) $_SERVER['HTTP_USER_AGENT'] = $originalUserAgent;
else unset($_SERVER['HTTP_USER_AGENT']);

$hadRemoteAddress = array_key_exists('REMOTE_ADDR', $_SERVER);
$originalRemoteAddress = $_SERVER['REMOTE_ADDR'] ?? null;
$_SERVER['REMOTE_ADDR'] = '192.168.1.25';
$assert(sk_audit_ip_address() === '192.168.1.25', 'The client address must come from the direct remote address.');
$assert(sk_audit_location('192.168.1.25') === 'Local or non-public IP address', 'Private network addresses must not be sent to GeoIP lookup.');
$_SERVER['REMOTE_ADDR'] = 'not-an-ip';
$assert(sk_audit_ip_address() === null, 'Invalid client addresses must not be recorded.');
if ($hadRemoteAddress) $_SERVER['REMOTE_ADDR'] = $originalRemoteAddress;
else unset($_SERVER['REMOTE_ADDR']);

echo 'All SK role and audit checks passed: ' . $checks . PHP_EOL;

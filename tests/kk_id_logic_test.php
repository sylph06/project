<?php
require_once __DIR__ . '/../kk_id_logic.php';

$checks = 0;
$assert = function ($condition, $message) use (&$checks) {
    $checks++;
    if (!$condition) throw new RuntimeException($message);
};

$token = str_repeat('a', 64);
$assert(kk_id_token_from_scan('KKID:' . $token) === $token, 'A valid prefixed QR token must be accepted.');
$assert(kk_id_token_from_scan(strtoupper($token)) === $token, 'QR tokens must be normalized to lowercase.');
$assert(kk_id_token_from_scan(substr($token, 1)) === null, 'A short token must be rejected.');
$assert(kk_id_token_from_scan('https://example.test/?member=' . $token) === null, 'A URL with user data must not be accepted as a KK ID QR.');
$assert(kk_id_print_method_is_valid('automatic'), 'Automatic print records must be accepted.');
$assert(kk_id_print_method_is_valid('manual'), 'Manual print records must be accepted.');
$assert(!kk_id_print_method_is_valid('unknown'), 'Unknown print methods must be rejected.');
$assert(kk_id_member_name(['firstName' => 'Ari', 'middleName' => 'B.', 'lastName' => 'Cruz', 'suffix' => 'Jr.']) === 'Ari B. Cruz Jr.', 'Member names must be assembled in display order.');

$records = kk_id_tracking_records('m1', [
    ['id' => 'e1', 'title' => 'Sports', 'date' => '2025-01-10'],
    ['id' => 'e2', 'title' => 'Seminar', 'date' => '2025-02-10'],
    ['id' => 'e3', 'title' => 'Cleanup', 'date' => '2025-03-10'],
], [
    ['eventId' => 'e1', 'memberId' => 'm1', 'eventTitle' => 'Old sports label', 'attendanceStatus' => 'Attended'],
    ['eventId' => 'e2', 'memberId' => 'm1', 'attendanceStatus' => 'Not recorded'],
    ['eventId' => 'e1', 'memberId' => 'm2', 'attendanceStatus' => 'Attended'],
], [
    ['eventId' => 'e1', 'memberId' => 'm1'],
    ['eventId' => 'e3', 'memberId' => 'm1'],
    ['eventId' => 'e3', 'memberId' => 'm1'],
]);
$assert(count($records) === 3, 'A duplicate walk-in must not duplicate an attended sign-up or another walk-in.');
$assert($records[0]['title'] === 'Cleanup' && $records[0]['source'] === 'Walk-in', 'Tracking records must be ordered newest first.');
$assert($records[1]['status'] === 'Not recorded' && $records[1]['title'] === 'Seminar', 'Unconfirmed sign-ups must remain visible.');
$assert($records[2]['title'] === 'Sports' && $records[2]['source'] === 'Sign-up', 'Event data must take precedence over a stale sign-up title.');

echo 'KK ID logic checks passed: ' . $checks . PHP_EOL;

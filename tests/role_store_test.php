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

echo 'All SK role checks passed: ' . $checks . PHP_EOL;

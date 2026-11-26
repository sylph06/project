<?php
require_once __DIR__ . '/../participation_logic.php';

$checks = 0;
$assert = function ($condition, $message) use (&$checks) {
    $checks++;
    if (!$condition) throw new RuntimeException($message);
};

$member = ['birthDate' => '2006-07-01'];
$assert(participation_age_at_event($member, '2024-06-30') === 17, 'Age must be calculated as of the event date before the birthday.');
$assert(participation_age_at_event($member, '2024-07-01') === 18, 'Age must advance on the event-date birthday.');
$assert(participation_age_at_event([], '2024-07-01', 22) === 22, 'Sign-up age must be used when the member birth date is missing.');
$assert(participation_age_at_event([], '2024-07-01', 0) === null, 'A blank age stored as zero must be treated as unknown.');
$assert(participation_age_at_event([], '2024-07-01', 'invalid') === null, 'Invalid sign-up ages must be treated as unknown.');
$assert(participation_signup_age(null) === null, 'An omitted sign-up age must be stored as unknown.');
$assert(participation_signup_age(0) === null, 'A legacy blank age of zero must be normalized to unknown.');
$assert(participation_signup_age('18') === 18, 'A valid sign-up age must be retained.');
$assert(participation_signup_age(121) === false, 'An age above the allowed maximum must be rejected.');
$assert(participation_signup_age(-1) === false, 'A negative age must be rejected.');
$assert(participation_signup_age('18.5') === false, 'A fractional age must be rejected.');
$assert(participation_walkin_is_active(['id' => 'walkin-1']), 'Walk-ins without a removal marker must be active.');
$assert(!participation_walkin_is_active(['id' => 'walkin-1', 'removedAt' => '2026-11-23T12:00:00Z']), 'Removed walk-ins must be inactive.');
$assert(!participation_walkin_is_active(null), 'Invalid walk-in records must be inactive.');
$assert(participation_event_interest_is_open(['date' => '2026-01-01', 'startTime' => '11:00'], strtotime('2026-01-01 10:00:00')), 'Interest must remain open before the event starts.');
$assert(!participation_event_interest_is_open(['date' => '2026-01-01', 'startTime' => '11:00'], strtotime('2026-01-01 11:00:00')), 'Interest must close at the event start time.');
$assert(!participation_event_interest_is_open(['date' => '2026-01-01', 'startTime' => '11:00', 'interestClosed' => true], strtotime('2026-01-01 10:00:00')), 'SK-closed interest must not accept responses.');
$assert(!participation_event_interest_is_open(['date' => 'not-a-date', 'startTime' => '11:00'], strtotime('2026-01-01 10:00:00')), 'Invalid event dates must not accept responses.');
$assert(participation_age_group(14) === 'Outside ages 15-30', 'Ages below the youth range must be grouped separately.');
$assert(participation_age_group(15) === '15-17', 'Age 15 must be in the first age group.');
$assert(participation_age_group(17) === '15-17', 'Age 17 must be in the first age group.');
$assert(participation_age_group(18) === '18-20', 'Age 18 must be in the second age group.');
$assert(participation_age_group(21) === '21-24', 'Age 21 must be in the third age group.');
$assert(participation_age_group(25) === '25-30', 'Age 25 must be in the fourth age group.');
$assert(participation_age_group(31) === 'Outside ages 15-30', 'Ages above the youth range must be grouped separately.');
$assert(participation_age_group(null) === 'Unknown', 'Missing age must be grouped as unknown.');

$attendedSignups = ['event-1:member-1' => true];
$walkins = ['event-1:member-2' => true];
$assert(!participation_walkin_should_count('event-1', 'member-1', $attendedSignups, []), 'A walk-in must not duplicate an attended sign-up.');
$assert(participation_walkin_should_count('event-1', 'member-2', [], []), 'A walk-in must count when no sign-up is marked attended.');
$assert(!participation_walkin_should_count('event-1', 'member-2', [], $walkins), 'Duplicate walk-ins must count only once.');
$assert(participation_walkin_should_count('event-2', 'member-2', [], $walkins), 'A member may attend different events.');
$assert(participation_walkin_should_count('event-1', '', $attendedSignups, $walkins), 'Unlinked walk-ins must remain countable without a member key.');

echo 'All participation logic checks passed: ' . $checks . PHP_EOL;

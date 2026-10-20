<?php
require_once __DIR__ . '/../meeting_minutes.php';

$checks = 0;
$assert = function ($condition, $message) use (&$checks) {
    $checks++;
    if (!$condition) throw new RuntimeException($message);
};

$unsafe = '<p onclick="alert(1)">Safe <script>alert(1)</script>'
    . '<a href="javascript:alert(1)" onclick="alert(2)">bad link</a>'
    . '<img src=x onerror=alert(3)>'
    . '<a href="https://example.com" target="_blank" onclick="alert(4)">good link</a></p>';
$safe = meeting_minutes_sanitize_html($unsafe);
$assert(strpos($safe, '<script') === false, 'Script tags must be removed.');
$assert(strpos($safe, 'onclick') === false && strpos($safe, 'onerror') === false, 'Event attributes must be removed.');
$assert(strpos($safe, 'javascript:') === false && strpos($safe, '<img') === false, 'Unsafe links and images must be removed.');
$assert(strpos($safe, 'target=') === false, 'All non-href link attributes must be removed.');
$assert(strpos($safe, 'href="https://example.com"') !== false, 'Safe HTTPS links must be preserved.');
$assert(strpos($safe, 'rel="noopener noreferrer"') !== false, 'Safe links must receive safe rel attributes.');
$safeMail = meeting_minutes_sanitize_html('<a href="mailto:secretary@example.com">email</a><a href="/relative">relative</a>');
$assert(strpos($safeMail, 'href="mailto:secretary@example.com"') !== false, 'Safe mailto links must be preserved.');
$assert(strpos($safeMail, 'href="/relative"') === false, 'Relative links must be removed.');
$assert(meeting_minutes_within_limit(meeting_minutes_sanitize_html('<p>' . str_repeat('x', 20000) . '</p>')), 'Exactly 20,000 text characters must be accepted.');
$assert(!meeting_minutes_within_limit(meeting_minutes_sanitize_html('<p>' . str_repeat('x', 20001) . '</p>')), 'The server must reject content exceeding 20,000 characters.');
$assert(meeting_minutes_within_limit(meeting_minutes_sanitize_html(str_repeat('<strong>x</strong>', 20000))), 'Formatting must not reduce the 20,000-character text limit.');
$assert(meeting_minutes_action_allowed('SK Secretary', 'save', 'Draft'), 'A Secretary must be able to save draft minutes.');
$assert(!meeting_minutes_role_can_manage('SK Kagawad'), 'Non-Secretary and non-Chairman roles must be rejected.');
$assert(!meeting_minutes_action_allowed('SK Kagawad', 'save', 'Draft'), 'Other SK roles must not save minutes.');
$assert(!meeting_minutes_action_allowed('SK Secretary', 'save', 'Approved'), 'The Secretary must not edit approved minutes.');
$assert(!meeting_minutes_action_allowed('SK Chairman', 'save', 'Approved'), 'Approved minutes must require reopening before editing.');
$assert(meeting_minutes_action_allowed('SK Chairman', 'approve', 'Draft'), 'Only the Chairman may approve draft minutes.');
$assert(!meeting_minutes_action_allowed('SK Secretary', 'approve', 'Draft'), 'The Secretary must not approve minutes.');
$assert(meeting_minutes_action_allowed('SK Chairman', 'reopen', 'Approved'), 'The Chairman must be able to reopen approved minutes.');
$assert(!meeting_minutes_action_allowed('SK Kagawad', 'reopen', 'Approved'), 'Other SK roles must not reopen minutes.');
$assert(meeting_minutes_generic_fields_match([], ['id' => 'mt1']), 'Normal meeting creation must work without minutes fields.');
$assert(!meeting_minutes_generic_fields_match([], ['minutes' => '<p>forged</p>']), 'Generic storage must not create minutes.');
$assert(meeting_minutes_generic_fields_match(
    ['minutes' => '<p>Existing</p>', 'minutesStatus' => 'Draft'],
    ['minutes' => '<p>Existing</p>', 'minutesStatus' => 'Draft']
), 'Generic meeting edits must preserve unchanged minutes.');
$assert(!meeting_minutes_generic_fields_match(
    ['minutes' => '<p>Existing</p>', 'minutesStatus' => 'Draft'],
    ['minutes' => '<p>Changed</p>', 'minutesStatus' => 'Draft']
), 'Generic storage must not edit minutes.');

echo 'All meeting-minutes checks passed: ' . $checks . PHP_EOL;

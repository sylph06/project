<?php
require_once __DIR__ . '/../google_auth.php';

$checks = 0;
$assert = function ($condition, $message) use (&$checks) {
    $checks++;
    if (!$condition) throw new RuntimeException($message);
};

$members = [
    ['id' => 'm1', 'email' => 'Member@Sample.org', 'status' => 'Active'],
    ['id' => 'm2', 'email' => 'old@example.com', 'status' => 'Active'],
];
$match = google_auth_match_member_email($members, ' member@sample.org ');
$assert($match['status'] === 'matched' && $match['member']['id'] === 'm1',
    'Verified Google email matching must ignore email letter case and surrounding spaces.');
$assert(google_auth_match_member_email($members, 'old@example.com')['status'] === 'invalid_email',
    'Placeholder email domains must not be eligible for Google linking.');
$assert(google_auth_match_member_email([
    ['id' => 'm1', 'email' => 'same@sample.org', 'status' => 'Active'],
    ['id' => 'm2', 'email' => 'SAME@sample.org', 'status' => 'Archived'],
], 'same@sample.org')['status'] === 'ambiguous',
    'Duplicate emails across active or archived profiles must not identify an account.');
$assert(google_auth_match_member_email([
    ['id' => 'm1', 'email' => 'inactive@sample.org', 'status' => 'Archived'],
], 'inactive@sample.org')['status'] === 'inactive',
    'Archived member profiles must not be eligible for Google sign-in.');
$assert(google_auth_changed_email_is_unique([
    ['id' => 'm1', 'email' => 'first@sample.org'],
    ['id' => 'm2', 'email' => 'second@sample.org'],
], 'm1', 'updated@sample.org'),
    'An email correction must allow a unique new email.');
$assert(!google_auth_changed_email_is_unique([
    ['id' => 'm1', 'email' => 'first@sample.org'],
    ['id' => 'm2', 'email' => 'second@sample.org'],
], 'm1', 'SECOND@sample.org'),
    'An email correction must reject another profile email case-insensitively.');
$assert(google_auth_account_is_member_linked(['id' => 10, 'legacy_id' => 'm1']),
    'A KK account keeps its Google identity when its SK role is granted.');
$assert(!google_auth_account_is_member_linked(['id' => 11, 'legacy_id' => null]),
    'Standalone official accounts must remain outside member Google login.');
$assert(google_member_link_role_can_request('SK Kagawad'),
    'SK Kagawads must be allowed to request a member Google link.');
$assert(google_member_link_role_can_request('SK Secretary')
    && google_member_link_role_can_request('SK Chairman')
    && google_member_link_role_can_request('SK Chairperson'),
    'The Chairperson and Secretary must also be allowed to request a member Google link.');
$assert(!google_member_link_role_can_request('SK Treasurer'),
    'Other SK roles must not request member Google links.');
$assert(google_member_link_role_can_approve('SK Chairman')
    && google_member_link_role_can_approve('SK Chairperson')
    && google_member_link_role_can_approve('SK Secretary'),
    'Only the Chairperson and Secretary roles must be allowed to approve Google links.');
$assert(!google_member_link_role_can_approve('SK Kagawad')
    && !google_member_link_role_can_approve('SK Treasurer'),
    'Kagawads and other SK roles must not approve Google links.');
$ticket = [
    'token' => 'opaque-ticket',
    'subject' => 'google-subject',
    'email' => 'member@sample.org',
    'expires_at' => time() + 60,
];
$assert(google_auth_registration_ticket($ticket, 'opaque-ticket', 'Member@Sample.org')['subject'] === 'google-subject',
    'A registration ticket must match the verified email case-insensitively.');
$assert(google_auth_registration_ticket($ticket, 'wrong-ticket', 'member@sample.org') === null,
    'A registration ticket must not be accepted with a different token.');
$assert(google_auth_registration_ticket($ticket, 'opaque-ticket', 'other@sample.org') === null,
    'A registration ticket must not be used for a different profile email.');
$memberLinkTicket = $ticket;
$memberLinkTicket['purpose'] = 'member_link';
$memberLinkTicket['member_id'] = 'm1';
$assert(google_auth_registration_ticket($memberLinkTicket, 'opaque-ticket', null, 'member_link', 'm1')['member_id'] === 'm1',
    'A Google-link ticket must be accepted only for its intended member.');
$assert(google_auth_registration_ticket($memberLinkTicket, 'opaque-ticket', null, 'registration') === null,
    'A member-link ticket must not be used to register a new profile.');
$assert(google_auth_registration_ticket($memberLinkTicket, 'opaque-ticket', null, 'member_link', 'm2') === null,
    'A member-link ticket must not be used for a different member.');
$ticket['expires_at'] = time() - 1;
$assert(google_auth_registration_ticket($ticket, 'opaque-ticket', 'member@sample.org') === null,
    'Expired Google verification tickets must be rejected.');

echo 'All Google-auth checks passed: ' . $checks . PHP_EOL;

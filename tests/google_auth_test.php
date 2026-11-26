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
$assert(google_auth_profile_email_binding_state('', 'member@sample.org') === 'set_profile_email',
    'Binding must fill in a blank profile email without requiring a replacement confirmation.');
$assert(google_auth_profile_email_binding_state('Member@Sample.org', 'member@sample.org') === 'already_matches',
    'Binding must accept a profile email that matches the verified Google email case-insensitively.');
$assert(google_auth_profile_email_binding_state('old@sample.org', 'new@sample.org') === 'replace_profile_email',
    'A nonempty mismatched profile email must require an explicit replacement confirmation.');
$assert(google_auth_profile_email_binding_state('old@example.com', 'member@sample.org') === 'replace_profile_email',
    'An invalid existing profile email must be replaceable only through the confirmation path.');
$assert(google_auth_member_email_matches($members, 'm1', 'member@sample.org'),
    'Self-service binding must accept the active member profile email case-insensitively.');
$assert(!google_auth_member_email_matches($members, 'm1', 'different@sample.org'),
    'Self-service binding must reject a Google email that differs from the profile email.');
$assert(!google_auth_member_email_matches([
    ['id' => 'm1', 'email' => 'same@sample.org', 'status' => 'Active'],
    ['id' => 'm2', 'email' => 'SAME@sample.org', 'status' => 'Active'],
], 'm1', 'same@sample.org'),
    'Self-service binding must reject duplicate member profile emails.');
$assert(!google_auth_member_email_matches([
    ['id' => 'm1', 'email' => 'member@sample.org', 'status' => 'Archived'],
], 'm1', 'member@sample.org'),
    'Self-service binding must reject archived member profiles.');
$assert(google_auth_account_is_member_linked(['id' => 10, 'legacy_id' => 'm1']),
    'A KK account keeps its Google identity when its SK role is granted.');
$assert(!google_auth_account_is_member_linked(['id' => 11, 'legacy_id' => null]),
    'Standalone official accounts must remain outside member Google login.');
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
$selfLinkTicket = $ticket;
$selfLinkTicket['purpose'] = 'member_self_link';
$selfLinkTicket['member_id'] = 'm1';
$assert(google_auth_registration_ticket($selfLinkTicket, 'opaque-ticket', null, 'member_self_link', 'm1')['member_id'] === 'm1',
    'A self-service Google ticket must be accepted only for its signed-in member.');
$assert(google_auth_registration_ticket($selfLinkTicket, 'opaque-ticket', null, 'member_self_link', 'm2') === null,
    'A self-service Google ticket must not be used by a different member account.');
$ticket['expires_at'] = time() - 1;
$assert(google_auth_registration_ticket($ticket, 'opaque-ticket', 'member@sample.org') === null,
    'Expired Google verification tickets must be rejected.');

$validClaims = [
    'iss' => 'https://accounts.google.com',
    'aud' => 'test-google-client',
    'exp' => time() + 300,
    'iat' => time() - 30,
    'sub' => 'stable-google-subject',
    'email' => 'member@sample.org',
    'email_verified' => 'true',
];
$verifiedIdentity = google_oauth_validate_id_token_claims($validClaims, 'test-google-client');
$assert($verifiedIdentity['sub'] === 'stable-google-subject'
    && $verifiedIdentity['email'] === 'member@sample.org'
    && $verifiedIdentity['email_verified'] === true,
    'A valid Google ID token must yield its stable subject and verified normalized email.');
$rejectClaims = function ($claims, $clientId = 'test-google-client') use ($assert) {
    $rejected = false;
    try {
        google_oauth_validate_id_token_claims($claims, $clientId);
    } catch (RuntimeException $exception) {
        $rejected = true;
    }
    $assert($rejected, 'Invalid Google ID token claims must be rejected.');
};
$badAudience = $validClaims;
$badAudience['aud'] = 'another-client';
$rejectClaims($badAudience);
$badIssuer = $validClaims;
$badIssuer['iss'] = 'attacker.example';
$rejectClaims($badIssuer);
$expiredClaims = $validClaims;
$expiredClaims['exp'] = time() - 1;
$rejectClaims($expiredClaims);
$unverifiedEmail = $validClaims;
$unverifiedEmail['email_verified'] = 'false';
$rejectClaims($unverifiedEmail);
$rejectClaims($validClaims, '');

echo 'All Google-auth checks passed: ' . $checks . PHP_EOL;

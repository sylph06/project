<?php
require_once __DIR__ . '/bootstrap.php';

$user = require_officer();
$memberId = trim((string)($user['legacy_id'] ?? ''));
if ($memberId === '') out(['ok' => false, 'error' => 'Only an active SK official can submit a committee preference.'], 403);

$preferences = $in['preferences'] ?? null;
$expected = $in['expected'] ?? null;
if (!is_array($preferences) || count($preferences) < 1 || count($preferences) > 2
    || array_keys($preferences) !== range(0, count($preferences) - 1) || !is_string($expected)) {
    out(['ok' => false, 'error' => 'Choose one or two committee preferences.'], 400);
}

$pdo = db();
$pdo->beginTransaction();
try {
    $committees = sk_committee_names($pdo);
    foreach ($preferences as $preference) {
        if (!is_string($preference) || mb_strlen($preference) > 100
            || !in_array($preference, $committees, true)) {
            throw new RuntimeException('Choose preferences from the current committee list.');
        }
    }
    if (count($preferences) !== count(array_unique($preferences))) {
        throw new RuntimeException('A committee can only be selected once.');
    }

    $roles = sk_role_rows($pdo, true);
    $role = null;
    foreach ($roles as $candidate) {
        if ((string)($candidate['kkId'] ?? '') === $memberId && sk_role_is_current($candidate)) {
            $role = $candidate;
            break;
        }
    }
    $member = sk_member_for_role_change($pdo, $memberId, true);
    if (!$role || !$member || ($member['status'] ?? 'Active') !== 'Active') {
        throw new RuntimeException('An active SK position and member record are required to submit a preference.');
    }

    $oldPreferences = sk_role_preferred_committees($role);
    $oldPreferencesJson = json_encode(array_values($oldPreferences), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (!hash_equals($oldPreferencesJson, $expected)) {
        throw new DomainException('Your committee preferences changed in another session. Reload Settings and try again.');
    }

    if (!sk_role_equal($oldPreferences, $preferences)) {
        $role['preferredCommittees'] = $preferences;
        $role['preferredCommittee'] = $preferences[0];
        if (!isset($role['history']) || !is_array($role['history'])) $role['history'] = [];
        $role['history'][] = [
            'at' => gmdate('Y-m-d\TH:i:s.000\Z'),
            'action' => 'Committee preferences updated',
            'by' => $user['username'],
            'note' => 'Preferred committees set to ' . implode(' and ', $preferences) . '.',
        ];
        sk_role_write($pdo, $role, $user['username']);
        sk_audit($pdo, $user, $memberId, 'committee_preference_updated',
            ['committees' => $oldPreferences],
            ['committees' => $preferences]);
    }

    $pdo->commit();
    out(['ok' => true, 'role' => $role]);
} catch (DomainException $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $error->getMessage()], 409);
} catch (RuntimeException $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $error->getMessage()], 400);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('SK committee preference update failed: ' . $error->getMessage());
    out(['ok' => false, 'error' => 'Could not save your committee preference.'], 500);
}

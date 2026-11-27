<?php
require_once __DIR__ . '/bootstrap.php';

$user = require_officer();
if (!can_manage_sk_roles()) out(['ok' => false, 'error' => 'Only the SK Chairman or SK Secretary can assign committee chairpersons.'], 403);

$assignments = $in['assignments'] ?? null;
$viceAssignments = $in['viceAssignments'] ?? null;
$memberAssignments = $in['memberAssignments'] ?? null;
$committees = $in['committees'] ?? null;
$expected = $in['expected'] ?? null;
$expectedVice = $in['expectedVice'] ?? null;
$expectedMembers = $in['expectedMembers'] ?? null;
if (!is_array($assignments) || !is_array($viceAssignments) || !is_array($memberAssignments) || !is_array($committees)
    || ($expected !== null && !is_string($expected))
    || ($expectedVice !== null && !is_string($expectedVice))
    || ($expectedMembers !== null && !is_string($expectedMembers))) {
    out(['ok' => false, 'error' => 'Committee assignments are invalid.'], 400);
}

$pdo = db();
$pdo->beginTransaction();
try {
    $currentCommittees = sk_committee_names($pdo, true);
    if (!sk_role_equal($committees, $currentCommittees)) {
        throw new DomainException('The committee list changed in another session. Reload and try again.');
    }

    $currentRaw = [];
    foreach ([
        'skCommitteeChairs' => $expected,
        'skCommitteeViceChairs' => $expectedVice,
        'skCommitteeMembers' => $expectedMembers,
    ] as $key => $expectedValue) {
        $lock = $pdo->prepare('SELECT data_value FROM app_data WHERE data_key = ? FOR UPDATE');
        $lock->execute([$key]);
        $raw = $lock->fetchColumn();
        $currentRaw[$key] = $raw === false ? null : (string)$raw;
        if ($expectedValue !== $currentRaw[$key]) {
            throw new DomainException('Committee assignments changed in another session. Reload and try again.');
        }
    }

    $roles = sk_role_rows($pdo, true);
    $normalized = [];
    $normalizedVice = [];
    $normalizedMembers = [];
    $today = date('Y-m-d');
    foreach ($currentCommittees as $committee) {
        $normalized[$committee] = validate_committee_official($pdo, $roles, $assignments[$committee] ?? null, $today);
        if ($normalized[$committee] === null) {
            throw new RuntimeException('Assign an active SK official as chairperson to every committee.');
        }
        $normalizedVice[$committee] = validate_committee_official($pdo, $roles, $viceAssignments[$committee] ?? null, $today);
        if ($normalized[$committee] !== null && $normalizedVice[$committee] === $normalized[$committee]) {
            throw new RuntimeException('The chairperson and vice chair must be different officials.');
        }
        $requestedMembers = $memberAssignments[$committee] ?? [];
        if (!is_array($requestedMembers) || ($requestedMembers !== [] && array_keys($requestedMembers) !== range(0, count($requestedMembers) - 1))) {
            throw new RuntimeException('Select valid committee members.');
        }
        $normalizedMembers[$committee] = [];
        foreach ($requestedMembers as $roleId) {
            $validRoleId = validate_committee_official($pdo, $roles, $roleId, $today);
            if ($validRoleId !== null && !in_array($validRoleId, $normalizedMembers[$committee], true)) {
                $normalizedMembers[$committee][] = $validRoleId;
            }
        }
        foreach ([$normalized[$committee], $normalizedVice[$committee]] as $leaderRoleId) {
            if ($leaderRoleId !== null && !in_array($leaderRoleId, $normalizedMembers[$committee], true)) {
                $normalizedMembers[$committee][] = $leaderRoleId;
            }
        }
    }

    $updates = [
        'skCommitteeChairs' => $normalized,
        'skCommitteeViceChairs' => $normalizedVice,
        'skCommitteeMembers' => $normalizedMembers,
    ];
    foreach ($updates as $key => $value) {
        $pdo->prepare('INSERT INTO app_data (data_key, data_value, updated_by)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE data_value = VALUES(data_value), updated_by = VALUES(updated_by)')
            ->execute([$key, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $user['username']]);
    }
    $old = [];
    foreach ($currentRaw as $key => $raw) $old[$key] = $raw === null ? [] : json_decode($raw, true);
    sk_audit($pdo, $user, null, 'committee_assignments_updated', $old, $updates);
    $pdo->commit();
    out([
        'ok' => true,
        'assignments' => (object)$normalized,
        'viceAssignments' => (object)$normalizedVice,
        'memberAssignments' => $normalizedMembers,
    ]);
} catch (DomainException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $ex->getMessage()], 409);
} catch (RuntimeException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $ex->getMessage()], 400);
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('SK committee chair update failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Could not save committee assignments.'], 500);
}

function validate_committee_official($pdo, $roles, $roleId, $today) {
    if ($roleId === null || $roleId === '') {
        return null;
    }
    if (!is_string($roleId) || !preg_match('/^[A-Za-z0-9_-]{1,80}$/', $roleId)) {
        throw new RuntimeException('Select a valid active SK official.');
    }
    $role = sk_role_find($roles, $roleId);
    if (!$role || !sk_role_is_current($role, $today)) {
        throw new RuntimeException('A selected SK official is no longer active. Reload and try again.');
    }
    $member = sk_member_for_role_change($pdo, (string)($role['kkId'] ?? ''), true);
    if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
        throw new RuntimeException('A selected SK official does not have an active member record.');
    }
    return $roleId;
}

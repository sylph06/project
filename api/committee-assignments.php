<?php
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$user = require_officer();
if (!can_manage_sk_roles()) {
    out(['ok' => false, 'error' => 'Only the SK Chairperson or Secretary can manage committee assignments.'], 403);
}
if (!in_array($action, ['get_assignments', 'save_assignments'], true)) {
    out(['ok' => false, 'error' => 'Unknown action.'], 400);
}

$pdo = db();
if ($action === 'get_assignments') {
    try {
        $roles = sk_role_rows($pdo);
        $committees = sk_committee_names($pdo);
        out([
            'ok' => true,
            'committees' => $committees,
            'roles' => array_values($roles),
            'assignments' => (object)committee_assignment_read_map($pdo, 'skCommitteeChairs'),
            'viceAssignments' => (object)committee_assignment_read_map($pdo, 'skCommitteeViceChairs'),
            'memberAssignments' => (object)committee_assignment_read_map($pdo, 'skCommitteeMembers'),
        ]);
    } catch (Throwable $error) {
        error_log('Committee assignments could not be loaded: ' . $error->getMessage());
        out(['ok' => false, 'error' => 'Could not load committee assignments.'], 500);
    }
}

$assignments = $in['assignments'] ?? null;
$viceAssignments = $in['viceAssignments'] ?? [];
$memberAssignments = $in['memberAssignments'] ?? null;
$committees = $in['committees'] ?? null;
$expected = $in['expected'] ?? null;
if (!is_array($assignments) || !is_array($viceAssignments) || !is_array($memberAssignments)
    || !is_array($committees) || !is_array($expected)
    || !array_key_exists('chairs', $expected) || !array_key_exists('viceChairs', $expected)
    || !array_key_exists('members', $expected)
    || !committee_assignment_expected_value_is_valid($expected['chairs'])
    || !committee_assignment_expected_value_is_valid($expected['viceChairs'])
    || !committee_assignment_expected_value_is_valid($expected['members'])) {
    out(['ok' => false, 'error' => 'Committee assignments are invalid. Reload and try again.'], 400);
}

$pdo->beginTransaction();
try {
    $currentCommittees = sk_committee_names($pdo, true);
    if (!sk_role_equal($committees, $currentCommittees)) {
        throw new DomainException('The committee list changed. Reload and try again.');
    }

    $rawByKey = [];
    foreach ([
        'skCommitteeChairs' => 'chairs',
        'skCommitteeViceChairs' => 'viceChairs',
        'skCommitteeMembers' => 'members',
    ] as $key => $expectedKey) {
        $lock = $pdo->prepare('SELECT data_value FROM app_data WHERE data_key = ? FOR UPDATE');
        $lock->execute([$key]);
        $raw = $lock->fetchColumn();
        $rawByKey[$key] = $raw === false ? null : (string)$raw;
        if ($expected[$expectedKey] !== $rawByKey[$key]) {
            throw new DomainException('Committee assignments changed in another session. Reload and try again.');
        }
    }

    $roles = sk_role_rows($pdo, true);
    $activeRoles = [];
    $today = date('Y-m-d');
    foreach ($roles as $role) {
        if (!sk_role_is_current($role, $today)) continue;
        $roleId = (string)($role['id'] ?? '');
        if ($roleId === '' || !preg_match('/^[A-Za-z0-9_-]{1,80}$/', $roleId)) continue;
        $member = sk_member_for_role_change($pdo, (string)($role['kkId'] ?? ''), true);
        if ($member && ($member['status'] ?? 'Active') === 'Active') {
            $activeRoles[$roleId] = $role;
        }
    }

    foreach ([$assignments, $viceAssignments, $memberAssignments] as $inputMap) {
        foreach (array_keys($inputMap) as $committee) {
            if (!is_string($committee) || !in_array($committee, $currentCommittees, true)) {
                throw new RuntimeException('An assignment references an unknown committee.');
            }
        }
    }

    $oldMaps = [
        'chairs' => committee_assignment_decode_map($rawByKey['skCommitteeChairs']),
        'viceChairs' => committee_assignment_decode_map($rawByKey['skCommitteeViceChairs']),
        'members' => committee_assignment_decode_map($rawByKey['skCommitteeMembers']),
    ];
    $normalizedChairs = [];
    $normalizedViceChairs = [];
    $normalizedMembers = [];

    foreach ($currentCommittees as $committee) {
        $memberIds = $memberAssignments[$committee] ?? [];
        if (!is_array($memberIds)
            || ($memberIds !== [] && array_keys($memberIds) !== range(0, count($memberIds) - 1))) {
            throw new RuntimeException('Select valid active SK members for each committee.');
        }
        $normalizedMembers[$committee] = [];
        foreach ($memberIds as $roleId) {
            $validRoleId = committee_assignment_validate_role($roleId, $activeRoles);
            if (!in_array($validRoleId, $normalizedMembers[$committee], true)) {
                $normalizedMembers[$committee][] = $validRoleId;
            }
        }

        $chairId = committee_assignment_validate_kagawad($assignments[$committee] ?? null, $activeRoles, true);
        if ($chairId === null) {
            throw new RuntimeException('Choose a chairperson for every committee.');
        }
        $viceId = committee_assignment_validate_kagawad($viceAssignments[$committee] ?? null, $activeRoles, true);
        if ($viceId !== null && $viceId === $chairId) {
            throw new RuntimeException('The chairperson and vice chair must be different officials.');
        }
        foreach ([$chairId, $viceId] as $leaderId) {
            if ($leaderId !== null && !in_array($leaderId, $normalizedMembers[$committee], true)) {
                $normalizedMembers[$committee][] = $leaderId;
            }
        }

        $normalizedChairs[$committee] = $chairId;
        $normalizedViceChairs[$committee] = $viceId;
    }

    $updates = [
        'skCommitteeChairs' => $normalizedChairs,
        'skCommitteeViceChairs' => $normalizedViceChairs,
        'skCommitteeMembers' => $normalizedMembers,
    ];
    $oldByKey = [
        'skCommitteeChairs' => $oldMaps['chairs'],
        'skCommitteeViceChairs' => $oldMaps['viceChairs'],
        'skCommitteeMembers' => $oldMaps['members'],
    ];
    $encodedUpdates = [];
    foreach ($updates as $key => $value) {
        if (sk_role_equal($oldByKey[$key], $value)) {
            $encodedUpdates[$key] = $rawByKey[$key];
            continue;
        }
        $encodedUpdates[$key] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $pdo->prepare('INSERT INTO app_data (data_key, data_value, updated_by)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE data_value = VALUES(data_value), updated_by = VALUES(updated_by)')
            ->execute([$key, $encodedUpdates[$key], $user['username']]);
    }

    $actionByKey = [
        'skCommitteeChairs' => 'committee_chair_assignment_updated',
        'skCommitteeViceChairs' => 'committee_vice_assignment_updated',
        'skCommitteeMembers' => 'committee_membership_updated',
    ];
    foreach ($updates as $key => $value) {
        if (sk_role_equal($oldByKey[$key], $value)) continue;
        sk_audit($pdo, $user, null, $actionByKey[$key], $oldByKey[$key], $value);
    }

    $pdo->commit();
    out([
        'ok' => true,
        'committees' => $currentCommittees,
        'assignments' => (object)$normalizedChairs,
        'viceAssignments' => (object)$normalizedViceChairs,
        'memberAssignments' => $normalizedMembers,
        'rawAssignments' => $encodedUpdates['skCommitteeChairs'],
        'rawViceAssignments' => $encodedUpdates['skCommitteeViceChairs'],
        'rawMemberAssignments' => $encodedUpdates['skCommitteeMembers'],
    ]);
} catch (DomainException $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $error->getMessage()], 409);
} catch (RuntimeException $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $error->getMessage()], 400);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Committee assignments failed: ' . $error->getMessage());
    out(['ok' => false, 'error' => 'Could not save committee assignments.'], 500);
}

function committee_assignment_expected_value_is_valid($value) {
    return $value === null || is_string($value);
}

function committee_assignment_decode_map($raw) {
    if ($raw === null) return [];
    $map = json_decode($raw, true);
    if (!is_array($map) || ($map !== [] && array_keys($map) === range(0, count($map) - 1))) {
        throw new RuntimeException('Saved committee assignment data is invalid.');
    }
    return $map;
}

function committee_assignment_read_map($pdo, $key) {
    $query = $pdo->prepare('SELECT data_value FROM app_data WHERE data_key = ?');
    $query->execute([$key]);
    $raw = $query->fetchColumn();
    return committee_assignment_decode_map($raw === false ? null : (string)$raw);
}

function committee_assignment_validate_role($roleId, $activeRoles, $allowUnassigned = false) {
    if ($roleId === null || $roleId === '') {
        if ($allowUnassigned) return null;
        throw new RuntimeException('Select active SK officials for committee membership.');
    }
    if (!is_string($roleId) || !isset($activeRoles[$roleId])) {
        throw new RuntimeException('A selected committee member or leader is not an active SK official.');
    }
    return $roleId;
}

function committee_assignment_validate_kagawad($roleId, $activeRoles, $allowUnassigned = false) {
    $validRoleId = committee_assignment_validate_role($roleId, $activeRoles, $allowUnassigned);
    if ($validRoleId !== null && ($activeRoles[$validRoleId]['position'] ?? '') !== 'SK Kagawad') {
        throw new RuntimeException('Only active SK Kagawads can be committee chairs or vice chairs.');
    }
    return $validRoleId;
}

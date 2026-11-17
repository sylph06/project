<?php
require_once __DIR__ . '/bootstrap.php';

$user = require_officer();
if (!can_manage_sk_roles()) out(['ok' => false, 'error' => 'Only the SK Chairman or SK Secretary can assign committee chairpersons.'], 403);

$assignments = $in['assignments'] ?? null;
$committees = $in['committees'] ?? null;
$expected = $in['expected'] ?? null;
if (!is_array($assignments) || !is_array($committees)
    || ($expected !== null && !is_string($expected))) {
    out(['ok' => false, 'error' => 'Committee chair assignments are invalid.'], 400);
}

$pdo = db();
$pdo->beginTransaction();
try {
    $currentCommittees = sk_committee_names($pdo, true);
    if (!sk_role_equal($committees, $currentCommittees)) {
        throw new DomainException('The committee list changed in another session. Reload and try again.');
    }

    $lock = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'skCommitteeChairs' FOR UPDATE");
    $lock->execute();
    $oldRaw = $lock->fetchColumn();
    $oldRaw = $oldRaw === false ? null : (string)$oldRaw;
    if ($expected !== $oldRaw) {
        throw new DomainException('Committee chair assignments changed in another session. Reload and try again.');
    }

    $roles = sk_role_rows($pdo, true);
    $normalized = [];
    foreach ($currentCommittees as $committee) {
        $roleId = $assignments[$committee] ?? null;
        if ($roleId === null || $roleId === '') {
            if (in_array($committee, sk_default_committees(), true)) {
                throw new RuntimeException('Assign an active SK official as chairperson to every required committee.');
            }
            $normalized[$committee] = null;
            continue;
        }
        if (!is_string($roleId) || !preg_match('/^[A-Za-z0-9_-]{1,80}$/', $roleId)) {
            throw new RuntimeException('Select a valid SK official for each chairperson assignment.');
        }
        $role = sk_role_find($roles, $roleId);
        $today = date('Y-m-d');
        if (!$role || ($role['status'] ?? '') !== 'Active'
            || (!empty($role['termStart']) && $role['termStart'] > $today)
            || (!empty($role['termEnd']) && $role['termEnd'] < $today)) {
            throw new RuntimeException('A selected SK official is no longer active. Reload and try again.');
        }
        $member = sk_member_for_role_change($pdo, (string)($role['kkId'] ?? ''), true);
        if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
            throw new RuntimeException('A selected SK official does not have an active member record.');
        }
        $normalized[$committee] = $roleId;
    }

    $value = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $pdo->prepare("INSERT INTO app_data (data_key, data_value, updated_by)
        VALUES ('skCommitteeChairs', ?, ?)
        ON DUPLICATE KEY UPDATE data_value = VALUES(data_value), updated_by = VALUES(updated_by)")
        ->execute([$value, $user['username']]);
    $old = $oldRaw === null ? null : json_decode($oldRaw, true);
    sk_audit($pdo, $user, null, 'committee_chairpersons_updated', $old, $normalized);
    $pdo->commit();
    out(['ok' => true, 'assignments' => $normalized]);
} catch (DomainException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $ex->getMessage()], 409);
} catch (RuntimeException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $ex->getMessage()], 400);
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('SK committee chair update failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Could not save committee chairpersons.'], 500);
}

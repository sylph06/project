<?php
/* =========================================================
   api/roles.php — SK role assign, update, end, archive
   ========================================================= */
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$user = require_officer();

if (!in_array($action, ['role_assign', 'role_update', 'role_end', 'role_archive', 'role_archive_expired'], true)) {
    out(['ok' => false, 'error' => 'Unknown action.'], 400);
}

if (!can_manage_sk_roles()) out(['ok' => false, 'error' => 'Only the SK Chairman or SK Secretary can manage SK roles.'], 403);
$pdo = db();
$pdo->beginTransaction();
try {
    $roles = sk_role_rows($pdo, true);
    if ($action === 'role_archive_expired') {
        $today = date('Y-m-d');
        $count = 0;
        foreach ($roles as $expired) {
            if (($expired['status'] ?? '') !== 'Active' || empty($expired['termEnd']) || $expired['termEnd'] >= $today) continue;
            $old = $expired;
            $expired['status'] = 'Archived';
            if (!isset($expired['history']) || !is_array($expired['history'])) $expired['history'] = [];
            $expired['history'][] = [
                'at' => gmdate('Y-m-d\TH:i:s.000\Z'),
                'action' => 'Auto-archived',
                'by' => $user['username'],
                'note' => 'Term ended on ' . $expired['termEnd'] . '.',
            ];
            sk_role_write($pdo, $expired, $user['username']);
            sk_audit($pdo, $user, (string)$expired['kkId'], 'role_archived', $old, $expired);
            $count++;
        }
        $pdo->commit();
        out(['ok' => true, 'archived' => $count, 'roles' => array_values($roles)]);
    }
    $roleId = (string)($in['id'] ?? ($in['role']['id'] ?? ''));
    $oldRole = $roleId === '' ? null : sk_role_find($roles, $roleId);
    if ($action === 'role_assign') {
        $inputRole = $in['role'] ?? null;
        $kkId = is_array($inputRole) ? (string)($inputRole['kkId'] ?? '') : '';
        $member = $kkId === '' ? null : sk_member_for_role_change($pdo, $kkId, true);
        if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
            throw new RuntimeException('Only an active KK member can be assigned an SK role.');
        }
        if ($roleId === '' || $oldRole) throw new RuntimeException('The role id is missing or already exists.');
        $validated = sk_role_validate_payload($inputRole, false, $pdo);
        $seatError = sk_role_seat_error($pdo, $roles, $validated['position']);
        if ($seatError !== '') throw new RuntimeException($seatError);
        $newRole = array_merge($validated, [
            'id' => $roleId,
            'kkId' => $kkId,
            'status' => 'Active',
            'history' => [],
            'createdAt' => gmdate('Y-m-d\TH:i:s.000\Z'),
        ]);
        $newRole['history'][] = [
            'at' => gmdate('Y-m-d\TH:i:s.000\Z'),
            'action' => 'SK role assigned',
            'by' => $user['username'],
            'note' => $newRole['position'] . ' (' . $newRole['basis'] . ').',
        ];
        sk_role_write($pdo, $newRole, $user['username']);
        sk_audit($pdo, $user, $kkId, 'role_assigned', null, $newRole);
        $pdo->commit();
        out(['ok' => true, 'role' => $newRole]);
    }

    if (!$oldRole) throw new RuntimeException('The SK role no longer exists.');
    $expectedRole = $in['expected'] ?? null;
    if (!is_array($expectedRole) || !sk_role_equal($expectedRole, $oldRole)) {
        throw new RuntimeException('This SK role changed in another session. Reload and try again.');
    }
    if ($action === 'role_update') {
        $member = sk_member_for_role_change($pdo, (string)$oldRole['kkId'], true);
        if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
            throw new RuntimeException('Only an active KK member can hold an SK role.');
        }
        $roleInput = $in['role'] ?? null;
        $validated = sk_role_validate_payload(
            $roleInput,
            ($oldRole['position'] ?? '') === 'SK Vice Chairperson',
            $pdo
        );
        $newRole = array_merge($oldRole, $validated);
        $newStatus = is_array($roleInput) ? (string)($roleInput['status'] ?? $oldRole['status']) : '';
        if (!in_array($newStatus, ['Active', 'Inactive'], true)) {
            throw new RuntimeException('Use the dedicated archive action to archive an SK role.');
        }
        $wasOccupyingSeat = ($oldRole['status'] ?? '') === 'Active'
            && (empty($oldRole['termStart']) || $oldRole['termStart'] <= date('Y-m-d'))
            && (empty($oldRole['termEnd']) || $oldRole['termEnd'] >= date('Y-m-d'));
        if ($newStatus === 'Active'
            && (!$wasOccupyingSeat || $validated['position'] !== ($oldRole['position'] ?? ''))) {
            $seatError = sk_role_seat_error($pdo, $roles, $validated['position'], (string)$oldRole['id']);
            if ($seatError !== '') throw new RuntimeException($seatError);
        }
        $newRole['status'] = $newStatus;
        if (!isset($newRole['history']) || !is_array($newRole['history'])) $newRole['history'] = [];
        $newRole['history'][] = [
            'at' => gmdate('Y-m-d\TH:i:s.000\Z'),
            'action' => 'Updated',
            'by' => $user['username'],
            'note' => 'SK role information updated.',
        ];
        sk_role_write($pdo, $newRole, $user['username']);
        sk_audit($pdo, $user, (string)$oldRole['kkId'], 'role_updated', $oldRole, $newRole);
        $pdo->commit();
        out(['ok' => true, 'role' => $newRole]);
    }

    $newRole = $oldRole;
    $newRole['status'] = $action === 'role_end' ? 'Inactive' : 'Archived';
    if (!isset($newRole['history']) || !is_array($newRole['history'])) $newRole['history'] = [];
    $newRole['history'][] = [
        'at' => gmdate('Y-m-d\TH:i:s.000\Z'),
        'action' => $action === 'role_end' ? 'Role ended' : 'Role archived',
        'by' => $user['username'],
        'note' => (string)($in['reason'] ?? ''),
    ];
    sk_role_write($pdo, $newRole, $user['username']);
    sk_audit($pdo, $user, (string)$oldRole['kkId'], $action === 'role_end' ? 'role_ended' : 'role_archived', $oldRole, $newRole);
    $pdo->commit();
    out(['ok' => true, 'role' => $newRole]);
} catch (RuntimeException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $ex->getMessage()], 400);
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('SK role operation failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Could not save the SK role.'], 500);
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('SK role operation failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Could not save the SK role.'], 500);
}

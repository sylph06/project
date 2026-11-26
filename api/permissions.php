<?php
require_once __DIR__ . '/bootstrap.php';

$user = require_officer();
$action = $_GET['action'] ?? '';
if (!in_array($action, [
    'permission_list', 'permission_grant', 'permission_revoke', 'official_recovery_code_generate',
], true)) {
    out(['ok' => false, 'error' => 'Unknown action.'], 400);
}
if (!can_manage_sk_roles()) {
    out(['ok' => false, 'error' => 'Only the SK Chairman or SK Secretary can manage permissions.'], 403);
}

$permissionNames = [
    'unbind_google',
    'reset_member_password',
    'record_attendance',
    'edit_members',
    'manage_projects',
    'view_audit_log',
];
$pdo = db();
$permissionTables = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('user_permissions', 'official_recovery_codes')");
$permissionTables->execute();
if ((int)$permissionTables->fetchColumn() !== 2) {
    out(['ok' => false, 'error' => 'Delegated permissions and emergency recovery are not configured. Run the required database migration.'], 503);
}

function permission_default_expiry_date($pdo, $memberId) {
    foreach (sk_role_rows($pdo) as $role) {
        if ((string)($role['kkId'] ?? '') === (string)$memberId
            && sk_role_is_current($role)
            && is_string($role['termEnd'] ?? null)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $role['termEnd'])) {
            return $role['termEnd'];
        }
    }
    return '2027-06-30';
}

function permission_target_account($pdo, $memberId, $lock = false) {
    $statement = $pdo->prepare('SELECT id, username, full_name, role, is_active, legacy_id
        FROM users WHERE legacy_id = ? AND is_active = 1 ORDER BY id LIMIT 1' . ($lock ? ' FOR UPDATE' : ''));
    $statement->execute([$memberId]);
    $account = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$account) return null;
    $account['effective_role'] = kk_effective_role($account);
    return $account;
}

function permission_target_is_protected($account) {
    return is_array($account)
        && in_array($account['effective_role'] ?? '', ['SK Chairman', 'SK Chairperson', 'SK Secretary'], true);
}

if ($action === 'permission_list') {
    $accounts = $pdo->query('SELECT id, username, full_name, role, is_active, legacy_id
        FROM users WHERE is_active = 1 ORDER BY full_name')
        ->fetchAll(PDO::FETCH_ASSOC);
    $officials = [];
    $protectedOfficials = [];
    foreach ($accounts as $account) {
        $role = kk_effective_role($account);
        if (in_array($role, ['SK Chairman', 'SK Chairperson', 'SK Secretary'], true)) {
            $protectedOfficials[] = [
                'id' => (int)$account['id'],
                'name' => (string)$account['full_name'],
                'username' => (string)$account['username'],
                'role' => $role,
            ];
        }
        if (!in_array($role, ['SK Treasurer', 'SK Kagawad', 'SK Vice Chairperson'], true)) continue;
        $officials[] = [
            'memberId' => (string)$account['legacy_id'],
            'name' => (string)$account['full_name'],
            'username' => (string)$account['username'],
            'role' => $role,
            'defaultExpiresAt' => permission_default_expiry_date($pdo, (string)$account['legacy_id']),
        ];
    }
    $grants = $pdo->query('SELECT p.id, p.member_id, p.permission, p.granted_at, p.expires_at,
            p.revoked_at, u.full_name AS granted_by_name
        FROM user_permissions p LEFT JOIN users u ON u.id = p.granted_by
        ORDER BY p.granted_at DESC, p.id DESC')->fetchAll(PDO::FETCH_ASSOC);
    $codeGenerated = (bool)$pdo->query('SELECT 1 FROM official_recovery_codes WHERE id = 1')->fetchColumn();
    out([
        'ok' => true,
        'officials' => $officials,
        'protectedOfficials' => $protectedOfficials,
        'grants' => $grants,
        'emergencyCodeGenerated' => $codeGenerated,
    ]);
}

try {
    $pdo->beginTransaction();
    if ($action === 'official_recovery_code_generate') {
        $existingCode = $pdo->query('SELECT id FROM official_recovery_codes WHERE id = 1 FOR UPDATE')->fetchColumn();
        if ($existingCode !== false) {
            throw new RuntimeException('An emergency recovery code has already been generated and cannot be replaced.');
        }
        $recoveryCode = bin2hex(random_bytes(32));
        $pdo->prepare('INSERT INTO official_recovery_codes (id, code_hash, generated_by, generated_at, used_at)
            VALUES (1, ?, ?, NOW(), NULL)')
            ->execute([hash('sha256', $recoveryCode), (int)$user['id']]);
        sk_audit($pdo, $user, null, 'official_recovery_code_generated', null, [
            'generated_by_user_id' => (int)$user['id'],
            'one_time_display' => true,
        ]);
        $pdo->commit();
        out(['ok' => true, 'recoveryCode' => $recoveryCode]);
    }

    if ($action === 'permission_grant') {
        $memberId = trim((string)($in['memberId'] ?? ''));
        $requested = $in['permissions'] ?? null;
        $expiryDate = $in['expiresAt'] ?? '';
        if ($memberId === '' || strlen($memberId) > 100 || !is_array($requested)
            || !$requested || ($requested !== [] && array_keys($requested) !== range(0, count($requested) - 1))
            || !is_string($expiryDate)) {
            throw new RuntimeException('Choose an official, at least one permission, and a valid expiry date.');
        }
        if ((string)($user['legacy_id'] ?? '') === $memberId) {
            throw new RuntimeException('You cannot change your own permissions.');
        }

        $target = permission_target_account($pdo, $memberId, true);
        if (!$target || !in_array($target['effective_role'], ['SK Treasurer', 'SK Kagawad', 'SK Vice Chairperson'], true)
            || permission_target_is_protected($target)) {
            throw new RuntimeException('Permissions can be granted only to active officials other than the SK Chairman or SK Secretary.');
        }
        if ((int)$target['id'] === (int)$user['id']) {
            throw new RuntimeException('You cannot change your own permissions.');
        }

        $expiresAt = null;
        $expiryDate = trim($expiryDate);
        if ($expiryDate === '') {
            $expiryDate = permission_default_expiry_date($pdo, $memberId);
        }
        if ($expiryDate !== '') {
            $parsedExpiry = DateTimeImmutable::createFromFormat('!Y-m-d', $expiryDate);
            if (!$parsedExpiry || $parsedExpiry->format('Y-m-d') !== $expiryDate
                || $parsedExpiry->format('Y-m-d') < date('Y-m-d')) {
                throw new RuntimeException('Choose a valid expiry date that is today or later.');
            }
            $expiresAt = $expiryDate . ' 23:59:59';
        }

        foreach ($requested as $permission) {
            if (!is_string($permission) || !in_array($permission, $permissionNames, true)) {
                throw new RuntimeException('The permission list contains an unsupported permission.');
            }
        }
        $requested = array_values(array_unique($requested));
        foreach ($requested as $permission) {
            if (!can($permission)) {
                throw new RuntimeException('You cannot grant a permission that you do not hold.');
            }
            $existing = $pdo->prepare("SELECT id FROM user_permissions
                WHERE member_id = ? AND permission = ? AND revoked_at IS NULL
                  AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1 FOR UPDATE");
            $existing->execute([$memberId, $permission]);
            if ($existing->fetchColumn() !== false) {
                throw new RuntimeException('This official already has that active permission.');
            }
            $pdo->prepare('INSERT INTO user_permissions
                (member_id, permission, granted_by, granted_at, expires_at, revoked_at)
                VALUES (?, ?, ?, NOW(), ?, NULL)')
                ->execute([$memberId, $permission, (int)$user['id'], $expiresAt]);
            $grantId = (int)$pdo->lastInsertId();
            sk_audit($pdo, $user, $memberId, 'permission_granted', null, [
                'grant_id' => $grantId,
                'permission' => $permission,
                'expires_at' => $expiresAt,
                'granted_by_user_id' => (int)$user['id'],
            ]);
        }
        $pdo->commit();
        out(['ok' => true]);
    }

    $grantId = filter_var($in['grantId'] ?? null, FILTER_VALIDATE_INT);
    if (!$grantId || $grantId < 1) throw new RuntimeException('A valid permission grant is required.');
    $grantQuery = $pdo->prepare('SELECT id, member_id, permission, granted_by, granted_at, expires_at, revoked_at
        FROM user_permissions WHERE id = ? FOR UPDATE');
    $grantQuery->execute([$grantId]);
    $grant = $grantQuery->fetch(PDO::FETCH_ASSOC);
    if (!$grant || $grant['revoked_at'] !== null) throw new RuntimeException('This permission grant is no longer active.');
    $target = permission_target_account($pdo, (string)$grant['member_id'], true);
    if ($target && (int)$target['id'] === (int)$user['id']) {
        throw new RuntimeException('You cannot change your own permissions.');
    }
    if (permission_target_is_protected($target)) {
        throw new RuntimeException('The SK Chairman and SK Secretary accounts cannot be changed through delegation.');
    }
    $pdo->prepare('UPDATE user_permissions SET revoked_at = NOW() WHERE id = ? AND revoked_at IS NULL')
        ->execute([$grantId]);
    sk_audit($pdo, $user, (string)$grant['member_id'], 'permission_revoked', $grant, [
        'grant_id' => (int)$grantId,
        'permission' => (string)$grant['permission'],
        'revoked_by_user_id' => (int)$user['id'],
    ]);
    $pdo->commit();
    out(['ok' => true]);
} catch (RuntimeException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $exception->getMessage()], 400);
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Permission change failed: ' . $exception->getMessage());
    out(['ok' => false, 'error' => 'Could not change permissions.'], 500);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Permission change failed: ' . $exception->getMessage());
    out(['ok' => false, 'error' => 'Could not change permissions.'], 500);
}

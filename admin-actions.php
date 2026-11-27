<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/role_store.php';
require_once __DIR__ . '/members_table.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST only.');
}

$actor = current_user();
if (!$actor || !is_super_admin()) {
    http_response_code(403);
    exit('You do not have permission to perform this action.');
}
if (!empty($actor['must_change_password'])) {
    header('Location: change-password.php');
    exit;
}
$submittedToken = $_POST['csrf_token'] ?? null;
if (!is_string($submittedToken) || !hash_equals(csrf_token(), $submittedToken)) {
    http_response_code(403);
    exit('Your session token is missing or expired. Reload the page and try again.');
}

function super_admin_post_string($key, $maxLength) {
    $value = $_POST[$key] ?? null;
    if (!is_string($value) || strlen($value) > $maxLength) {
        throw new RuntimeException('The submitted form data is invalid.');
    }
    return $value;
}

function super_admin_post_date($key) {
    $value = super_admin_post_string($key, 10);
    if ($value === '') return null;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count']))
        || $date->format('Y-m-d') !== $value) {
        throw new RuntimeException('Enter valid dates in YYYY-MM-DD format.');
    }
    return $value;
}

function super_admin_redirect($type, $message) {
    $_SESSION['super_admin_flash'] = ['type' => $type, 'message' => $message];
    header('Location: super-admin.php');
    exit;
}

$action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
$pdo = db();

try {
    if ($action === 'override_role') {
        $accountId = super_admin_post_string('account_id', 20);
        $role = super_admin_post_string('role', 60);
        $allowedRoles = array_values(array_unique(array_merge(['KK Member'], APP_WRITE_ROLES)));
        if (!ctype_digit($accountId) || (int)$accountId < 1 || !in_array($role, $allowedRoles, true)) {
            throw new RuntimeException('Choose a valid login account and role.');
        }

        $pdo->beginTransaction();
        $statement = $pdo->prepare('SELECT id, username, email, role, legacy_id FROM users WHERE id = ? FOR UPDATE');
        $statement->execute([(int)$accountId]);
        $account = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$account) throw new RuntimeException('The login account no longer exists.');
        if (strtolower((string)$account['email']) === SUPER_ADMIN_EMAIL) {
            throw new RuntimeException('The Admin role is reserved for its configured email account.');
        }
        if ($account['legacy_id'] !== null) {
            throw new RuntimeException('Member-linked accounts get their effective role from the SK position record.');
        }
        $oldRole = (string)$account['role'];
        if ($oldRole !== $role) {
            $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, (int)$accountId]);
            sk_audit($pdo, $actor, 'user:' . (int)$accountId, 'super_admin_login_role_updated',
                ['role' => $oldRole], ['role' => $role]);
        }
        $pdo->commit();
        super_admin_redirect('success', $oldRole === $role ? 'The login account already has that role.' : 'The login role was updated.');
    }

    if ($action === 'create_position_override') {
        $memberId = super_admin_post_string('member_id', 100);
        $position = super_admin_post_string('position', 60);
        $basis = super_admin_post_string('basis', 20);
        $electionYear = super_admin_post_string('election_year', 4);
        $dateElected = super_admin_post_date('date_elected');
        $termStart = super_admin_post_date('term_start');
        $termEnd = super_admin_post_date('term_end');
        if ($memberId === '' || !preg_match('/^[A-Za-z0-9_-]{1,100}$/', $memberId)) {
            throw new RuntimeException('Choose a valid KK member.');
        }
        if ($termStart !== null && $termEnd !== null && $termEnd <= $termStart) {
            throw new RuntimeException('The term end date must be after its start date.');
        }

        $pdo->beginTransaction();
        $allRoles = sk_role_rows($pdo, true);
        $member = sk_member_for_role_change($pdo, $memberId, true);
        if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
            throw new RuntimeException('Only an active KK member can be assigned an SK role.');
        }
        if (sk_role_member_has_current_assignment($allRoles, $memberId)) {
            throw new RuntimeException('This KK member already has a current SK role. Update that role instead.');
        }
        $validated = sk_role_validate_payload([
            'position' => $position,
            'committee' => '',
            'basis' => $basis,
            'electionYear' => $electionYear,
            'dateElected' => $dateElected,
            'termStart' => $termStart,
            'termEnd' => $termEnd,
            'description' => '',
            'responsibilities' => '',
        ], true, $pdo);
        do {
            $roleId = bin2hex(random_bytes(16));
        } while (sk_role_find($allRoles, $roleId));
        $newRole = array_merge($validated, [
            'id' => $roleId,
            'kkId' => $memberId,
            'status' => 'Active',
            'history' => [[
                'at' => gmdate('Y-m-d\TH:i:s.000\Z'),
                'action' => 'Admin role assignment override',
                'by' => $actor['username'],
                'note' => $position . ' (' . $basis . '); seat-capacity override.',
            ]],
            'createdAt' => gmdate('Y-m-d\TH:i:s.000\Z'),
        ]);
        sk_role_write($pdo, $newRole, $actor['username']);
        sk_audit($pdo, $actor, $memberId, 'super_admin_position_override', null, $newRole);
        $pdo->commit();
        super_admin_redirect('success', 'The official assignment override was created and audited.');
    }

    if ($action === 'override_position') {
        $roleId = super_admin_post_string('role_id', 100);
        $expectedHash = super_admin_post_string('expected_hash', 64);
        $position = super_admin_post_string('position', 60);
        $status = super_admin_post_string('status', 20);
        $basis = super_admin_post_string('basis', 20);
        $electionYear = super_admin_post_string('election_year', 4);
        $dateElected = super_admin_post_date('date_elected');
        $termStart = super_admin_post_date('term_start');
        $termEnd = super_admin_post_date('term_end');
        $allowedPositions = ['SK Chairperson', 'SK Secretary', 'SK Treasurer', 'SK Kagawad', 'SK Vice Chairperson'];
        if ($roleId === '' || !preg_match('/^[A-Za-z0-9_-]{1,100}$/', $roleId)
            || !preg_match('/^[a-f0-9]{64}$/', $expectedHash)
            || !in_array($position, $allowedPositions, true)
            || !in_array($status, ['Active', 'Inactive', 'Archived'], true)
            || !in_array($basis, ['elected', 'appointed'], true)
            || !preg_match('/^[0-9]{4}$/', $electionYear)
            || (int)$electionYear < 2000 || (int)$electionYear > 2100
            || $dateElected === null || substr($dateElected, 0, 4) !== $electionYear) {
            throw new RuntimeException('Choose a valid position, status, basis, year, and role record.');
        }
        if (($termStart !== null && $termStart < $dateElected)
            || ($termStart !== null && $termEnd !== null && $termEnd <= $termStart)) {
            throw new RuntimeException('The term dates must follow the election/appointment date and be in order.');
        }

        $pdo->beginTransaction();
        $allRoles = sk_role_rows($pdo, true);
        $oldRole = sk_role_find($allRoles, $roleId);
        if (!$oldRole) throw new RuntimeException('The SK role no longer exists.');
        $currentHash = hash('sha256', json_encode(
            $oldRole,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
        ));
        if (!hash_equals($currentHash, $expectedHash)) {
            throw new RuntimeException('This SK role changed in another session. Reload and try again.');
        }
        $member = sk_member_for_role_change($pdo, (string)($oldRole['kkId'] ?? ''), true);
        if (!$member) throw new RuntimeException('The KK member linked to this role could not be found.');
        if ($status === 'Active' && ($member['status'] ?? 'Active') !== 'Active') {
            throw new RuntimeException('An archived KK member cannot hold an active SK position.');
        }
        if ($status === 'Active'
            && sk_role_member_has_current_assignment($allRoles, (string)$oldRole['kkId'], (string)$oldRole['id'])) {
            throw new RuntimeException('This KK member already has another current SK role.');
        }

        $newRole = $oldRole;
        $newRole['position'] = $position;
        $newRole['status'] = $status;
        $newRole['basis'] = $basis;
        $newRole['electionYear'] = $electionYear;
        $newRole['dateElected'] = $dateElected;
        $newRole['termStart'] = $termStart;
        $newRole['termEnd'] = $termEnd;
        if (!isset($newRole['history']) || !is_array($newRole['history'])) $newRole['history'] = [];
        $newRole['history'][] = [
            'at' => gmdate('Y-m-d\TH:i:s.000\Z'),
            'action' => 'Admin override',
            'by' => $actor['username'],
            'note' => 'Official position or term was changed by Admin.',
        ];
        sk_role_write($pdo, $newRole, $actor['username']);
        sk_audit($pdo, $actor, (string)$oldRole['kkId'], 'super_admin_position_override', $oldRole, $newRole);
        $pdo->commit();
        super_admin_redirect('success', 'The official-position override was saved and audited.');
    }

    if ($action === 'save_app_data') {
        $dataKey = super_admin_post_string('data_key', 100);
        $expectedHash = super_admin_post_string('expected_hash', 64);
        $jsonValue = super_admin_post_string('json_value', 2097152);
        if (!preg_match('/^[A-Za-z0-9_]{1,100}$/', $dataKey) || $dataKey === 'maintenance_mode'
            || !preg_match('/^[a-f0-9]{64}$/', $expectedHash)) {
            throw new RuntimeException('Choose a valid application data record.');
        }
        if (($_POST['confirm_live_data'] ?? null) !== 'yes') {
            throw new RuntimeException('Confirm that you understand this changes live application data.');
        }
        try {
            json_decode($jsonValue, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new RuntimeException('The value is not valid JSON. Correct it before saving.');
        }
        $encodedValue = $jsonValue;

        $pdo->beginTransaction();
        $statement = $pdo->prepare('SELECT data_value FROM app_data WHERE data_key = ? FOR UPDATE');
        $statement->execute([$dataKey]);
        $oldRaw = $statement->fetchColumn();
        if ($oldRaw === false) throw new RuntimeException('The application data record no longer exists.');
        if (!hash_equals(hash('sha256', (string)$oldRaw), $expectedHash)) {
            throw new RuntimeException('This application data changed in another session. Reload and try again.');
        }
        $pdo->prepare('UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = ?')
            ->execute([$encodedValue, $actor['username'], $dataKey]);
        sk_audit($pdo, $actor, substr('appdata:' . $dataKey, 0, 100), 'super_admin_app_data_updated', [
            'sha256' => hash('sha256', (string)$oldRaw),
            'bytes' => strlen((string)$oldRaw),
        ], [
            'sha256' => hash('sha256', $encodedValue),
            'bytes' => strlen($encodedValue),
        ]);
        $pdo->commit();
        super_admin_redirect('success', 'The JSON was validated, saved, and recorded in the audit log.');
    }

    if ($action === 'toggle_maintenance') {
        $enabledValue = super_admin_post_string('enabled', 1);
        if (!in_array($enabledValue, ['0', '1'], true)) {
            throw new RuntimeException('Choose whether maintenance mode should be enabled or disabled.');
        }
        $enabled = $enabledValue === '1';
        $newRaw = json_encode(['enabled' => $enabled], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $pdo->beginTransaction();
        $statement = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'maintenance_mode' FOR UPDATE");
        $oldRaw = $statement->fetchColumn();
        $pdo->prepare("INSERT INTO app_data (data_key, data_value, updated_by)
            VALUES ('maintenance_mode', ?, ?)
            ON DUPLICATE KEY UPDATE data_value = VALUES(data_value), updated_by = VALUES(updated_by)")
            ->execute([$newRaw, $actor['username']]);
        sk_audit($pdo, $actor, 'maintenance_mode', 'super_admin_maintenance_mode_changed', [
            'sha256' => $oldRaw === false ? null : hash('sha256', (string)$oldRaw),
            'bytes' => $oldRaw === false ? 0 : strlen((string)$oldRaw),
        ], ['enabled' => $enabled]);
        $pdo->commit();
        super_admin_redirect('success', $enabled ? 'Maintenance mode is enabled.' : 'Maintenance mode is disabled.');
    }

    http_response_code(400);
    exit('Unknown Admin action.');
} catch (RuntimeException $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    super_admin_redirect('danger', $error->getMessage());
} catch (PDOException $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Admin action failed: ' . $error->getMessage());
    super_admin_redirect('danger', 'The action could not be saved. Check the application error log.');
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Admin action failed: ' . $error->getMessage());
    super_admin_redirect('danger', 'The action could not be saved. Check the application error log.');
}

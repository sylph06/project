<?php
require_once __DIR__ . '/config.php';

function member_account_migration_authorized() {
    if (PHP_SAPI === 'cli' || in_array((string)($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1', '::1'], true)) return true;
    return can_manage_sk_roles();
}

function member_account_emit($result, $status = 200) {
    if (PHP_SAPI === 'cli') {
        fwrite($status >= 400 ? STDERR : STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        if ($status >= 400) exit(1);
        return;
    }
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

function member_account_ensure_schema($pdo) {
    $columns = [
        'legacy_id' => 'ALTER TABLE users ADD COLUMN legacy_id VARCHAR(100) NULL',
        'must_change_password' => 'ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0',
        'guardian_consent' => 'ALTER TABLE users ADD COLUMN guardian_consent TINYINT(1) NOT NULL DEFAULT 0',
        'consent_at' => 'ALTER TABLE users ADD COLUMN consent_at DATETIME NULL',
        'consent_version' => 'ALTER TABLE users ADD COLUMN consent_version VARCHAR(50) NULL',
        'created_by' => 'ALTER TABLE users ADD COLUMN created_by INT UNSIGNED NULL',
    ];
    foreach ($columns as $name => $ddl) {
        $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
                             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
        $st->execute([$name]);
        if (!(int)$st->fetchColumn()) $pdo->exec($ddl);
    }

    $index = $pdo->query("SELECT COUNT(*) FROM information_schema.STATISTICS
                          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_users_legacy_id'")->fetchColumn();
    if (!(int)$index) $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uq_users_legacy_id (legacy_id)');

    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_log (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        actor_user_id INT UNSIGNED NULL,
        actor VARCHAR(120) NOT NULL,
        target_legacy_id VARCHAR(100) NULL,
        action VARCHAR(50) NOT NULL,
        old_value LONGTEXT NULL,
        new_value LONGTEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_audit_target (target_legacy_id),
        KEY idx_audit_action (action),
        KEY idx_audit_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sk_roles (
        role_code VARCHAR(100) NOT NULL PRIMARY KEY,
        kk_id VARCHAR(100) NOT NULL,
        position VARCHAR(60) NOT NULL,
        status VARCHAR(20) NOT NULL,
        basis VARCHAR(20) NULL,
        election_year VARCHAR(4) NULL,
        date_elected DATE NULL,
        term_start DATE NULL,
        term_end DATE NULL,
        role_data LONGTEXT NOT NULL,
        updated_by VARCHAR(50) NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_sk_roles_member (kk_id),
        KEY idx_sk_roles_status_term (status, term_start, term_end)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function member_account_roles_from_json($pdo) {
    $raw = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'skRoles'")->fetchColumn();
    if ($raw === false) throw new RuntimeException('The skRoles source row is missing.');
    $roles = json_decode((string)$raw);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($roles)) {
        throw new RuntimeException('The skRoles source is not a valid JSON array.');
    }
    return $roles;
}

function member_account_role_columns($role) {
    if (!($role instanceof stdClass) || !isset($role->id, $role->kkId, $role->position, $role->status)
        || !is_string($role->id) || $role->id === ''
        || !is_string($role->kkId) || $role->kkId === ''
        || !is_string($role->position) || !is_string($role->status)) {
        throw new RuntimeException('A role is missing required identifiers or state.');
    }
    $values = [
        'role_code' => $role->id,
        'kk_id' => $role->kkId,
        'position' => $role->position,
        'status' => $role->status,
        'basis' => isset($role->basis) ? $role->basis : null,
        'election_year' => isset($role->electionYear) ? (string)$role->electionYear : null,
        'date_elected' => isset($role->dateElected) && $role->dateElected !== '' ? $role->dateElected : null,
        'term_start' => isset($role->termStart) && $role->termStart !== '' ? $role->termStart : null,
        'term_end' => isset($role->termEnd) && $role->termEnd !== '' ? $role->termEnd : null,
        'role_data' => json_encode($role, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR),
    ];
    foreach (['date_elected', 'term_start', 'term_end'] as $field) {
        if ($values[$field] !== null) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$values[$field]);
            if (!$date || $date->format('Y-m-d') !== $values[$field]) {
                throw new RuntimeException('A role date is invalid.');
            }
        }
    }
    if ($values['basis'] !== null && !in_array($values['basis'], ['elected', 'appointed'], true)) {
        throw new RuntimeException('A role basis is invalid.');
    }
    return $values;
}

function member_account_verify_roles($pdo) {
    $source = member_account_roles_from_json($pdo);
    $rows = $pdo->query('SELECT role_code, role_data FROM sk_roles ORDER BY role_code')->fetchAll(PDO::FETCH_ASSOC);
    $rebuilt = [];
    foreach ($rows as $row) {
        $role = json_decode($row['role_data']);
        if (json_last_error() !== JSON_ERROR_NONE || !($role instanceof stdClass)) {
            throw new RuntimeException('A role table payload is invalid.');
        }
        $rebuilt[$row['role_code']] = $role;
    }
    $expected = [];
    foreach ($source as $role) {
        if (!($role instanceof stdClass) || !isset($role->id) || !is_string($role->id)) {
            throw new RuntimeException('A source role is missing its id.');
        }
        if (isset($expected[$role->id])) throw new RuntimeException('The source contains duplicate role ids.');
        $expected[$role->id] = $role;
    }
    $differences = 0;
    foreach ($expected as $id => $role) {
        if (!isset($rebuilt[$id]) || json_encode(kk_role_canonicalize($role), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) !==
            json_encode(kk_role_canonicalize($rebuilt[$id]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION)) {
            $differences++;
        }
    }
    foreach ($rebuilt as $id => $_) if (!isset($expected[$id])) $differences++;
    return [
        'roles_in_json' => count($source),
        'rows_in_sk_roles' => count($rows),
        'differences' => $differences,
        'result' => $differences === 0 ? 'MATCH' : 'DIFFERENCES FOUND',
    ];
}

function kk_role_canonicalize($value) {
    if ($value instanceof stdClass) {
        $properties = get_object_vars($value);
        ksort($properties, SORT_STRING);
        $canonical = new stdClass();
        foreach ($properties as $key => $child) $canonical->$key = kk_role_canonicalize($child);
        return $canonical;
    }
    if (is_array($value)) {
        foreach ($value as $index => $child) $value[$index] = kk_role_canonicalize($child);
    }
    return $value;
}

function member_account_migrate_roles($pdo) {
    $source = member_account_roles_from_json($pdo);
    $seen = [];
    $validated = [];
    foreach ($source as $role) {
        $values = member_account_role_columns($role);
        if (isset($seen[$values['role_code']])) throw new RuntimeException('The source contains duplicate role ids.');
        $seen[$values['role_code']] = true;
        $validated[] = $values;
    }

    $pdo->beginTransaction();
    try {
        $write = $pdo->prepare("INSERT INTO sk_roles
            (role_code, kk_id, position, status, basis, election_year, date_elected, term_start, term_end, role_data, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'migration')
            ON DUPLICATE KEY UPDATE kk_id=VALUES(kk_id), position=VALUES(position), status=VALUES(status),
                basis=VALUES(basis), election_year=VALUES(election_year), date_elected=VALUES(date_elected),
                term_start=VALUES(term_start), term_end=VALUES(term_end), role_data=VALUES(role_data)");
        foreach ($validated as $values) {
            $write->execute([
                $values['role_code'], $values['kk_id'], $values['position'], $values['status'],
                $values['basis'], $values['election_year'], $values['date_elected'],
                $values['term_start'], $values['term_end'], $values['role_data'],
            ]);
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $ex;
    }
    return ['roles_in_json' => count($source), 'migrated' => count($validated), 'source_blob_modified' => false];
}

if (!member_account_migration_authorized()) {
    member_account_emit(['error' => 'Forbidden.'], 403);
    exit;
}

try {
    $pdo = db();
    member_account_ensure_schema($pdo);
    $verifyOnly = PHP_SAPI === 'cli' && in_array('--verify', $argv, true)
        || PHP_SAPI !== 'cli' && isset($_GET['verify']);
    if ($verifyOnly) {
        $result = member_account_verify_roles($pdo);
        member_account_emit($result);
        if ($result['differences'] !== 0) exit(2);
    } else {
        $result = member_account_migrate_roles($pdo);
        member_account_emit($result);
    }
} catch (Throwable $ex) {
    error_log('Member account / SK role migration failed: ' . $ex->getMessage());
    member_account_emit(['error' => $ex->getMessage()], 500);
}

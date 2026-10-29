<?php
require_once __DIR__ . '/config.php';

const KK_BACKUP_FORMAT = 'kk-youth-profiling-backup';
const KK_BACKUP_VERSION = 1;
const KK_BACKUP_MAX_BYTES = 52428800;

function kk_backup_authorized() {
    $user = current_user();
    return $user
        && empty($user['must_change_password'])
        && in_array($user['role'] ?? '', ['SK Chairman', 'SK Secretary'], true);
}

function kk_backup_ensure_table($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS kk_restore_backups (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        actor_user_id INT UNSIGNED NULL,
        actor_username VARCHAR(120) NOT NULL,
        backup_json LONGTEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_kk_restore_backups_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function kk_backup_operational_tables() {
    $tables = [];
    if (USE_MEMBERS_TABLE) $tables[] = 'members';
    if (USE_SK_ROLES_TABLE) $tables[] = 'sk_roles';
    return $tables;
}

function kk_backup_table_columns($pdo, $table) {
    if (!in_array($table, ['members', 'sk_roles'], true)) {
        throw new RuntimeException('The backup contains an unsupported table.');
    }
    $columns = $pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
    if (!$columns) throw new RuntimeException('The ' . $table . ' table is unavailable.');
    return array_map(function ($column) { return (string)$column['Field']; }, $columns);
}

function kk_backup_snapshot($pdo, $lock = false) {
    $tables = [];
    foreach (kk_backup_operational_tables() as $table) {
        $tables[$table] = $pdo->query('SELECT * FROM `' . $table . '`' . ($lock ? ' FOR UPDATE' : ''))
            ->fetchAll(PDO::FETCH_ASSOC);
    }
    $rows = $pdo->query('SELECT data_key, data_value, updated_by FROM app_data ORDER BY data_key' . ($lock ? ' FOR UPDATE' : ''))
        ->fetchAll(PDO::FETCH_ASSOC);
    return [
        'format' => KK_BACKUP_FORMAT,
        'version' => KK_BACKUP_VERSION,
        'createdAt' => gmdate('Y-m-d\TH:i:s\Z'),
        'sources' => [
            'membersTable' => (bool)USE_MEMBERS_TABLE,
            'rolesTable' => (bool)USE_SK_ROLES_TABLE,
        ],
        'appData' => $rows,
        'tables' => $tables,
    ];
}

function kk_backup_encode($snapshot) {
    return json_encode(
        $snapshot,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
    );
}

function kk_backup_validate($snapshot, $pdo) {
    if (!is_array($snapshot)
        || ($snapshot['format'] ?? null) !== KK_BACKUP_FORMAT
        || ($snapshot['version'] ?? null) !== KK_BACKUP_VERSION
        || !is_string($snapshot['createdAt'] ?? null)
        || !is_array($snapshot['sources'] ?? null)
        || !is_array($snapshot['appData'] ?? null)
        || !is_array($snapshot['tables'] ?? null)) {
        throw new RuntimeException('This file is not a supported KK system backup.');
    }
    $snapshotFields = array_keys($snapshot);
    sort($snapshotFields);
    $expectedFields = ['appData', 'createdAt', 'format', 'sources', 'tables', 'version'];
    sort($expectedFields);
    if ($snapshotFields !== $expectedFields) {
        throw new RuntimeException('The backup contains unsupported data fields.');
    }

    $sources = $snapshot['sources'];
    $sourceFields = array_keys($sources);
    sort($sourceFields);
    if ($sourceFields !== ['membersTable', 'rolesTable']) {
        throw new RuntimeException('The backup storage metadata is invalid.');
    }
    if (($sources['membersTable'] ?? null) !== (bool)USE_MEMBERS_TABLE
        || ($sources['rolesTable'] ?? null) !== (bool)USE_SK_ROLES_TABLE) {
        throw new RuntimeException('This backup was created with a different data-storage configuration.');
    }

    $expectedTables = kk_backup_operational_tables();
    $providedTables = array_keys($snapshot['tables']);
    sort($expectedTables);
    sort($providedTables);
    if ($expectedTables !== $providedTables) {
        throw new RuntimeException('The backup does not contain the expected operational data tables.');
    }

    $seenKeys = [];
    foreach ($snapshot['appData'] as $row) {
        $rowFields = is_array($row) ? array_keys($row) : [];
        sort($rowFields);
        if ($rowFields !== ['data_key', 'data_value', 'updated_by']) {
            throw new RuntimeException('The backup contains unsupported application data fields.');
        }
        if (!is_array($row)
            || !is_string($row['data_key'] ?? null)
            || !preg_match('/^[A-Za-z0-9_]{1,100}$/', $row['data_key'])
            || !is_string($row['data_value'] ?? null)
            || !array_key_exists('updated_by', $row)
            || ($row['updated_by'] !== null && !is_string($row['updated_by']))) {
            throw new RuntimeException('The backup contains an invalid application data record.');
        }
        if (isset($seenKeys[$row['data_key']])) {
            throw new RuntimeException('The backup contains duplicate application data records.');
        }
        json_decode($row['data_value']);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('The backup contains invalid JSON application data.');
        }
        $seenKeys[$row['data_key']] = true;
    }

    foreach ($expectedTables as $table) {
        if (!is_array($snapshot['tables'][$table])) {
            throw new RuntimeException('The backup contains invalid ' . $table . ' records.');
        }
        $columns = kk_backup_table_columns($pdo, $table);
        foreach ($snapshot['tables'][$table] as $row) {
            if (!is_array($row)) throw new RuntimeException('The backup contains an invalid ' . $table . ' record.');
            $rowColumns = array_keys($row);
            sort($columns);
            sort($rowColumns);
            if ($columns !== $rowColumns) {
                throw new RuntimeException('The backup ' . $table . ' table structure does not match this system.');
            }
            foreach ($row as $value) {
                if ($value !== null && !is_scalar($value)) {
                    throw new RuntimeException('The backup contains an invalid ' . $table . ' field value.');
                }
            }
        }
    }
}

function kk_backup_clear_table($pdo, $table) {
    if (!in_array($table, ['members', 'sk_roles'], true)) {
        throw new RuntimeException('The restore contains an unsupported table.');
    }
    $pdo->exec('DELETE FROM `' . $table . '`');
}

function kk_backup_insert_table_rows($pdo, $table, $rows) {
    if (!in_array($table, ['members', 'sk_roles'], true)) {
        throw new RuntimeException('The restore contains an unsupported table.');
    }
    $columns = kk_backup_table_columns($pdo, $table);
    $quotedColumns = array_map(function ($column) {
        return '`' . str_replace('`', '``', $column) . '`';
    }, $columns);
    $insert = $pdo->prepare(
        'INSERT INTO `' . $table . '` (' . implode(', ', $quotedColumns) . ') VALUES (' .
        implode(', ', array_fill(0, count($columns), '?')) . ')'
    );

    foreach ($rows as $row) {
        $values = [];
        foreach ($columns as $column) $values[] = $row[$column];
        $insert->execute($values);
    }
}

function kk_backup_replace_app_data($pdo, $rows) {
    $pdo->exec('DELETE FROM app_data');
    $insert = $pdo->prepare('INSERT INTO app_data (data_key, data_value, updated_by) VALUES (?, ?, ?)');
    foreach ($rows as $row) {
        $insert->execute([$row['data_key'], $row['data_value'], $row['updated_by']]);
    }
}

function kk_backup_latest_safety($pdo) {
    kk_backup_ensure_table($pdo);
    return $pdo->query('SELECT id, created_at FROM kk_restore_backups ORDER BY id DESC LIMIT 1')
        ->fetch(PDO::FETCH_ASSOC);
}

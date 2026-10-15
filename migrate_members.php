<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/members_table.php';

function kk_migration_authorize() {
    if (kk_members_is_authorized_operator()) return;
    http_response_code(403);
    exit('Forbidden: use an authorized SK officer account or run locally.');
}

function kk_migration_emit($result, $status = 200) {
    if (PHP_SAPI === 'cli') {
        fwrite($status >= 400 ? STDERR : STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        if ($status >= 400) exit(1);
        return;
    }
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>KK member migration</title><pre>';
    echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
    echo '</pre>';
}

function kk_migration_verify($pdo) {
    $blob = $pdo->query("SELECT data_value FROM app_data WHERE data_key='kkMembers'")->fetchColumn();
    if ($blob === false) throw new RuntimeException('The kkMembers source row is missing.');
    $original = json_decode((string)$blob);
    if (json_last_error() !== JSON_ERROR_NONE || !kk_members_is_list($original)) {
        throw new RuntimeException('The kkMembers source is not a valid JSON array.');
    }
    $rebuilt = kk_members_from_table($pdo);
    $comparison = kk_members_compare($original, $rebuilt);
    return [
        'total_in_json' => count($original),
        'rows_in_members' => count($rebuilt),
        'field_differences' => $comparison['count'],
        'result' => $comparison['count'] === 0 ? 'MATCH' : 'DIFFERENCES FOUND',
        'details' => $comparison['details'],
    ];
}

function kk_migration_run($pdo) {
    $backupTable = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='app_data_backup'")->fetchColumn();
    if ((int)$backupTable !== 1) throw new RuntimeException('app_data_backup is missing; refusing to migrate.');

    $pdo->beginTransaction();
    try {
        $blob = $pdo->query("SELECT data_value FROM app_data WHERE data_key='kkMembers' FOR UPDATE")->fetchColumn();
        if ($blob === false) throw new RuntimeException('The kkMembers source row is missing.');
        $backup = $pdo->query("SELECT data_value FROM app_data_backup WHERE data_key='kkMembers'")->fetchColumn();
        if ($backup === false || !hash_equals(hash('sha256', (string)$backup), hash('sha256', (string)$blob))) {
            throw new RuntimeException('The live kkMembers row differs from the immutable backup; migration stopped.');
        }

        $members = json_decode((string)$blob);
        if (json_last_error() !== JSON_ERROR_NONE || !kk_members_is_list($members)) {
            throw new RuntimeException('The kkMembers source is not a valid JSON array.');
        }

        $candidateRows = [];
        $skipped = [];
        $seenIds = [];
        $seenNumbers = [];
        foreach ($members as $index => $member) {
            try {
                $values = kk_member_to_columns($member);
                $legacyId = $values['legacy_id'];
                $memberNo = $values['member_no'];
                if (isset($seenIds[$legacyId])) {
                    $skipped[$index] = 'duplicate legacy id';
                    $skipped[$seenIds[$legacyId]] = 'duplicate legacy id';
                    continue;
                }
                if (isset($seenNumbers[$memberNo])) {
                    $skipped[$index] = 'duplicate member number';
                    $skipped[$seenNumbers[$memberNo]] = 'duplicate member number';
                    continue;
                }
                $seenIds[$legacyId] = $index;
                $seenNumbers[$memberNo] = $index;
                $candidateRows[$index] = $values;
            } catch (Throwable $ex) {
                $skipped[$index] = $ex->getMessage();
            }
        }

        foreach ($skipped as $index => $_) unset($candidateRows[$index]);

        $inserted = 0;
        $updated = 0;
        $unchanged = 0;
        $columnMap = kk_member_column_map();
        $dbColumns = array_merge(array_values($columnMap), ['extra_json']);
        $dbColumns = array_values(array_unique($dbColumns));
        $insertColumns = array_merge($dbColumns, ['updated_by']);
        $insertSql = 'INSERT INTO members (' . implode(', ', $insertColumns) . ')
                      VALUES (' . implode(', ', array_fill(0, count($insertColumns), '?')) . ')';
        $insert = $pdo->prepare($insertSql);

        $updateColumns = array_values(array_diff($dbColumns, ['legacy_id']));
        $updateSql = 'UPDATE members SET ' . implode(' = ?, ', $updateColumns) . ' = ?, updated_by = ? WHERE legacy_id = ?';
        $update = $pdo->prepare($updateSql);
        $find = $pdo->prepare('SELECT * FROM members WHERE legacy_id = ?');

        foreach ($candidateRows as $index => $values) {
            $find->execute([$values['legacy_id']]);
            $existing = $find->fetch(PDO::FETCH_ASSOC);
            $memberObject = $members[$index];
            if ($existing && kk_members_json_equal($memberObject, kk_member_row_to_object($existing))) {
                $unchanged++;
                continue;
            }
            if ($existing) {
                $params = [];
                foreach ($updateColumns as $column) $params[] = $values[$column] ?? null;
                $params[] = 'migration';
                $params[] = $values['legacy_id'];
                $update->execute($params);
                $updated++;
            } else {
                $params = [];
                foreach ($insertColumns as $column) {
                    $params[] = $column === 'updated_by' ? 'migration' : ($values[$column] ?? null);
                }
                $insert->execute($params);
                $inserted++;
            }
        }
        $pdo->commit();

        $skipDetails = [];
        foreach ($skipped as $index => $reason) {
            $skipDetails[] = ['index' => $index, 'reason' => $reason];
        }
        if ($unchanged) $skipDetails[] = ['reason' => 'already current', 'count' => $unchanged];
        return [
            'total_in_json' => count($members),
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => count($skipped) + $unchanged,
            'skip_details' => $skipDetails,
            'original_blob_modified' => false,
        ];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $ex;
    }
}

kk_migration_authorize();

if (PHP_SAPI === 'cli') {
    $mode = in_array('--verify', $argv, true) ? 'verify' : 'run';
} elseif (isset($_GET['verify'])) {
    $mode = 'verify';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf'] ?? '');
    if (empty($_SESSION['members_migration_csrf']) || !hash_equals($_SESSION['members_migration_csrf'], $token)) {
        kk_migration_emit(['error' => 'Invalid or expired migration form token.'], 403);
        exit;
    }
    $mode = 'run';
} else {
    if (empty($_SESSION['members_migration_csrf'])) {
        $_SESSION['members_migration_csrf'] = bin2hex(random_bytes(32));
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>KK member migration</title>';
    echo '<p>This local/admin-only tool migrates kkMembers into the members table. The original blob is preserved.</p>';
    echo '<form method="post"><input type="hidden" name="csrf" value="' .
        htmlspecialchars($_SESSION['members_migration_csrf'], ENT_QUOTES, 'UTF-8') .
        '"><button type="submit">Run / resume migration</button></form>';
    echo '<p><a href="?verify=1">Run read-only JSON verification</a></p>';
    exit;
}

try {
    $result = $mode === 'verify' ? kk_migration_verify(db()) : kk_migration_run(db());
    kk_migration_emit($result);
    if (PHP_SAPI === 'cli' && $mode === 'verify' && $result['field_differences'] !== 0) exit(2);
} catch (Throwable $ex) {
    kk_migration_emit(['error' => $ex->getMessage()], 500);
}

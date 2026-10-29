<?php
require_once __DIR__ . '/backup_restore.php';

header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function kk_backup_json_response($result, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function kk_backup_download($json, $filename) {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($json));
    echo $json;
    exit;
}

if (!kk_backup_authorized()) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        http_response_code(403);
        exit('Forbidden.');
    }
    kk_backup_json_response(['ok' => false, 'error' => 'Only the SK Chairman or SK Secretary can manage backups.'], 403);
}

$pdo = db();
$action = (string)($_GET['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'download') {
    try {
        $pdo->beginTransaction();
        $snapshot = kk_backup_snapshot($pdo);
        $pdo->commit();
        $json = kk_backup_encode($snapshot);
        kk_backup_download($json, 'kk-system-backup-' . gmdate('Ymd-His') . '.json');
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('KK operational backup failed: ' . $ex->getMessage());
        http_response_code(500);
        exit('Could not create the backup. Check the server error log.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'download-safety') {
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400);
        exit('Invalid safety backup id.');
    }
    try {
        kk_backup_ensure_table($pdo);
        $st = $pdo->prepare('SELECT backup_json FROM kk_restore_backups WHERE id = ?');
        $st->execute([$id]);
        $json = $st->fetchColumn();
        if ($json === false) {
            http_response_code(404);
            exit('Safety backup not found.');
        }
        kk_backup_download((string)$json, 'kk-pre-restore-safety-backup-' . $id . '.json');
    } catch (Throwable $ex) {
        error_log('KK safety backup download failed: ' . $ex->getMessage());
        http_response_code(500);
        exit('Could not download the safety backup.');
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $action !== 'restore') {
    kk_backup_json_response(['ok' => false, 'error' => 'Unsupported backup operation.'], 405);
}

$token = (string)($_POST['csrf'] ?? '');
if (empty($_SESSION['backup_restore_csrf']) || !hash_equals($_SESSION['backup_restore_csrf'], $token)) {
    kk_backup_json_response(['ok' => false, 'error' => 'The restore form expired. Reload Settings and try again.'], 403);
}

$upload = $_FILES['backup_file'] ?? null;
if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    kk_backup_json_response(['ok' => false, 'error' => 'Choose a valid backup file to restore.'], 400);
}
if (($upload['size'] ?? 0) < 1 || $upload['size'] > KK_BACKUP_MAX_BYTES || !is_uploaded_file($upload['tmp_name'] ?? '')) {
    kk_backup_json_response(['ok' => false, 'error' => 'Backup files must be valid JSON and no larger than 50 MB.'], 400);
}

try {
    $contents = file_get_contents($upload['tmp_name']);
    if ($contents === false) throw new RuntimeException('Could not read the uploaded backup file.');
    $snapshot = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    kk_backup_validate($snapshot, $pdo);

    kk_backup_ensure_table($pdo);
    $actor = current_user();
    $pdo->beginTransaction();
    $safetySnapshot = kk_backup_snapshot($pdo, true);
    $safetyJson = kk_backup_encode($safetySnapshot);
    $safety = $pdo->prepare(
        'INSERT INTO kk_restore_backups (actor_user_id, actor_username, backup_json) VALUES (?, ?, ?)'
    );
    $safety->execute([$actor['id'], $actor['username'], $safetyJson]);
    $safetyId = (int)$pdo->lastInsertId();

    foreach (array_reverse(kk_backup_operational_tables()) as $table) kk_backup_clear_table($pdo, $table);
    kk_backup_replace_app_data($pdo, $snapshot['appData']);
    foreach (kk_backup_operational_tables() as $table) {
        kk_backup_insert_table_rows($pdo, $table, $snapshot['tables'][$table]);
    }
    $pdo->commit();

    kk_backup_json_response([
        'ok' => true,
        'message' => 'Operational records were restored. User accounts and server configuration were not changed.',
        'safetyBackupId' => $safetyId,
    ]);
} catch (JsonException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    kk_backup_json_response(['ok' => false, 'error' => 'The uploaded file is not valid JSON.'], 400);
} catch (RuntimeException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    kk_backup_json_response(['ok' => false, 'error' => $ex->getMessage()], 400);
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('KK operational restore failed: ' . $ex->getMessage());
    kk_backup_json_response(['ok' => false, 'error' => 'Restore failed. No changes were applied. Check the server error log.'], 500);
}

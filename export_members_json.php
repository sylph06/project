<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/members_table.php';

if (!kk_members_is_authorized_operator()) {
    http_response_code(403);
    exit('Forbidden: an authorized SK officer or local access is required.');
}

try {
    $json = json_encode(
        kk_members_from_table(db()),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
    );
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="kkMembers-export-' . gmdate('Ymd-His') . '.json"');
    echo $json;
} catch (Throwable $ex) {
    error_log('KK member JSON export failed: ' . $ex->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Could not export members from the table.';
}

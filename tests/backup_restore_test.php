<?php
require_once __DIR__ . '/../backup_restore.php';

$failures = [];
$assert = function ($condition, $message) use (&$failures) {
    if (!$condition) $failures[] = $message;
};
$base = [
    'format' => KK_BACKUP_FORMAT,
    'version' => KK_BACKUP_VERSION,
    'createdAt' => '2026-01-01T00:00:00Z',
    'sources' => [
        'membersTable' => (bool)USE_MEMBERS_TABLE,
        'rolesTable' => (bool)USE_SK_ROLES_TABLE,
    ],
    'appData' => [
        ['data_key' => 'skAnnouncements', 'data_value' => '[]', 'updated_by' => 'tester'],
    ],
    'tables' => [],
];

try {
    kk_backup_validate($base, null);
    $assert(true, '');
} catch (Throwable $ex) {
    $assert(false, 'A valid application-data backup must be accepted: ' . $ex->getMessage());
}

$invalidVersion = $base;
$invalidVersion['version'] = KK_BACKUP_VERSION + 1;
try {
    kk_backup_validate($invalidVersion, null);
    $assert(false, 'Unsupported backup versions must be rejected.');
} catch (RuntimeException $ex) {
    $assert(true, '');
}

$invalidJson = $base;
$invalidJson['appData'][0]['data_value'] = '{invalid';
try {
    kk_backup_validate($invalidJson, null);
    $assert(false, 'Invalid stored JSON must be rejected.');
} catch (RuntimeException $ex) {
    $assert(true, '');
}

$duplicateKeys = $base;
$duplicateKeys['appData'][] = $duplicateKeys['appData'][0];
try {
    kk_backup_validate($duplicateKeys, null);
    $assert(false, 'Duplicate application data keys must be rejected.');
} catch (RuntimeException $ex) {
    $assert(true, '');
}

$accountPayload = $base;
$accountPayload['users'] = [['username' => 'unexpected']];
try {
    kk_backup_validate($accountPayload, null);
    $assert(false, 'Account data must never be accepted in a restore file.');
} catch (RuntimeException $ex) {
    $assert(true, '');
}

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "Backup validation tests passed.\n");

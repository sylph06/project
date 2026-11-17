<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/role_store.php';
require_once __DIR__ . '/members_table.php';
require_once __DIR__ . '/private_files.php';
open_to_everyone();

$type = (string)($_GET['type'] ?? '');
$id = (string)($_GET['id'] ?? '');
if (!in_array($type, ['photo', 'saln'], true) || !preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp|pdf)$/', $id)) {
    http_response_code(404);
    exit('File not found.');
}

try {
    [, $profiles] = official_profiles_read(db());
    $currentRoleIds = [];
    $today = date('Y-m-d');
    foreach (sk_role_rows(db()) as $role) {
        if (($role['status'] ?? '') !== 'Active'
            || (!empty($role['termStart']) && $role['termStart'] > $today)
            || (!empty($role['termEnd']) && $role['termEnd'] < $today)) continue;
        $member = sk_member_for_role_change(db(), (string)($role['kkId'] ?? ''));
        if ($member && ($member['status'] ?? 'Active') === 'Active') $currentRoleIds[(string)$role['id']] = true;
    }

    $allowed = false;
    foreach ($profiles as $roleId => $profile) {
        if (!isset($currentRoleIds[(string)$roleId]) || !is_array($profile)) continue;
        $fileId = $type === 'photo'
            ? (string)($profile['photoId'] ?? '')
            : (string)(is_array($profile['saln'] ?? null) ? ($profile['saln']['publicId'] ?? '') : '');
        if ($fileId !== '' && hash_equals($fileId, $id)) {
            $allowed = true;
            break;
        }
    }
    if (!$allowed) {
        http_response_code(404);
        exit('File not found.');
    }
    $bucket = $type === 'photo' ? 'official-photos' : 'saln-public';
    private_file_send(private_file_path($bucket, $id), $type === 'saln' ? 'public-saln.' . pathinfo($id, PATHINFO_EXTENSION) : 'official-photo.' . pathinfo($id, PATHINFO_EXTENSION), $type === 'saln' && pathinfo($id, PATHINFO_EXTENSION) === 'pdf');
} catch (Throwable $ex) {
    error_log('Public official file could not be served: ' . $ex->getMessage());
    http_response_code(500);
    exit('File unavailable.');
}

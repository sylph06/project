<?php

function private_files_root() {
    $configured = getenv('KK_PRIVATE_UPLOAD_DIR');
    $root = $configured !== false && trim($configured) !== ''
        ? trim($configured)
        : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'kk-private-files';
    if (!is_dir($root) && !mkdir($root, 0700, true) && !is_dir($root)) {
        throw new RuntimeException('Private upload storage could not be created.');
    }
    $realRoot = realpath($root);
    if ($realRoot === false) throw new RuntimeException('Private upload storage is unavailable.');
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    if ($documentRoot !== false) {
        $prefix = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $documentRoot), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $candidate = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $realRoot), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (stripos($candidate, $prefix) === 0) {
            throw new RuntimeException('Private upload storage must be outside the public web directory.');
        }
    }
    return $realRoot;
}

function private_file_path($bucket, $id) {
    if (!in_array($bucket, ['receipts', 'saln-original', 'saln-public', 'official-photos', 'member-documents'], true)
        || !is_string($id) || !preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp|pdf)$/', $id)) {
        return null;
    }
    $root = private_files_root();
    $directory = $root . DIRECTORY_SEPARATOR . $bucket;
    if (!is_dir($directory)) return null;
    $path = $directory . DIRECTORY_SEPARATOR . $id;
    $realPath = realpath($path);
    $realDirectory = realpath($directory);
    if ($realPath === false || $realDirectory === false
        || strpos($realPath, rtrim($realDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) !== 0
        || !is_file($realPath)) {
        return null;
    }
    return $realPath;
}

function private_file_store_upload($upload, $bucket, $allowedTypes, $maxBytes) {
    if (!is_array($upload) || !isset($upload['error'], $upload['tmp_name'], $upload['size'])) {
        throw new RuntimeException('Choose a file to upload.');
    }
    if ((int)$upload['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException((int)$upload['error'] === UPLOAD_ERR_INI_SIZE || (int)$upload['error'] === UPLOAD_ERR_FORM_SIZE
            ? 'The file exceeds the upload size limit.'
            : 'The file upload did not complete. Try again.');
    }
    if ((int)$upload['size'] < 1 || (int)$upload['size'] > $maxBytes || !is_uploaded_file($upload['tmp_name'])) {
        throw new RuntimeException('The uploaded file is empty, too large, or invalid.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($upload['tmp_name']);
    $types = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
    if (!isset($types[$mime]) || !in_array($mime, $allowedTypes, true)) {
        throw new RuntimeException('Use a PDF, JPEG, PNG, or WebP file of an allowed type.');
    }
    if (strpos($mime, 'image/') === 0) {
        $image = @getimagesize($upload['tmp_name']);
        if (!$image || empty($image[0]) || empty($image[1]) || (int)$image[0] * (int)$image[1] > 20000000) {
            throw new RuntimeException('The uploaded image is invalid or has excessive dimensions.');
        }
    }
    $directory = private_files_root() . DIRECTORY_SEPARATOR . $bucket;
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Private upload storage is unavailable.');
    }
    $id = bin2hex(random_bytes(16)) . '.' . $types[$mime];
    $path = $directory . DIRECTORY_SEPARATOR . $id;
    if (!move_uploaded_file($upload['tmp_name'], $path)) {
        throw new RuntimeException('The uploaded file could not be stored securely.');
    }
    @chmod($path, 0600);
    return ['id' => $id, 'mime' => $mime];
}

function private_file_delete($bucket, $id) {
    try {
        $path = private_file_path($bucket, $id);
        if ($path !== null && !unlink($path)) {
            error_log('Could not remove private upload: ' . $bucket . '/' . $id);
        }
    } catch (Throwable $ex) {
        error_log('Could not remove private upload: ' . $bucket . '/' . $id . ' (' . $ex->getMessage() . ')');
    }
}

function official_profiles_read($pdo, $forUpdate = false) {
    $sql = "SELECT data_value FROM app_data WHERE data_key = 'skOfficialProfiles'" . ($forUpdate ? ' FOR UPDATE' : '');
    $raw = $pdo->query($sql)->fetchColumn();
    if ($raw === false) return [null, []];
    $profiles = json_decode((string)$raw, true);
    if (!is_array($profiles) || ($profiles !== [] && array_is_list($profiles))) {
        throw new RuntimeException('Official profile data is invalid.');
    }
    return [(string)$raw, $profiles];
}

function official_profiles_write($pdo, $profiles, $username) {
    $value = json_encode($profiles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $pdo->prepare("INSERT INTO app_data (data_key, data_value, updated_by)
        VALUES ('skOfficialProfiles', ?, ?)
        ON DUPLICATE KEY UPDATE data_value = VALUES(data_value), updated_by = VALUES(updated_by)")
        ->execute([$value, $username]);
    return $value;
}

function official_profile_role_for_user($pdo, $user) {
    $legacyId = (string)($user['legacy_id'] ?? '');
    if ($legacyId === '') return null;
    $today = date('Y-m-d');
    foreach (sk_role_rows($pdo) as $role) {
        if ((string)($role['kkId'] ?? '') !== $legacyId
            || ($role['status'] ?? '') !== 'Active'
            || (!empty($role['termStart']) && $role['termStart'] > $today)
            || (!empty($role['termEnd']) && $role['termEnd'] < $today)) continue;
        $member = sk_member_for_role_change($pdo, $legacyId);
        if (!$member || ($member['status'] ?? 'Active') !== 'Active') return null;
        return [$role, $member];
    }
    return null;
}

function private_file_send($path, $downloadName, $attachment = false) {
    if ($path === null || !is_file($path)) {
        http_response_code(404);
        exit('File not found.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($path);
    if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true)) {
        http_response_code(415);
        exit('Unsupported file type.');
    }
    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)$downloadName);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string)filesize($path));
    header('Content-Disposition: ' . ($attachment ? 'attachment' : 'inline') . '; filename="' . ($name ?: 'document') . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store, max-age=0');
    readfile($path);
    exit;
}

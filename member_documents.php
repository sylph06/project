<?php

const MEMBER_DOCUMENT_MAX_FILES = 3;
const MEMBER_DOCUMENT_MAX_BYTES = 8388608;

function member_document_normalize_uploads($files) {
    if ($files === null) return [];
    if (!is_array($files) || !isset($files['name'], $files['tmp_name'], $files['error'], $files['size'])
        || !is_array($files['name']) || !is_array($files['tmp_name'])
        || !is_array($files['error']) || !is_array($files['size'])) {
        throw new InvalidArgumentException('Supporting document upload data is invalid.');
    }

    $count = count($files['name']);
    if ($count > MEMBER_DOCUMENT_MAX_FILES
        || count($files['tmp_name']) !== $count || count($files['error']) !== $count || count($files['size']) !== $count) {
        throw new InvalidArgumentException('Upload no more than three supporting documents at a time.');
    }

    $uploads = [];
    for ($index = 0; $index < $count; $index++) {
        $error = (int)$files['error'][$index];
        if ($error === UPLOAD_ERR_NO_FILE) continue;
        if ($error !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE
                ? 'A supporting document exceeds the upload size limit.'
                : 'A supporting document could not be uploaded. Try again.');
        }

        $size = filter_var($files['size'][$index], FILTER_VALIDATE_INT);
        $name = $files['name'][$index];
        $tmpName = $files['tmp_name'][$index];
        if ($size === false || $size < 1 || $size > MEMBER_DOCUMENT_MAX_BYTES
            || !is_string($name) || !is_string($tmpName) || $tmpName === '') {
            throw new InvalidArgumentException('Supporting documents must be valid files no larger than 8 MB each.');
        }

        $name = basename(str_replace('\\', '/', $name));
        $name = trim(mb_substr(preg_replace('/[\x00-\x1F\x7F]/', '', $name), 0, 120));
        $uploads[] = [
            'name' => $name !== '' ? $name : 'supporting-document',
            'tmp_name' => $tmpName,
            'error' => $error,
            'size' => $size,
        ];
    }

    if (count($uploads) > MEMBER_DOCUMENT_MAX_FILES) {
        throw new InvalidArgumentException('Upload no more than three supporting documents at a time.');
    }
    return $uploads;
}

function member_document_id_is_valid($id) {
    return is_string($id) && preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp|pdf)$/', $id) === 1;
}

function member_profile_photo_is_valid($value) {
    if (!is_string($value)) return false;
    if ($value === '') return true;
    if (strlen($value) > 2097152
        || !preg_match('#^data:image/(jpeg|png|webp);base64,([A-Za-z0-9+/]+={0,2})$#D', $value, $matches)) {
        return false;
    }

    $contents = base64_decode($matches[2], true);
    if ($contents === false) return false;
    $image = @getimagesizefromstring($contents);
    return $image !== false
        && in_array($image['mime'] ?? '', ['image/jpeg', 'image/png', 'image/webp'], true)
        && !empty($image[0]) && !empty($image[1])
        && (int)$image[0] * (int)$image[1] <= 20000000;
}

function member_document_request_has_attachment($requests, $id) {
    if (!is_array($requests) || !member_document_id_is_valid($id)) return false;
    foreach ($requests as $request) {
        if (!is_array($request) || !is_array($request['attachments'] ?? null)) continue;
        foreach ($request['attachments'] as $attachment) {
            if (is_array($attachment) && is_string($attachment['id'] ?? null)
                && hash_equals($attachment['id'], $id)) return true;
        }
    }
    return false;
}

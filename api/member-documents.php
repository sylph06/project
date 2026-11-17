<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../private_files.php';
require_once __DIR__ . '/../member_documents.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit('GET only.');
}

$user = current_user();
if (!$user || !can_write_app_data() || !in_array($user['role'] ?? '', MEMBER_DATA_ADMIN_ROLES, true)) {
    http_response_code(403);
    exit('Only the SK Chairperson and Secretary can access member supporting documents.');
}
if (!empty($user['must_change_password'])) {
    http_response_code(403);
    exit('Change your temporary password before accessing member documents.');
}

$id = $_GET['id'] ?? '';
if (!is_string($id) || !member_document_id_is_valid($id)) {
    http_response_code(404);
    exit('Document not found.');
}

$raw = db()->query("SELECT data_value FROM app_data WHERE data_key = 'kkEditRequests'")->fetchColumn();
$requests = $raw === false ? [] : json_decode((string)$raw, true);
if (!member_document_request_has_attachment($requests, $id)) {
    http_response_code(404);
    exit('Document not found.');
}

$downloadName = 'member-supporting-document.' . pathinfo($id, PATHINFO_EXTENSION);
if (is_array($requests)) {
    foreach ($requests as $request) {
        if (!is_array($request) || !is_array($request['attachments'] ?? null)) continue;
        foreach ($request['attachments'] as $attachment) {
            if (is_array($attachment) && hash_equals((string)($attachment['id'] ?? ''), $id)) {
                $downloadName = (string)($attachment['name'] ?? $downloadName);
                break 2;
            }
        }
    }
}

private_file_send(private_file_path('member-documents', $id), $downloadName, true);

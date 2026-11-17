<?php
require_once __DIR__ . '/../member_documents.php';

$checks = 0;
$assert = function ($condition, $message) use (&$checks) {
    $checks++;
    if (!$condition) throw new RuntimeException($message);
};
$assertThrows = function ($callback, $message) use (&$checks) {
    $checks++;
    try {
        $callback();
    } catch (InvalidArgumentException $exception) {
        return;
    }
    throw new RuntimeException($message);
};

$assert(member_document_normalize_uploads(null) === [], 'No selected files must produce an empty upload list.');
$assert(member_document_normalize_uploads([
    'name' => [''],
    'tmp_name' => [''],
    'error' => [UPLOAD_ERR_NO_FILE],
    'size' => [0],
]) === [], 'Empty file controls must be ignored.');

$uploads = member_document_normalize_uploads([
    'name' => ['C:\\fakepath\\proof.pdf', "receipt\r\n.pdf"],
    'tmp_name' => ['temporary-one', 'temporary-two'],
    'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
    'size' => [1024, 2048],
]);
$assert(count($uploads) === 2, 'Valid multi-file uploads must be normalized.');
$assert($uploads[0]['name'] === 'proof.pdf', 'Client file paths must not be retained.');
$assert($uploads[1]['name'] === 'receipt.pdf', 'Control characters must be removed from display names.');
$assert(member_document_id_is_valid(str_repeat('a', 32) . '.pdf'), 'Generated document ids must be recognized.');
$assert(!member_document_id_is_valid('../' . str_repeat('a', 32) . '.pdf'), 'Path traversal ids must be rejected.');
$assert(member_document_request_has_attachment([
    ['attachments' => [['id' => str_repeat('b', 32) . '.png']]],
], str_repeat('b', 32) . '.png'), 'A document referenced by an edit request must be recognized.');
$assert(!member_document_request_has_attachment([], str_repeat('b', 32) . '.png'), 'Unreferenced documents must not be considered accessible.');
$assert(member_profile_photo_is_valid(''), 'An empty profile photo must remain valid.');
$assert(!member_profile_photo_is_valid('javascript:alert(1)'), 'Non-image profile photo values must be rejected.');

$assertThrows(function () {
    member_document_normalize_uploads([
        'name' => ['1.pdf', '2.pdf', '3.pdf', '4.pdf'],
        'tmp_name' => ['a', 'b', 'c', 'd'],
        'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK],
        'size' => [1, 1, 1, 1],
    ]);
}, 'More than three supporting documents must be rejected.');
$assertThrows(function () {
    member_document_normalize_uploads([
        'name' => ['large.pdf'],
        'tmp_name' => ['temporary'],
        'error' => [UPLOAD_ERR_OK],
        'size' => [MEMBER_DOCUMENT_MAX_BYTES + 1],
    ]);
}, 'Oversized supporting documents must be rejected.');

echo 'All member document checks passed: ' . $checks . PHP_EOL;

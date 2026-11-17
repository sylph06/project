<?php
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../role_store.php';
    require_once __DIR__ . '/../members_table.php';
    require_once __DIR__ . '/../private_files.php';
    $user = current_user();
    if (!$user || !can_write_app_data()) {
        http_response_code(403);
        exit('Only authenticated SK officials can access these files.');
    }
    $pdo = db();
    $action = (string)($_GET['action'] ?? '');
    if ($action === 'receipt_view') {
        $id = (string)($_GET['id'] ?? '');
        $raw = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'skTransparencyExpenses'")->fetchColumn();
        $expenses = $raw === false ? [] : json_decode((string)$raw, true);
        $found = false;
        foreach (is_array($expenses) ? $expenses : [] as $expense) {
            if (is_array($expense) && hash_equals((string)($expense['receiptId'] ?? ''), $id)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            http_response_code(404);
            exit('Receipt not found.');
        }
        sk_audit($pdo, $user, null, 'official_file_viewed', null, [
            'file_type' => 'expense_receipt',
            'file_id' => $id,
        ]);
        private_file_send(private_file_path('receipts', $id), 'expense-receipt.' . pathinfo($id, PATHINFO_EXTENSION), true);
    }
    if ($action === 'saln_original_view') {
        $owner = official_profile_role_for_user($pdo, $user);
        if (!$owner) {
            http_response_code(403);
            exit('Your account must be linked to an active SK official profile.');
        }
        $id = (string)($_GET['id'] ?? '');
        [, $profiles] = official_profiles_read($pdo);
        $profile = $profiles[(string)$owner[0]['id']] ?? [];
        $saln = is_array($profile) && is_array($profile['saln'] ?? null) ? $profile['saln'] : [];
        if ($id === '' || !hash_equals((string)($saln['originalId'] ?? ''), $id)) {
            http_response_code(404);
            exit('Original SALN not found.');
        }
        sk_audit($pdo, $user, null, 'official_file_viewed', null, [
            'file_type' => 'saln_original',
            'file_id' => $id,
        ]);
        private_file_send(private_file_path('saln-original', $id), 'saln-original.' . pathinfo($id, PATHINFO_EXTENSION), true);
    }
    http_response_code(400);
    exit('Unknown official file action.');
}

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../private_files.php';

$action = (string)($_GET['action'] ?? '');
$user = require_officer();
if (!can_write_app_data()) out(['ok' => false, 'error' => 'Only authenticated SK officials can access these files.'], 403);
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['ok' => false, 'error' => 'POST only.'], 405);

if ($action === 'profile_files') {
    $owner = official_profile_role_for_user($pdo, $user);
    if (!$owner) out(['ok' => false, 'error' => 'Your account must be linked to an active SK official profile.'], 403);
    $role = $owner[0];
    $bio = trim((string)($_POST['bio'] ?? ''));
    $honorariumRaw = trim((string)($_POST['monthlyHonorarium'] ?? ''));
    $honorarium = null;
    if (mb_strlen($bio) > 1000) out(['ok' => false, 'error' => 'Keep the public bio under 1,000 characters.'], 400);
    if ($honorariumRaw !== '') {
        if (!is_numeric($honorariumRaw) || !is_finite((float)$honorariumRaw)
            || (float)$honorariumRaw < 0 || (float)$honorariumRaw > 10000000) {
            out(['ok' => false, 'error' => 'Enter a valid monthly SK honorarium amount.'], 400);
        }
        $honorarium = round((float)$honorariumRaw, 2);
    }
    $photoPresent = isset($_FILES['photo']) && (int)($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $originalPresent = isset($_FILES['salnOriginal']) && (int)($_FILES['salnOriginal']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $publicPresent = isset($_FILES['salnPublic']) && (int)($_FILES['salnPublic']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($originalPresent !== $publicPresent) {
        out(['ok' => false, 'error' => 'Upload both the private original and its redacted public SALN copy together.'], 400);
    }
    $salnYear = trim((string)($_POST['salnYear'] ?? ''));
    if ($originalPresent && (!preg_match('/^\d{4}$/', $salnYear) || (int)$salnYear < 1900 || (int)$salnYear > 2100)) {
        out(['ok' => false, 'error' => 'Enter the four-digit SALN year.'], 400);
    }

    $newFiles = [];
    try {
        if ($photoPresent) {
            $newFiles['photo'] = private_file_store_upload(
                $_FILES['photo'], 'official-photos', ['image/jpeg', 'image/png', 'image/webp'], 3 * 1024 * 1024
            );
        }
        if ($originalPresent) {
            $newFiles['salnOriginal'] = private_file_store_upload(
                $_FILES['salnOriginal'], 'saln-original',
                ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], 8 * 1024 * 1024
            );
            $newFiles['salnPublic'] = private_file_store_upload(
                $_FILES['salnPublic'], 'saln-public',
                ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], 8 * 1024 * 1024
            );
        }

        $pdo->beginTransaction();
        [, $profiles] = official_profiles_read($pdo, true);
        $roleId = (string)$role['id'];
        $oldProfile = $profiles[$roleId] ?? [];
        if (!is_array($oldProfile)) throw new RuntimeException('This official profile is invalid.');
        $profile = $oldProfile;
        $profile['bio'] = $bio;
        $profile['monthlyHonorarium'] = $honorarium;
        $removed = [];
        if (isset($newFiles['photo'])) {
            if (!empty($profile['photoId'])) $removed[] = ['official-photos', $profile['photoId']];
            $profile['photoId'] = $newFiles['photo']['id'];
        }
        if (isset($newFiles['salnOriginal'], $newFiles['salnPublic'])) {
            $oldSaln = is_array($profile['saln'] ?? null) ? $profile['saln'] : [];
            if (!empty($oldSaln['originalId'])) $removed[] = ['saln-original', $oldSaln['originalId']];
            if (!empty($oldSaln['publicId'])) $removed[] = ['saln-public', $oldSaln['publicId']];
            $profile['saln'] = [
                'year' => $salnYear,
                'originalId' => $newFiles['salnOriginal']['id'],
                'publicId' => $newFiles['salnPublic']['id'],
            ];
        }
        $profiles[$roleId] = $profile;
        $value = official_profiles_write($pdo, $profiles, $user['username']);
        sk_audit($pdo, $user, (string)$owner[1]['id'], 'official_profile_updated', [
            'bio' => $oldProfile['bio'] ?? '',
            'monthlyHonorarium' => $oldProfile['monthlyHonorarium'] ?? null,
        ], [
            'bio' => $bio,
            'monthlyHonorarium' => $honorarium,
            'photoUpdated' => isset($newFiles['photo']),
            'salnYear' => isset($profile['saln']['year']) ? $profile['saln']['year'] : null,
            'salnUpdated' => isset($newFiles['salnOriginal'], $newFiles['salnPublic']),
        ]);
        $pdo->commit();
        foreach ($removed as [$bucket, $id]) private_file_delete($bucket, (string)$id);
        out(['ok' => true, 'value' => $value]);
    } catch (RuntimeException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        foreach ($newFiles as $key => $file) {
            $bucket = $key === 'photo' ? 'official-photos' : ($key === 'salnOriginal' ? 'saln-original' : 'saln-public');
            private_file_delete($bucket, $file['id']);
        }
        out(['ok' => false, 'error' => $ex->getMessage()], 400);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        foreach ($newFiles as $key => $file) {
            $bucket = $key === 'photo' ? 'official-photos' : ($key === 'salnOriginal' ? 'saln-original' : 'saln-public');
            private_file_delete($bucket, $file['id']);
        }
        error_log('Official profile file update failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not save your profile files.'], 500);
    }
}

if ($action === 'receipt_upload') {
    if (!isset($_POST['expenseIndex']) || !ctype_digit((string)$_POST['expenseIndex'])
        || !isset($_POST['expected']) || !is_string($_POST['expected'])) {
        out(['ok' => false, 'error' => 'Expense receipt details are invalid. Reload Reports and try again.'], 400);
    }
    $newFile = null;
    try {
        $newFile = private_file_store_upload(
            $_FILES['receipt'] ?? null, 'receipts',
            ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], 8 * 1024 * 1024
        );
        $pdo->beginTransaction();
        $lock = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'skTransparencyExpenses' FOR UPDATE");
        $oldRaw = $lock->fetchColumn();
        $oldRaw = $oldRaw === false ? null : (string)$oldRaw;
        $postedExpected = $oldRaw === null && $_POST['expected'] === '' ? null : $_POST['expected'];
        if ($oldRaw === null || $postedExpected !== $oldRaw) {
            throw new DomainException('Expense data changed in another session. Reload Reports and try again.');
        }
        $expenses = json_decode($oldRaw, true);
        $index = (int)$_POST['expenseIndex'];
        if (!is_array($expenses) || !array_is_list($expenses) || !isset($expenses[$index]) || !is_array($expenses[$index])) {
            throw new RuntimeException('The expense record no longer exists.');
        }
        $oldId = (string)($expenses[$index]['receiptId'] ?? '');
        $expenses[$index]['receiptId'] = $newFile['id'];
        $value = json_encode($expenses, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        $pdo->prepare("UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = 'skTransparencyExpenses'")
            ->execute([$value, $user['username']]);
        sk_audit($pdo, $user, null, 'expense_receipt_uploaded', null, [
            'expenseIndex' => $index,
            'replacedReceipt' => $oldId !== '',
        ]);
        $pdo->commit();
        if ($oldId !== '') private_file_delete('receipts', $oldId);
        out(['ok' => true, 'value' => $value, 'receiptId' => $newFile['id']]);
    } catch (DomainException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($newFile) private_file_delete('receipts', $newFile['id']);
        out(['ok' => false, 'error' => $ex->getMessage()], 409);
    } catch (RuntimeException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($newFile) private_file_delete('receipts', $newFile['id']);
        out(['ok' => false, 'error' => $ex->getMessage()], 400);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($newFile) private_file_delete('receipts', $newFile['id']);
        error_log('Expense receipt upload failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not save the receipt.'], 500);
    }
}

out(['ok' => false, 'error' => 'Unknown official file action.'], 400);

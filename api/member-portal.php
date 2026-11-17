<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../member_documents.php';
require_once __DIR__ . '/../kk_id_logic.php';
require_once __DIR__ . '/../kk_id_schema.php';

$action = $_GET['action'] ?? '';
$user = current_user();
if (!$user) out(['ok' => false, 'error' => 'Please log in again.'], 401);
if (!empty($user['must_change_password'])) {
    out(['ok' => false, 'error' => 'Change your temporary password before continuing.'], 403);
}

$isStaff = can_write_app_data();
$ownMemberId = $user['legacy_id'] !== null ? (string)$user['legacy_id'] : '';
$pdo = db();

function kk_id_api_failure($exception, $context, $message) {
    error_log($context . ': ' . $exception->getMessage());
    out(['ok' => false, 'error' => $message], 500);
}

function kk_portal_members($pdo, $lock = false) {
    if (USE_MEMBERS_TABLE) {
        $rows = $pdo->query('SELECT * FROM members' . ($lock ? ' FOR UPDATE' : ''))->fetchAll(PDO::FETCH_ASSOC);
        $members = [];
        foreach ($rows as $row) {
            $members[] = json_decode(
                json_encode(kk_member_row_to_object($row), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                true
            );
        }
        return $members;
    }
    $row = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'kkMembers'" . ($lock ? ' FOR UPDATE' : ''))->fetchColumn();
    if ($row === false) throw new RuntimeException('KK member data is missing.');
    $members = json_decode((string)$row, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($members)) {
        throw new RuntimeException('KK member data is invalid.');
    }
    return $members;
}

function kk_portal_member_index($members, $memberId) {
    foreach ($members as $index => $member) {
        if (is_array($member) && (string)($member['id'] ?? '') === (string)$memberId) return $index;
    }
    return null;
}

function kk_portal_member_is_active($member) {
    return is_array($member) && ($member['status'] ?? 'Active') === 'Active';
}

function kk_portal_authorized_member_id($memberId, $ownMemberId, $isStaff) {
    if (!is_string($memberId) || $memberId === '' || strlen($memberId) > 100) {
        out(['ok' => false, 'error' => 'A valid KK member is required.'], 400);
    }
    if ($isStaff || ($ownMemberId !== '' && hash_equals($ownMemberId, $memberId))) return $memberId;
    out(['ok' => false, 'error' => 'You may only access your own KK member record.'], 403);
}

if ($action === 'member_portal_data') {
    if ($ownMemberId === '') out(['ok' => false, 'error' => 'This account is not linked to a KK member profile.'], 403);
    try {
    $members = kk_portal_members($pdo);
    $index = kk_portal_member_index($members, $ownMemberId);
    if ($index === null) out(['ok' => false, 'error' => 'Your KK member profile could not be found.'], 404);
    $member = $members[$index];
    if (!kk_portal_member_is_active($member)) out(['ok' => false, 'error' => 'This KK member profile is not active.'], 403);

    $read = function ($key) use ($pdo) {
        $statement = $pdo->prepare('SELECT data_value FROM app_data WHERE data_key = ?');
        $statement->execute([$key]);
        $value = $statement->fetchColumn();
        if ($value === false) return [];
        $decoded = json_decode((string)$value, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new RuntimeException($key . ' data is invalid.');
        }
        return $decoded;
    };
    $tracking = kk_id_tracking_records(
        $ownMemberId,
        $read('skAnnouncements'),
        $read('skEventRegistrations'),
        $read('skEventAttendance')
    );
    kk_id_ensure_schema($pdo);
    $prints = $pdo->prepare('SELECT print_method, printed_at FROM kk_member_id_prints
        WHERE member_id = ? ORDER BY printed_at DESC, id DESC LIMIT 20');
    $prints->execute([$ownMemberId]);
    out([
        'ok' => true,
        'member' => [
            'id' => (string)$member['id'],
            'memberNo' => (string)($member['memberNo'] ?? ''),
            'name' => kk_id_member_name($member),
            'firstName' => (string)($member['firstName'] ?? ''),
            'lastName' => (string)($member['lastName'] ?? ''),
            'birthDate' => (string)($member['birthDate'] ?? ''),
            'gender' => (string)($member['gender'] ?? $member['sex'] ?? ''),
            'purok' => (string)($member['purok'] ?? ''),
            'mobile' => (string)($member['mobile'] ?? ''),
            'email' => (string)($member['email'] ?? ''),
            'photo' => member_profile_photo_is_valid($member['photo'] ?? '') ? (string)($member['photo'] ?? '') : '',
            'status' => (string)($member['status'] ?? 'Active'),
            'createdAt' => (string)($member['createdAt'] ?? ''),
        ],
        'tracking' => $tracking,
        'prints' => $prints->fetchAll(PDO::FETCH_ASSOC),
    ]);
    } catch (Throwable $ex) {
        kk_id_api_failure($ex, 'KK member portal data load failed', 'Could not load your member portal.');
    }
}

if ($action === 'member_portal_photo') {
    if ($ownMemberId === '') out(['ok' => false, 'error' => 'This account is not linked to a KK member profile.'], 403);
    $photo = $in['photo'] ?? null;
    if (!member_profile_photo_is_valid($photo) || $photo === '') {
        out(['ok' => false, 'error' => 'Choose a valid JPG, PNG, or WebP profile picture.'], 400);
    }
    try {
        $pdo->beginTransaction();
        if (USE_MEMBERS_TABLE) {
            $find = $pdo->prepare('SELECT * FROM members WHERE legacy_id = ? FOR UPDATE');
            $find->execute([$ownMemberId]);
            $row = $find->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'Your KK member profile could not be found.'], 404);
            }
            $member = json_decode(json_encode(kk_member_row_to_object($row)), true);
            if (!kk_portal_member_is_active($member)) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'This KK member profile is not active.'], 403);
            }
            $oldHasPhoto = !empty($row['photo']);
            $pdo->prepare('UPDATE members SET photo = ?, updated_by = ? WHERE legacy_id = ?')
                ->execute([$photo, $user['username'], $ownMemberId]);
        } else {
            $members = kk_portal_members($pdo, true);
            $index = kk_portal_member_index($members, $ownMemberId);
            if ($index === null) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'Your KK member profile could not be found.'], 404);
            }
            if (!kk_portal_member_is_active($members[$index])) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'This KK member profile is not active.'], 403);
            }
            $oldHasPhoto = !empty($members[$index]['photo']);
            $members[$index]['photo'] = $photo;
            $pdo->prepare('UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = ?')
                ->execute([
                    json_encode($members, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR),
                    $user['username'],
                    'kkMembers',
                ]);
        }
        sk_audit($pdo, $user, $ownMemberId, 'member_profile_photo_changed',
            ['has_photo' => $oldHasPhoto], ['has_photo' => true]);
        $pdo->commit();
        out(['ok' => true, 'photo' => $photo]);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        kk_id_api_failure($ex, 'KK member profile photo update failed', 'Could not update your profile picture.');
    }
}

if ($action === 'kk_id_token') {
    $memberId = kk_portal_authorized_member_id($in['memberId'] ?? null, $ownMemberId, $isStaff);
    try {
        kk_id_ensure_schema($pdo);
        $pdo->beginTransaction();
        $members = kk_portal_members($pdo, true);
        $index = kk_portal_member_index($members, $memberId);
        if ($index === null) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'The KK member profile could not be found.'], 404);
        }
        if (!kk_portal_member_is_active($members[$index])) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'A digital ID is available only for active KK members.'], 403);
        }
        $statement = $pdo->prepare('SELECT token FROM kk_member_id_tokens WHERE member_id = ?');
        $statement->execute([$memberId]);
        $token = $statement->fetchColumn();
        if ($token === false) {
            $token = bin2hex(random_bytes(32));
            $pdo->prepare('INSERT INTO kk_member_id_tokens (member_id, token) VALUES (?, ?)')
                ->execute([$memberId, $token]);
        }
        $pdo->commit();
        out(['ok' => true, 'token' => (string)$token]);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        kk_id_api_failure($ex, 'KK member QR token generation failed', 'Could not create the member QR code.');
    }
}

if ($action === 'kk_id_print') {
    if (!$isStaff) out(['ok' => false, 'error' => 'Only authorized SK staff can print or record KK IDs.'], 403);
    $memberId = kk_portal_authorized_member_id($in['memberId'] ?? null, $ownMemberId, $isStaff);
    $method = $in['method'] ?? null;
    if (!kk_id_print_method_is_valid($method)) {
        out(['ok' => false, 'error' => 'Choose automatic or manual printing.'], 400);
    }
    try {
    kk_id_ensure_schema($pdo);
    $members = kk_portal_members($pdo);
    $index = kk_portal_member_index($members, $memberId);
    if ($index === null) out(['ok' => false, 'error' => 'The KK member profile could not be found.'], 404);
    if (!kk_portal_member_is_active($members[$index])) {
        out(['ok' => false, 'error' => 'An ID can be printed only for an active KK member.'], 403);
    }
    $pdo->prepare('INSERT INTO kk_member_id_prints (member_id, printed_by_user_id, printed_by, print_method)
        VALUES (?, ?, ?, ?)')
        ->execute([$memberId, $user['id'], $user['username'], $method]);
    out(['ok' => true, 'printedAt' => date('Y-m-d H:i:s')]);
    } catch (Throwable $ex) {
        kk_id_api_failure($ex, 'KK ID print record failed', 'Could not record the ID print.');
    }
}

if ($action === 'kk_id_prints') {
    $memberId = kk_portal_authorized_member_id($in['memberId'] ?? null, $ownMemberId, $isStaff);
    try {
    kk_id_ensure_schema($pdo);
    $statement = $pdo->prepare('SELECT print_method, printed_at, printed_by FROM kk_member_id_prints
        WHERE member_id = ? ORDER BY printed_at DESC, id DESC LIMIT 100');
    $statement->execute([$memberId]);
    out(['ok' => true, 'prints' => $statement->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Throwable $ex) {
        kk_id_api_failure($ex, 'KK ID print history load failed', 'Could not load ID print history.');
    }
}

if ($action === 'kk_id_scan') {
    if (!$isStaff) out(['ok' => false, 'error' => 'Only authorized SK staff can scan KK IDs.'], 403);
    try {
    kk_id_ensure_schema($pdo);
    $token = kk_id_token_from_scan($in['value'] ?? null);
    if ($token === null) out(['ok' => false, 'error' => 'The QR code is not a valid KK ID.'], 400);
    $statement = $pdo->prepare('SELECT member_id FROM kk_member_id_tokens WHERE token = ?');
    $statement->execute([$token]);
    $memberId = $statement->fetchColumn();
    if ($memberId === false) out(['ok' => false, 'error' => 'This KK ID could not be verified.'], 404);
    $members = kk_portal_members($pdo);
    $index = kk_portal_member_index($members, (string)$memberId);
    if ($index === null) out(['ok' => false, 'error' => 'This KK ID is no longer linked to a member profile.'], 404);
    $member = $members[$index];
    if (!kk_portal_member_is_active($member)) {
        out(['ok' => false, 'error' => 'This KK member profile is not active.'], 410);
    }
    out([
        'ok' => true,
        'member' => [
            'id' => (string)$member['id'],
            'name' => kk_id_member_name($member),
            'memberNo' => (string)($member['memberNo'] ?? ''),
            'status' => (string)($member['status'] ?? 'Active'),
        ],
    ]);
    } catch (Throwable $ex) {
        kk_id_api_failure($ex, 'KK ID scan failed', 'Could not verify this KK ID.');
    }
}

out(['ok' => false, 'error' => 'Unknown action.'], 400);

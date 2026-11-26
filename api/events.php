<?php
/* =========================================================
   api/events.php — event sign-up, attendance, walk-in
   ========================================================= */
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';

/* ---------- event sign-up: requires an authenticated account ---------- */
if ($action === 'event_register') {
    $authenticatedUser = current_user();
    if ($authenticatedUser && !empty($authenticatedUser['must_change_password'])) {
        out(['ok' => false, 'error' => 'Change your temporary password before continuing.'], 403);
    }

    $eventUser = current_user();
    if (!$eventUser) out(['ok' => false, 'error' => 'Please log in before signing up for an event.'], 401);
    if (!empty($eventUser['must_change_password'])) out(['ok' => false, 'error' => 'Change your temporary password before signing up.'], 403);

    $eventId = (string)($in['eventId'] ?? '');
    $d = $in['data'] ?? null;
    if (!preg_match('/^[A-Za-z0-9_\-]{1,60}$/', $eventId) || !is_array($d)) out(['ok' => false, 'error' => 'Bad form data.'], 400);

    $s = function ($k, $max) use ($d) { $v = $d[$k] ?? ''; return is_string($v) ? mb_substr(trim($v), 0, $max) : ''; };
    $name = $s('name', 100); $mobile = $s('mobile', 30); $email = $s('email', 120);
    $purok = $s('purok', 60); $notes = $s('notes', 300);
    require_once __DIR__ . '/../participation_logic.php';
    $age = participation_signup_age($d['age'] ?? null);
    if ($name === '' || $mobile === '') out(['ok' => false, 'error' => 'Please enter your name and mobile number.'], 400);
    if (!preg_match('/^[0-9+\-\s()]{7,30}$/', $mobile)) out(['ok' => false, 'error' => 'Please enter a valid mobile number.'], 400);
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) out(['ok' => false, 'error' => 'Please enter a valid email address.'], 400);
    if ($age === false) out(['ok' => false, 'error' => 'Please enter a valid age.'], 400);

    enforce_submission_limit('event_register', 15);

    $pdo = db();
    $pdo->beginTransaction();
    $get = function ($k) use ($pdo) {
        $st = $pdo->prepare('SELECT data_value FROM app_data WHERE data_key = ? FOR UPDATE');
        $st->execute([$k]);
        $v = json_decode((string)$st->fetchColumn(), true);
        return is_array($v) ? $v : [];
    };
    $anns = $get('skAnnouncements');
    $ev = null;
    foreach ($anns as $a) { if ((string)($a['id'] ?? '') === $eventId) { $ev = $a; break; } }
    $today = date('Y-m-d');
    $open = $ev && ($ev['publishState'] ?? '') === 'Published' && (empty($ev['publishDate']) || $ev['publishDate'] <= $today);
    if (!$open) { $pdo->rollBack(); out(['ok' => false, 'error' => 'This event is not open for sign-up.'], 404); }
    if (!empty($ev['cancelled'])) { $pdo->rollBack(); out(['ok' => false, 'error' => 'This event was cancelled.'], 400); }
    $evDate = (string)($ev['date'] ?? '');
    $evEnd = $evDate . ' ' . (!empty($ev['endTime']) ? $ev['endTime'] : '23:59') . ':00';
    if ($evDate === '' || date('Y-m-d H:i:s') > $evEnd) { $pdo->rollBack(); out(['ok' => false, 'error' => 'This event has already ended.'], 400); }

    $regs = $get('skEventRegistrations');
    $memberId = $eventUser['legacy_id'] !== null ? (string)$eventUser['legacy_id'] : '';
    foreach ($regs as $r) {
        if (($r['eventId'] ?? '') === $eventId
            && (($memberId !== '' && (string)($r['memberId'] ?? '') === $memberId)
                || (strtolower($r['name'] ?? '') === strtolower($name) && ($r['mobile'] ?? '') === $mobile))) {
            $pdo->rollBack(); out(['ok' => false, 'error' => 'You are already signed up for this event.'], 409);
        }
    }
    $rec = [
        'id' => 'e' . round(microtime(true) * 1000) . substr(bin2hex(random_bytes(2)), 0, 3),
        'eventId' => $eventId, 'eventTitle' => mb_substr((string)($ev['title'] ?? ''), 0, 150),
        'memberId' => $memberId !== '' ? $memberId : null,
        'name' => $name, 'age' => $age, 'purok' => $purok, 'mobile' => $mobile, 'email' => $email, 'notes' => $notes,
        'attendanceStatus' => 'Not recorded',
        'submittedAt' => gmdate('c'),
    ];
    $regs[] = $rec;
    $pdo->prepare('INSERT INTO app_data (data_key, data_value, updated_by) VALUES (?, ?, ?)
                   ON DUPLICATE KEY UPDATE data_value = VALUES(data_value), updated_by = VALUES(updated_by)')
        ->execute(['skEventRegistrations', json_encode($regs, JSON_UNESCAPED_UNICODE), $eventUser['username']]);
    $pdo->commit();
    out(['ok' => true, 'ref' => strtoupper(substr($rec['id'], -6)), 'record' => $rec]);
}

/* ---------- member event interest ---------- */
if ($action === 'event_interest') {
    $memberUser = current_user();
    if (!$memberUser) out(['ok' => false, 'error' => 'Please log in to respond to an event.'], 401);
    if (($memberUser['role'] ?? '') !== 'KK Member' || empty($memberUser['legacy_id'])) {
        out(['ok' => false, 'error' => 'Only linked KK member accounts can respond to events.'], 403);
    }
    if (!empty($memberUser['must_change_password'])) {
        out(['ok' => false, 'error' => 'Change your temporary password before responding.'], 403);
    }

    $eventId = $in['eventId'] ?? null;
    $response = $in['response'] ?? null;
    if (!is_string($eventId) || !preg_match('/^[A-Za-z0-9_-]{1,60}$/', $eventId)
        || !is_string($response) || !in_array($response, ['Interested', 'Not interested'], true)) {
        out(['ok' => false, 'error' => 'Choose a valid event response.'], 400);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $memberId = (string)$memberUser['legacy_id'];
        if (USE_MEMBERS_TABLE) {
            $memberQuery = $pdo->prepare('SELECT status FROM members WHERE legacy_id = ? FOR UPDATE');
            $memberQuery->execute([$memberId]);
            $memberStatus = $memberQuery->fetchColumn();
        } else {
            $memberQuery = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'kkMembers' FOR UPDATE");
            $memberQuery->execute();
            $members = json_decode((string)$memberQuery->fetchColumn(), true);
            if (!is_array($members)) throw new RuntimeException('KK member data is invalid.');
            $memberStatus = false;
            foreach ($members as $member) {
                if (is_array($member) && (string)($member['id'] ?? '') === $memberId) {
                    $memberStatus = $member['status'] ?? 'Active';
                    break;
                }
            }
        }
        if ($memberStatus !== 'Active') {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'Only active KK members can respond to events.'], 403);
        }

        $announcementQuery = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'skAnnouncements' FOR UPDATE");
        $announcementQuery->execute();
        $announcements = json_decode((string)$announcementQuery->fetchColumn(), true);
        if (!is_array($announcements)) throw new RuntimeException('Announcement data is invalid.');
        $announcement = null;
        foreach ($announcements as $record) {
            if (is_array($record) && (string)($record['id'] ?? '') === $eventId) {
                $announcement = $record;
                break;
            }
        }
        $today = date('Y-m-d');
        if (!$announcement
            || ($announcement['publishState'] ?? '') !== 'Published'
            || (!empty($announcement['publishDate']) && $announcement['publishDate'] > $today)) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'This event is not available for responses.'], 404);
        }
        if (!empty($announcement['interestClosed'])) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'Interest responses for this event have been closed by the SK.'], 409);
        }
        $eventDate = (string)($announcement['date'] ?? '');
        $startTime = (string)($announcement['startTime'] ?? $announcement['time'] ?? '');
        $endTime = (string)($announcement['endTime'] ?? '');
        if (!empty($announcement['cancelled'])
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)
            || !checkdate((int)substr($eventDate, 5, 2), (int)substr($eventDate, 8, 2), (int)substr($eventDate, 0, 4))
            || (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $startTime) && $startTime !== '')
            || (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $endTime) && $endTime !== '')) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'This event is not available for responses.'], 400);
        }
        require_once __DIR__ . '/../participation_logic.php';
        if (!participation_event_interest_is_open($announcement)) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'Interest responses close when the event starts.'], 400);
        }

        $pdo->prepare("INSERT INTO app_data (data_key, data_value, updated_by)
                       VALUES ('skEventInterests', '[]', ?)
                       ON DUPLICATE KEY UPDATE data_key = VALUES(data_key)")
            ->execute([$memberUser['username']]);
        $interestQuery = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'skEventInterests' FOR UPDATE");
        $interestQuery->execute();
        $interests = json_decode((string)$interestQuery->fetchColumn(), true);
        if (!is_array($interests)) throw new RuntimeException('Event interest data is invalid.');

        $interests = array_values(array_filter($interests, function ($interest) use ($memberId, $eventId) {
            return !is_array($interest)
                || (string)($interest['memberId'] ?? '') !== $memberId
                || (string)($interest['eventId'] ?? '') !== $eventId;
        }));
        $record = [
            'memberId' => $memberId,
            'eventId' => $eventId,
            'response' => $response,
            'respondedAt' => gmdate('c'),
        ];
        $interests[] = $record;
        $save = $pdo->prepare("UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = 'skEventInterests'");
        $save->execute([
            json_encode($interests, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            $memberUser['username'],
        ]);
        $pdo->commit();
        out(['ok' => true, 'response' => $response]);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Event interest response failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not save your event response. Please try again.'], 500);
    }
}

/* ---------- everything below needs a logged-in officer ---------- */
$user = require_officer();

/* ---------- event attendance ---------- */
if ($action === 'event_attendance') {
    if (!can('record_attendance')) out(['ok' => false, 'error' => 'You do not have permission to record event attendance.'], 403);
    $eventId = (string)($in['eventId'] ?? '');
    $registrationId = (string)($in['registrationId'] ?? '');
    $status = (string)($in['status'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_-]{1,60}$/', $eventId)
        || !preg_match('/^[A-Za-z0-9_-]{1,80}$/', $registrationId)
        || !in_array($status, ['Attended', 'Did not attend', 'Not recorded'], true)) {
        out(['ok' => false, 'error' => 'Bad attendance data.'], 400);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $select = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'skEventRegistrations' FOR UPDATE");
        $select->execute();
        $raw = $select->fetchColumn();
        if ($raw === false) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'The event registration was not found.'], 404);
        }
        $registrations = json_decode((string)$raw, true);
        if (!is_array($registrations)) throw new RuntimeException('Event registration data is invalid.');
        $updated = null;
        foreach ($registrations as &$registration) {
            if (!is_array($registration)
                || (string)($registration['id'] ?? '') !== $registrationId
                || (string)($registration['eventId'] ?? '') !== $eventId) continue;
            $registration['attendanceStatus'] = $status;
            if ($status === 'Not recorded') {
                unset($registration['attendanceMarkedAt'], $registration['attendanceMarkedBy']);
            } else {
                $registration['attendanceMarkedAt'] = gmdate('c');
                $registration['attendanceMarkedBy'] = $user['username'];
            }
            $updated = $registration;
            break;
        }
        unset($registration);
        if ($updated === null) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'The event registration was not found.'], 404);
        }
        $save = $pdo->prepare('UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = ?');
        $save->execute([
            json_encode($registrations, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            $user['username'],
            'skEventRegistrations',
        ]);
        $pdo->commit();
        out(['ok' => true, 'registration' => $updated]);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Event attendance update failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not save event attendance.'], 500);
    }
}

/* ---------- walk-in attendance ---------- */
if ($action === 'event_walkin') {
    if (!can('record_attendance')) out(['ok' => false, 'error' => 'You do not have permission to record event attendance.'], 403);
    $eventId = (string)($in['eventId'] ?? '');
    $memberId = (string)($in['memberId'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_-]{1,60}$/', $eventId)
        || !preg_match('/^[A-Za-z0-9_-]{1,100}$/', $memberId)) {
        out(['ok' => false, 'error' => 'Choose a valid event and KK member.'], 400);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $lockAnnouncements = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'skAnnouncements' FOR UPDATE");
        $lockAnnouncements->execute();
        $announcements = json_decode((string)$lockAnnouncements->fetchColumn(), true);
        if (!is_array($announcements)) throw new RuntimeException('Announcement data is invalid.');
        $event = null;
        foreach ($announcements as $announcement) {
            if (is_array($announcement) && (string)($announcement['id'] ?? '') === $eventId) {
                $event = $announcement;
                break;
            }
        }
        if (!$event || !empty($event['cancelled'])) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'The selected event was not found or was cancelled.'], 404);
        }

        if (USE_MEMBERS_TABLE) {
            $memberQuery = $pdo->prepare('SELECT status FROM members WHERE legacy_id = ? FOR UPDATE');
            $memberQuery->execute([$memberId]);
            $memberStatus = $memberQuery->fetchColumn();
        } else {
            $lockMembers = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'kkMembers' FOR UPDATE");
            $lockMembers->execute();
            $members = json_decode((string)$lockMembers->fetchColumn(), true);
            if (!is_array($members)) throw new RuntimeException('KK member data is invalid.');
            $memberStatus = false;
            foreach ($members as $member) {
                if (is_array($member) && (string)($member['id'] ?? '') === $memberId) {
                    $memberStatus = $member['status'] ?? 'Active';
                    break;
                }
            }
        }
        if ($memberStatus !== 'Active') {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'Only active KK members can be added as walk-in attendees.'], 400);
        }

        $lockRegistrations = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'skEventRegistrations' FOR UPDATE");
        $lockRegistrations->execute();
        $registrationsRaw = $lockRegistrations->fetchColumn();
        $registrations = $registrationsRaw === false ? [] : json_decode((string)$registrationsRaw, true);
        if (!is_array($registrations)) throw new RuntimeException('Event registration data is invalid.');
        foreach ($registrations as $registration) {
            if (is_array($registration)
                && (string)($registration['eventId'] ?? '') === $eventId
                && (string)($registration['memberId'] ?? '') === $memberId) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'This member already has a sign-up for the event. Record attendance on that sign-up instead.'], 409);
            }
        }

        $pdo->prepare("INSERT INTO app_data (data_key, data_value, updated_by)
                       VALUES ('skEventAttendance', '[]', ?)
                       ON DUPLICATE KEY UPDATE data_key = VALUES(data_key)")
            ->execute([$user['username']]);
        $lockAttendance = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'skEventAttendance' FOR UPDATE");
        $lockAttendance->execute();
        $attendance = json_decode((string)$lockAttendance->fetchColumn(), true);
        if (!is_array($attendance)) throw new RuntimeException('Event walk-in attendance data is invalid.');
        foreach ($attendance as $record) {
            if (is_array($record)
                && (string)($record['eventId'] ?? '') === $eventId
                && (string)($record['memberId'] ?? '') === $memberId) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'This member is already recorded as a walk-in attendee.'], 409);
            }
        }
        $record = [
            'id' => 'w' . round(microtime(true) * 1000) . substr(bin2hex(random_bytes(3)), 0, 5),
            'eventId' => $eventId,
            'eventTitle' => mb_substr((string)($event['title'] ?? 'Untitled event'), 0, 150),
            'memberId' => $memberId,
            'attendanceStatus' => 'Attended',
            'attendanceMarkedAt' => gmdate('c'),
            'attendanceMarkedBy' => $user['username'],
        ];
        $attendance[] = $record;
        $pdo->prepare('UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = ?')
            ->execute([
                json_encode($attendance, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                $user['username'],
                'skEventAttendance',
            ]);
        $pdo->commit();
        out(['ok' => true, 'record' => $record]);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Walk-in event attendance update failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not save walk-in attendance.'], 500);
    }
}

/* ---------- QR scan attendance ---------- */
if ($action === 'event_attendance_qr') {
    $user = current_user();
    if (!$user || !can('record_attendance')) out(['ok' => false, 'error' => 'You do not have permission to record event attendance.'], 403);
    $eventId = (string)($in['eventId'] ?? '');
    $value = (string)($in['value'] ?? '');
    $memberId = (string)($in['memberId'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_-]{1,60}$/', $eventId)) {
        out(['ok' => false, 'error' => 'Choose a valid event.'], 400);
    }
    if ($value !== '' && $memberId !== '') {
        out(['ok' => false, 'error' => 'Provide either a scanned KK ID or a member, not both.'], 400);
    }
    if ($value === '' && $memberId === '') {
        out(['ok' => false, 'error' => 'Scan a KK ID or choose a member to mark attendance.'], 400);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $lockAnnouncements = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'skAnnouncements' FOR UPDATE");
        $lockAnnouncements->execute();
        $announcements = json_decode((string)$lockAnnouncements->fetchColumn(), true);
        if (!is_array($announcements)) throw new RuntimeException('Announcement data is invalid.');
        $event = null;
        foreach ($announcements as $announcement) {
            if (is_array($announcement) && (string)($announcement['id'] ?? '') === $eventId) {
                $event = $announcement;
                break;
            }
        }
        if (!$event || !empty($event['cancelled'])) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'The selected event was not found or was cancelled.'], 404);
        }

        // Resolve memberId from QR scan token, or use the manually chosen memberId.
        if ($value !== '') {
            require_once __DIR__ . '/../kk_id_logic.php';
            $token = kk_id_token_from_scan($value);
            if ($token === null) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'The QR code is not a valid KK ID.'], 400);
            }
            require_once __DIR__ . '/../kk_id_schema.php';
            kk_id_ensure_schema($pdo);
            $tokenRow = $pdo->prepare('SELECT member_id FROM kk_member_id_tokens WHERE token = ?');
            $tokenRow->execute([$token]);
            $memberId = (string)$tokenRow->fetchColumn();
            if ($memberId === false) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'This KK ID could not be verified.'], 404);
            }
        }
        if (!preg_match('/^[A-Za-z0-9_-]{1,100}$/', $memberId)) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'Choose a valid KK member.'], 400);
        }

        if (USE_MEMBERS_TABLE) {
            $memberQuery = $pdo->prepare('SELECT status FROM members WHERE legacy_id = ? FOR UPDATE');
            $memberQuery->execute([$memberId]);
            $memberStatus = $memberQuery->fetchColumn();
        } else {
            $lockMembers = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'kkMembers' FOR UPDATE");
            $lockMembers->execute();
            $members = json_decode((string)$lockMembers->fetchColumn(), true);
            if (!is_array($members)) throw new RuntimeException('KK member data is invalid.');
            $memberStatus = false;
            foreach ($members as $member) {
                if (is_array($member) && (string)($member['id'] ?? '') === $memberId) {
                    $memberStatus = $member['status'] ?? 'Active';
                    break;
                }
            }
        }
        if ($memberStatus !== 'Active') {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'Only active KK members can be marked as attendees.'], 400);
        }

        $lockRegistrations = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'skEventRegistrations' FOR UPDATE");
        $lockRegistrations->execute();
        $registrationsRaw = $lockRegistrations->fetchColumn();
        $registrations = $registrationsRaw === false ? [] : json_decode((string)$registrationsRaw, true);
        if (!is_array($registrations)) throw new RuntimeException('Event registration data is invalid.');
        foreach ($registrations as $registration) {
            if (is_array($registration)
                && (string)($registration['eventId'] ?? '') === $eventId
                && (string)($registration['memberId'] ?? '') === $memberId) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'This member already has a sign-up for the event. Record attendance on that sign-up instead.'], 409);
            }
        }

        $pdo->prepare("INSERT INTO app_data (data_key, data_value, updated_by)
                       VALUES ('skEventAttendance', '[]', ?)
                       ON DUPLICATE KEY UPDATE data_key = VALUES(data_key)")
            ->execute([$user['username']]);
        $lockAttendance = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'skEventAttendance' FOR UPDATE");
        $lockAttendance->execute();
        $attendance = json_decode((string)$lockAttendance->fetchColumn(), true);
        if (!is_array($attendance)) throw new RuntimeException('Event walk-in attendance data is invalid.');
        foreach ($attendance as $record) {
            if (is_array($record)
                && (string)($record['eventId'] ?? '') === $eventId
                && (string)($record['memberId'] ?? '') === $memberId) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'This member is already recorded as attending this event.'], 409);
            }
        }
        $member = null;
        if (!USE_MEMBERS_TABLE) {
            foreach ($members as $m) {
                if (is_array($m) && (string)($m['id'] ?? '') === $memberId) {
                    $member = $m;
                    break;
                }
            }
        }
        $memberName = $member ? trim(implode(' ', array_filter([
            $member['firstName'] ?? '', $member['middleName'] ?? '', $member['lastName'] ?? '', $member['suffix'] ?? '',
        ], function ($part) { return is_string($part) && trim($part) !== ''; }))) : '';
        $record = [
            'id' => 'q' . round(microtime(true) * 1000) . substr(bin2hex(random_bytes(3)), 0, 5),
            'eventId' => $eventId,
            'eventTitle' => mb_substr((string)($event['title'] ?? 'Untitled event'), 0, 150),
            'memberId' => $memberId,
            'memberName' => $memberName,
            'attendanceStatus' => 'Attended',
            'attendanceMarkedAt' => gmdate('c'),
            'attendanceMarkedBy' => $user['username'],
            'source' => $value !== '' ? 'qr_scan' : 'manual',
        ];
        $attendance[] = $record;
        $pdo->prepare('UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = ?')
            ->execute([
                json_encode($attendance, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                $user['username'],
                'skEventAttendance',
            ]);
        sk_audit($pdo, $user, $memberId, 'event_attendance_qr', null, [
            'eventId' => $eventId, 'memberId' => $memberId, 'source' => $record['source'],
        ]);
        $pdo->commit();
        out(['ok' => true, 'record' => $record]);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('QR event attendance failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not mark attendance.'], 500);
    }
}

if ($action === 'event_walkin_remove' || $action === 'event_walkin_restore') {
    require_once __DIR__ . '/../participation_logic.php';
    if (!can_manage_sk_roles()) out(['ok' => false, 'error' => 'Attendance record removal cannot be delegated.'], 403);
    $eventId = (string)($in['eventId'] ?? '');
    $recordId = (string)($in['recordId'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_-]{1,60}$/', $eventId)
        || !preg_match('/^[A-Za-z0-9_-]{1,80}$/', $recordId)) {
        out(['ok' => false, 'error' => 'Choose a valid event and walk-in record.'], 400);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $attendanceQuery = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'skEventAttendance' FOR UPDATE");
        $attendanceQuery->execute();
        $rawAttendance = $attendanceQuery->fetchColumn();
        if ($rawAttendance === false) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'The walk-in attendance record was not found.'], 404);
        }
        $attendance = json_decode((string)$rawAttendance, true);
        if (!is_array($attendance)) throw new RuntimeException('Walk-in attendance data is invalid.');

        $updated = null;
        foreach ($attendance as &$record) {
            if (!is_array($record)
                || (string)($record['id'] ?? '') !== $recordId
                || (string)($record['eventId'] ?? '') !== $eventId) continue;

            if ($action === 'event_walkin_remove') {
                if (!participation_walkin_is_active($record)) {
                    $pdo->rollBack();
                    out(['ok' => false, 'error' => 'This walk-in record has already been removed.'], 409);
                }
                $record['removedAt'] = gmdate('c');
                $record['removedBy'] = $user['username'];
            } else {
                if (participation_walkin_is_active($record)) {
                    $pdo->rollBack();
                    out(['ok' => false, 'error' => 'This walk-in record is already active.'], 409);
                }
                unset($record['removedAt'], $record['removedBy']);
                $record['attendanceStatus'] = 'Attended';
                $record['attendanceMarkedAt'] = gmdate('c');
                $record['attendanceMarkedBy'] = $user['username'];
            }
            $updated = $record;
            break;
        }
        unset($record);

        if ($updated === null) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'The walk-in attendance record was not found.'], 404);
        }
        $save = $pdo->prepare("UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = 'skEventAttendance'");
        $save->execute([
            json_encode($attendance, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            $user['username'],
        ]);
        $pdo->commit();
        out(['ok' => true, 'record' => $updated]);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Walk-in event attendance update failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not update walk-in attendance.'], 500);
    }
}

out(['ok' => false, 'error' => 'Unknown action.'], 400);


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
    $age = (int)($d['age'] ?? 0);
    if ($name === '' || $mobile === '') out(['ok' => false, 'error' => 'Please enter your name and mobile number.'], 400);
    if (!preg_match('/^[0-9+\-\s()]{7,30}$/', $mobile)) out(['ok' => false, 'error' => 'Please enter a valid mobile number.'], 400);
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) out(['ok' => false, 'error' => 'Please enter a valid email address.'], 400);
    if ($age < 0 || $age > 120) out(['ok' => false, 'error' => 'Please enter a valid age.'], 400);

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

/* ---------- everything below needs a logged-in officer ---------- */
$user = require_officer();

/* ---------- event attendance ---------- */
if ($action === 'event_attendance') {
    if (!can_write_app_data()) out(['ok' => false, 'error' => 'Your account role cannot record event attendance.'], 403);
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
    if (!can_write_app_data()) out(['ok' => false, 'error' => 'Your account role cannot record event attendance.'], 403);
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

out(['ok' => false, 'error' => 'Unknown action.'], 400);


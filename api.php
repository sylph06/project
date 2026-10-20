<?php
/* =========================================================
   api.php  - the only place the pages talk to the database
     ?action=login    check username + password, start the session
                      (5 wrong tries from one device = locked for 20 seconds)
     ?action=event_register  an authenticated user signs up for an event / announcement
     ?action=event_attendance officials mark a sign-up as attended or not attended
     ?action=meeting_minutes_*  save, approve, or reopen private SK meeting minutes
     ?action=set      save one data list        (officers only)
     ?action=remove   delete one data list      (officers only)
     ?action=password change your own password   (officers only)
   Public viewers can only read and send a registration (?action=register).
   set / remove are refused for them.
   ========================================================= */
require_once __DIR__ . '/config.php';
if (USE_MEMBERS_TABLE) require_once __DIR__ . '/members_table.php';
require_once __DIR__ . '/role_store.php';
require_once __DIR__ . '/meeting_minutes.php';
header('Content-Type: application/json; charset=utf-8');

function out($arr, $code = 200) {
    http_response_code($code);
    echo json_encode($arr);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['ok' => false, 'error' => 'POST only.'], 405);

$action = $_GET['action'] ?? '';
$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) $in = [];

$key = (string)($in['key'] ?? '');
function check_key($key) {
    if (!preg_match('/^[A-Za-z0-9_]{1,100}$/', $key)) out(['ok' => false, 'error' => 'Bad data name.'], 400);
}

/* ---------- login ---------- */
if ($action === 'login') {
    $username = trim((string)($in['username'] ?? ''));
    $password = (string)($in['password'] ?? '');
    // An older copy of the database had a hashed "password_hash" column instead of "password".
    // If that is what is installed, add the plain-text column and give the starter accounts "sk2026".
    try { db()->query('SELECT password FROM users LIMIT 1'); }
    catch (Exception $ex) {
        db()->exec("ALTER TABLE users ADD COLUMN password VARCHAR(100) NOT NULL DEFAULT ''");
        db()->exec("UPDATE users SET password = 'sk2026' WHERE password = ''");
    }

    /* ---- brute-force lock: 5 wrong tries from one device (IP) = locked for 20 seconds ----
       Kept in MySQL (table login_attempts) so refreshing the page or clearing cookies does not reset it. */
    $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
    $maxTries = 5; $lockSeconds = 20;
    db()->exec('CREATE TABLE IF NOT EXISTS login_attempts (
        ip VARCHAR(45) NOT NULL PRIMARY KEY,
        fails INT UNSIGNED NOT NULL DEFAULT 0,
        last_fail DATETIME NULL,
        locked_until DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $st = db()->prepare('SELECT COALESCE(TIMESTAMPDIFF(SECOND, NOW(), locked_until), 0) FROM login_attempts WHERE ip = ?');
    $st->execute([$ip]);
    $wait = (int)$st->fetchColumn();
    if ($wait > 0) {
        header('Retry-After: ' . $wait);
        out(['ok' => false, 'error' => 'Too many wrong attempts. Please wait ' . $wait . ' seconds.', 'retryAfter' => $wait], 429);
    }

    $st = db()->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1');
    $st->execute([$username]);
    $u = $st->fetch();
    $matches = $u && (password_verify($password, (string)$u['password'])
        || hash_equals((string)$u['password'], $password));
    if (!$matches) {
        db()->prepare('INSERT INTO login_attempts (ip, fails, last_fail) VALUES (?, 1, NOW())
                       ON DUPLICATE KEY UPDATE
                         fails = IF(last_fail IS NULL OR last_fail < NOW() - INTERVAL 15 MINUTE, 1, fails + 1),
                         last_fail = NOW()')->execute([$ip]);
        $st = db()->prepare('SELECT fails FROM login_attempts WHERE ip = ?');
        $st->execute([$ip]);
        $fails = (int)$st->fetchColumn();
        if ($fails >= $maxTries) {
            db()->prepare('UPDATE login_attempts SET fails = 0, locked_until = NOW() + INTERVAL ' . $lockSeconds . ' SECOND WHERE ip = ?')->execute([$ip]);
            header('Retry-After: ' . $lockSeconds);
            out(['ok' => false, 'error' => 'Too many wrong attempts. Please wait ' . $lockSeconds . ' seconds.', 'retryAfter' => $lockSeconds], 429);
        }
        usleep(600000);                                   // slows down password guessing
        out(['ok' => false, 'error' => 'Wrong username or password.', 'triesLeft' => $maxTries - $fails], 401);
    }
    db()->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
    if (password_get_info((string)$u['password'])['algo'] === null) {
        db()->prepare('UPDATE users SET password = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }
    session_regenerate_id(true);
    unset($_SESSION['public']);
    $_SESSION['user'] = ['id' => (int)$u['id']];
    db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$u['id']]);
    $user = current_user();
    out(['ok' => true, 'user' => $user, 'forcePasswordChange' => $user['must_change_password']]);
}

if ($action !== 'password') {
    $authenticatedUser = current_user();
    if ($authenticatedUser && !empty($authenticatedUser['must_change_password'])) {
        out(['ok' => false, 'error' => 'Change your temporary password before continuing.'], 403);
    }
}

/* ---------- public registration: add ONE pending request (public viewers + officers) ---------- */
if ($action === 'register') {
    if (!current_user() && !is_public()) out(['ok' => false, 'error' => 'Please open the system first.'], 401);
    $d = $in['data'] ?? null;
    if (!is_array($d)) out(['ok' => false, 'error' => 'Bad form data.'], 400);

    // simple limit so the form cannot be spammed
    $_SESSION['reg_log'] = array_values(array_filter($_SESSION['reg_log'] ?? [], function ($t) { return $t > time() - 3600; }));
    if (count($_SESSION['reg_log']) >= 10) out(['ok' => false, 'error' => 'Too many submissions. Please try again later.'], 429);

    $text = ['firstName','middleName','lastName','suffix','birthDate','gender','genderOther','civilStatus','religion','religionOther',
             'houseNo','street','purok','mobile','email','facebook','classification','classificationOther','educationLevel','educationOther',
             'educationStatus','educationStatusOther','school','schoolOther','employment','employmentOther','occupation','occupationOther',
             'training','trainingOther','pwd','disabilityType','disabilityOther','skVoter','voterNo','interestOther','notes'];
    $rec = [];
    foreach ($text as $k) {
        $v = $d[$k] ?? '';
        $rec[$k] = is_string($v) ? mb_substr(trim($v), 0, $k === 'notes' ? 500 : 120) : '';
    }
    if ($rec['email'] !== '' && !filter_var($rec['email'], FILTER_VALIDATE_EMAIL))
        out(['ok' => false, 'error' => 'Please enter a valid email address.'], 400);
    $rec['interests'] = [];
    if (isset($d['interests']) && is_array($d['interests'])) {
        foreach (array_slice($d['interests'], 0, 30) as $i) { if (is_string($i)) $rec['interests'][] = mb_substr($i, 0, 80); }
    }
    if ($rec['firstName'] === '' || $rec['lastName'] === '' || $rec['gender'] === '' || $rec['purok'] === '' || $rec['mobile'] === '')
        out(['ok' => false, 'error' => 'Please fill in all required fields.'], 400);
    $b = DateTime::createFromFormat('Y-m-d', $rec['birthDate']);
    if (!$b || $b->format('Y-m-d') !== $rec['birthDate']) out(['ok' => false, 'error' => 'Please enter a valid date of birth.'], 400);
    $age = $b->diff(new DateTime('today'))->y;
    if ($b > new DateTime('today') || $age < 15 || $age > 30) out(['ok' => false, 'error' => 'Only youth aged 15 to 30 years old can register.'], 400);

    $pdo = db();
    $pdo->beginTransaction();
    $get = function ($k) use ($pdo) {
        $st = $pdo->prepare('SELECT data_value FROM app_data WHERE data_key = ? FOR UPDATE');
        $st->execute([$k]);
        $v = json_decode((string)$st->fetchColumn(), true);
        return is_array($v) ? $v : [];
    };
    $members = USE_MEMBERS_TABLE ? [] : $get('kkMembers');
    $pending = $get('kkPendingRegistrations');
    $same = function ($m) use ($rec) {
        return strtolower($m['firstName'] ?? '') === strtolower($rec['firstName']) &&
               strtolower($m['lastName'] ?? '') === strtolower($rec['lastName']) &&
               ($m['birthDate'] ?? '') === $rec['birthDate'];
    };
    if (USE_MEMBERS_TABLE) {
        $duplicate = $pdo->prepare('SELECT 1 FROM members
            WHERE LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?) AND birthdate = ?
            LIMIT 1 FOR UPDATE');
        $duplicate->execute([$rec['firstName'], $rec['lastName'], $rec['birthDate']]);
        if ($duplicate->fetchColumn()) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'This youth is already registered (same name and date of birth).'], 409);
        }
    } else {
        foreach ($members as $m) { if ($same($m)) { $pdo->rollBack(); out(['ok' => false, 'error' => 'This youth is already registered (same name and date of birth).'], 409); } }
    }
    foreach ($pending as $m) { if (($m['status'] ?? '') === 'Pending' && $same($m)) { $pdo->rollBack(); out(['ok' => false, 'error' => 'A registration for this youth is already waiting for approval.'], 409); } }

    $rec['id'] = 'p' . round(microtime(true) * 1000) . substr(bin2hex(random_bytes(2)), 0, 3);
    $rec['refNo'] = 'REG-' . date('Y') . '-' . str_pad((string)(count($pending) + 1), 4, '0', STR_PAD_LEFT);
    $rec['status'] = 'Pending';
    $rec['submittedAt'] = gmdate('c');
    $pending[] = $rec;
    $pdo->prepare('INSERT INTO app_data (data_key, data_value, updated_by) VALUES (?, ?, ?)
                   ON DUPLICATE KEY UPDATE data_value = VALUES(data_value), updated_by = VALUES(updated_by)')
        ->execute(['kkPendingRegistrations', json_encode($pending, JSON_UNESCAPED_UNICODE), current_user() ? current_user()['username'] : 'public']);
    $pdo->commit();
    $_SESSION['reg_log'][] = time();
    out(['ok' => true, 'refNo' => $rec['refNo']]);
}

/* ---------- event sign-up: requires an authenticated account ---------- */
if ($action === 'event_register') {
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

    // simple limit so the form cannot be spammed
    $_SESSION['ev_log'] = array_values(array_filter($_SESSION['ev_log'] ?? [], function ($t) { return $t > time() - 3600; }));
    if (count($_SESSION['ev_log']) >= 15) out(['ok' => false, 'error' => 'Too many sign-ups. Please try again later.'], 429);

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
    $_SESSION['ev_log'][] = time();
    out(['ok' => true, 'ref' => strtoupper(substr($rec['id'], -6)), 'record' => $rec]);
}

/* ---------- everything below needs a logged-in officer ---------- */
$user = current_user();
if (!$user) {
    if (is_public()) out(['ok' => false, 'error' => 'View only. Only officials can change data.'], 403);
    out(['ok' => false, 'error' => 'Please log in again.'], 401);
}
if (!empty($user['must_change_password']) && $action !== 'password') {
    out(['ok' => false, 'error' => 'Change your temporary password before continuing.'], 403);
}

if (in_array($action, ['meeting_minutes_save', 'meeting_minutes_approve', 'meeting_minutes_reopen'], true)) {
    $operation = substr($action, strlen('meeting_minutes_'));
    $meetingId = (string)($in['meetingId'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_-]{1,80}$/', $meetingId)) {
        out(['ok' => false, 'error' => 'Choose a valid meeting.'], 400);
    }
    if (!meeting_minutes_role_can_manage($user['role'] ?? '')) {
        out(['ok' => false, 'error' => 'Only the SK Secretary or SK Chairman can manage meeting minutes.'], 403);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $select = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'skMeetings' FOR UPDATE");
        $select->execute();
        $raw = $select->fetchColumn();
        $meetings = $raw === false ? [] : json_decode((string)$raw, true);
        if (!is_array($meetings)) throw new RuntimeException('Meeting data is invalid.');

        $updatedIndex = null;
        foreach ($meetings as $index => $meeting) {
            if (is_array($meeting) && (string)($meeting['id'] ?? '') === $meetingId) {
                $updatedIndex = $index;
                break;
            }
        }
        if ($updatedIndex === null) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'The meeting was not found.'], 404);
        }

        $meeting = $meetings[$updatedIndex];
        $status = ($meeting['minutesStatus'] ?? '') === 'Approved' ? 'Approved' : 'Draft';
        if (!meeting_minutes_action_allowed($user['role'], $operation, $status)) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'You cannot perform this action on the current minutes.'], 403);
        }

        $auditAction = '';
        if ($operation === 'save') {
            $html = $in['minutes'] ?? null;
            if (!is_string($html) || strlen($html) > 1000000) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'The minutes submission is too large.'], 400);
            }
            $meeting['minutes'] = meeting_minutes_sanitize_html($html);
            if (!meeting_minutes_within_limit($meeting['minutes'])) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'Minutes cannot exceed 20,000 characters.'], 400);
            }
            $auditAction = 'meeting_minutes_saved';
        } elseif ($operation === 'approve') {
            if (meeting_minutes_text_length((string)($meeting['minutes'] ?? '')) === 0) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'Add minutes before approving them.'], 400);
            }
            $meeting['minutesStatus'] = 'Approved';
            $auditAction = 'meeting_minutes_approved';
        } else {
            $meeting['minutesStatus'] = 'Draft';
            $auditAction = 'meeting_minutes_reopened';
        }

        if ($operation === 'save') $meeting['minutesStatus'] = 'Draft';
        $meeting['minutesUpdatedBy'] = (string)$user['username'];
        $meeting['minutesUpdatedAt'] = gmdate('c');
        $meetings[$updatedIndex] = $meeting;
        $encoded = json_encode($meetings, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        $save = $pdo->prepare('INSERT INTO app_data (data_key, data_value, updated_by) VALUES (?, ?, ?)
                               ON DUPLICATE KEY UPDATE data_value = VALUES(data_value), updated_by = VALUES(updated_by)');
        $save->execute(['skMeetings', $encoded, $user['username']]);
        $pdo->prepare('INSERT INTO audit_log (actor_user_id, actor, target_legacy_id, action, old_value, new_value)
                       VALUES (?, ?, ?, ?, NULL, NULL)')
            ->execute([$user['id'], $user['username'], 'meeting:' . $meetingId, $auditAction]);
        $pdo->commit();
        out(['ok' => true, 'meeting' => $meeting]);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Meeting minutes update failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not save the meeting minutes.'], 500);
    }
}

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

if (in_array($action, ['role_assign', 'role_update', 'role_end', 'role_archive', 'role_archive_expired'], true)) {
    if (!can_manage_sk_roles()) out(['ok' => false, 'error' => 'Only the SK Chairman or SK Secretary can manage SK roles.'], 403);
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $roles = sk_role_rows($pdo, true);
        if ($action === 'role_archive_expired') {
            $today = date('Y-m-d');
            $count = 0;
            foreach ($roles as $expired) {
                if (($expired['status'] ?? '') !== 'Active' || empty($expired['termEnd']) || $expired['termEnd'] >= $today) continue;
                $old = $expired;
                $expired['status'] = 'Archived';
                if (!isset($expired['history']) || !is_array($expired['history'])) $expired['history'] = [];
                $expired['history'][] = [
                    'at' => gmdate('Y-m-d\TH:i:s.000\Z'),
                    'action' => 'Auto-archived',
                    'by' => $user['username'],
                    'note' => 'Term ended on ' . $expired['termEnd'] . '.',
                ];
                sk_role_write($pdo, $expired, $user['username']);
                sk_audit($pdo, $user, (string)$expired['kkId'], 'role_archived', $old, $expired);
                $count++;
            }
            $pdo->commit();
            out(['ok' => true, 'archived' => $count, 'roles' => array_values($roles)]);
        }
        $roleId = (string)($in['id'] ?? ($in['role']['id'] ?? ''));
        $oldRole = $roleId === '' ? null : sk_role_find($roles, $roleId);
        if ($action === 'role_assign') {
            $inputRole = $in['role'] ?? null;
            $kkId = is_array($inputRole) ? (string)($inputRole['kkId'] ?? '') : '';
            $member = $kkId === '' ? null : sk_member_for_role_change($pdo, $kkId, true);
            if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
                throw new RuntimeException('Only an active KK member can be assigned an SK role.');
            }
            if ($roleId === '' || $oldRole) throw new RuntimeException('The role id is missing or already exists.');
            $validated = sk_role_validate_payload($inputRole);
            $newRole = array_merge($validated, [
                'id' => $roleId,
                'kkId' => $kkId,
                'status' => 'Active',
                'history' => [],
                'createdAt' => gmdate('Y-m-d\TH:i:s.000\Z'),
            ]);
            $newRole['history'][] = [
                'at' => gmdate('Y-m-d\TH:i:s.000\Z'),
                'action' => 'SK role assigned',
                'by' => $user['username'],
                'note' => $newRole['position'] . ' (' . $newRole['basis'] . ').',
            ];
            sk_role_write($pdo, $newRole, $user['username']);
            sk_audit($pdo, $user, $kkId, 'role_assigned', null, $newRole);
            $pdo->commit();
            out(['ok' => true, 'role' => $newRole]);
        }

        if (!$oldRole) throw new RuntimeException('The SK role no longer exists.');
        $expectedRole = $in['expected'] ?? null;
        if (!is_array($expectedRole) || !sk_role_equal($expectedRole, $oldRole)) {
            throw new RuntimeException('This SK role changed in another session. Reload and try again.');
        }
        if ($action === 'role_update') {
            $member = sk_member_for_role_change($pdo, (string)$oldRole['kkId'], true);
            if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
                throw new RuntimeException('Only an active KK member can hold an SK role.');
            }
            $roleInput = $in['role'] ?? null;
            $validated = sk_role_validate_payload($roleInput);
            $newRole = array_merge($oldRole, $validated);
            $newStatus = is_array($roleInput) ? (string)($roleInput['status'] ?? $oldRole['status']) : '';
            if (!in_array($newStatus, ['Active', 'Inactive'], true)) {
                throw new RuntimeException('Use the dedicated archive action to archive an SK role.');
            }
            $newRole['status'] = $newStatus;
            if (!isset($newRole['history']) || !is_array($newRole['history'])) $newRole['history'] = [];
            $newRole['history'][] = [
                'at' => gmdate('Y-m-d\TH:i:s.000\Z'),
                'action' => 'Updated',
                'by' => $user['username'],
                'note' => 'SK role information updated.',
            ];
            sk_role_write($pdo, $newRole, $user['username']);
            sk_audit($pdo, $user, (string)$oldRole['kkId'], 'role_updated', $oldRole, $newRole);
            $pdo->commit();
            out(['ok' => true, 'role' => $newRole]);
        }

        $newRole = $oldRole;
        $newRole['status'] = $action === 'role_end' ? 'Inactive' : 'Archived';
        if (!isset($newRole['history']) || !is_array($newRole['history'])) $newRole['history'] = [];
        $newRole['history'][] = [
            'at' => gmdate('Y-m-d\TH:i:s.000\Z'),
            'action' => $action === 'role_end' ? 'Role ended' : 'Role archived',
            'by' => $user['username'],
            'note' => (string)($in['reason'] ?? ''),
        ];
        sk_role_write($pdo, $newRole, $user['username']);
        sk_audit($pdo, $user, (string)$oldRole['kkId'], $action === 'role_end' ? 'role_ended' : 'role_archived', $oldRole, $newRole);
        $pdo->commit();
        out(['ok' => true, 'role' => $newRole]);
    } catch (RuntimeException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        out(['ok' => false, 'error' => $ex->getMessage()], 400);
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('SK role operation failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not save the SK role.'], 500);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('SK role operation failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not save the SK role.'], 500);
    }
}

if (in_array($action, ['accounts_unlinked', 'account_link', 'account_create', 'account_reset', 'account_deactivate'], true)) {
    if (!can_manage_sk_roles()) out(['ok' => false, 'error' => 'Only the SK Chairman or SK Secretary can manage member accounts.'], 403);
    $pdo = db();
    if ($action === 'accounts_unlinked') {
        $rows = $pdo->query('SELECT id, username, full_name, role, is_active FROM users WHERE legacy_id IS NULL ORDER BY full_name')
            ->fetchAll(PDO::FETCH_ASSOC);
        $linked = $pdo->query('SELECT legacy_id FROM users WHERE legacy_id IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);
        out(['ok' => true, 'accounts' => $rows, 'linkedLegacyIds' => $linked]);
    }

    $pdo->beginTransaction();
    try {
        if ($action === 'account_link') {
            $accountId = filter_var($in['accountId'] ?? null, FILTER_VALIDATE_INT);
            $legacyId = (string)($in['legacyId'] ?? '');
            $member = $legacyId === '' ? null : sk_member_for_role_change($pdo, $legacyId, true);
            if (!$accountId || !$member || ($member['status'] ?? 'Active') !== 'Active') {
                throw new RuntimeException('Choose an active KK member and a valid unlinked account.');
            }
            $account = $pdo->prepare('SELECT id, username, legacy_id, is_active, must_change_password FROM users WHERE id = ? FOR UPDATE');
            $account->execute([$accountId]);
            $target = $account->fetch(PDO::FETCH_ASSOC);
            if (!$target || $target['legacy_id'] !== null || !(int)$target['is_active']) {
                throw new RuntimeException('That account is already linked or inactive.');
            }
            $pdo->prepare('UPDATE users SET legacy_id = ? WHERE id = ?')->execute([$legacyId, $accountId]);
            sk_audit($pdo, $user, $legacyId, 'account_linked', ['account_id' => $accountId, 'username' => $target['username'], 'legacy_id' => null],
                ['account_id' => $accountId, 'username' => $target['username'], 'legacy_id' => $legacyId]);
            $pdo->commit();
            out(['ok' => true]);
        }

        if ($action === 'account_create') {
            $legacyId = (string)($in['legacyId'] ?? '');
            $member = $legacyId === '' ? null : sk_member_for_role_change($pdo, $legacyId, true);
            if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
                throw new RuntimeException('Only an active KK member can receive an account.');
            }
            $username = trim((string)($in['username'] ?? ''));
            if (!preg_match('/^[A-Za-z0-9._-]{4,50}$/', $username)) {
                throw new RuntimeException('Username must be 4 to 50 letters, numbers, dots, underscores, or hyphens.');
            }
            $consent = !empty($in['guardianConsent']);
            $birthDate = (string)($member['birthDate'] ?? '');
            if ($birthDate !== '') {
                $birth = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
                if ($birth && $birth->diff(new DateTimeImmutable('today'))->y < 18 && !$consent) {
                    throw new RuntimeException('Guardian consent is required to create an account for a member under 18.');
                }
            }
            $exists = $pdo->prepare('SELECT id FROM users WHERE legacy_id = ? OR username = ? FOR UPDATE');
            $exists->execute([$legacyId, $username]);
            if ($exists->fetchColumn() !== false) throw new RuntimeException('A member can have only one account, and usernames must be unique.');
            $temporaryPassword = bin2hex(random_bytes(12));
            $name = trim(implode(' ', array_filter([
                $member['firstName'] ?? '', $member['middleName'] ?? '', $member['lastName'] ?? '', $member['suffix'] ?? '',
            ], function ($part) { return (string)$part !== ''; })));
            $version = $consent ? trim((string)($in['consentVersion'] ?? '')) : null;
            if ($consent && $version === '') throw new RuntimeException('Consent version is required when guardian consent is recorded.');
            $pdo->prepare('INSERT INTO users
                (username, password, full_name, role, is_active, legacy_id, must_change_password,
                 guardian_consent, consent_at, consent_version, created_by)
                VALUES (?, ?, ?, ?, 1, ?, 1, ?, ?, ?, ?)')
                ->execute([
                    $username, password_hash($temporaryPassword, PASSWORD_DEFAULT), $name, 'KK Member', $legacyId,
                    $consent ? 1 : 0, $consent ? date('Y-m-d H:i:s') : null, $version, $user['id'],
                ]);
            $newId = (int)$pdo->lastInsertId();
            sk_audit($pdo, $user, $legacyId, 'account_created', null, [
                'account_id' => $newId, 'username' => $username, 'must_change_password' => true,
                'guardian_consent' => $consent, 'consent_version' => $version,
            ]);
            $pdo->commit();
            out(['ok' => true, 'accountId' => $newId, 'temporaryPassword' => $temporaryPassword]);
        }

        $targetId = filter_var($in['accountId'] ?? null, FILTER_VALIDATE_INT);
        if (!$targetId) throw new RuntimeException('A valid account id is required.');
        $account = $pdo->prepare('SELECT id, username, legacy_id, is_active, must_change_password FROM users WHERE id = ? FOR UPDATE');
        $account->execute([$targetId]);
        $target = $account->fetch(PDO::FETCH_ASSOC);
        if (!$target) throw new RuntimeException('The account no longer exists.');
        if ($action === 'account_reset') {
            $temporaryPassword = bin2hex(random_bytes(12));
            $pdo->prepare('UPDATE users SET password = ?, must_change_password = 1 WHERE id = ?')
                ->execute([password_hash($temporaryPassword, PASSWORD_DEFAULT), $targetId]);
            sk_audit($pdo, $user, $target['legacy_id'], 'account_password_reset', ['must_change_password' => (bool)$target['must_change_password']],
                ['account_id' => $targetId, 'username' => $target['username'], 'must_change_password' => true]);
            $pdo->commit();
            out(['ok' => true, 'temporaryPassword' => $temporaryPassword]);
        }
        if ($targetId === $user['id']) throw new RuntimeException('You cannot deactivate your own account.');
        $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = ?')->execute([$targetId]);
        sk_audit($pdo, $user, $target['legacy_id'], 'account_deactivated',
            ['account_id' => $targetId, 'is_active' => (bool)$target['is_active']],
            ['account_id' => $targetId, 'is_active' => false]);
        $pdo->commit();
        out(['ok' => true]);
    } catch (RuntimeException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        out(['ok' => false, 'error' => $ex->getMessage()], 400);
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Member account operation failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not update the member account.'], 500);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Member account operation failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not update the member account.'], 500);
    }
}

if (USE_MEMBERS_TABLE && in_array($action, ['member_add', 'member_update', 'member_delete'], true)) {
    if (!can_write_app_data()) out(['ok' => false, 'error' => 'Your account role cannot change KK member records.'], 403);
    $pdo = db();
    $pdo->beginTransaction();
    try {
        if ($action === 'member_add') {
            $member = $in['member'] ?? null;
            if (!is_array($member) || empty($member['id'])) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'A member record with an id is required.'], 400);
            }
            $exists = $pdo->prepare('SELECT 1 FROM members WHERE legacy_id = ? FOR UPDATE');
            $exists->execute([(string)$member['id']]);
            if ($exists->fetchColumn()) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'That member already exists.'], 409);
            }
            $result = kk_member_upsert($pdo, $member, $user['username']);
            $pdo->commit();
            out(['ok' => true, 'result' => $result]);
        }

        $legacyId = $in['id'] ?? '';
        if (!is_string($legacyId) || $legacyId === '') {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'A member id is required.'], 400);
        }
        $find = $pdo->prepare('SELECT * FROM members WHERE legacy_id = ? FOR UPDATE');
        $find->execute([$legacyId]);
        $row = $find->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'The member no longer exists.'], 404);
        }

        if ($action === 'member_update') {
            $member = $in['member'] ?? null;
            $expected = $in['expected'] ?? null;
            if (!is_array($member) || !is_array($expected) || (string)($member['id'] ?? '') !== $legacyId) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'A member record and its original values are required.'], 400);
            }
            $expected = kk_members_request_to_object($expected);
            if (!kk_members_json_equal($expected, kk_member_row_to_object($row))) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'This member changed in another session. Reload and try again.'], 409);
            }
            $result = kk_member_upsert($pdo, $member, $user['username']);
            $pdo->commit();
            out(['ok' => true, 'result' => $result]);
        }

        $expected = $in['expected'] ?? null;
        if (!is_array($expected)) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'The original member values are required to delete this record.'], 400);
        }
        $expected = kk_members_request_to_object($expected);
        if (!kk_members_json_equal($expected, kk_member_row_to_object($row))) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'This member changed in another session. Reload and try again.'], 409);
        }

        foreach (sk_role_rows($pdo, true) as $role) {
            if (is_array($role) && (string)($role['kkId'] ?? '') === $legacyId) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'Unassign this member’s SK role before deleting the member.'], 409);
            }
        }
        $requests = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'kkEditRequests' FOR UPDATE")->fetchColumn();
        $requests = $requests === false ? [] : json_decode($requests, true);
        foreach (is_array($requests) ? $requests : [] as $request) {
            if (is_array($request) && (string)($request['memberId'] ?? '') === $legacyId) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'Resolve this member’s edit requests before deleting the member.'], 409);
            }
        }
        $pdo->prepare('DELETE FROM members WHERE legacy_id = ?')->execute([$legacyId]);
        $pdo->commit();
        out(['ok' => true]);
    } catch (DomainException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        out(['ok' => false, 'error' => $ex->getMessage()], 409);
    } catch (RuntimeException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        out(['ok' => false, 'error' => $ex->getMessage()], 400);
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('KK member table write failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not save the KK member record.'], 500);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('KK member table save failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not save the KK member record.'], 500);
    }
}

if ($action === 'set') {
    check_key($key);
    if ($key === 'skRoles') out(['ok' => false, 'error' => 'Use the dedicated SK role actions.'], 403);
    if ($key === 'skEventAttendance') out(['ok' => false, 'error' => 'Use the dedicated event attendance actions.'], 403);
    if (!can_write_app_data()) out(['ok' => false, 'error' => 'Your account role cannot make this change.'], 403);
    if (USE_MEMBERS_TABLE) {
        $allowedKeys = [
            'skAnnouncements', 'skMeetings', 'skAttendance', 'skUnlinked',
            'kkEditRequests', 'kkPendingRegistrations', 'skTransparencyBudget',
            'skTransparencyExpenses', 'skEventRegistrations',
        ];
        if (!in_array($key, $allowedKeys, true) || $key === 'kkMembers') {
            out(['ok' => false, 'error' => 'This data key cannot be written through the generic storage endpoint.'], 403);
        }
    }
    $value = $in['value'] ?? null;
    if (!is_string($value)) out(['ok' => false, 'error' => 'Bad value.'], 400);
    $decoded = json_decode($value, true);
    if (json_last_error() !== JSON_ERROR_NONE) out(['ok' => false, 'error' => 'Data is not valid JSON.'], 400);
    if ($key === 'skMeetings') {
        if (!is_array($decoded) || !is_array(json_decode($value))) {
            out(['ok' => false, 'error' => 'Meeting data must be a list.'], 400);
        }
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $select = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'skMeetings' FOR UPDATE");
            $select->execute();
            $raw = $select->fetchColumn();
            $existing = $raw === false ? [] : json_decode((string)$raw, true);
            if (!is_array($existing)) throw new RuntimeException('Saved meeting data is invalid.');
            $existingById = [];
            foreach ($existing as $oldMeeting) {
                if (is_array($oldMeeting) && isset($oldMeeting['id'])) {
                    $existingById[(string)$oldMeeting['id']] = $oldMeeting;
                }
            }
            $seen = [];
            foreach ($decoded as $index => $meeting) {
                if (!is_array($meeting) || !isset($meeting['id'])
                    || !preg_match('/^[A-Za-z0-9_-]{1,80}$/', (string)$meeting['id'])) {
                    $pdo->rollBack();
                    out(['ok' => false, 'error' => 'Each meeting must have a valid id.'], 400);
                }
                $id = (string)$meeting['id'];
                if (isset($seen[$id])) {
                    $pdo->rollBack();
                    out(['ok' => false, 'error' => 'Meeting ids must be unique.'], 400);
                }
                $seen[$id] = true;
                $old = $existingById[$id] ?? null;
                if (!meeting_minutes_generic_fields_match(is_array($old) ? $old : [], $meeting)) {
                    $pdo->rollBack();
                    out(['ok' => false, 'error' => 'Use the dedicated minutes actions to change meeting minutes.'], 403);
                }
                $oldMinutes = is_array($old) && is_string($old['minutes'] ?? null) ? $old['minutes'] : '';
                $decoded[$index]['minutes'] = meeting_minutes_sanitize_html($oldMinutes);
                $decoded[$index]['minutesStatus'] = is_array($old) && ($old['minutesStatus'] ?? '') === 'Approved' ? 'Approved' : 'Draft';
                $decoded[$index]['minutesUpdatedBy'] = is_array($old) && is_string($old['minutesUpdatedBy'] ?? null) ? $old['minutesUpdatedBy'] : '';
                $decoded[$index]['minutesUpdatedAt'] = is_array($old) && is_string($old['minutesUpdatedAt'] ?? null) ? $old['minutesUpdatedAt'] : '';
            }
            if (!meeting_minutes_role_can_manage($user['role'] ?? '')) {
                foreach ($existingById as $oldId => $oldMeeting) {
                    if (!isset($seen[$oldId]) && meeting_minutes_text_length(
                        meeting_minutes_sanitize_html((string)($oldMeeting['minutes'] ?? ''))
                    ) > 0) {
                        $pdo->rollBack();
                        out(['ok' => false, 'error' => 'Only the SK Secretary or SK Chairman can remove a meeting with minutes.'], 403);
                    }
                }
            }
            $canonical = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            $save = $pdo->prepare('INSERT INTO app_data (data_key, data_value, updated_by) VALUES (?, ?, ?)
                                   ON DUPLICATE KEY UPDATE data_value = VALUES(data_value), updated_by = VALUES(updated_by)');
            $save->execute([$key, $canonical, $user['username']]);
            $pdo->commit();
            out(['ok' => true, 'value' => $canonical]);
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Meeting data write failed: ' . $ex->getMessage());
            out(['ok' => false, 'error' => 'Could not save meeting data.'], 500);
        }
    }
    db()->prepare('INSERT INTO app_data (data_key, data_value, updated_by) VALUES (?, ?, ?)
                   ON DUPLICATE KEY UPDATE data_value = VALUES(data_value), updated_by = VALUES(updated_by)')
        ->execute([$key, $value, $user['username']]);
    out(['ok' => true]);
}

if ($action === 'password') {                         // an officer changes their own password
    $cur = (string)($in['current'] ?? '');
    $new = (string)($in['new'] ?? '');
    if (strlen($new) < 8 || strlen($new) > 100) out(['ok' => false, 'error' => 'The new password must be 8 to 100 characters.'], 400);
    $st = db()->prepare('SELECT password FROM users WHERE id = ?');
    $st->execute([$user['id']]);
    $stored = (string)$st->fetchColumn();
    if (!(password_verify($cur, $stored) || hash_equals($stored, $cur))) { usleep(600000); out(['ok' => false, 'error' => 'Your current password is wrong.'], 403); }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        sk_audit($pdo, $user, $user['legacy_id'], 'account_password_changed',
            ['must_change_password' => $user['must_change_password']], ['must_change_password' => false]);
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Password update failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not update the password.'], 500);
    }
    unset($_SESSION['user']);
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => $user['id']];
    out(['ok' => true]);
}

if ($action === 'remove') {
    check_key($key);
    if ($key === 'skRoles') out(['ok' => false, 'error' => 'Use the dedicated SK role actions.'], 403);
    if ($key === 'skEventAttendance') out(['ok' => false, 'error' => 'Use the dedicated event attendance actions.'], 403);
    if ($key === 'skMeetings') out(['ok' => false, 'error' => 'Meeting records cannot be removed through the generic storage endpoint.'], 403);
    if (!can_write_app_data()) out(['ok' => false, 'error' => 'Your account role cannot make this change.'], 403);
    if (USE_MEMBERS_TABLE) {
        $allowedKeys = [
            'skAnnouncements', 'skMeetings', 'skAttendance', 'skUnlinked',
            'kkEditRequests', 'kkPendingRegistrations', 'skTransparencyBudget',
            'skTransparencyExpenses', 'skEventRegistrations',
        ];
        if (!in_array($key, $allowedKeys, true) || $key === 'kkMembers') {
            out(['ok' => false, 'error' => 'This data key cannot be removed through the generic storage endpoint.'], 403);
        }
    }
    db()->prepare('DELETE FROM app_data WHERE data_key = ?')->execute([$key]);
    out(['ok' => true]);
}

out(['ok' => false, 'error' => 'Unknown action.'], 400);
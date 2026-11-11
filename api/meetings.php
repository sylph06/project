<?php
/* =========================================================
   api/meetings.php — meeting minutes save, approve, reopen
   ========================================================= */
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$user = require_officer();

if (!in_array($action, [
    'meeting_minutes_save', 'meeting_minutes_approve', 'meeting_minutes_reopen',
    'meeting_attendance_save', 'meeting_attendance_delete',
], true)) {
    out(['ok' => false, 'error' => 'Unknown action.'], 400);
}

$pdo = db();
if ($action === 'meeting_attendance_save' || $action === 'meeting_attendance_delete') {
    if (!can_manage_sk_roles()) {
        out(['ok' => false, 'error' => 'Only the SK Chairman or SK Secretary can manage official attendance.'], 403);
    }
    $meetingId = (string)($in['meetingId'] ?? '');
    $expected = $in['expected'] ?? null;
    if (!preg_match('/^[A-Za-z0-9_-]{1,80}$/', $meetingId) || !is_array($expected)) {
        out(['ok' => false, 'error' => 'A valid meeting and its previous attendance record are required.'], 400);
    }
    $pdo->beginTransaction();
    try {
        $meetingLock = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'skMeetings' FOR UPDATE");
        $meetings = json_decode((string)$meetingLock->fetchColumn(), true);
        if (!is_array($meetings)) throw new RuntimeException('Meeting data is invalid.');
        $meeting = null;
        foreach ($meetings as $candidate) {
            if (is_array($candidate) && (string)($candidate['id'] ?? '') === $meetingId) {
                $meeting = $candidate;
                break;
            }
        }
        if ($action === 'meeting_attendance_save' && $meeting === null) {
            throw new DomainException('The meeting no longer exists.');
        }

        $attendanceLock = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'skAttendance' FOR UPDATE");
        $attendanceRaw = $attendanceLock->fetchColumn();
        $attendance = $attendanceRaw === false ? [] : json_decode((string)$attendanceRaw, true);
        if (!is_array($attendance)) throw new RuntimeException('Meeting attendance data is invalid.');
        $current = $attendance[$meetingId] ?? [];
        if (!is_array($current)) throw new RuntimeException('This meeting attendance record is invalid.');
        if (!sk_role_equal($expected, $current)) {
            throw new DomainException('Meeting attendance changed in another session. Reload and try again.');
        }

        if ($action === 'meeting_attendance_delete') {
            if ($current) {
                sk_audit($pdo, $user, 'meeting:' . $meetingId, 'meeting_attendance_deleted', $current, null);
                unset($attendance[$meetingId]);
                $pdo->prepare("UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = 'skAttendance'")
                    ->execute([json_encode($attendance, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR), $user['username']]);
            }
            $pdo->commit();
            out(['ok' => true]);
        }

        $roles = sk_role_rows($pdo, true);
        $members = [];
        if (USE_MEMBERS_TABLE) {
            foreach ($pdo->query('SELECT legacy_id, status FROM members FOR UPDATE')->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $members[(string)$row['legacy_id']] = (string)$row['status'];
            }
        } else {
            $memberLock = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'kkMembers' FOR UPDATE");
            $memberList = json_decode((string)$memberLock->fetchColumn(), true);
            if (!is_array($memberList)) throw new RuntimeException('KK member data is invalid.');
            foreach ($memberList as $member) {
                if (is_array($member) && isset($member['id'])) {
                    $members[(string)$member['id']] = (string)($member['status'] ?? 'Active');
                }
            }
        }
        $allowedIds = [];
        foreach (is_array($meeting['attendees'] ?? null) ? $meeting['attendees'] : [] as $id) {
            $id = (string)$id;
            if ($id !== '' && isset($members[$id])) $allowedIds[$id] = true;
        }
        $today = date('Y-m-d');
        foreach ($roles as $role) {
            $id = (string)($role['kkId'] ?? '');
            if ($id === '' || ($role['status'] ?? '') !== 'Active' || ($members[$id] ?? 'Archived') === 'Archived'
                || (!empty($role['termStart']) && $role['termStart'] > $today)
                || (!empty($role['termEnd']) && $role['termEnd'] < $today)) continue;
            $allowedIds[$id] = true;
        }

        $records = $in['records'] ?? null;
        if (!is_array($records)) throw new RuntimeException('Attendance marks must be an object.');
        foreach ($records as $id => $mark) {
            $id = (string)$id;
            if (!isset($allowedIds[$id]) || !is_array($mark)
                || !in_array($mark['status'] ?? '', ['Present', 'Late', 'Excused', 'Absent'], true)) {
                throw new RuntimeException('Attendance can be marked only for active SK officials and invited attendees.');
            }
            $remarks = $mark['remarks'] ?? '';
            if (!is_string($remarks) || mb_strlen($remarks) > 120) {
                throw new RuntimeException('Attendance remarks must not exceed 120 characters.');
            }
            $oldMark = is_array($current[$id] ?? null) ? $current[$id] : null;
            if ($oldMark && ($oldMark['status'] ?? '') === $mark['status']
                && (string)($oldMark['remarks'] ?? '') === trim($remarks)) continue;

            $history = $oldMark && is_array($oldMark['history'] ?? null) ? $oldMark['history'] : [];
            $now = gmdate('c');
            $history[] = [
                'at' => $now,
                'by' => $user['username'],
                'action' => $oldMark ? 'Attendance corrected' : 'Attendance marked',
                'from' => $oldMark['status'] ?? null,
                'to' => $mark['status'],
                'remarks' => trim($remarks),
            ];
            $newMark = [
                'status' => $mark['status'],
                'remarks' => trim($remarks),
                'markedAt' => $now,
                'markedBy' => $user['username'],
                'history' => $history,
            ];
            $current[$id] = $newMark;
            sk_audit($pdo, $user, $id, $oldMark ? 'meeting_attendance_corrected' : 'meeting_attendance_marked', $oldMark, $newMark);
        }
        $attendance[$meetingId] = $current;
        $pdo->prepare("INSERT INTO app_data (data_key, data_value, updated_by) VALUES ('skAttendance', ?, ?)
            ON DUPLICATE KEY UPDATE data_value = VALUES(data_value), updated_by = VALUES(updated_by)")
            ->execute([json_encode($attendance, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR), $user['username']]);
        $pdo->commit();
        out(['ok' => true, 'attendance' => $current]);
    } catch (DomainException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        out(['ok' => false, 'error' => $ex->getMessage()], 409);
    } catch (RuntimeException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        out(['ok' => false, 'error' => $ex->getMessage()], 400);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Official attendance update failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not save official attendance.'], 500);
    }
}

$operation = substr($action, strlen('meeting_minutes_'));
$meetingId = (string)($in['meetingId'] ?? '');
if (!preg_match('/^[A-Za-z0-9_-]{1,80}$/', $meetingId)) {
    out(['ok' => false, 'error' => 'Choose a valid meeting.'], 400);
}
if (!meeting_minutes_role_can_manage($user['role'] ?? '')) {
    out(['ok' => false, 'error' => 'Only the SK Secretary or SK Chairman can manage meeting minutes.'], 403);
}

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

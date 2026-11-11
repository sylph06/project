<?php
/* =========================================================
   api/storage.php — generic set/remove for app_data
   ========================================================= */
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$user = require_officer();

function generic_storage_key_is_allowed($key, $action, $user) {
    $allowedKeys = [
        'skAnnouncements', 'skMeetings', 'skAttendance', 'skUnlinked',
        'kkEditRequests', 'kkPendingRegistrations', 'skTransparencyBudget',
        'skTransparencyExpenses', 'skEventRegistrations',
    ];
    if (!in_array($key, $allowedKeys, true)) {
        out(['ok' => false, 'error' => 'This data key cannot be ' . $action . ' through the generic storage endpoint.'], 403);
    }

    if (in_array($key, ['kkEditRequests', 'kkPendingRegistrations'], true)
        && !in_array($user['role'] ?? '', MEMBER_DATA_ADMIN_ROLES, true)) {
        out(['ok' => false, 'error' => 'Only the Chairman or Secretary can change member registration data.'], 403);
    }
}

if (!in_array($action, ['set', 'remove'], true)) {
    out(['ok' => false, 'error' => 'Unknown action.'], 400);
}

if ($action === 'set') {
    check_key($key);
    if ($key === 'skRoles') out(['ok' => false, 'error' => 'Use the dedicated SK role actions.'], 403);
    if ($key === 'skEventAttendance') out(['ok' => false, 'error' => 'Use the dedicated event attendance actions.'], 403);
    if ($key === 'skCommittees') out(['ok' => false, 'error' => 'Use the dedicated committee actions.'], 403);
    if ($key === 'skAttendance') out(['ok' => false, 'error' => 'Use the dedicated meeting attendance actions.'], 403);
    if ($key === 'kkMembers') out(['ok' => false, 'error' => 'Use the atomic member-data action to save KK member records.'], 403);
    if (!can_write_app_data()) out(['ok' => false, 'error' => 'Your account role cannot make this change.'], 403);
    generic_storage_key_is_allowed($key, 'written', $user);
    $value = $in['value'] ?? null;
    if (!is_string($value)) out(['ok' => false, 'error' => 'Bad value.'], 400);
    $decoded = json_decode($value, true);
    if (json_last_error() !== JSON_ERROR_NONE) out(['ok' => false, 'error' => 'Data is not valid JSON.'], 400);
    $expected = $in['expected'] ?? null;
    if ($expected !== null && !is_string($expected)) {
        out(['ok' => false, 'error' => 'The original data is invalid. Reload the page and try again.'], 400);
    }
    if ($key === 'skTransparencyBudget' || $key === 'skTransparencyExpenses') {
        if ($key === 'skTransparencyBudget') {
            if (!is_array($decoded)) out(['ok' => false, 'error' => 'Budget data is invalid.'], 400);
            $amount = $decoded['amount'] ?? null;
            $fy = $decoded['fy'] ?? null;
            $allocations = $decoded['allocations'] ?? [];
            if (!is_array($decoded) || !is_string($fy) || !preg_match('/^\d{4}-\d{4}$/', $fy)
                || !is_numeric($amount) || (float)$amount < 0 || !is_array($allocations)) {
                out(['ok' => false, 'error' => 'Budget data is invalid.'], 400);
            }
            $allocated = 0.0;
            $categories = [
                'Health', 'Education', 'Economic Empowerment', 'Social Inclusion and Equity',
                'Peace-Building and Security', 'Governance', 'Active Citizenship', 'Environment', 'Global Mobility', 'Agriculture',
            ];
            foreach ($allocations as $category => $allocation) {
                if (!is_string($category) || !in_array($category, $categories, true)
                    || !is_numeric($allocation) || (float)$allocation < 0) {
                    out(['ok' => false, 'error' => 'COP allocation data is invalid.'], 400);
                }
                $allocated += (float)$allocation;
            }
            if ($allocated > (float)$amount + 0.005) {
                out(['ok' => false, 'error' => 'COP allocations cannot exceed the total budget.'], 400);
            }
        } elseif (!is_array($decoded) || ($decoded !== [] && array_keys($decoded) !== range(0, count($decoded) - 1))) {
            out(['ok' => false, 'error' => 'Expense data must be a list.'], 400);
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT data_value FROM app_data WHERE data_key = ? FOR UPDATE');
            $lock->execute([$key]);
            $oldRaw = $lock->fetchColumn();
            $oldRaw = $oldRaw === false ? null : (string)$oldRaw;
            if ($expected !== $oldRaw) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'Transparency data changed in another session. Reload and try again.'], 409);
            }
            $canonical = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
            $save = $pdo->prepare('INSERT INTO app_data (data_key, data_value, updated_by) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE data_value = VALUES(data_value), updated_by = VALUES(updated_by)');
            $save->execute([$key, $canonical, $user['username']]);
            $old = $oldRaw === null ? null : json_decode($oldRaw, true);
            sk_audit($pdo, $user, null, $key === 'skTransparencyBudget' ? 'transparency_budget_updated' : 'transparency_expenses_updated', $old, $decoded);
            $pdo->commit();
            out(['ok' => true, 'value' => $canonical]);
        } catch (DomainException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            out(['ok' => false, 'error' => $ex->getMessage()], 409);
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Transparency data write failed: ' . $ex->getMessage());
            out(['ok' => false, 'error' => 'Could not save transparency data.'], 500);
        }
    }
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
            $raw = $raw === false ? null : (string)$raw;
            if ($expected !== $raw) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'Meeting data changed in another session. Reload and try again.'], 409);
            }
            $existing = $raw === null ? [] : json_decode($raw, true);
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
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare('SELECT data_value FROM app_data WHERE data_key = ? FOR UPDATE');
        $lock->execute([$key]);
        $raw = $lock->fetchColumn();
        $raw = $raw === false ? null : (string)$raw;
        if ($expected !== $raw) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'This data changed in another session. Reload and try again.'], 409);
        }
        $save = $pdo->prepare('INSERT INTO app_data (data_key, data_value, updated_by) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE data_value = VALUES(data_value), updated_by = VALUES(updated_by)');
        $save->execute([$key, $value, $user['username']]);
        $pdo->commit();
        out(['ok' => true, 'value' => $value]);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Application data write failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not save this data.'], 500);
    }
}

if ($action === 'remove') {
    check_key($key);
    if ($key === 'skRoles') out(['ok' => false, 'error' => 'Use the dedicated SK role actions.'], 403);
    if ($key === 'kkMembers') out(['ok' => false, 'error' => 'KK member records cannot be removed through generic storage.'], 403);
    if ($key === 'skEventAttendance') out(['ok' => false, 'error' => 'Use the dedicated event attendance actions.'], 403);
    if ($key === 'skCommittees') out(['ok' => false, 'error' => 'Use the dedicated committee actions.'], 403);
    if ($key === 'skAttendance') out(['ok' => false, 'error' => 'Use the dedicated meeting attendance actions.'], 403);
    if ($key === 'skTransparencyBudget' || $key === 'skTransparencyExpenses') {
        out(['ok' => false, 'error' => 'Transparency records cannot be removed through generic storage.'], 403);
    }
    if ($key === 'skMeetings') out(['ok' => false, 'error' => 'Meeting records cannot be removed through the generic storage endpoint.'], 403);
    if (!can_write_app_data()) out(['ok' => false, 'error' => 'Your account role cannot make this change.'], 403);
    generic_storage_key_is_allowed($key, 'removed', $user);
    $expected = $in['expected'] ?? null;
    if ($expected !== null && !is_string($expected)) {
        out(['ok' => false, 'error' => 'The original data is invalid. Reload the page and try again.'], 400);
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare('SELECT data_value FROM app_data WHERE data_key = ? FOR UPDATE');
        $lock->execute([$key]);
        $raw = $lock->fetchColumn();
        $raw = $raw === false ? null : (string)$raw;
        if ($expected !== $raw) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'This data changed in another session. Reload and try again.'], 409);
        }
        $pdo->prepare('DELETE FROM app_data WHERE data_key = ?')->execute([$key]);
        $pdo->commit();
        out(['ok' => true]);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Application data removal failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not remove this data.'], 500);
    }
}

out(['ok' => false, 'error' => 'Unknown action.'], 400);

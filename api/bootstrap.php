<?php
/* =========================================================
   api/bootstrap.php — shared setup for all API modules
   ========================================================= */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../members_table.php';
require_once __DIR__ . '/../role_store.php';
require_once __DIR__ . '/../meeting_minutes.php';
header('Content-Type: application/json; charset=utf-8');

function out($arr, $code = 200) {
    if ($code === 200 && is_array($arr) && ($arr['ok'] ?? false) === true) {
        try {
            audit_successful_api_action();
        } catch (Throwable $error) {
            error_log('Successful API action could not be audited: ' . $error->getMessage());
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'The action completed, but its audit entry could not be saved. Do not retry; contact an administrator.',
            ]);
            exit;
        }
    }
    http_response_code($code);
    echo json_encode($arr);
    exit;
}

function audit_successful_api_action() {
    $actions = [
        'login', 'password',
        'event_register', 'event_interest', 'event_attendance', 'event_walkin',
        'event_walkin_remove', 'event_walkin_restore', 'event_attendance_qr',
        'meeting_minutes_save', 'meeting_minutes_approve', 'meeting_minutes_reopen',
        'meeting_attendance_save', 'meeting_attendance_delete',
        'role_assign', 'role_update', 'role_end', 'role_archive', 'role_archive_expired',
        'committee_save', 'committee_chairs_save',
        'accounts_unlinked', 'account_link',
        'account_create', 'account_reset',
        'account_reset_member', 'account_reset_official', 'account_username_member', 'account_deactivate',
        'account_google_unbind', 'account_google_clear_email',
        'permission_grant', 'permission_revoke',
        'member_add', 'member_update', 'member_delete', 'member_restore', 'member_register',
        'member_data_save', 'member_portal_data', 'member_portal_photo',
        'kk_id_token', 'kk_id_print', 'kk_id_prints', 'kk_id_scan',
        'set', 'remove', 'profile_files', 'receipt_upload',
    ];
    $action = (string)($_GET['action'] ?? '');
    if (!in_array($action, $actions, true)) return;

    $actor = $GLOBALS['user'] ?? current_user();
    if (!is_array($actor) || !in_array($actor['role'] ?? '', APP_WRITE_ROLES, true)) return;
    if (!empty($GLOBALS['SK_AUDIT_RECORD_WRITTEN'])) return;

    $request = is_array($GLOBALS['in'] ?? null) ? $GLOBALS['in'] : [];
    $target = null;
    if ($action === 'set' || $action === 'remove') {
        $target = isset($request['key']) && is_string($request['key']) ? $request['key'] : null;
    } else {
        foreach (['memberId', 'legacyId', 'kkId', 'meetingId', 'eventId', 'registrationId', 'accountId', 'id'] as $field) {
            if (isset($request[$field]) && is_scalar($request[$field])) {
                $candidate = (string)$request[$field];
                if ($candidate !== '' && strlen($candidate) <= 100) {
                    $target = $candidate;
                    break;
                }
            }
        }
    }

    $details = ['api_action' => $action];
    if (($action === 'set' || $action === 'remove') && $target !== null) {
        $details['resource_key'] = $target;
    }
    sk_audit(db(), $actor, $target, 'api_' . $action, null, $details);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['ok' => false, 'error' => 'POST only.'], 405);

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) $in = [];
if (!hash_equals(csrf_token(), (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    out(['ok' => false, 'error' => 'Your session token is missing or expired. Reload the page and try again.'], 403);
}

function enforce_submission_limit($action, $limit) {
    $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS submission_attempts (
        action_name VARCHAR(32) NOT NULL,
        ip VARCHAR(45) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
        attempts INT UNSIGNED NOT NULL DEFAULT 0,
        window_started DATETIME NOT NULL,
        PRIMARY KEY (action_name, ip)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->prepare('INSERT INTO submission_attempts (action_name, ip, attempts, window_started)
                   VALUES (?, ?, 1, NOW())
                   ON DUPLICATE KEY UPDATE
                     attempts = IF(window_started < NOW() - INTERVAL 1 HOUR, 1, attempts + 1),
                     window_started = IF(window_started < NOW() - INTERVAL 1 HOUR, NOW(), window_started)')
        ->execute([$action, $ip]);
    $st = $pdo->prepare('SELECT attempts FROM submission_attempts WHERE action_name = ? AND ip = ?');
    $st->execute([$action, $ip]);
    if ((int)$st->fetchColumn() > $limit) {
        out(['ok' => false, 'error' => 'Too many submissions. Please try again later.'], 429);
    }
}

$key = (string)($in['key'] ?? '');
function check_key($key) {
    if (!preg_match('/^[A-Za-z0-9_]{1,100}$/', $key)) out(['ok' => false, 'error' => 'Bad data name.'], 400);
}

/**
 * Require a logged-in officer. Returns the current user or exits with 401/403.
 */
function require_officer() {
    $user = current_user();
    if (!$user) {
        if (is_public()) out(['ok' => false, 'error' => 'View only. Only officials can change data.'], 403);
        out(['ok' => false, 'error' => 'Please log in again.'], 401);
    }
    if (!empty($user['must_change_password'])) {
        out(['ok' => false, 'error' => 'Change your temporary password before continuing.'], 403);
    }
    return $user;
}

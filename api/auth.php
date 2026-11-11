<?php
/* =========================================================
   api/auth.php — login and password change
   ========================================================= */
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';

/* ---------- login ---------- */
if ($action === 'login') {
    $username = trim((string)($in['username'] ?? ''));
    $password = (string)($in['password'] ?? '');
    $userColumns = db()->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS
                                  WHERE TABLE_SCHEMA = DATABASE()
                                    AND TABLE_NAME = 'users'
                                    AND COLUMN_NAME IN ('password', 'last_login')");
    $userColumns->execute();
    $userColumns = $userColumns->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('password', $userColumns, true)) {
        out(['ok' => false, 'error' => 'The account database needs an administrator migration before login is available.'], 503);
    }
    $hasLastLogin = in_array('last_login', $userColumns, true);

    /* Failed attempts are tracked per username and IP so a shared office IP does not lock out other users. */
    $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
    $usernameKey = hash('sha256', mb_strtolower($username, 'UTF-8'));
    db()->exec('CREATE TABLE IF NOT EXISTS login_attempts_v2 (
        ip VARCHAR(45) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
        username_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
        failures INT UNSIGNED NOT NULL DEFAULT 0,
        window_started DATETIME NOT NULL,
        locked_until DATETIME NULL,
        PRIMARY KEY (ip, username_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $st = db()->prepare('SELECT COALESCE(TIMESTAMPDIFF(SECOND, NOW(), locked_until), 0)
                         FROM login_attempts_v2 WHERE ip = ? AND username_key = ?');
    $st->execute([$ip, $usernameKey]);
    $wait = (int)$st->fetchColumn();
    if ($wait > 0) {
        header('Retry-After: ' . $wait);
        out(['ok' => false, 'error' => 'Too many wrong attempts. Please wait ' . $wait . ' seconds.', 'retryAfter' => $wait], 429);
    }

    $st = db()->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1');
    $st->execute([$username]);
    $u = $st->fetch();
    $matches = $u && password_verify($password, (string)$u['password']);
    if (!$matches) {
        db()->prepare('INSERT INTO login_attempts_v2 (ip, username_key, failures, window_started)
                       VALUES (?, ?, 1, NOW())
                       ON DUPLICATE KEY UPDATE
                         locked_until = IF(window_started < NOW() - INTERVAL 15 MINUTE, NULL, locked_until),
                         failures = IF(window_started < NOW() - INTERVAL 15 MINUTE, 1, failures + 1),
                         window_started = IF(window_started < NOW() - INTERVAL 15 MINUTE, NOW(), window_started)')
            ->execute([$ip, $usernameKey]);
        $st = db()->prepare('SELECT failures FROM login_attempts_v2 WHERE ip = ? AND username_key = ?');
        $st->execute([$ip, $usernameKey]);
        $failures = (int)$st->fetchColumn();
        $lockSeconds = $failures >= 7 ? 900 : ($failures >= 5 ? 300 : ($failures >= 3 ? 60 : 0));
        if ($lockSeconds > 0) {
            db()->prepare('UPDATE login_attempts_v2 SET locked_until = NOW() + INTERVAL ' . $lockSeconds . ' SECOND
                           WHERE ip = ? AND username_key = ?')->execute([$ip, $usernameKey]);
            header('Retry-After: ' . $lockSeconds);
            out(['ok' => false, 'error' => 'Too many wrong attempts. Please wait ' . $lockSeconds . ' seconds.', 'retryAfter' => $lockSeconds], 429);
        }
        usleep(600000);
        out(['ok' => false, 'error' => 'Wrong username or password.', 'triesLeft' => 3 - $failures], 401);
    }
    db()->prepare('DELETE FROM login_attempts_v2 WHERE ip = ? AND username_key = ?')->execute([$ip, $usernameKey]);
    session_regenerate_id(true);
    unset($_SESSION['public']);
    $_SESSION['user'] = ['id' => (int)$u['id']];
    if ($hasLastLogin) {
        db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$u['id']]);
    }
    $user = current_user();
    out(['ok' => true, 'user' => $user, 'forcePasswordChange' => $user['must_change_password']]);
}

/* ---------- password change ---------- */
if ($action === 'password') {
    $user = current_user();
    if (!$user) out(['ok' => false, 'error' => 'Please log in again.'], 401);

    $cur = (string)($in['current'] ?? '');
    $new = (string)($in['new'] ?? '');
    if (strlen($new) < 8 || strlen($new) > 100) out(['ok' => false, 'error' => 'The new password must be 8 to 100 characters.'], 400);
    $st = db()->prepare('SELECT password FROM users WHERE id = ?');
    $st->execute([$user['id']]);
    $stored = (string)$st->fetchColumn();
    if (!password_verify($cur, $stored)) { usleep(600000); out(['ok' => false, 'error' => 'Your current password is wrong.'], 403); }
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

out(['ok' => false, 'error' => 'Unknown action.'], 400);

<?php
/* =========================================================
   api/auth.php — login and password change
   ========================================================= */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../google_auth.php';

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
    $googleLoginStatus = google_auth_password_login_status(db(), $u);
    if ($googleLoginStatus === 'setup_missing') {
        out(['ok' => false, 'error' => 'Google sign-in is required for KK members, but its database setup is incomplete. Contact an administrator.'], 503);
    }
    if ($googleLoginStatus === 'google_required') {
        out(['ok' => false, 'error' => 'This KK account must use Google sign-in. Please continue with Google.'], 403);
    }
    db()->prepare('DELETE FROM login_attempts_v2 WHERE ip = ? AND username_key = ?')->execute([$ip, $usernameKey]);
    session_regenerate_id(true);
    unset($_SESSION['public']);
    $_SESSION['user'] = ['id' => (int)$u['id']];
    if ($hasLastLogin) {
        db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$u['id']]);
    }
    $noticeTable = db()->prepare("SELECT COUNT(*) FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_password_reset_notices'");
    $noticeTable->execute();
    $resetNotice = null;
    if ((int)$noticeTable->fetchColumn() === 1) {
        $noticeQuery = db()->prepare('SELECT reset_by_name, reset_at FROM user_password_reset_notices WHERE user_id = ?');
        $noticeQuery->execute([(int)$u['id']]);
        $resetNotice = $noticeQuery->fetch(PDO::FETCH_ASSOC);
    }
    if ($resetNotice) {
        db()->prepare('DELETE FROM user_password_reset_notices WHERE user_id = ?')->execute([(int)$u['id']]);
        $_SESSION['password_reset_notice'] = 'Your password was reset by ' . $resetNotice['reset_by_name']
            . ' on ' . $resetNotice['reset_at'] . '.';
    }
    $user = current_user();
    $passwordResetNotice = $_SESSION['password_reset_notice'] ?? null;
    if (empty($user['must_change_password'])) unset($_SESSION['password_reset_notice']);
    out([
        'ok' => true,
        'user' => $user,
        'forcePasswordChange' => $user['must_change_password'],
        'passwordResetNotice' => $passwordResetNotice,
    ]);
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

if ($action === 'official_recovery_code_reset') {
    $username = trim((string)($in['username'] ?? ''));
    $recoveryCode = (string)($in['recoveryCode'] ?? '');
    $new = (string)($in['new'] ?? '');
    $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
    $usernameKey = hash('sha256', 'official-recovery:' . mb_strtolower($username, 'UTF-8'));
    $pdo = db();
    $pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts_v2 (
        ip VARCHAR(45) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
        username_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
        failures INT UNSIGNED NOT NULL DEFAULT 0,
        window_started DATETIME NOT NULL,
        locked_until DATETIME NULL,
        PRIMARY KEY (ip, username_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $lockQuery = $pdo->prepare('SELECT COALESCE(TIMESTAMPDIFF(SECOND, NOW(), locked_until), 0)
        FROM login_attempts_v2 WHERE ip = ? AND username_key = ?');
    $lockQuery->execute([$ip, $usernameKey]);
    $wait = (int)$lockQuery->fetchColumn();
    if ($wait > 0) {
        header('Retry-After: ' . $wait);
        out(['ok' => false, 'error' => 'Recovery could not be completed. Try again later or contact the SK administrator.'], 429);
    }
    if (strlen($username) > 100 || strlen($recoveryCode) > 100 || strlen($new) < 8 || strlen($new) > 100) {
        out(['ok' => false, 'error' => 'Recovery could not be completed. Check the recovery details or contact the SK administrator.'], 400);
    }
    $recoveryTable = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'official_recovery_codes'");
    $recoveryTable->execute();
    if ((int)$recoveryTable->fetchColumn() !== 1) {
        out(['ok' => false, 'error' => 'Emergency recovery is not configured. Contact the SK administrator.'], 503);
    }

    $pdo->beginTransaction();
    try {
        $codeQuery = $pdo->prepare('SELECT code_hash FROM official_recovery_codes WHERE id = 1 AND used_at IS NULL FOR UPDATE');
        $codeQuery->execute();
        $storedHash = $codeQuery->fetchColumn();
        $candidateHash = hash('sha256', $recoveryCode);
        $validCode = is_string($storedHash)
            && hash_equals((string)$storedHash, $candidateHash);
        $accountQuery = $pdo->prepare('SELECT id, username, role, is_active, legacy_id
            FROM users WHERE username = ? AND is_active = 1 FOR UPDATE');
        $accountQuery->execute([$username]);
        $account = $accountQuery->fetch(PDO::FETCH_ASSOC);
        $isOfficial = $account && in_array(kk_effective_role($account), [
            'SK Chairman', 'SK Chairperson', 'SK Secretary',
        ], true);
        if (!$validCode || !$isOfficial) {
            $pdo->rollBack();
            $pdo->prepare('INSERT INTO login_attempts_v2 (ip, username_key, failures, window_started)
                VALUES (?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                  locked_until = IF(window_started < NOW() - INTERVAL 15 MINUTE, NULL, locked_until),
                  failures = IF(window_started < NOW() - INTERVAL 15 MINUTE, 1, failures + 1),
                  window_started = IF(window_started < NOW() - INTERVAL 15 MINUTE, NOW(), window_started)')
                ->execute([$ip, $usernameKey]);
            $failuresQuery = $pdo->prepare('SELECT failures FROM login_attempts_v2 WHERE ip = ? AND username_key = ?');
            $failuresQuery->execute([$ip, $usernameKey]);
            $failures = (int)$failuresQuery->fetchColumn();
            $lockSeconds = $failures >= 7 ? 900 : ($failures >= 5 ? 300 : ($failures >= 3 ? 60 : 0));
            if ($lockSeconds > 0) {
                $pdo->prepare('UPDATE login_attempts_v2 SET locked_until = NOW() + INTERVAL ' . $lockSeconds . ' SECOND
                    WHERE ip = ? AND username_key = ?')->execute([$ip, $usernameKey]);
            }
            out(['ok' => false, 'error' => 'Recovery could not be completed. Check the recovery details or contact the SK administrator.'], 400);
        }
        $pdo->prepare('UPDATE users SET password = ?, must_change_password = 1 WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), (int)$account['id']]);
        $pdo->prepare('UPDATE official_recovery_codes SET used_at = NOW() WHERE id = 1 AND used_at IS NULL')->execute();
        $systemActor = ['id' => null, 'username' => 'Emergency recovery code'];
        sk_audit($pdo, $systemActor, $account['legacy_id'], 'official_password_reset_emergency', null, [
            'account_id' => (int)$account['id'],
            'must_change_password' => true,
        ]);
        $pdo->commit();
        $pdo->prepare('DELETE FROM login_attempts_v2 WHERE ip = ? AND username_key = ?')->execute([$ip, $usernameKey]);
        out(['ok' => true, 'message' => 'Password reset. Sign in with the new password and change it again when prompted.']);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Official emergency recovery failed: ' . $exception->getMessage());
        out(['ok' => false, 'error' => 'Recovery could not be completed. Check the recovery details or contact the SK administrator.'], 400);
    }
}

if ($action === 'password_google_set') {
    $user = current_user();
    $authenticatedAt = (int)($_SESSION['google_authenticated_at'] ?? 0);
    if (!$user || ($user['role'] ?? '') !== 'KK Member'
        || (int)($_SESSION['google_authenticated_user_id'] ?? 0) !== (int)$user['id']
        || $authenticatedAt < time() - 600 || $authenticatedAt > time() + 60) {
        out(['ok' => false, 'error' => 'Sign in with your bound Google account again to set a password.'], 403);
    }
    $new = (string)($in['new'] ?? '');
    if (strlen($new) < 8 || strlen($new) > 100) {
        out(['ok' => false, 'error' => 'The new password must be 8 to 100 characters.'], 400);
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $accountQuery = $pdo->prepare('SELECT google_sub, legacy_id, is_active FROM users WHERE id = ? FOR UPDATE');
        $accountQuery->execute([(int)$user['id']]);
        $account = $accountQuery->fetch(PDO::FETCH_ASSOC);
        if (!$account || !(int)$account['is_active'] || empty($account['google_sub'])
            || (string)$account['legacy_id'] !== (string)$user['legacy_id']
            || kk_effective_role($account + ['id' => (int)$user['id']]) !== 'KK Member') {
            throw new DomainException('A bound Google account is required to set a password this way.');
        }
        $pdo->prepare('UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), (int)$user['id']]);
        sk_audit($pdo, $user, $user['legacy_id'], 'account_password_set_after_google_login',
            ['must_change_password' => (bool)$user['must_change_password']],
            ['must_change_password' => false, 'provider' => 'Google']);
        $pdo->commit();
        unset($_SESSION['google_authenticated_at'], $_SESSION['google_authenticated_user_id']);
        unset($_SESSION['password_reset_notice']);
        out(['ok' => true]);
    } catch (DomainException $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        out(['ok' => false, 'error' => $exception->getMessage()], 403);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Google-authenticated password update failed: ' . $exception->getMessage());
        out(['ok' => false, 'error' => 'Could not update the password.'], 500);
    }
}

out(['ok' => false, 'error' => 'Unknown action.'], 400);

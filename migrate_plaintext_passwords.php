<?php
require_once __DIR__ . '/config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$pdo = db();
$pdo->beginTransaction();
try {
    $users = $pdo->query('SELECT id, password FROM users FOR UPDATE')->fetchAll(PDO::FETCH_ASSOC);
    $reset = $pdo->prepare('UPDATE users SET password = ?, must_change_password = 1 WHERE id = ?');
    $count = 0;
    foreach ($users as $user) {
        if (password_get_info((string)$user['password'])['algo'] !== null) continue;
        $reset->execute([password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), $user['id']]);
        $count++;
    }
    $pdo->commit();
    fwrite(STDOUT, "Invalidated {$count} plaintext password(s); an administrator must issue new temporary passwords.\n");
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Plaintext password migration failed: ' . $ex->getMessage());
    fwrite(STDERR, "Migration failed. No password changes were committed.\n");
    exit(1);
}

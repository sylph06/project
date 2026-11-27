<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Run this migration from the command line.');
}

require_once __DIR__ . '/config.php';

try {
    $pdo = db();
    $statement = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email'");
    $statement->execute();
    if ((int)$statement->fetchColumn() === 0) {
        $pdo->exec('ALTER TABLE users ADD COLUMN email VARCHAR(254) NULL');
        fwrite(STDOUT, "Added nullable users.email column.\n");
    }

    $duplicates = $pdo->query("SELECT COUNT(*) FROM (
        SELECT LOWER(email) AS normalized_email FROM users
        WHERE email IS NOT NULL
        GROUP BY LOWER(email) HAVING COUNT(*) > 1
    ) duplicate_emails")->fetchColumn();
    if ((int)$duplicates > 0) {
        throw new RuntimeException('Resolve duplicate user email addresses before adding the unique index.');
    }

    $uniqueIndex = $pdo->query("SELECT COUNT(*) FROM (
        SELECT INDEX_NAME FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
          AND NON_UNIQUE = 0
        GROUP BY INDEX_NAME HAVING COUNT(*) = 1 AND MAX(COLUMN_NAME = 'email') = 1
    ) single_column_email_indexes")->fetchColumn();
    if ((int)$uniqueIndex === 0) {
        $namedIndex = $pdo->query("SELECT COUNT(*) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_users_email'")->fetchColumn();
        if ((int)$namedIndex > 0) {
            throw new RuntimeException('The uq_users_email index exists but is not unique. Resolve it manually before continuing.');
        }
        $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uq_users_email (email)');
        fwrite(STDOUT, "Added unique users.email index.\n");
    } else {
        fwrite(STDOUT, "A unique users.email index already exists.\n");
    }
    fwrite(STDOUT, "No user accounts were changed.\n");
} catch (Throwable $error) {
    error_log('Admin email schema migration failed: ' . $error->getMessage());
    fwrite(STDERR, "Could not add users.email. Check the application error log.\n");
    exit(1);
}

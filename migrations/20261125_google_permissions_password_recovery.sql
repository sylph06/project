CREATE TABLE IF NOT EXISTS user_permissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    member_id VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    permission VARCHAR(64) NOT NULL,
    granted_by BIGINT UNSIGNED NOT NULL,
    granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    revoked_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY ix_user_permissions_member_active (member_id, permission, revoked_at, expires_at),
    KEY ix_user_permissions_granted_by (granted_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_password_reset_tokens_hash (token_hash),
    KEY ix_password_reset_tokens_user (user_id, expires_at, used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_password_reset_notices (
    user_id BIGINT UNSIGNED NOT NULL,
    reset_by_name VARCHAR(120) NOT NULL,
    reset_at DATETIME NOT NULL,
    PRIMARY KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS official_recovery_codes (
    id TINYINT UNSIGNED NOT NULL,
    code_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    generated_by BIGINT UNSIGNED NOT NULL,
    generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    used_at DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @google_sub_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'google_sub'
);
SET @google_sub_ddl = IF(
    @google_sub_exists = 0,
    'ALTER TABLE users ADD COLUMN google_sub VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL',
    'SELECT 1'
);
PREPARE google_sub_stmt FROM @google_sub_ddl;
EXECUTE google_sub_stmt;
DEALLOCATE PREPARE google_sub_stmt;

SET @google_links_exists = (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'google_account_links'
);
SET @google_sub_backfill = IF(
    @google_links_exists = 1,
    'UPDATE users AS u JOIN google_account_links AS g ON g.user_id = u.id
     SET u.google_sub = g.google_subject
     WHERE u.google_sub IS NULL AND g.google_subject <> ''''',
    'SELECT 1'
);
PREPARE google_sub_backfill_stmt FROM @google_sub_backfill;
EXECUTE google_sub_backfill_stmt;
DEALLOCATE PREPARE google_sub_backfill_stmt;

SET @google_sub_index_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_users_google_sub'
);
SET @google_sub_index_ddl = IF(
    @google_sub_index_exists = 0,
    'ALTER TABLE users ADD UNIQUE KEY uq_users_google_sub (google_sub)',
    'SELECT 1'
);
PREPARE google_sub_index_stmt FROM @google_sub_index_ddl;
EXECUTE google_sub_index_stmt;
DEALLOCATE PREPARE google_sub_index_stmt;

SET @password_nullable = (
    SELECT IS_NULLABLE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password'
);
SET @password_ddl = IF(
    @password_nullable = 'NO',
    (
        SELECT CONCAT(
            'ALTER TABLE users MODIFY COLUMN password ',
            COLUMN_TYPE,
            IF(CHARACTER_SET_NAME IS NULL, '', CONCAT(' CHARACTER SET ', CHARACTER_SET_NAME)),
            IF(COLLATION_NAME IS NULL, '', CONCAT(' COLLATE ', COLLATION_NAME)),
            IF(COLUMN_DEFAULT IS NULL, '', CONCAT(' DEFAULT ', QUOTE(COLUMN_DEFAULT))),
            ' NULL'
        )
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password'
    ),
    'SELECT 1'
);
PREPARE password_nullable_stmt FROM @password_ddl;
EXECUTE password_nullable_stmt;
DEALLOCATE PREPARE password_nullable_stmt;

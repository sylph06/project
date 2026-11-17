<?php

function kk_id_ensure_schema($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS kk_member_id_tokens (
        member_id VARCHAR(100) NOT NULL PRIMARY KEY,
        token CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS kk_member_id_prints (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        member_id VARCHAR(100) NOT NULL,
        printed_by_user_id INT UNSIGNED NULL,
        printed_by VARCHAR(120) NOT NULL,
        print_method VARCHAR(20) NOT NULL,
        printed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_kk_id_print_member_date (member_id, printed_at),
        KEY idx_kk_id_print_actor_date (printed_by_user_id, printed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

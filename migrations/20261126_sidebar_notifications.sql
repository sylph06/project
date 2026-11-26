CREATE TABLE IF NOT EXISTS user_sidebar_last_seen (
    user_id BIGINT NOT NULL,
    page_key VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    last_seen_at DATETIME(3) NOT NULL,
    PRIMARY KEY (user_id, page_key),
    KEY ix_user_sidebar_last_seen_page (page_key, last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

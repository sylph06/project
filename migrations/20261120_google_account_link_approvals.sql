CREATE TABLE google_account_link_requests (
    request_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    legacy_id VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    google_subject VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    google_email VARCHAR(254) NOT NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (request_id),
    UNIQUE KEY uq_google_link_requests_user (user_id),
    UNIQUE KEY uq_google_link_requests_member (legacy_id),
    UNIQUE KEY uq_google_link_requests_subject (google_subject),
    KEY ix_google_link_requests_requested_at (requested_at),
    CONSTRAINT fk_google_link_requests_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_google_link_requests_requester
        FOREIGN KEY (requested_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

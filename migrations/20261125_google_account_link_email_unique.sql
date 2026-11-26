SET @google_email_index_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'google_account_links'
      AND INDEX_NAME = 'uq_google_account_links_email'
);
SET @google_email_index_ddl = IF(
    @google_email_index_exists = 0,
    'ALTER TABLE google_account_links ADD UNIQUE KEY uq_google_account_links_email (google_email)',
    'SELECT 1'
);
PREPARE google_email_index_stmt FROM @google_email_index_ddl;
EXECUTE google_email_index_stmt;
DEALLOCATE PREPARE google_email_index_stmt;

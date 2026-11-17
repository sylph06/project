ALTER TABLE app_data
    MODIFY COLUMN data_value LONGTEXT
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci
    NOT NULL;

ALTER TABLE audit_log
    ADD COLUMN user_agent VARCHAR(512) NULL AFTER new_value;

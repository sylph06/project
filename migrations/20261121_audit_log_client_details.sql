ALTER TABLE audit_log
    ADD COLUMN device_type VARCHAR(20) NULL AFTER user_agent,
    ADD COLUMN browser VARCHAR(80) NULL AFTER device_type,
    ADD COLUMN ip_address VARCHAR(45) NULL AFTER browser,
    ADD COLUMN location VARCHAR(255) NULL AFTER ip_address;

-- Stage 7 additive migration for existing Stage 6 databases.
-- Run once as a database migration administrator against the configured database.
-- This adds persistent registration-attempt storage; no existing table or data is changed.

CREATE TABLE IF NOT EXISTS registration_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    outcome ENUM('submitted','rate_limited') NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_registration_attempts_ip_created (ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

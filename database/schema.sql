CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    status ENUM('active','suspended','closed') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    last_login_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_profiles (
    user_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    phone VARCHAR(20) NULL,
    contact_details VARCHAR(255) NULL,
    anonymised_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_customer_profiles_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE account_status_events (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    action ENUM('suspend','reactivate','close_request','anonymise') NOT NULL,
    reason_category VARCHAR(40) NOT NULL,
    reason_text VARCHAR(500) NULL,
    acted_by_user_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_account_status_events_user_id (user_id, id),
    CONSTRAINT fk_account_status_events_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_account_status_events_actor FOREIGN KEY (acted_by_user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NOT NULL,
    succeeded TINYINT(1) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_login_attempts_ip_created (ip_address, created_at),
    KEY idx_login_attempts_user_created (user_id, created_at),
    CONSTRAINT fk_login_attempts_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sku VARCHAR(40) NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    default_points INT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL,
    created_by_user_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_sku (sku),
    CONSTRAINT fk_products_created_by FOREIGN KEY (created_by_user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE batches (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    batch_code VARCHAR(30) NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    points_per_code INT UNSIGNED NOT NULL,
    planned_quantity INT UNSIGNED NOT NULL,
    generated_count INT UNSIGNED NULL,
    status ENUM('draft','generated','exported','printed','active','voided') NOT NULL DEFAULT 'draft',
    created_by_user_id INT UNSIGNED NOT NULL,
    generated_at DATETIME NULL,
    exported_at DATETIME NULL,
    printed_at DATETIME NULL,
    activated_at DATETIME NULL,
    voided_at DATETIME NULL,
    activated_by_user_id INT UNSIGNED NULL,
    voided_by_user_id INT UNSIGNED NULL,
    void_reason VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_batches_batch_code (batch_code),
    KEY idx_batches_status (status),
    KEY idx_batches_product_id (product_id),
    CONSTRAINT fk_batches_product FOREIGN KEY (product_id)
        REFERENCES products (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_batches_created_by FOREIGN KEY (created_by_user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_batches_activated_by FOREIGN KEY (activated_by_user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_batches_voided_by FOREIGN KEY (voided_by_user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hmac_key_registry (
    key_id SMALLINT UNSIGNED NOT NULL,
    label VARCHAR(60) NOT NULL,
    algorithm VARCHAR(20) NOT NULL,
    status ENUM('active','retired','revoked') NOT NULL,
    key_check_value BINARY(32) NOT NULL,
    created_at DATETIME NOT NULL,
    activated_at DATETIME NULL,
    retired_at DATETIME NULL,
    revoked_at DATETIME NULL,
    notes VARCHAR(255) NULL,
    PRIMARY KEY (key_id),
    UNIQUE KEY uq_hmac_key_registry_label (label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE codes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    batch_id INT UNSIGNED NOT NULL,
    key_id SMALLINT UNSIGNED NOT NULL,
    code_hash BINARY(32) NOT NULL,
    status ENUM('unclaimed','claimed','voided') NOT NULL DEFAULT 'unclaimed',
    claimed_by_user_id INT UNSIGNED NULL,
    claimed_at DATETIME NULL,
    voided_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_codes_code_hash (code_hash),
    KEY idx_codes_batch_status (batch_id, status),
    KEY idx_codes_claimed_by_user (claimed_by_user_id),
    KEY idx_codes_key_id (key_id),
    CONSTRAINT fk_codes_batch FOREIGN KEY (batch_id)
        REFERENCES batches (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_codes_key_registry FOREIGN KEY (key_id)
        REFERENCES hmac_key_registry (key_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_codes_claimed_by FOREIGN KEY (claimed_by_user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE code_exports (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    batch_id INT UNSIGNED NOT NULL,
    exported_by_user_id INT UNSIGNED NOT NULL,
    code_count INT UNSIGNED NOT NULL,
    status ENUM('recorded','failed') NOT NULL,
    ip_address VARCHAR(45) NULL,
    correlation_id VARCHAR(36) NULL,
    exported_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_code_exports_batch (batch_id),
    CONSTRAINT fk_code_exports_batch FOREIGN KEY (batch_id)
        REFERENCES batches (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_code_exports_user FOREIGN KEY (exported_by_user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE claim_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    outcome ENUM('success','invalid_format','not_found','already_claimed','batch_inactive','code_voided','rate_limited') NOT NULL,
    code_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_claim_attempts_user_created (user_id, created_at),
    KEY idx_claim_attempts_ip_created (ip_address, created_at),
    KEY idx_claim_attempts_code_id (code_id),
    CONSTRAINT fk_claim_attempts_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_claim_attempts_code FOREIGN KEY (code_id)
        REFERENCES codes (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rewards (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    points_cost INT UNSIGNED NOT NULL,
    stock_quantity INT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_rewards_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE redemptions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    reward_id INT UNSIGNED NOT NULL,
    points_cost INT UNSIGNED NOT NULL,
    status ENUM('pending','fulfilled','cancelled') NOT NULL DEFAULT 'pending',
    idempotency_token CHAR(32) NOT NULL,
    requested_at DATETIME NOT NULL,
    fulfilled_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    fulfilled_by_user_id INT UNSIGNED NULL,
    cancelled_by_user_id INT UNSIGNED NULL,
    fulfilment_note VARCHAR(255) NULL,
    cancel_reason VARCHAR(500) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_redemptions_user_token (user_id, idempotency_token),
    KEY idx_redemptions_user_requested (user_id, requested_at),
    KEY idx_redemptions_status_requested (status, requested_at),
    KEY idx_redemptions_reward_id (reward_id),
    CONSTRAINT fk_redemptions_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_redemptions_reward FOREIGN KEY (reward_id)
        REFERENCES rewards (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_redemptions_fulfilled_by FOREIGN KEY (fulfilled_by_user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_redemptions_cancelled_by FOREIGN KEY (cancelled_by_user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE points_ledger (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    entry_type ENUM('claim','redemption','reversal','adjustment') NOT NULL,
    points_delta INT NOT NULL,
    claim_code_id BIGINT UNSIGNED NULL,
    redemption_id INT UNSIGNED NULL,
    reverses_entry_id BIGINT UNSIGNED NULL,
    idempotency_key VARCHAR(64) NULL,
    reason VARCHAR(255) NULL,
    created_by_user_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_points_ledger_claim_code (claim_code_id),
    UNIQUE KEY uq_points_ledger_redemption (redemption_id),
    UNIQUE KEY uq_points_ledger_reversal (reverses_entry_id),
    UNIQUE KEY uq_points_ledger_idempotency (idempotency_key),
    KEY idx_points_ledger_user_id (user_id, id),
    CONSTRAINT fk_points_ledger_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_points_ledger_claim_code FOREIGN KEY (claim_code_id)
        REFERENCES codes (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_points_ledger_redemption FOREIGN KEY (redemption_id)
        REFERENCES redemptions (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_points_ledger_reverses_entry FOREIGN KEY (reverses_entry_id)
        REFERENCES points_ledger (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_points_ledger_created_by FOREIGN KEY (created_by_user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fraud_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_type ENUM('login_bruteforce','repeated_invalid_codes','claim_rate_exceeded','many_accounts_one_ip','voided_batch_code_claimed','redemption_burst','other') NOT NULL,
    severity ENUM('info','warning','high') NOT NULL,
    user_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    batch_id INT UNSIGNED NULL,
    code_id BIGINT UNSIGNED NULL,
    details VARCHAR(500) NULL,
    review_status ENUM('open','reviewed','dismissed','escalated') NOT NULL DEFAULT 'open',
    reviewed_by_user_id INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_fraud_events_review_created (review_status, created_at),
    KEY idx_fraud_events_user (user_id),
    KEY idx_fraud_events_ip (ip_address),
    KEY idx_fraud_events_batch (batch_id),
    CONSTRAINT fk_fraud_events_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_fraud_events_batch FOREIGN KEY (batch_id)
        REFERENCES batches (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_fraud_events_code FOREIGN KEY (code_id)
        REFERENCES codes (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_fraud_events_reviewer FOREIGN KEY (reviewed_by_user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_user_id INT UNSIGNED NULL,
    action VARCHAR(60) NOT NULL,
    entity_type VARCHAR(40) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    reason VARCHAR(500) NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    correlation_id VARCHAR(36) NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_audit_logs_entity (entity_type, entity_id),
    KEY idx_audit_logs_actor_created (actor_user_id, created_at),
    KEY idx_audit_logs_action_created (action, created_at),
    CONSTRAINT fk_audit_logs_actor FOREIGN KEY (actor_user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reconciliation_runs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    run_by_user_id INT UNSIGNED NOT NULL,
    scope ENUM('all','customer','batch') NOT NULL,
    scope_ref_id BIGINT UNSIGNED NULL,
    outcome ENUM('clean','discrepancies','failed') NOT NULL,
    discrepancy_count INT UNSIGNED NOT NULL,
    started_at DATETIME NOT NULL,
    finished_at DATETIME NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_reconciliation_runs_user FOREIGN KEY (run_by_user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reconciliation_findings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    run_id INT UNSIGNED NOT NULL,
    check_type ENUM('claim_vs_code','redemption_vs_debit','reversal_link','balance_vs_report') NOT NULL,
    user_id INT UNSIGNED NULL,
    ledger_entry_id BIGINT UNSIGNED NULL,
    expected_value BIGINT NULL,
    actual_value BIGINT NULL,
    status ENUM('open','acknowledged','resolved_by_correction') NOT NULL DEFAULT 'open',
    note VARCHAR(500) NULL,
    PRIMARY KEY (id),
    KEY idx_reconciliation_findings_run (run_id),
    KEY idx_reconciliation_findings_status (status),
    CONSTRAINT fk_reconciliation_findings_run FOREIGN KEY (run_id)
        REFERENCES reconciliation_runs (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_reconciliation_findings_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_reconciliation_findings_ledger FOREIGN KEY (ledger_entry_id)
        REFERENCES points_ledger (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

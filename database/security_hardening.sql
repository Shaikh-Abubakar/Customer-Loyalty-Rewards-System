-- Stage 7 database-side security hardening.
-- Run as a database migration/administrator account, after importing schema.sql
-- (or applying stage7_security_upgrade.sql) and creating the application DB user.
-- Update the database name and account host below to match config/.env.
-- The web application must NEVER connect as root or as this migration account.

USE customer_loyalty;

-- Defense in depth: even an application-code mistake cannot modify/delete history.
DROP TRIGGER IF EXISTS points_ledger_deny_update;
DROP TRIGGER IF EXISTS points_ledger_deny_delete;
DROP TRIGGER IF EXISTS audit_logs_deny_update;
DROP TRIGGER IF EXISTS audit_logs_deny_delete;

DELIMITER //
CREATE TRIGGER points_ledger_deny_update
BEFORE UPDATE ON points_ledger FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'points_ledger is append-only';
END//

CREATE TRIGGER points_ledger_deny_delete
BEFORE DELETE ON points_ledger FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'points_ledger is append-only';
END//

CREATE TRIGGER audit_logs_deny_update
BEFORE UPDATE ON audit_logs FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is append-only';
END//

CREATE TRIGGER audit_logs_deny_delete
BEFORE DELETE ON audit_logs FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is append-only';
END//
DELIMITER ;

-- SECURITY-CRITICAL: change the account host if DB_HOST is not 127.0.0.1.
-- Create the user separately with a unique local password, for example:
-- CREATE USER 'loyalty_app'@'127.0.0.1' IDENTIFIED BY 'REPLACE_WITH_A_STRONG_LOCAL_PASSWORD';
-- Then remove any broad/global grants before assigning table-level privileges:
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'loyalty_app'@'127.0.0.1';

-- Tables with normal application create/update workflows.
GRANT SELECT, INSERT, UPDATE ON customer_loyalty.users TO 'loyalty_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE ON customer_loyalty.customer_profiles TO 'loyalty_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE ON customer_loyalty.products TO 'loyalty_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE ON customer_loyalty.batches TO 'loyalty_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE ON customer_loyalty.codes TO 'loyalty_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE ON customer_loyalty.code_exports TO 'loyalty_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE ON customer_loyalty.rewards TO 'loyalty_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE ON customer_loyalty.redemptions TO 'loyalty_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE ON customer_loyalty.fraud_events TO 'loyalty_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE ON customer_loyalty.reconciliation_runs TO 'loyalty_app'@'127.0.0.1';

-- Insert/read-only operational and security histories.
GRANT SELECT, INSERT ON customer_loyalty.account_status_events TO 'loyalty_app'@'127.0.0.1';
GRANT SELECT, INSERT ON customer_loyalty.login_attempts TO 'loyalty_app'@'127.0.0.1';
GRANT SELECT, INSERT ON customer_loyalty.registration_attempts TO 'loyalty_app'@'127.0.0.1';
GRANT SELECT, INSERT ON customer_loyalty.claim_attempts TO 'loyalty_app'@'127.0.0.1';
GRANT SELECT, INSERT ON customer_loyalty.hmac_key_registry TO 'loyalty_app'@'127.0.0.1';
GRANT SELECT, INSERT ON customer_loyalty.reconciliation_findings TO 'loyalty_app'@'127.0.0.1';

-- Immutable financial and audit histories: NO UPDATE and NO DELETE.
GRANT SELECT, INSERT ON customer_loyalty.points_ledger TO 'loyalty_app'@'127.0.0.1';
GRANT SELECT, INSERT ON customer_loyalty.audit_logs TO 'loyalty_app'@'127.0.0.1';

-- Verify after execution: SHOW GRANTS FOR 'loyalty_app'@'127.0.0.1';
-- No global privileges, FILE, SUPER, CREATE USER, TRIGGER, ALTER, DROP or GRANT OPTION
-- should be granted to the runtime application account.


-- Separate, CLI-only maintenance account (never configured as DB_USER):
-- CREATE USER 'loyalty_maintenance'@'127.0.0.1' IDENTIFIED BY 'REPLACE_WITH_ANOTHER_STRONG_PASSWORD';
-- REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'loyalty_maintenance'@'127.0.0.1';
GRANT SELECT, DELETE ON customer_loyalty.login_attempts TO 'loyalty_maintenance'@'127.0.0.1';
GRANT SELECT, DELETE ON customer_loyalty.claim_attempts TO 'loyalty_maintenance'@'127.0.0.1';
GRANT SELECT, DELETE ON customer_loyalty.registration_attempts TO 'loyalty_maintenance'@'127.0.0.1';
-- Verify the maintenance account has no other permissions. It must never be used by Apache.
-- SHOW GRANTS FOR 'loyalty_maintenance'@'127.0.0.1';

# Stage 7 Security Review and Hardening Report

**Project:** Customer Loyalty Rewards System  
**Scope:** Stage 6 application archive, approved Stage 2 architecture/database/workflows/assumptions/test plan, Stage 3 UI/security hand-off, and assignment brief text extracted from the original PDF.  
**Runtime inspected:** PHP 8.4.24 CLI in the build container. The runtime has PDO but not `pdo_mysql`; no MySQL/MariaDB server is available. The archive contains no `.git` directory, so Git status could not be inspected.

## 1. Assignment/design basis

The assignment requires a loyalty system for boxed physical products and requires the design to address proof of purchase, fraud/code reuse prevention, and factory-owner reward management. The approved design uses a concealed, one-time factory code as the operational purchase-proof credential. It does not independently verify a retailer sale, payment, purchase date or purchaser identity (A-24/A-25).

The Stage 7 changes preserve the approved 16-character alphabet, manual code entry as the primary claim method, URL-fragment-only optional QR prefill, the A-04 login/claim thresholds, the append-only points ledger, the single admin permission tier, and the existing claim/redemption services.

## 2. Findings and remediation

| Area | Finding in Stage 6 | Stage 7 remediation |
|---|---|---|
| Registration abuse | Registration had no persistent rate limit or attempt evidence. | Added `registration_attempts`, a configurable default of 5 submissions/IP/15 minutes, rate-limited outcome rows, a deduplicated fraud-review event, schema migration and fresh-schema support (A-35). |
| Concurrent throttling | Account/IP counters were read and then written without a cross-request serialization guard; simultaneous requests could observe the same old count. | Added `RateLimitGuard` using sorted MySQL/MariaDB named locks around registration, login, claims and admin export password re-entry. Lock timeout fails closed. |
| Login race | Password was checked before acquiring the user row lock; the hash could change between verification and session establishment. | Re-verifies the supplied password against the locked row's current hash before successful login; rehashes when policy needs updating. |
| Bcrypt input handling | Login did not explicitly reject passwords longer than bcrypt's 72-byte input limit. | Oversized values take the generic invalid-credentials path and do not undergo bcrypt truncation-based authentication. Registration already enforces the byte limit. |
| Session hardening | Some ID regenerations bypassed the checked helper; authenticated session establishment retained unrelated pre-auth session data. | Session-ID regeneration now uses `SessionManager::regenerateId()` and clears pre-auth data before setting identity; middleware resets use the same checked helper. |
| Response headers | Base CSP and common headers existed, but additional browser isolation and transport policy were absent. | Added COOP/CORP, X-Permitted-Cross-Domain-Policies, stricter CSP directives and HSTS only on HTTPS (local XAMPP HTTP exception preserved). |
| Technical logs | Exception messages were written after control-character cleaning but without secret-pattern redaction. | Added masking for password/token/key fields, compact and grouped 16-character product-code-shaped strings, and long encoded secrets. Logs are now daily files for approved 30-day retention. |
| Ledger/audit immutability | The app code used INSERT-only repositories, but schema setup did not include append-only triggers or an executable least-privilege privilege plan. | Added triggers denying UPDATE/DELETE and table-level GRANT instructions giving the runtime account SELECT/INSERT only on `points_ledger` and `audit_logs`. |
| Retention operation | A-11 required an operator-run monthly retention command, but none was present. | Added CLI-only `bin/maintain_retention.php` and a separate maintenance DB identity restricted to the three attempt tables. It never deletes ledger, audit, fraud or reconciliation history. |
| Web-root boundary | Project layout expected Apache to serve only `public/`, but the archive lacked a fallback deny file at the project root. | Added root deny `.htaccess` and public directory-index/sensitive-extension restrictions; README reiterates `DocumentRoot=public/`. |

## 3. Controls re-reviewed and preserved

- Authentication and authorization are enforced by database-backed middleware on every protected customer/admin route; role and active status are checked server-side. The admin account remains CLI-created, and no admin hierarchy or public admin registration was added.
- PDO uses native prepared statements (`ATTR_EMULATE_PREPARES=false`). Search values are bound. Dynamic sort/status/scope fragments are allowlisted. Dashboard `LIMIT` values are integer-clamped before interpolation; reconciliation scope is restricted to `all`, `customer`, or `batch`, with the ID bound as a parameter.
- CSRF tokens are 32 random bytes, stored in session and compared with `hash_equals()`. `require_csrf()` now also rejects non-POST methods. GET handlers remain read-only.
- `REMOTE_ADDR` is the only IP source; untrusted forwarded headers are ignored, as A-29 requires for the direct-Apache deployment.
- Product codes still use the approved 30-character alphabet and `random_int()` per symbol, HMAC-SHA-256 with key IDs, HMAC key registry checks and a conditional single-use state transition.
- Claim transaction continues to lock the customer row first, then batch/code state, mark the code claimed conditionally, insert the unique claim ledger row and record the claim attempt in the same transaction. The unique code-claim reference in `points_ledger` remains the duplicate-credit backstop.
- Claim attempt rows record outcome/code ID/IP/account only, never the submitted plaintext product code. Customer-visible responses do not reveal another customer's claim state.
- Points balance remains `SUM(points_delta)` from the append-only ledger; no editable balance column or direct ledger update/delete path was introduced. Claim credits, redemption debits and cancellation reversals remain transaction-bound.
- Redemption processing remains idempotent and transactional: pending-only status transition, stock reservation/restoration and ledger debit/reversal are coordinated by the service layer.
- CSV export remains POST + CSRF + administrator re-authentication, one-time per batch, streamed from memory, and not persisted as a plaintext server file. The password check shares serialized login throttling.
- Optional QR links still use URL fragments to prefill only; there is no automatic claim submission and the raw code is not saved into the session.

## 4. New database setup files

- `database/stage7_security_upgrade.sql`: additive migration for an existing Stage 6 database; creates only `registration_attempts`.
- `database/security_hardening.sql`: DBA-run append-only triggers and least-privilege grant template. Review the database name, host and usernames before execution. Create the runtime and maintenance accounts separately with distinct local passwords.
- `database/schema.sql`: fresh-install schema now includes the added registration-attempt table.

Do not run the GRANT template as the web user. The runtime account must not be `root`, must not have global/schema-management privileges, and must have no UPDATE/DELETE rights on `points_ledger` or `audit_logs`. The maintenance account is CLI-only and may delete only old attempt records.

## 4. Items absent from the supplied Stage 6 scope

- `profile.php` / customer profile editing and customer-initiated password change are specified as future UI work in `PROJECT_STRUCTURE.md` and `UI_UX_SPEC.md`, but no such route or password-change service exists in the supplied Stage 6 archive. This stage did not invent a new account-management workflow. Consequently, profile-change CSRF and password-change re-authentication/rate limiting could not be audited as an existing route. Implement those operations in a later approved stage before exposing profile editing.
- No password-reset-by-email service exists, consistent with A-33. The login and administrator export re-authentication paths use the shared login-attempt throttling.
- Product-code generation/export is included in Stage 6; this Stage 7 report reviews its security controls but cannot assert a live CSV/download test in this runtime.

## 5. Automated checks actually run in this build container

| Test | Result |
|---|---:|
| `php bin/test_foundation.php` | 20/20 passed |
| `php bin/test_stage5.php` | 26/26 passed |
| `php bin/test_stage6.php` | 41/41 passed |
| `php bin/test_stage7.php` | 68/68 passed |
| All PHP syntax checks | 88/88 passed |
| `php bin/test_database.php` | Blocked: `pdo_mysql` is not installed |

These source-level checks do not prove MySQL transactions, named-lock contention, trigger behaviour, grants, or browser flows. Those need to be run with XAMPP/MariaDB or Ubuntu/MySQL and the outcomes entered into `TEST_PLAN.md` by the student.

## 6. Required local verification before calling the application secure

1. Apply `database/stage7_security_upgrade.sql` once to an existing Stage 6 database (not needed after importing the updated fresh schema).
2. Run `database/security_hardening.sql` as a database administrator after checking database/user/host names; inspect both `SHOW GRANTS` results.
3. Confirm a direct `UPDATE`/`DELETE` against points_ledger and audit_logs is refused when connected as the runtime account.
4. Run concurrent registration, login, claim and export re-authentication attempts from multiple workers; verify threshold behavior and retry messages.
5. Run the complete `TEST_PLAN.md` database, session, CSRF, XSS, SQL injection, ledger, redemption, export and rate-limit scenarios on XAMPP and record PHP/MySQL/MariaDB versions.
6. Confirm Apache `DocumentRoot` is `public/`, HTTPS is enabled for any externally reachable deployment, and local `.env`/HMAC key files are outside the web root and excluded from Git.

**Conclusion:** the Stage 7 code hardens the identified application-layer gaps and supplies the missing DB-side setup/retention procedures. Additional regression checks cover registration abuse-event recording and compact/grouped code redaction. The environment cannot perform live penetration or database tests, so it would be inaccurate to claim a completed penetration test or production security certification.

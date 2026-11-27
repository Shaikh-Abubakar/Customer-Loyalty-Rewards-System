# AI_LOG.md — working log

The assignment requires 5–10 key AI prompts/interactions and at least two cases where the AI was wrong, how the mistake was noticed and how it was corrected. This file records the Stage 4–7 work evidenced in this project. Earlier Stage 1–3 prompts must still be added from the actual conversation history; do not invent quotations or mistakes to fill the assignment requirement.

## Recorded interaction

| # | Stage | User direction | AI work / decision | Verification or correction |
|---|---|---|---|---|
| 1 | Stage 4 — project foundation | Implement configuration, PDO, sessions, shared helpers, schema, entry point and CSS without implementing later business workflows. | Inspected the Stage 2/3 documents; implemented the foundation and the 17-table schema without changing the approved table design. | PHP syntax and foundation checks are recorded in `tests/foundation_checks.md`. Live PDO connectivity depends on the local XAMPP/MariaDB installation. |

## Cross-document issue detected

`PROJECT_STRUCTURE.md` says the database directory/schema was planned for Stage 3, while the approved Stage 3 execution brief prohibited creating or modifying SQL. Stage 3 therefore delivered documentation only. The schema was deferred to the first later implementation stage (Stage 4) without changing any approved table, field, relationship or rule. This is recorded as a stage-timing conflict, not presented as a client decision.

## Completion needed for final assignment submission

Add the actual Stage 1–3 prompt interactions and any genuine AI mistakes/corrections from the conversation history. Keep dates and quotations accurate. This starter log is not yet a claim that the assignment's 5–10 interaction requirement is complete.


## Verified implementation correction

| # | Error found | How it was noticed | Correction |
|---|---|---|---|
| 1 | The first generated autoloader had an incorrectly escaped PHP namespace-prefix string and did not parse. | `php -l bootstrap/autoload.php` reported a parse error before any app testing. | Corrected the namespace-prefix literal and reran PHP lint across every PHP file; all files then parsed successfully. |

The cross-document schema timing mismatch above is a second documented issue detected during project inspection. It is a source-document conflict rather than a fabricated claim that a runtime defect occurred.


## Stage 5 — customer registration, claims and rewards (2026-11-25)

| Item | Detail |
|---|---|
| User direction | Implement customer registration/login, dashboard, 16-character product-code claiming, points and reward redemption in the supplied Stage 4 PHP/MySQL project; inspect the assignment and approved documents first. |
| Source material inspected | Original Assignment 2 PDF content surfaced from the project Library; Stage 4 ZIP; `ARCHITECTURE.md`, `DATABASE_DESIGN.md`, `WORKFLOWS.md`, `UI_UX_SPEC.md`, `DESIGN_SYSTEM.md`, `ASSUMPTIONS.md`, `PROJECT_STRUCTURE.md`, `TEST_PLAN.md`, `.env.example`, schema and existing PHP foundation. |
| Scope implemented | Customer registration/login/logout, dashboard, manual-first HMAC code claim, points history, reward catalogue, confirmation/redemption and customer redemption list. Added repositories/services/middleware, CSRF-safe pages, responsive customer styles, one-time HMAC registry setup CLI and database-independent Stage 5 tests. No database schema changes were made. |
| Important implementation conventions | The database design specified a fixed public HMAC key-check text without spelling it out; Stage 5 uses `loyalty-key-check-v1` and records this in `ASSUMPTIONS.md` (IC-01). Password input is capped at 72 bytes to avoid bcrypt truncation (IC-02). |
| Verification in the AI build container | PHP lint was run on the implementation; `php bin/test_foundation.php`: 20 checks, 0 failures; `php bin/test_stage5.php`: 26 checks, 0 failures after adding a SQL-string regression check. `php bin/test_database.php` did not connect because this environment has no `pdo_mysql` extension. A PHP built-in-server HTTP smoke returned 200 for the updated public landing page and customer CSS. No MySQL/MariaDB server, browser or Windows XAMPP installation was available, so registration, SQL transactions, concurrency, claim and redemption flows are not claimed as integration-tested. |
| Repository state | The supplied ZIP did not include `.git`; no Git branch, changeset or existing commit history could be inspected. A Stage 5 ZIP is delivered; apply and commit it in the actual student repository. |

### Stage 5 review notes and limitations

1. Product-code verification uses the approved `loyalty-code-v1:` domain prefix, HMAC-SHA-256 and active/retired key registry. The raw code is not persisted, logged or carried through login.
2. Claim and redemption writes use transactions, per-customer row locking, conditional updates and unique ledger/idempotency constraints. These require real MySQL/MariaDB execution tests before final sign-off.
3. Admin product/batch/code generation and reward administration are outside this stage; a full claim demonstration needs code records from the later generator or controlled fixtures.
4. Purchase verification remains possession of the concealed code, per A-24/A-25; there is no independent retailer receipt or POS check.
5. A source review found several repository SQL statements written as single-quoted PHP strings with a literal `\n` sequence. PHP would pass the backslash and `n` to the SQL parser rather than a line break. Those statements were converted to double-quoted PHP strings so the SQL receives real whitespace. A source-level regression check was added to `bin/test_stage5.php`.
6. Student review, own-machine tests and earlier Stage 1–3 AI interaction entries remain to be completed. Do not present build-container checks as student-run XAMPP results.


## Stage 6 — administrator dashboard and management (2026-11-25)

| Item | Detail |
|---|---|
| User direction | Implement the administrator dashboard and management module using the approved architecture, database design, workflows and UI/UX documents; preserve the existing Stage 5 customer flows and PHP/MySQL-only stack. |
| Source material inspected | Stage 5 ZIP (including Stage 4 foundation); original Assignment 2 requirements as previously retrieved; `ARCHITECTURE.md`, `DATABASE_DESIGN.md`, `WORKFLOWS.md`, `UI_UX_SPEC.md`, `DESIGN_SYSTEM.md`, `ASSUMPTIONS.md`, `PROJECT_STRUCTURE.md`, `TEST_PLAN.md`, existing repositories/services, schema and PHP pages. The ZIP had no `.git` directory. |
| Scope implemented | Server-side administrator guard and CLI-only initial admin creation; live dashboard; product CRUD lifecycle without deletion; batch lifecycle and one-time HMAC code/CSV generation; customer search/detail and reasoned suspension/reactivation; reward management; pending redemption fulfilment/cancellation; fraud review; read-only audit; reports; manual reconciliation; responsive admin navigation/styles. Existing database schema was not changed. |
| Corrections found during cross-document review | (1) The first batch form generated an internal label automatically, but WORKFLOWS 2.2 and UI_UX_SPEC require an administrator-entered label; changed it to a required unique 1–30 character label. (2) Nullable ownership fields could make SQL inequality tests evaluate UNKNOWN during reconciliation; added explicit NULL checks. (3) An export failure during the generated state could be recorded before the batch became exported, while the details page only warned for exported/failed; expanded the warning to both generated/failed and exported/failed. (4) Re-authentication now uses the existing login-attempt throttle; export validates the registered HMAC algorithm. |
| Verification in the AI build container | Final build-container verification on 2026-11-25: `php -l` passed for 84 PHP files; `php bin/test_foundation.php` passed 20/20; `php bin/test_stage5.php` passed 26/26; `php bin/test_stage6.php` passed 41/41. `php bin/test_database.php` was blocked because PDO MySQL (`pdo_mysql`) is not installed. No MySQL/MariaDB, XAMPP, browser or concurrency results are claimed. |
| Student review | Student must inspect the generated code, understand each service/transaction/guard, import the schema in local XAMPP, run Stage 6 test scenarios AD-01 to AD-16 and record genuine results. No student-run results are asserted here. |



## Stage 7 — security hardening and fraud prevention (2026-11-25)

| Item | Detail |
|---|---|
| User direction | Audit and harden the existing Stage 6 customer loyalty application without rebuilding it; inspect the approved design, protect authentication/authorization, SQL, CSRF, claims, rate limiting, points and redemptions. |
| Source material inspected | Stage 6 ZIP and existing Stage 2 architecture, database design, workflows, project structure, assumptions and test plan; Stage 3 UI/UX specification/design system/wireframes; Stage 4 configuration/schema/security helpers; Stage 5 customer services/pages; Stage 6 administrator services/pages; original Assignment 2 PDF text extracted by the project file index. The PDF page-image read failed in this environment, so its extracted assignment text was used and page rendering is not claimed. The ZIP has no `.git` metadata. |
| Scope implemented | Added persistent registration attempt recording and configurable IP throttling; serialized registration/login/claim/export re-authentication counters using MySQL/MariaDB named locks; verifies the password again under the locked user row and rehashes outdated bcrypt hashes; tightens checked session rotation and response headers; redacts compact and grouped product codes/secrets in technical logs; emits a deduplicated fraud event when registration is throttled; added append-only ledger/audit triggers and least-privilege DB grants; added a separate CLI maintenance identity and monthly retention command; added Apache web-root deny rules; updated schema/migration/README/security review/manual test plan. |
| Corrections found during review | (1) Stage 6 did not persistently throttle registration or record registration-abuse evidence; added `registration_attempts` and an `other` warning fraud event with no submitted form data. (2) The logger masked compact 16-character codes but not the approved four-group export presentation; added both grouped and compact code redaction. (3) The attempt counters were read then written without a cross-request lock; introduced DB named-lock coordination around their checks and updates. These are concrete code-review findings and corrections, not claims of a live exploit. |
| Verification in the AI build container | `php bin/test_foundation.php`: 20/20; Stage 5: 26/26; Stage 6: 41/41; Stage 7: 68/68; total database-independent checks: 155/155. PHP lint: 88/88 files. `php bin/test_database.php` is blocked because `pdo_mysql` is not installed. No MySQL/MariaDB server, actual concurrent workers, Apache browser session or XAMPP installation was available; trigger, GRANT, DB-backed lock contention, SQL injection payload execution and browser tests remain local verification tasks. |
| Important boundaries | No password-reset service or customer password-change route is present in the supplied Stage 6 application; no route was invented as part of this security-only stage. CSV export is protected by the existing administrator re-authentication, CSRF, one-time export and shared login throttle. Other administrator mutations continue to require active administrator authorization, POST+CSRF and audit logging. The points ledger and audit log are append-only at both application and supplied DB-trigger/grant layers, pending local execution of the SQL script. |
| Student review | The student must run `database/stage7_security_upgrade.sql` for an existing Stage 6 database, review and run `database/security_hardening.sql` as a DBA, verify SHOW GRANTS and rejected UPDATE/DELETE, and complete all S7-01 to S7-10 manual scenarios on XAMPP/MariaDB or Ubuntu/MySQL. No student-run test results are asserted. |

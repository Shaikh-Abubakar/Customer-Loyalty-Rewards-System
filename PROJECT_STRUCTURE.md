# PROJECT_STRUCTURE.md: Customer Loyalty Rewards System

Stage 2 design document, updated with Stage 5 and Stage 6 implementation-status notes. Stage 4 created the foundation, Stage 5 implemented customer workflows, and Stage 6 implemented the administrator module. The tree remains a design map; a planned helper is not implemented unless explicitly listed below.

Companion documents: [ARCHITECTURE.md](ARCHITECTURE.md), [DATABASE_DESIGN.md](DATABASE_DESIGN.md), [WORKFLOWS.md](WORKFLOWS.md), [ASSUMPTIONS.md](ASSUMPTIONS.md), [TEST_PLAN.md](TEST_PLAN.md).

Labels: **[Confirmed]**, **[Requirement]**, **[Project constraint]**, **[Fact]**, **[Recommendation]**, **[Assumption]**, **[Unresolved]**.

---

## 1. Rules that shape the layout

| Rule | Label |
|---|---|
| HTML lives inside `.php` files. No separate `.html` files. | [Project constraint] |
| CSS is in separate `.css` files. | [Project constraint] |
| JavaScript is **inline inside PHP files**, only when necessary. **No `.js` files.** | [Project constraint] |
| No React, Vue, Angular or Node.js. | [Project constraint] |
| Apache serves **only `public/`**. Everything else is unreachable by URL. | [Recommendation, ARCH 9 and 13.7] |
| Layers: pages → middleware → services → repositories → database; support helpers and templates beside them. | [Recommendation, ARCH section 2] |
| Composer, a QR library and PHPUnit are optional and pending compatibility checks. | [Unresolved, ARCH 8.2 and 13.8] |
| One consistent file-name case (lowercase, underscores for pages, PascalCase for class files) because Ubuntu is case-sensitive. | [Recommendation, ARCH 9] |

---

## 2. Complete planned folder structure

```text
loyalty-system/                      <- project root (NOT the web root)
|
|-- public/                          <- the ONLY folder Apache serves (DocumentRoot)
|   |-- index.php                    landing page
|   |-- register.php
|   |-- login.php
|   |-- logout.php                   POST only
|   |-- dashboard.php                customer home: balance, shortcuts
|   |-- profile.php
|   |-- claim.php                    QR and manual code claiming
|   |-- history.php                  points history
|   |-- rewards.php                  reward catalogue
|   |-- redeem.php                   confirm and submit a redemption
|   |-- redemptions.php              customer's redemption status
|   |-- admin/
|   |   |-- index.php                admin dashboard
|   |   |-- products.php
|   |   |-- product_edit.php
|   |   |-- batches.php
|   |   |-- batch_create.php
|   |   |-- batch_view.php           state actions: printed, activate, void
|   |   |-- batch_generate_export.php  POST only: generate + CSV download
|   |   |-- customers.php
|   |   |-- customer_view.php        suspend/reactivate, adjustments
|   |   |-- rewards.php
|   |   |-- redemptions.php          fulfilment queue
|   |   |-- fraud.php
|   |   |-- audit.php
|   |   |-- reports.php
|   |   `-- reconciliation.php
|   `-- assets/
|       |-- css/
|       |   |-- base.css             shared styles
|       |   |-- customer.css
|       |   `-- admin.css
|       `-- img/                     logo and static images only
|
|-- bootstrap/                       <- start-up code (private)
|   |-- app.php                      config, session, headers, PDO, error handler
|   |-- autoload.php                 small class autoloader (no Composer needed)
|   `-- cli.php                      start-up for command-line scripts
|
|-- config/                          <- non-secret settings (private)
|   `-- app.php                      returns settings read from environment values
|
|-- src/                             <- application classes (private)
|   |-- Middleware/
|   |-- Service/
|   |-- Repository/
|   `-- Support/
|
|-- templates/                       <- PHP view files that print HTML (private)
|   |-- layout/                      header, footer, navigation
|   |-- partials/                    flash messages, pagination, CSRF field
|   |-- customer/
|   `-- admin/
|
|-- bin/                             <- command-line scripts (private)
|   |-- create_admin.php             create first/additional admin accounts under operator control
|   |-- key_check.php                compares .env keys with hmac_key_registry
|   |-- key_rotate.php               operator-assisted HMAC key rotation; no secret output
|   `-- retention_maintenance.php    manually prune expired attempt rows and technical logs
|
|-- storage/                         <- runtime data (private, not in Git)
|   `-- logs/                        application log files
|
|-- database/                        <- PLANNED for Stage 3 only; empty in Stage 2
|
|-- tests/                           <- manual test records now; PHPUnit tests only if approved
|
|-- docs/                            <- the six Stage 2 documents and later notes
|   |-- ARCHITECTURE.md
|   |-- DATABASE_DESIGN.md
|   |-- WORKFLOWS.md
|   |-- PROJECT_STRUCTURE.md
|   |-- ASSUMPTIONS.md
|   `-- TEST_PLAN.md
|
|-- .env                             <- REAL secrets, never committed
|-- .env.example                     <- placeholders only, committed
|-- .gitignore
|-- .gitattributes                   line-ending rule for Windows/Ubuntu
|-- README.md                        what the app does, stack, setup/run, deployed link if available
|-- AI_LOG.md                        5-10 real AI prompts/interactions; at least 2 mistakes found and fixed
`-- composer.json / composer.lock    ONLY if Composer is adopted (pending)
```

Notes:

- The six documents may stay at the repository root or move into `docs/`. The relative links inside them assume they sit **together**; either place works as long as they stay together. [Recommendation]
- `database/` is shown only as the planned home for the Stage 3 schema and migration notes. **No schema file is created in Stage 2.**
- There is no `vendor/` folder unless Composer packages are adopted, and `vendor/` is not committed unless a deployment decision requires it. [ARCH 13.8]

---

## 3. Purpose of each folder

| Folder | Purpose | Public? |
|---|---|---|
| `public/` | Page files (one per URL) and CSS/images. Pages stay thin: guards, one service call, one template. | **Yes** (the only one) |
| `public/admin/` | Administrator pages. Separation is for readability; **protection comes from server-side role checks, not from the folder.** | Yes (URL-reachable), role-protected |
| `public/assets/css/` | All stylesheets. | Yes |
| `public/assets/img/` | Static images only. No scripts, no HTML. | Yes |
| `bootstrap/` | Start-up code that every page and command-line script loads first. | No |
| `config/` | Settings that are not secret (thresholds, page sizes, feature switches) assembled from environment values. | No |
| `src/Middleware/` | Reusable guards: require login, require role, require active account, verify CSRF token, generate a fresh response nonce and send security headers. | No |
| `src/Service/` | Business rules and transactions: AuthService/AccountLifecycleService, ProfileService, ProductService, BatchService, CodeGeneratorService, ClaimService, ExportService, KeyService, LedgerService, RewardService, RedemptionService, FraudService, AuditService, ReportService, ReconciliationService and LogRetentionService. | No |
| `src/Repository/` | All SQL, one class per table or aggregate, PDO prepared statements only. | No |
| `src/Support/` | Helpers: config reader, escaping, UTC clock, HMAC helper, random code generator, CSP nonce generator, validators, path helper and logger. | No |
| `templates/` | PHP files that print HTML. Every value passes through the escaping helper. | No |
| `bin/` | Command-line scripts for one-time and operator tasks. | No |
| `storage/logs/` | Technical logs (no secrets, no plaintext codes). The **only** folder the web server may write to. | No |
| `database/` | Stage 3 schema and migration notes (empty now). | No |
| `tests/` | Test records and, if approved, PHPUnit tests. | No |
| `docs/` | Design documents. | No |

---

## 4. Purpose of the important PHP files

### 4.1 Pages in `public/`

| File | Purpose | Main service |
|---|---|---|
| `register.php`, `login.php`, `logout.php` | **Implemented in Stage 5:** customer registration, login and POST/CSRF logout | `AuthService` |
| `dashboard.php` | **Implemented in Stage 5:** customer home, live ledger summary, recent activity and redemptions | `ProfileService`, `LedgerService`, `RedemptionService`, `RewardService` |
| `profile.php` | Planned profile-management page; not implemented in Stage 5 | `ProfileService` (future) |
| `claim.php` | **Implemented in Stage 5:** manual-first code claim, optional QR-fragment prefill | `ClaimService` |
| `history.php` | **Implemented in Stage 5:** ledger-derived points history | `LedgerService` |
| `rewards.php`, `redeem.php`, `redemptions.php` | **Implemented in Stage 5:** browse active rewards, confirm one-unit requests and track status | `RewardService`, `RedemptionService` |
| `admin/index.php` | **Implemented in Stage 6:** database-backed administrator dashboard | `AdminService` |
| `admin/products.php`, `product_edit.php` | **Implemented in Stage 6:** product list, create, edit and activate/deactivate | `ProductService` |
| `admin/batches.php`, `batch_create.php`, `batch_view.php` | **Implemented in Stage 6:** batch labels, lifecycle transitions and voiding | `BatchService` |
| `admin/batch_generate_export.php` | **Implemented in Stage 6:** POST-only, re-authenticated, one-time in-memory code/CSV generation | `BatchService` |
| `admin/customers.php`, `customer_view.php` | **Implemented in Stage 6:** customer search/detail, ledger/history and reasoned status changes | `AdminService`, `AccountLifecycleService` |
| `admin/rewards.php`, `redemptions.php` | **Implemented in Stage 6:** reward management and pending fulfil/cancel workflow | `RewardService`, `RedemptionService` |
| `admin/fraud.php`, `audit.php`, `reports.php`, `reconciliation.php` | **Implemented in Stage 6:** fraud review, read-only audit, reports and manual reconciliation | `AdminService`, `ReconciliationService` |

### 4.2 Bootstrap

| File | Purpose |
|---|---|
| `bootstrap/app.php` | Reads `.env`, starts the session safely, sends security headers, opens the PDO connection (exceptions on, UTC, strict SQL mode, real prepared statements), installs the central exception handler. [ARCH 5, 13.5] |
| `bootstrap/autoload.php` | Maps class names to files in `src/` so Composer is not required for the application's own code. [ARCH 8.2] |
| `bootstrap/cli.php` | Same set-up for `bin/` scripts, without HTTP or sessions. |

### 4.3 Class groups in `src/` (class names are conceptual)

| Folder | Classes (conceptual) | Notes |
|---|---|---|
| `Middleware/` | `RequireLogin`, `RequireActiveAccount`, `RequireRole`, `VerifyCsrf`, `SecurityHeaders` | Run in the order of WORKFLOWS section 0 |
| `Service/` | `AuthService`, `AccountLifecycleService`, `ProfileService`, `ProductService`, `BatchService`, `CodeGeneratorService`, `CodeVerifierService`, `KeyService`, `ExportService`, `ClaimService`, `LedgerService`, `RewardService`, `RedemptionService`, `FraudService`, `AuditService`, `ReportService`, `ReconciliationService`, `AdminCustomerService`, `LogRetentionService` | Each table has one owning module/repository; coordinator methods may participate in an existing transaction but must not start nested transactions. [ARCH 3.2] |
| `Service/` (optional) | `QrSvgRenderer` | Optional QR adapter; only `ExportService` may use it. [Confirmed, decision 1] |
| `Repository/` | One per table: users, profiles, status events, login attempts, products, batches, key registry, codes, exports, claim attempts, ledger, rewards, redemptions, fraud events, audit logs, reconciliation runs and findings | Exactly one repository per table; services own business decisions and transactions |
| `Support/` | `Config`, `Escape`, `Clock`, `Hmac`, `CodeAlphabet`/`RandomCode`, `Validator`, `Paths`, `Logger`, `Database` (connection and `transaction()` helper) | `Database::transaction` begins, commits, rolls back, retries once on deadlock [ARCH 6.7] |

### 4.4 Command-line scripts

| File | Purpose |
|---|---|
| `bin/create_admin.php` | **Implemented in Stage 6:** creates the first administrator through a controlled CLI-only flow and writes an audit event in the same transaction. No web admin creation or hierarchy. [A-07/A-16] |
| `bin/register_hmac_key.php` | **Implemented in Stage 5:** one-time initial active-key registry setup; never prints/stores the secret. Does not rotate keys. |
| `bin/key_check.php` | Planned full key/configuration report; not implemented. |
| `bin/key_rotate.php` | Operator-only helper for the rotation sequence in WORKFLOWS 6.7; reads secrets from protected configuration and never prints or logs them. |
| `bin/retention_maintenance.php` | Manually runs the owning services' retention methods: prune expired login/claim attempts and old technical log files; never deletes ledger or audit rows. [WORKFLOWS 6.8, A-11] |

---

## 5. Public versus private

| Reachable by a browser URL | Never reachable |
|---|---|
| Files under `public/` only | `src/`, `templates/`, `bootstrap/`, `config/`, `bin/`, `storage/`, `database/`, `tests/`, `docs/`, `.env`, `.git`, `composer.*`, `vendor/` |

Pages are public only in the sense of being reachable; each protected page enforces its own guards. A file in `public/` must contain **no secrets, no database credentials and no business logic**.

Exports are **not stored anywhere** (no `public/exports/` and no `storage/exports/`). [ARCH §7 item 8]

---

## 6. Configuration and secret storage

### 6.1 Where values live

| Value | Location | Committed to Git? |
|---|---|---|
| Database host, name, user, password | `.env` | **No** |
| HMAC secret keys (one entry per key ID) and the active key ID | `.env` | **No** |
| Environment mode, base URL, session-cookie Secure switch | `.env` | **No** (placeholders in `.env.example`) |
| Non-secret tuning (rate-limit numbers, page sizes) | `config/app.php` reading environment defaults | Yes (initial values are selected assumptions in ASSUMPTIONS.md and remain configurable) |
| Secret key backup | Separate encrypted, access-controlled location outside the project | **No**, never in the repository |

### 6.2 `.env` variable names (names only, placeholders only)

| Name | Meaning |
|---|---|
| `APP_ENV` | `development` or `production` (controls error display) |
| `APP_BASE_URL` | Site address used in claim links [Unresolved Q-04] |
| `APP_TIMEZONE` | `UTC` |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` | Database connection |
| `SESSION_COOKIE_SECURE` | `1` when HTTPS is used |
| `HMAC_ACTIVE_KEY_ID` | The key ID for new codes |
| `HMAC_KEY_<id>` | Secret for each key ID, as random bytes in a text encoding [format is a Stage 3 detail] |

### 6.3 Rules [Requirement + Recommendation]

1. `.env.example` lists names with fake values; `.gitignore` excludes `.env` **from the first commit**. If a secret is ever committed, treat it as compromised and replace it.
2. Demo and development keys are obviously fake and different from any production key.
3. A small hand-written reader in `src/Support/Config` loads `.env` because PHP has no built-in reader. [ARCH 6.1]
4. Never log, print or put into the session: passwords, session IDs, CSRF tokens, plaintext codes, HMAC keys.
5. Use the least-privilege database user (not `root`); INSERT and SELECT only on `points_ledger` and `audit_logs`. [DATABASE_DESIGN section 1]

---

## 7. XAMPP (Windows) compatibility

1. Keep the project folder anywhere stable. Create an Apache **virtual host** whose `DocumentRoot` is the project's `public/` folder, add the matching hostname to the Windows hosts file, enable the vhost include, and restart Apache. [ARCH 13.7]
2. Illustrative shape only (not tested; paths and names are placeholders):

```text
<VirtualHost *:80>
    ServerName loyalty.local
    DocumentRoot "<path-to-project>/loyalty-system/public"
    <Directory "<path-to-project>/loyalty-system/public">
        Require all granted
    </Directory>
</VirtualHost>
```

3. Use the same PHP for the command line and for Apache, or at least **confirm both versions and extensions**: they can differ. [Unresolved Q-01]
4. Create the database and a dedicated user in the local MySQL/MariaDB; store credentials only in `.env`.
5. Weaker fallback if no vhost is possible: place the project in `htdocs` and add deny rules to every private folder. A missed rule exposes files, so this is a temporary development option only. [ARCH 9]
6. XAMPP over plain HTTP is for development only. [ARCH 11]

## 8. Ubuntu compatibility

1. Create an Apache site whose `DocumentRoot` is the same logical `public/` folder; enable the site; do not expose the project root. [ARCH 13.7]
2. Install a compatible PHP 8 and the PDO MySQL driver; confirm extensions for both the CLI and the Apache/FPM context. [Unresolved Q-01]
3. The web-server account gets **read** access to the code and **write** access only to `storage/logs/`. It does not get write access to the whole tree. [ARCH 13.7]
4. `.env` is readable only by its owner and the web-server group (restrictive permissions) and sits outside `public/`.
5. File names are case-sensitive: use the same case in file names and in every `require`. [ARCH 9]
6. Configure HTTPS before real customer accounts or real codes exist. [ARCH 13.7]

## 9. Portability checklist for both systems

- Paths come from one path helper using PHP's path functions; no `C:\` or `/var/www` is hard-coded. [ARCH 9]
- Database names and users come from `.env`.
- Only SQL features common to MySQL and MariaDB; no JSON column type. [ARCH 9]
- `.gitattributes` controls line endings.
- `README.md` documents the vhost, database creation and dependency notes. [ARCH 13.7]
- Test through the browser-served application, not only the command line. [ARCH 13.7]

---

## 10. Assignment submission deliverables and repository practices

The assignment PDF requires the following items in the final public GitHub repository. Their presence in this planned tree does not mean they have already been created or completed.

| Assignment item | Planned location / action | Completion condition |
|---|---|---|
| README | Repository-root `README.md` | Explain the application, stack, setup and run instructions; include a deployed link only if one exists. |
| Assumptions and rationale | Repository-root `ASSUMPTIONS.md` | Document every requirement gap filled and why the chosen option was proposed; document every selected assumption with its rationale and limitation, while distinguishing assumptions from client-verified facts. |
| AI usage record | Repository-root `AI_LOG.md` | Record 5-10 real prompts/interactions and at least two real AI mistakes, how each was detected and how it was corrected. Do not invent retrospective examples. |
| Architecture | Repository-root `ARCHITECTURE.md` | Include stack choice with reasons, data model and at least one diagram. |
| Tests and results | `TEST_PLAN.md` / `tests/` | Provide automated tests or a documented manual test plan **with actual results**. The Stage 2 result log is empty by design and must be filled only after tests are run. |
| Environment template | Repository-root `.env.example` | Placeholder values only; no real API keys, passwords or secrets. |
| Commit history | Git history (not a standalone file) | Make regular, meaningful commits that show project evolution; do not leave all work for one final-code commit. |
| Submission repository | One public GitHub repository | Repository must be accessible without login. |
| Live demo | Demonstration preparation | Be prepared for a short live demo if requested. |

The assignment asks for a deployed link **if available**; it does not make a deployed URL mandatory when none exists. The selected purchase-proof assumption (A-24) uses the concealed per-box code; the README must also disclose that no independent POS/receipt verification is performed (A-25). The public GitHub repository is a submission requirement. The user must be able to explain every part of the submission and should retain genuine AI interaction evidence throughout development.

## 11. Stage 5 implementation status

Implemented customer routes: `register.php`, `login.php`, `logout.php`, `dashboard.php`, `claim.php`, `history.php`, `rewards.php`, `redeem.php`, and `redemptions.php`. Implemented supporting services/repositories cover user/profile creation, session authentication, code HMAC verification, claim attempts, fraud signals, ledger totals/history and transactional redemptions. `bin/test_stage5.php` contains database-independent tests; `bin/register_hmac_key.php` provisions the first key registry row.

Not implemented by Stage 5: customer profile editing, first-admin creation, product/batch management, code generation/CSV export, full admin dashboard, fulfilment and cancellation, fraud review, audit/reporting/reconciliation or automated retention. See README and AI_LOG for environment limits.

## 12. Stage 6 implementation status

Implemented administrator pages: `public/admin/index.php`, `products.php`, `product_edit.php`, `batches.php`, `batch_create.php`, `batch_view.php`, `batch_generate_export.php`, `customers.php`, `customer_view.php`, `rewards.php`, `redemptions.php`, `fraud.php`, `audit.php`, `reports.php` and `reconciliation.php`. They use `bootstrap/admin.php` and the existing `AuthMiddleware::requireAdmin()` guard. Shared admin layout and responsive styles are in `templates/admin/_layout.php` and `public/assets/css/admin.css`.

Stage 6 services/repositories cover products, batches and code generation/export, customer account lifecycle, rewards/redemptions, dashboard/report queries, fraud review, audit read access and reconciliation. The database schema was not changed. `bin/create_admin.php` is the only initial administrator-creation path. Static Stage 6 checks are in `bin/test_stage6.php`; live database integration remains pending until tested against MySQL/MariaDB on the student's XAMPP/Ubuntu environment.

Not added: web-based administrator creation or role hierarchy, HMAC key rotation UI, individual-code recall selection UI, arbitrary points editing, physical packaging integration or automatic reconciliation repair. These remain excluded or operator-controlled in the approved design.

## 13. What this document does not do

This document describes the project layout and implementation status; it is not a substitute for executing the local database, browser and concurrency test plan.

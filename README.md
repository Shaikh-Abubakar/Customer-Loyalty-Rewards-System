# Customer Loyalty Rewards System

A PHP 8 and MySQL/MariaDB college project for a factory loyalty programme. Customers register, sign in, claim one-time concealed product-box codes, earn points, browse rewards and submit redemption requests. The points ledger is the source of truth for every balance.

**Implemented through Stage 5:** customer registration, login/logout, authenticated dashboard, manual product-code claiming, points history, rewards catalogue, one-unit redemption requests and customer redemption tracking. The administrator product/batch/code-generation, fulfilment, monitoring and reporting modules remain later-stage work.

## Technology

- PHP 8.0+ with PDO
- MySQL or compatible MariaDB using InnoDB and `utf8mb4`
- HTML5 in PHP files; CSS3 in separate CSS files
- Small inline JavaScript only for optional QR-fragment prefill
- XAMPP on Windows and Ubuntu Apache/PHP/MySQL compatibility
- No React, Vue, Angular, Node.js, Composer runtime dependency or separate JavaScript files

## Project layout and web root

Apache must serve **only `public/`**. Keep `.env`, bootstrap, config, application classes, templates, logs, SQL, tests and project documents outside the document root. `public/` contains the customer entry points and the CSS assets.

## Requirements

- PHP 8.0 or later
- PHP extensions: `PDO`, `pdo_mysql`, `session`, `openssl`
- MySQL 8 or compatible MariaDB with InnoDB, `utf8mb4_unicode_ci`, transactions and row locking
- Apache configured with the project's `public/` directory as its document root

Check PHP CLI and Apache separately; they can load different `php.ini` files and extensions.

## 1. Configure the application

1. Copy `.env.example` to `.env` in the project root.
2. Set `APP_BASE_URL`, `APP_TIMEZONE`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` and `DB_PASSWORD` to your local values.
3. Keep `.env` outside `public/` and never commit it. `.gitignore` excludes it.
4. In both the CLI and Apache PHP configurations, enable `pdo_mysql`. In XAMPP, check the `php.ini` used by the XAMPP PHP runtime, then restart Apache.
5. For localhost-only HTTP testing, `SESSION_COOKIE_SECURE=0` is acceptable. Use HTTPS and Secure cookies for any network-accessible deployment.

### Configure the HMAC key before claiming codes

The code-claim workflow requires an active key in `.env` and a matching registry row in `hmac_key_registry`. Generate a fresh 32-byte random key locally:

```bash
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

Copy its output into `.env` as `HMAC_KEY_1`, keeping `HMAC_ACTIVE_KEY_ID=1`. Do not paste the real key into this README, SQL, screenshots, source code or Git.

After importing the schema and configuring the database connection, run this one-time setup command from the project root:

```bash
php bin/register_hmac_key.php
```

It registers the configured active key ID and a 32-byte check value; it does not store or print the secret. It refuses to run when an active key already exists or the key ID is already registered. It is an initial provisioning helper, **not** a key rotation tool. Keep any retired key values in protected environment configuration while their codes remain in the database.

The application uses `HMAC-SHA-256` over `loyalty-code-v1:` plus the normalized code. The registry key-check uses the public fixed text `loyalty-key-check-v1`. This is an implementation convention for the otherwise unspecified fixed key-check text in `DATABASE_DESIGN.md`; it is not a secret.

## 2. Create and import the database

The schema file contains table definitions only. Create/select the target database separately:

```sql
CREATE DATABASE customer_loyalty
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

Select `customer_loyalty` in phpMyAdmin and import `database/schema.sql`. It creates the approved 17 tables and does not insert demo records.

Use a dedicated application database user, not `root`. Follow the least-privilege rules in `DATABASE_DESIGN.md`; `points_ledger` and `audit_logs` must be SELECT/INSERT only for the application account (never UPDATE or DELETE). Do not grant broader privileges merely to make a test pass.

## 3. Run on Windows XAMPP

A virtual host is preferred. Example (adjust the path):

```apache
<VirtualHost *:80>
    ServerName loyalty.local
    DocumentRoot "C:/projects/Customer reward system/public"
    <Directory "C:/projects/Customer reward system/public">
        Options -Indexes
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Add `127.0.0.1 loyalty.local` to the Windows hosts file, enable the virtual-host include, restart Apache, start MySQL from XAMPP, and set `APP_BASE_URL=http://loyalty.local` in `.env`. Open `http://loyalty.local/`.

Do not point Apache's document root at the project root or put `.env` inside `htdocs` as a public file. Run the CLI commands from the project root using the same PHP installation that has `pdo_mysql` enabled.

## 4. Run on Ubuntu Apache

Configure the Apache site with `DocumentRoot /path/to/Customer reward system/public` and an appropriate `<Directory>` block. Install PHP 8 and the PDO MySQL extension for both CLI and Apache/PHP-FPM. Give Apache read access to source files and write access only to `storage/logs/`; keep `.env` owner-readable and outside the document root. Enable HTTPS before using real accounts or product codes.

The code avoids Windows-specific absolute paths. The provided build container did not have `pdo_mysql` or a MySQL/MariaDB server, so the database-backed workflows have **not** been end-to-end verified on Ubuntu or XAMPP.

## 5. Stage 5 customer workflows

### Registration and authentication

- Registration asks for full name, email, optional phone, password and confirmation. Email is normalized to lowercase; the account and profile are inserted in one transaction.
- Passwords are hashed with PHP `password_hash()` using bcrypt cost 12 and checked with `password_verify()`. The selected policy is at least 12 characters; the implementation also caps input at 72 bytes to avoid bcrypt truncation.
- Duplicate-email feedback is generic. Registration creates a customer role only; there is no public admin selector or email-verification workflow.
- Login uses generic invalid-credential/account-state messages, an IP/account failure window, a dummy password hash for unknown emails, session-ID regeneration and a DB status check on each protected request.
- Logout is POST-only and CSRF-protected. Only the fixed `return=claim` destination is accepted as a post-login return target.

### Product-code claims

- Manual entry is the primary workflow. Input is uppercased and spaces/hyphens are removed; the result must be 16 characters from `23456789ABCDEFGHJKMNPQRSTVWXYZ`.
- A keyed HMAC-SHA-256 digest is compared with stored `codes.code_hash` values. The raw product code is not stored, logged or echoed back. Active and retired keys are checked; each needs a matching protected configuration value and registry check.
- A successful claim conditionally changes one code from `unclaimed` to `claimed`, adds one positive append-only ledger entry and records the successful attempt in one transaction. Unique constraints and conditional updates prevent code reuse and duplicate credits.
- Claims are available only from active batches. Invalid/used/voided/inactive-batch cases use non-revealing customer messages; a same-customer repeat gets the documented repeat message.
- The optional QR fragment only prefills the manual field; it never submits the form. A logged-out visitor signs in and must rescan or type the code; the plaintext is not carried through login.
- The fraud thresholds and review events follow `WORKFLOWS.md` and the adopted A-04 thresholds.

### Dashboard, history and rewards

- The dashboard reads live ledger totals, successful claim count, available reward catalogue entries, recent ledger activity and recent redemption requests.
- Points balance is always calculated by summing `points_ledger.points_delta`; no cached balance column is introduced.
- The reward catalogue shows active rewards, cost, stock and insufficient-points/out-of-stock states.
- A redemption confirmation displays cost, current balance and projected balance. Confirmation is a CSRF-protected POST with a one-time idempotency token.
- The transaction locks the customer and reward, reserves one stock unit, creates a pending redemption and appends the negative ledger debit atomically. Repeated submission with the same token returns the existing request. Customers cannot cancel their own requests.

## 6. Demo-data and remaining modules

The schema imports empty tables. The Stage 5 customer pages display genuine database values and meaningful empty states; they do not fabricate demo customers, product codes, points or rewards.

The Stage 5 scope does not yet include administrator screens or a code-generation/export tool. Therefore, a full claim demo requires a code/batch created by the later approved admin-generation stage (or controlled synthetic fixture records that obey the HMAC and foreign-key rules). Rewards can be added by the later admin module or a controlled demo seed. Use synthetic records described in `DEMO_DATA_GUIDE.md`; never commit actual product codes, HMAC keys or export CSVs.

**Purchase-proof limitation:** possession of a valid concealed one-time code is the selected operational purchase credential. The system does not independently verify a retailer receipt or point-of-sale transaction (A-24/A-25).

## 7. Tests and verification

Run these commands from the project root:

```bash
php -v
php -m
php bin/test_foundation.php
php bin/test_stage5.php
php bin/test_database.php
```

- `test_foundation.php`: 20 shared configuration/session/helper checks.
- `test_stage5.php`: 26 database-independent checks for code normalization/alphabet, HMAC key and digest handling, password hashing, UTF-8 character counting and a source-level SQL-string regression guard.
- `test_database.php`: real PDO MySQL connection test; it needs `pdo_mysql` and a running database.
- Run the full browser, SQL, concurrency, rate-limit and CSRF test plan in `TEST_PLAN.md` on your own XAMPP or Ubuntu installation. Do not mark tests as passed until they are run there. Record actual versions and results.

## Security notes

- `.env`, passwords, database credentials, HMAC secrets, plaintext product codes and export files never belong in Git.
- Customer pages use server-side authentication, role/status checks, CSRF for state changes, prepared statements, escaped output and private no-store responses.
- Claim and redemption state changes use transactions; the points ledger is append-only and is the sole source of balance.
- Database timestamps are stored in UTC; PDO sets UTC and strict SQL mode.
- The first administrator and all admin workflows are not part of Stage 5; there is no public admin registration.

## Assignment and Git submission

The assignment requires meaningful incremental commits, a 5–10 interaction AI log with at least two genuine AI errors/corrections, test evidence and a public repository. The supplied Stage 4 ZIP does not contain `.git` metadata, so no commit history could be inspected or created here. Apply the Stage 5 files to your own Git working tree, inspect the changes, then commit the stage as a separate milestone. Add your actual earlier prompts and student-run test evidence to `AI_LOG.md`/`TEST_PLAN.md`; do not claim that sandbox checks are XAMPP tests.

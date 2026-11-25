# Customer Loyalty Rewards System

A PHP 8 and MySQL/MariaDB college project for a factory loyalty programme. Customers will claim one-time concealed product-box codes to earn points and redeem rewards; the factory administrator will manage products, batches, customers, rewards and monitoring.

**Current implementation:** Stage 4 foundation only. Customer registration/login, code claims, points transactions, rewards, redemptions and administrator modules are intentionally not implemented yet.

## Technology

- PHP 8 with PDO
- MySQL or MariaDB using InnoDB and `utf8mb4`
- HTML5 inside PHP templates; CSS3 in separate files
- Windows XAMPP as the primary development environment; Ubuntu Apache/PHP/MySQL compatibility is a design target
- No front-end framework, Node.js or separate JavaScript files

## Project layout

Apache must serve **only** `public/`. Keep `.env`, bootstrap, config, application classes, templates, logs, SQL and tests outside the document root. The Stage 2 and Stage 3 design documents remain at the project root.

## Requirements

- PHP 8.0 or later
- PHP extensions: `PDO`, `pdo_mysql`, `session`, `openssl` (plus standard JSON support)
- MySQL 8 or a compatible MariaDB release with InnoDB and `utf8mb4_unicode_ci`
- Apache configured with the project's `public/` directory as its document root

Confirm versions in both PHP CLI and the Apache PHP runtime; they may differ.

## 1. Configure the application

1. Copy `.env.example` to `.env` in the project root.
2. Edit `.env` with local values for `APP_BASE_URL`, `APP_TIMEZONE`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` and `DB_PASSWORD`.
3. Keep `.env` outside `public/` and never commit it. `.gitignore` already excludes it.
4. Check the PHP configuration used by both CLI and Apache. Run `php --ini` in a terminal; in XAMPP, open the matching `php.ini` and ensure the `pdo_mysql` extension is enabled (for example, the `extension=pdo_mysql` line is not commented out). Restart Apache after changing its PHP configuration.
5. For a local HTTP-only XAMPP demonstration, `SESSION_COOKIE_SECURE=0` is allowed only while using localhost. HTTPS is required for network-accessible deployments; Secure cookies are enabled automatically when PHP sees HTTPS. Do not trust forwarded proxy headers unless a trusted proxy is explicitly configured.
6. `HMAC_ACTIVE_KEY_ID` and `HMAC_KEY_<id>` are placeholders for later code-generation stages. Replace them locally with generated secrets before implementing code generation. Never use the placeholder value or commit a real key.

The `.env` reader supports simple `KEY=value` lines, single/double-quoted values and comments. Process environment variables take precedence over `.env` values.

## 2. Create the database

The schema file contains table definitions only. It deliberately does **not** contain `CREATE DATABASE` or `USE` as its first statements; create/select the target database separately before importing it.

In phpMyAdmin or a MySQL/MariaDB administrator console, create an empty database using the approved character set/collation:

```sql
CREATE DATABASE customer_loyalty
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

Select `customer_loyalty` and import `database/schema.sql`. The schema creates exactly the 17 tables described in `DATABASE_DESIGN.md`; it inserts no demonstration data.

Use an application database account, not `root`. Grant only the table permissions needed by the owning services. In particular, `points_ledger` and `audit_logs` must receive **SELECT and INSERT only** for the application account; never grant UPDATE or DELETE on those two tables. Grant only the specific operations needed on other tables, with DELETE reserved for the later retention operation on attempt tables if implemented.

## 3. Run on Windows XAMPP

Preferred setup is an Apache virtual host whose `DocumentRoot` points to the project's `public` directory. Example (replace the path with the actual project path):

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

Add `127.0.0.1 loyalty.local` to the Windows hosts file, enable the virtual-host include in Apache, restart Apache, and set `APP_BASE_URL=http://loyalty.local` in `.env`. Start both Apache and MySQL from the XAMPP Control Panel. Open `http://loyalty.local/`.

Do not point `DocumentRoot` at the project root or put `.env` under `htdocs` as a public file.

## 4. Run on Ubuntu Apache

Configure an Apache site with `DocumentRoot /path/to/Customer reward system/public` and a matching `<Directory>` block. Install PHP 8 and the PDO MySQL driver for both CLI and Apache/FPM contexts. Give Apache read access to source files and write access only to `storage/logs/`; keep `.env` owner-readable and outside the document root. Enable HTTPS before using real accounts or codes.

No operating-system-specific absolute path is embedded in application code. The Ubuntu deployment is a compatibility target; it has not been tested by this Stage 4 build.

## 5. Verify the foundation

Run from the project root:

```bash
php -v
php -m
php bin/test_foundation.php
php bin/test_database.php
```

- `test_foundation.php` checks the shared configuration, session and helper foundation.
- `test_database.php` makes a real PDO MySQL connection attempt and reports the database server version only on success.

A successful syntax/smoke test does not mean the database is connected. Record the PHP, MySQL/MariaDB and Apache versions and actual results in `tests/foundation_checks.md` and `TEST_PLAN.md` after testing your own XAMPP/Ubuntu environment.

## Security notes

- Passwords, DB credentials and HMAC keys do not belong in source code, SQL, logs or Git.
- Code values will be stored as HMAC-SHA-256 hashes with key IDs, not plaintext.
- The points ledger and audit log are append-only; the application database account must not be able to update or delete either table.
- All dates stored in the database are UTC `DATETIME` values; the PDO connection sets UTC and strict SQL mode.
- The first administrator is created through the approved CLI workflow in a later stage; there is no public admin registration.

## Stage boundary and next work

Stage 4 provides configuration, secure sessions, error handling, helpers, database connectivity, the approved initial schema, a placeholder landing page and CSS tokens/components. Implement authentication and first-admin CLI workflow in the next approved stage. Do not treat the database schema or the placeholder page as a completed loyalty application.

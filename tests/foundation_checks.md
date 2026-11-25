# Stage 4 foundation checks

This record covers only the checks actually run in the build container. It does not mark customer/admin workflows or a live database import as tested.

## Environment used

- PHP CLI: **8.4.24** (PHP 8.0-compatible syntax was used; the test runtime itself was 8.4.24).
- PHP extensions: `PDO`, `session`, `openssl`, and JSON available.
- PDO drivers: **none available**; `pdo_mysql` is not enabled.
- MySQL/MariaDB client/server: not available in this container.
- Apache: **2.4.68 (Debian)**; `apache2ctl -t` returned `Syntax OK` (with a non-blocking ServerName warning).
- No Windows XAMPP installation was available. Ubuntu/XAMPP end-to-end compatibility was therefore not tested.
- No real `.env` file or production secrets were created or used.

## Executed checks

| Check | Result | Detail |
|---|---|---|
| PHP syntax | PASS | All 18 PHP files passed `php -l`. |
| Foundation smoke test | PASS | 20 checks, 0 failures (`php bin/test_foundation.php`). |
| Environment configuration parser | PASS | Quoted `.env` value parsing and process-environment key discovery were verified. |
| Session/CSRF/helpers | PASS | Strict session mode, HttpOnly/SameSite settings, HTTPS Secure-cookie behavior, session regeneration, CSRF accept/reject, HTML escaping and URL validation were checked. |
| Missing DB configuration handling | PASS | Incomplete database settings fail with a generic message; the test password marker was not disclosed. |
| Application entry point | PASS | GET `/` returned HTTP 200 from PHP's built-in development server. Placeholder content rendered and did not expose PDO internals. |
| HTTP security headers | PASS | Response included CSP, `X-Content-Type-Options`, `X-Frame-Options`, Referrer-Policy, no-cache headers and an HttpOnly/SameSite session cookie. The request used HTTP, so the cookie was not marked Secure; Secure behavior was checked separately under simulated HTTPS. |
| CSS loading | PASS | `base.css` and `customer.css` each returned HTTP 200 with `text/css`; the expected design token was present. |
| Apache syntax | PASS | Host Apache configuration syntax check returned `Syntax OK`; this was not a test of the project's XAMPP/Ubuntu virtual host. |
| Schema structure | PASS (static inspection only) | 17 approved table definitions, InnoDB/utf8mb4 declarations, 34 foreign keys, named unique indexes and no `ON DELETE CASCADE` were checked in the SQL text. The first SQL statement is `CREATE TABLE users (`; no `CREATE DATABASE` or `USE` statement is included. |
| PDO MySQL connection | BLOCKED | `php bin/test_database.php` exited 1 because `pdo_mysql` is not enabled in this container. |
| SQL import / FK execution | NOT RUN | No MySQL/MariaDB server or client was available. The schema has not been executed against a database here. |

## Repeat locally in XAMPP

From the project root, after creating `.env` and an empty database:

```bash
php -v
php -m
php bin/test_foundation.php
php bin/test_database.php
```

Then import `database/schema.sql` into the already-created database using phpMyAdmin or the MySQL/MariaDB client. Confirm the 17 tables and all foreign keys exist. Check both PHP CLI and the PHP runtime used by Apache; they can load different `php.ini` files and extensions.

Record the actual XAMPP PHP and MySQL/MariaDB versions and database import/connection results here after running the project locally. Do not treat the static schema check as proof that the SQL imported successfully.

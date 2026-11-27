# ASSUMPTIONS.md: Customer Loyalty Rewards System

Stage 2 document. It keeps assignment requirements, project constraints, verified facts, engineering recommendations, selected assumptions and open verification items **separate**, so a design choice is not mistaken for a client-confirmed fact.

Companion documents: [ARCHITECTURE.md](ARCHITECTURE.md), [DATABASE_DESIGN.md](DATABASE_DESIGN.md), [WORKFLOWS.md](WORKFLOWS.md), [PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md), [TEST_PLAN.md](TEST_PLAN.md).

## 0. How this file is organised

| Section | Contents | Can the design rely on it? |
|---|---|---|
| 1 | **Confirmed decisions** | Yes |
| 2 | **Assignment requirements** | Yes; transcribed from the uploaded Assignment 2 PDF |
| 3 | **Technical facts** | Yes, with the stated verification status |
| 4 | **Recommendations** | Only if you accept them; they are my engineering judgement |
| 5 | **Selected design assumptions** (A-01 to A-35) | Adopted for this coursework design under your instruction to make reasonable assumptions; not represented as client-verified facts. |
| 6 | **Open verification items** (Q-01 to Q-22; most business questions are resolved by selected assumptions) | Only the listed environment, domain and repository checks remain open. |
| 7 | **Contradictions and gaps found** (C-01 to C-22) and the corrections/remaining gaps recorded across the six design documents | Review required |
| 8 | Approval tracking | n/a |

 > Section 5 records design assumptions selected to fill gaps because the assignment expressly allows assumptions and you instructed me to choose practical defaults. These are adopted for the coursework design, **not claims that the client separately confirmed them**. References use "[Assumption A-xx]". Section 6 contains only facts that still require a real environment, deployment domain or repository inspection. The uploaded Assignment 2 PDF is the source for section 2; additional technology constraints came from the separate project brief.

**Numbering note.** A-01 to A-35 are the selected design assumptions used by the six documents. Q-19 remains open only because no separate older repository copy was supplied for comparison.

---

## 1. Confirmed decisions

### 1.1 Approved Stage 1 decisions (preserved unchanged)

| # | Decision | Reflected in |
|---|---|---|
| 1 | QR generation is **hybrid**: the vendor CSV is the baseline; an optional PHP QR library may be added behind an adapter | ARCH 8.1, WORKFLOWS 2.4 |
| 2 | **PHPUnit** is used if compatible with the installed PHP | TEST_PLAN section 2 (pending) |
| 3 | Codes are stored as **HMAC-SHA-256 hashes with key IDs** | DATABASE_DESIGN 3.7, 3.8, section 7 |
| 4 | **16-character** codes from `23456789ABCDEFGHJKMNPQRSTVWXYZ` (30 symbols), secure random, no biased modulo | WORKFLOWS 2.3 |
| 5 | QR claim links use the **URL fragment**; manual entry always works | WORKFLOWS 3.1, 3.2 |
| 6 | The code sits under a **scratch-off layer and/or on an insert inside the box** | WORKFLOWS 3.1 |

**Statement that must stay true in every document:** the code proves the customer possesses a genuine factory-issued secret. It does **not** independently prove a retail sale.

### 1.2 Confirmed through your separate Stage 2 project brief (not the Assignment 2 PDF)

- The project-specific technology and deployment constraints in section 2.3 below.
- Stage 2 consists of six documents, and `hmac_key_registry` must exist in the database design.
- No PHP code, no SQL schema files, no created tables, no installed dependencies, no Stage 3 work, and no invented test results or environment information during Stage 2.
- Stage 2 stays open until all six documents are reviewed for consistency.

---

## 2. Assignment requirements

The following requirements are taken from the uploaded one-page **Assignment 2: Customer Loyalty Rewards System** PDF. This replaces the earlier caveat that the assignment text was unavailable. The PDF does not prescribe a programming language, database, framework or specific code/QR implementation.

### 2.1 Functional requirements stated by the assignment [Requirement]

| ID | Assignment requirement | Current design coverage |
|---|---|---|
| AR-01 | Design and build a customer loyalty rewards system for a factory owner whose physical products are sold in boxes | Overall system scope and factory-owner/admin workflows are documented |
| AR-02 | Address how a customer proves they bought a product | **Covered by the selected coursework model:** each sealed product box contains one concealed, single-use factory code; revealing and successfully claiming that code is accepted as the system's operational proof-of-purchase credential (A-24). It is a deliberate proxy, not independent POS/receipt verification (A-25). |
| AR-03 | Address how fraud and code reuse are prevented | HMAC-backed code verification, one-time conditional claiming, uniqueness/idempotency controls, rate limiting and fraud events are designed; see ARCH, DATABASE_DESIGN, WORKFLOWS and TEST_PLAN |
| AR-04 | Address how the factory owner views, manages and monitors rewards | Administrator dashboard, product/batch management, reward catalogue, fulfilment, fraud review, audit and reporting workflows are designed |

### 2.2 Repository and submission requirements stated by the assignment [Requirement]

| ID | Required item | Required content / status in this design |
|---|---|---|
| AR-05 | `README.md` | Must explain what the app does, the tech stack, setup/run instructions and a deployed link if available. Planned in PROJECT_STRUCTURE; final content remains a later deliverable. |
| AR-06 | `ASSUMPTIONS.md` | Must document every gap filled and why that option was chosen. The selected-assumption register records every gap filled and the rationale/limitation for each selected option; assumptions are distinguished from facts confirmed by the factory client. |
| AR-07 | `AI_LOG.md` | Must record 5–10 key prompts/interactions, including at least two cases where the AI was wrong, how the mistake was noticed and how it was fixed. Planned in PROJECT_STRUCTURE; entries must reflect real interactions, not invented examples. |
| AR-08 | `ARCHITECTURE.md` | Must include stack choice with reasons, data model and at least one diagram. Present in the architecture document. |
| AR-09 | Tests | Automated tests or a documented manual test plan **with results**. TEST_PLAN currently contains a plan and an intentionally empty result log; actual results remain a final submission task. |
| AR-10 | `.env.example` | Placeholder values only; never commit API keys, passwords or secrets. Planned in PROJECT_STRUCTURE. |
| AR-11 | Commit history | Regular, meaningful commits must show project evolution; one final-code-only commit loses marks. This is a development/repository practice, not a document or file. |
| AR-12 | Submission repository | Submit one public GitHub repository accessible without login. |
| AR-13 | Live demonstration | A short live demo may be requested; this is a possibility to prepare for, not a guaranteed event. |

### 2.3 Project-specific technology and delivery constraints [Confirmed project constraints; not stated in the Assignment 2 PDF]

These constraints were supplied separately as part of the project brief. They must be followed for this project, but must not be misattributed to the uploaded assignment PDF.

| ID | Project constraint |
|---|---|
| P-01 | PHP 8, MySQL or MariaDB, **PDO** for all database access |
| P-02 | HTML inside PHP files; CSS3 in separate CSS files; JavaScript only inline in PHP files and only when necessary |
| P-03 | No React, Vue, Angular, Node.js, separate `.html` files or separate `.js` files |
| P-04 | Windows XAMPP is the primary environment; must also work on Ubuntu (Apache, PHP, MySQL/MariaDB) |
| P-05 | PHP libraries, Composer and test frameworks are allowed but must be justified by real compatibility |
| P-06 | Use a simple architecture appropriate to the project; avoid unnecessary microservices (also an engineering recommendation in ARCH section 2) |

### 2.4 Current designed solution scope (design choices, not individual assignment requirements)

The current design elaborates the assignment through customer registration/login; products; production batches; unique code generation; vendor CSV export; batch activation; QR and manual code claiming; points history; reward catalogue and redemption; administrator management; fraud monitoring; audit records; and reports. These are selected solution features and Stage 1 decisions. The assignment names the factory owner but does not define separate staff roles. For AR-02, the project adopts the concealed per-box code as the purchase-proof credential under A-24, while clearly disclosing that no retailer/POS transaction is independently verified under A-25.

---

## 3. Technical facts

F-01 to F-06, F-08 to F-10, F-12 and F-13 retain their Stage 1/general-knowledge verification status as shown. F-07, F-11 and F-14 to F-15 were checked against official MySQL/MariaDB, IETF, PHP, MDN and W3C documentation during this review; implementation and browser behavior still require Stage 3 tests.

| ID | Fact | Where used | Status |
|---|---|---|---|
| F-01 | PHP `random_int` uses the operating system's secure random source and returns uniform integers | Code generation | From Stage 1 research (PHP manual); verify |
| F-02 | `hash_equals()` compares strings in constant time | CSRF, key check | PHP manual (Stage 1) |
| F-03 | `password_hash` / `password_verify` are the supported way to store and check passwords; use a column of 255 characters | `users.password_hash` | General knowledge of PHP manual; verify |
| F-04 | `htmlspecialchars` default flags include quote handling since PHP 8.1 | Escaping | ARCH 6.4 (Stage 1) |
| F-05 | Browsers do not send the URL fragment in the HTTP request | QR claim link | Stage 1 (HTTP/URL standards); confirm in browser test |
| F-06 | InnoDB: at REPEATABLE READ a plain SELECT uses a snapshot from the first consistent read; at READ COMMITTED each plain SELECT sees the latest committed data; locking reads see the latest committed row | DATABASE_DESIGN 6.2 | Stage 1 (MySQL manual); verify |
| F-07 | MySQL InnoDB and MariaDB unique indexes permit multiple NULL values in nullable unique-index columns | Nullable unique ledger reference columns | Official [MySQL 8.4 manual](https://dev.mysql.com/doc/refman/8.4/en/create-index.html) and [MariaDB index guide](https://mariadb.com/docs/server/mariadb-quickstart-guides/mariadb-indexes-guide), checked 2026-11-25 |
| F-08 | PDO MySQL `rowCount()` reports changed rows by default | Conditional updates | General knowledge of PHP manual; verify |
| F-09 | DDL statements in MySQL/MariaDB cause an implicit commit | Migrations | General knowledge; verify |
| F-10 | `utf8mb4_unicode_ci` exists in both MySQL and MariaDB | Collation | ARCH 6.6; `[Unresolved]` confirm with `SHOW COLLATION` |
| F-11 | Under HTTP semantics, a 3xx `Location` value that omits a fragment inherits the original fragment. Supplying an explicit empty fragment (`#`) prevents that inheritance. | QR claim redirect to login (WORKFLOWS 1.2 and 3.1) | [IETF RFC 9110 §10.2.2 and §17.11](https://www.rfc-editor.org/rfc/rfc9110.html), checked 2026-11-25; test browser handling in Stage 3 |
| F-12 | chillerlan/php-qrcode lists PHP 8.2+ and endroid/qr-code lists PHP ^8.4 for current releases | Optional QR | ARCH 8.1 (Stage 1); neither installed or tested |
| F-13 | 30^16 is about 4.3 × 10^23 (about 78.5 bits) | Code space | Arithmetic from decision 4 |
| F-14 | `php://memory` always stores stream data in memory; `php://temp` spills to a temporary file after its configured threshold (2 MB by default) | In-memory CSV export | Official [PHP stream wrapper manual](https://www.php.net/manual/en/wrappers.php.php), checked 2026-11-25 |
| F-15 | A CSP nonce must be unpredictable and unique per response; the same nonce is placed in the policy and on the intended script/style element. W3C CSP3 recommends at least 128 bits from a cryptographically secure RNG. | Inline-script CSP | [MDN CSP guide](https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/CSP) and [W3C CSP Level 3](https://www.w3.org/TR/CSP3/), checked 2026-11-25 |

---

## 4. Recommendations (engineering judgement, not assignment requirements)

You must be able to explain and defend each of these. All are labelled [Recommendation] where they appear.

| ID | Recommendation | Document |
|---|---|---|
| RC-01 | Layered monolith, no framework | ARCH section 2 |
| RC-02 | Ledger is the only source of the balance; no balance column | DATABASE_DESIGN 9 |
| RC-03 | `READ COMMITTED` plus customer-row lock for points-changing transactions | DATABASE_DESIGN 6.2, 6.3 |
| RC-04 | Fixed lock order users → batches → codes → redemptions → rewards | DATABASE_DESIGN 6.3 |
| RC-05 | Decide by **conditional update** and changed-row count, not by read-then-write | DATABASE_DESIGN 6.1 |
| RC-06 | Nullable unique reference columns on the ledger as the duplicate backstop | DATABASE_DESIGN 3.11 |
| RC-07 | `BINARY(32)` for `code_hash`; hex conversion only in the repository | DATABASE_DESIGN 3.8 |
| RC-08 | `DATETIME` in UTC rather than `TIMESTAMP` | DATABASE_DESIGN 1 |
| RC-09 | No `ON DELETE CASCADE`; deactivate instead of delete | DATABASE_DESIGN 1 |
| RC-10 | Least-privilege database user; INSERT and SELECT only on ledger and audit tables | DATABASE_DESIGN 1 |
| RC-11 | `key_check_value` column (HMAC of fixed text) to verify `.env` keys against the registry without storing secrets | DATABASE_DESIGN 3.7 |
| RC-12 | Generation and export in **one request**, plaintext only in memory, two commits | DATABASE_DESIGN 8.2, WORKFLOWS 2.3, 2.4 |
| RC-13 | Cross-key duplicate check at code generation | DATABASE_DESIGN 7.4 |
| RC-14 | Fixed prefix inside the HMAC input (`loyalty-code-v1:` + code) | WORKFLOWS 2.3 |
| RC-15 | Use an explicit empty redirect fragment and a fixed allowlisted return token; customer rescans or types after login | WORKFLOWS 1.2, 3.1 |
| RC-16 | One-time `idempotency_token` on the redemption form | DATABASE_DESIGN 3.13 |
| RC-17 | Fraud events and failed attempts recorded **outside** the rolled-back transaction | ARCH 3.2; DATABASE_DESIGN 6.1 |
| RC-18 | Password check even when the account does not exist; generic login and registration messages | WORKFLOWS 1.1, 1.2 |
| RC-19 | `rate_limited` rows are not counted toward the limit | DATABASE_DESIGN 3.10 |
| RC-20 | Keyset paging ("older than the last id") for history | DATABASE_DESIGN 9 |
| RC-21 | Key rotation as an operator procedure with a command-line helper | WORKFLOWS 6.7 |
| RC-22 | Virtual host with `DocumentRoot = public/` on both XAMPP and Ubuntu | PROJECT_STRUCTURE 7, 8 |

---

## 5. Selected design assumptions (A-01 to A-35)

The assignment explicitly permits assumptions for missing requirements. The following choices are therefore adopted as the working coursework design. They are **not represented as statements independently confirmed by the factory owner**. They may be changed only if later client facts, legal obligations or environment checks require it; every selected choice has a rationale and is reflected in the companion documents.

| ID | Selected assumption | Why this option was chosen / boundary | Status |
|---|---|---|---|
| A-01 | Email is the unique login identifier; phone is profile/contact data only. | Simple account recovery and unique lookup without SMS infrastructure. | Adopted for design |
| A-02 | Registration is open; email verification is not required in the first version. | No email service or verification workflow is required for the coursework demo. Generic registration responses still prevent account enumeration. | Adopted for design |
| A-03 | Passwords must be at least 12 characters; no arbitrary character-class rules are imposed. | Encourages length and passphrases while keeping validation understandable. Passwords are stored only as password hashes. | Adopted for design |
| A-04 | Block further logins after 5 failed logins per account or IP in 15 minutes. Block code claims after 10 invalid code attempts per account or IP in 15 minutes. Twenty successful claims per account in a rolling 24 hours creates a fraud-review alert but does not automatically block legitimate claims. Ten or more distinct accounts claiming from one IP within 1 hour also creates an alert. `rate_limited` claim-attempt rows do not count toward the claim failure limit; a login request rejected solely because its lockout is already active does not create another `login_attempts` failure row. | Separates abuse-prevention lockouts from review-only signals, reducing the chance of blocking a customer with many genuine purchases. Keep thresholds configurable and tune before production. | Adopted for design |
| A-05 | Each product has a default points value per box/code; a batch copies that value and may override it when created. | Preserves historical batch economics when a product default changes later. | Adopted for design |
| A-06 | One reward unit is issued per redemption; every reward has finite stock. | Avoids unlimited-stock and quantity edge cases in the first version. | Adopted for design |
| A-07 | The first administrator is created once through a protected CLI script; additional administrators, if needed, are also created through the controlled CLI process. No public admin registration or web-based administrator management. | Prevents public privilege escalation and avoids inventing a multi-role admin hierarchy. | Adopted for design |
| A-08 | Redemption states are `pending`, `fulfilled`, and `cancelled`. Only an administrator may cancel a pending redemption; cancellation creates a ledger reversal and restores one unit of stock. A fulfilled redemption cannot be cancelled through the normal workflow. | Provides a simple fulfilment lifecycle and preserves accounting history. | Adopted for design |
| A-09 | Points do not expire. | Avoids expiry jobs and expiry-related disputes in the initial system. | Adopted for design |
| A-10 | Maximum batch size is 1,000 box codes per generation/export request. | Bounds memory and request time for a simple PHP/XAMPP application. The cap must still be load-tested against the actual PHP environment; reduce it if required, do not increase it without retesting. | Adopted for design; runtime check remains |
| A-11 | `claim_attempts`, `login_attempts` and the Stage 7 `registration_attempts` table are retained for 90 days; technical application logs for 30 days. `fraud_events` and reconciliation records are retained for at least 2 years and are not automatically deleted in the first version. `audit_logs` are append-only and are not automatically deleted in the first version. An operator manually runs the documented monthly retention-maintenance command for the attempt rows and technical logs. | Gives the coursework build a concrete minimisation schedule without undermining immutable audit history. Before real customer data is used, the factory owner must approve legally appropriate periods and any archive/disposal process. | Adopted for coursework; legal review before production |
| A-12 | A retired HMAC key remains available for verification of every code row created with it, including claimed and voided codes, for as long as those code rows are retained. Routine key rotation changes the active key but does not revoke historical keys. Revoke a key only for suspected or confirmed compromise. The initial system has no routine code-row purge, so normal key rotation never revokes historical keys. Emergency revocation makes associated codes unverifiable and requires batch/code incident handling. | Preserves both legitimate unclaimed-code claims and the same-customer repeat-claim response (A-23) after rotation. Since code rows are retained in the initial design, normal retirement does not mean routine revocation. | Adopted for design |
| A-13 | Codes have no time-based expiry. They remain claimable while unclaimed and associated with an active batch; voiding is the invalidation mechanism. | Avoids surprise expiry for products sitting in retail inventory. | Adopted for design |
| A-14 | Rewards are collected at a designated factory collection point; the system does not collect a delivery address or integrate a courier in the first version. | Keeps personal data and delivery logistics out of scope. | Adopted for design |
| A-15 | Code export is administrator-only, POST-only, requires password re-entry, is one-time per batch, audited, and streamed from memory without saving a file. | Plaintext codes are high-value secrets; the export must not be recoverable from server storage. | Adopted for design |
| A-16 | There is one administrator permission tier initially; the factory owner is the initial administrator. | Matches the named client actor without inventing supervisors/operators. | Adopted for design |
| A-17 | A closure request sets account status to `closed` immediately and disables login. The factory owner may cancel the closure and reactivate the account after identity checks during a 30-day recovery window. After 30 days, replace the email with a unique non-routable `closed-user-<id>@example.invalid` value; set `customer_profiles.full_name` to `Closed account`, clear `phone` and `contact_details`, and set `anonymised_at`. Keep ledger, redemption, audit, fraud and reconciliation references needed for integrity. | Preserves financial/reward integrity while allowing a short recovery period and removes direct profile identifiers without breaking foreign keys or the non-null name field. Final retention must be checked before production data is used. | Adopted for coursework; legal review before production |
| A-18 | Suspension and reactivation require an administrator reason category and an audit record. | Makes sensitive account-state changes explainable and reviewable. | Adopted for design |
| A-19 | No cached points balance is stored; the balance is calculated from the append-only ledger. | Keeps one source of truth and avoids cache drift at coursework scale. | Adopted for design |
| A-20 | Reconciliation is run manually by an administrator; it is not a scheduled background job. | Avoids a scheduler/worker dependency in the initial PHP deployment. | Adopted for design |
| A-21 | For the college/demo deployment, make a manual database backup before major changes and keep one separate encrypted copy. The factory owner is responsible for arranging the copy. | Suitable for a manually operated demo. Production backup frequency and recovery targets require a separate operations policy. | Adopted for coursework |
| A-22 | Batch activation is a deliberate administrator action after packaging and release checks. Voiding an active batch blocks remaining unclaimed codes; already-claimed points are not silently reversed. | Stops claims before physical packaging/release is ready and protects ledger history. | Adopted for design |
| A-23 | If a customer submits a code they already claimed, show that same customer "You have already claimed this code". For all other invalid/used/voided/inactive outcomes, use the same generic failure message. | Helpful repeat-submission feedback without revealing code validity to another account. | Adopted for design |
| A-24 | **Purchase proof:** every retail box contains exactly one unique 16-character code under a scratch-off layer or on an insert inside a tamper-evident sealed box. The customer reveals and claims that code after buying/opening the box. Possession of a valid concealed code is accepted as the project's operational proof-of-purchase credential. | Reuses the approved Stage 1 code/QR design and is practical without retailer/POS integration. Batch activation occurs only after factory packing and release checks. It proves possession of the concealed box credential, not the payment transaction itself. | Adopted for coursework design |
| A-25 | No retailer/POS integration, receipt upload or manual receipt-review workflow is included in the first version. | Avoids collecting receipts and building a second verification process. The system cannot independently confirm sale date, price, retailer or named purchaser. This limitation must be disclosed in the demo and README. | Adopted for coursework design |
| A-26 | A valid reversal may make a customer's ledger balance negative if the points were already spent. Redemptions are blocked until the calculated balance is at least the reward cost; later earned credits reduce the deficit. | Preserves the full accounting reversal rather than hiding an already-spent credit. | Adopted for design |
| A-27 | The factory owner is the named custodian of the encrypted HMAC key backup. The backup is kept offline or in a dedicated encrypted vault, separate from the application host and database backup; its passphrase is stored separately. | Preserves the key-to-key-ID association while limiting access to one accountable custodian. | Adopted for coursework; production custody review |
| A-28 | Local XAMPP demonstrations may use `http://localhost` with the Secure cookie flag off. Any deployment reachable over a network or used with real customers/codes must use HTTPS and Secure cookies. | Distinguishes a local demo exception from safe public deployment. | Adopted for design |
| A-29 | Initial XAMPP/Ubuntu deployment connects directly to Apache without a reverse proxy and uses the server-provided client address. If a trusted proxy is introduced later, forwarded IP headers are accepted only from explicitly allowlisted proxy addresses. | Avoids trusting spoofable client-supplied forwarding headers. | Adopted for design |
| A-30 | Suspension/closure reason categories are: `fraud_review`, `abuse`, `customer_request`, `security_concern`, `legal_or_retention`, `admin_correction`, and `other`. Free-text reason is optional except where the workflow requires an explanatory note. | Provides a small consistent vocabulary for audit and reporting. | Adopted for design |
| A-31 | If a customer opens a QR link while logged out, `claim.php` redirects to `/login.php?return=claim#`: the fixed `return=claim` token is allowlisted, and the explicit empty fragment prevents the original code fragment from being inherited. After successful login the customer returns to `claim.php` without the code and rescans or manually enters it. No code is stored in the session. | The explicit empty fragment prevents redirect inheritance; a fixed route token returns the customer to the interrupted task without accepting an arbitrary redirect URL or retaining the secret. | Adopted for design; browser test required |
| A-32 | Default vendor CSV columns are `batch_code`, `row_number`, `product_sku`, `points`, `code`, `claim_url`; code text is grouped into four-character blocks for printing, while claim normalisation removes separators. | Gives the print vendor enough context to assign labels to a product/batch without exposing HMAC values or key material. Vendor-specific transformation can be agreed later without changing claim semantics. | Adopted default; vendor format may vary |
| A-33 | No email-based forgot-password or automated reset flow is included initially. A customer who can log in may change their password; a locked-out customer contacts the factory owner for identity-checked support/reset. | Keeps email delivery out of scope without leaving account recovery undefined. | Adopted for coursework design |
| A-34 | Product default points and batch override points are whole numbers from 1 to 10,000 per box. Reward cost is a whole number from 1 to 1,000,000 points. | Provides explicit input bounds for service validation and prevents a free-form/unbounded numeric field; the values are comfortably within the planned signed ledger integer range for a single transaction. | Adopted for coursework; tune only with documented client/business need |
| A-35 | Registration is limited to 5 submissions per IP in a rolling 15-minute window. Attempt count/check/record operations use MySQL/MariaDB named locks to serialize concurrent requests. `rate_limited` rows are recorded but do not increase the submitted count. | Adds a persistent brake against automated account creation without adding an email-delivery dependency. This is a Stage 7 technical safeguard and does not change A-04 login or claim thresholds. The limit is configurable in `.env`. | Adopted for Stage 7 coursework; tune after XAMPP testing |

---


### 5.1 Stage 5 implementation conventions (technical details, not new client requirements)

| ID | Implementation convention | Rationale / boundary |
|---|---|---|
| IC-01 | The HMAC registry check value is `HMAC-SHA-256("loyalty-key-check-v1", secret)` using raw 32-byte output. | DATABASE_DESIGN specifies a fixed public check text but does not name it. Stage 5 selects and documents this stable text so the CLI registrar and verifier agree. It is public and is not the HMAC key. |
| IC-02 | Registration uses bcrypt cost 12 and enforces a maximum password length of 72 bytes in addition to the adopted A-03 minimum of 12 characters. | A fixed cost keeps the dummy unknown-account login hash aligned across PHP 8 runtimes; the byte cap prevents bcrypt truncation. This is an implementation safeguard, not a client-specified complexity rule. |


### 5.2 Stage 6 implementation conventions (technical details, not new client requirements)

| IC-03 | Administrator-entered batch labels are 1–30 characters and use letters, numbers, dot, underscore or hyphen; they are unique by the database constraint. | Implements the human-readable `batch_code` label required by WORKFLOWS 2.2 and UI_UX_SPEC. No new business workflow is introduced. |
| IC-04 | Password re-entry for the one-time CSV export records attempts through the existing `login_attempts` repository and login throttle configuration. | Reuses the approved account/IP throttling mechanism rather than introducing a parallel export-password rate-limit store. |
| IC-05 | The approved CSV code formatting uses four-character groups joined by hyphens; batch labels and product SKUs are constrained to start with an alphanumeric character. | Implements A-32/WORKFLOWS 2.4 and avoids spreadsheet formula prefixes in the generated CSV data fields. |


## 6. Open verification items

Business and workflow gaps that can reasonably be filled for this coursework have been decided in section 5. The following items still need external information or a real environment; they do not invalidate the design itself.

| ID | Item still to verify | Needed from | When it matters | Status |
|---|---|---|---|---|
| Q-01 | `php -v`, `php -m`, and `composer --version` in both XAMPP CLI and Apache web context | Your machine | Before selecting PHPUnit or any optional QR library; before Stage 3 | Open |
| Q-02 | MySQL/MariaDB version; availability of `utf8mb4_unicode_ci`; CHECK-constraint enforcement; support for the selected locking syntax | Your machine | Before finalizing and running the schema in Stage 3 | Open |
| Q-04 | Final public deployment domain/base URL | Factory owner / deployment | Before producing labels/QR codes for a real distribution batch. For development, use a local configurable base URL. | Open; not a Stage 2 blocker |
| Q-17 | Actual PHP `memory_limit` and `max_execution_time` in Apache | Your machine | Before testing the 1,000-code batch cap; lower A-10 if the environment cannot support it | Open |
| Q-19 | Whether older repository design files exist and whether they differ from this six-document set | Existing repository | Before replacing older files/committing the reviewed set | Open; repository not supplied |

The following questions have been resolved by the selected assumptions above: Q-03 (A-01), Q-05 (A-10), Q-06 (A-02/A-33), Q-07 (A-26), Q-08 (A-14), Q-09 (A-07/A-16), Q-10 (A-27), Q-11 (A-11/A-17), Q-12 (A-04), Q-13 (A-09), Q-14 (A-28), Q-15 (original assignment PDF inspected), Q-16 (A-29), Q-18 (A-30), Q-20 (A-32 default layout), Q-21 (A-31), Q-22 (A-24/A-25), and the numeric points bounds (A-34). Q-20 may be revisited if the actual vendor supplies a different required layout.

---

## 7. Contradictions and gaps found

This section records the initial findings and their final disposition after reviewing all six documents and the original Assignment 2 PDF. Selected assumptions are deliberately distinguished from verified client facts; the remaining open items are environment/domain/repository checks.

| ID | Finding | Type | What the companion documents do | Proposed correction to ARCHITECTURE.md |
|---|---|---|---|---|
| C-01 | ARCH 13.9 says plaintext exists "only during the controlled export process" yet lists `generated` and `exported` as separate states, with "create the export" as a later step. Plaintext cannot be kept between steps without storing it, which §7 item 8 and §10 forbid. | Contradiction in lifecycle | Generation and export happen in **one request**: Commit A (`generated`), Commit B (`exported`), then the CSV is sent from memory. If delivery fails, void and regenerate. States are unchanged. (DATABASE_DESIGN 8.2, WORKFLOWS 2.3, 2.4) | Add one sentence to 13.9: "generated and exported are reached in the same request; if the download fails the batch is voided." |
| C-02 | ARCH §3 listed 13 tables, omitting login rate limiting, status history and reconciliation records. | Inventory gap | The companion database design defines the four additional tables; ARCH §3 now lists and assigns them. | The four additional tables and their owning modules are now listed in ARCHITECTURE §3 and DATABASE_DESIGN §2. (Completed.) |
| C-03 | ARCH §3 named multiple writers for `codes` and `batches`, conflicting with §3.2 single-owner rule; ledger writers were also not explicit. | Ownership inconsistency | Database/workflow docs name the transition owners and sole LedgerService writer; coordinators delegate. | ARCHITECTURE §3 and §3.2 now specify transition ownership and sole LedgerService ledger inserts. DATABASE_DESIGN and WORKFLOWS delegate coordinated writes accordingly. (Completed.) |
| C-04 | "Designated authorized administrator" conflicted with the one-tier administrator model. | Contradiction | The factory owner is the initial administrator; any additional administrator is created through the controlled CLI procedure only (A-07/A-16). | ARCHITECTURE §13.1 and WORKFLOWS §6.1 now use the same one-tier/CLI-only rule. Resolved by selected assumption. |
| C-05 | ARCH §7 says export is owner-only, but no owner role exists. | Contradiction | Export is administrator-only under the single initial tier. | ARCHITECTURE §7 now says administrator-only, consistent with the one-tier role model. (Completed.) |
| C-06 | Generic invalid/already-used messages could confuse a customer double-clicking a code they just claimed. | Refinement | A-23 selects a same-customer repeat-claim confirmation; all other invalid/used/voided/inactive outcomes remain generic. TEST_PLAN DC-01 and CC-02 cover it. | Security summary and tests use the same selected message rule. Resolved by A-23. |
| C-07 | Older repository documents (if any) were not available for comparison. | Unknown / evidence gap | The supplied ZIP contained the five companion documents and the separate architecture upload; no older `ARCHITECTURE 0.1` repository files were supplied. | Do not assume no older files exist; compare with the actual Git repository before replacing or committing documents (Q-19). |
| C-08 | ARCH §1.2 row 6 says the scratch-off/insert placement is "in ASSUMPTIONS" while it is a **confirmed** decision. | Presentation | Recorded here under Confirmed (section 1.1) | No change needed. |
| C-09 | ARCH §6.8 and CSP: inline JavaScript is required by the project constraints, but the original nonce handling was unverified. | Security design gap | Selected a per-response cryptographically random nonce (at least 128 bits), reused only between that response's CSP header and intended inline script tags; no `unsafe-inline`, inline event-handler attributes or `eval`. | ARCH §6.8, PROJECT_STRUCTURE §3 and TEST_PLAN SC-12 now define the nonce model. Official MDN/W3C guidance is recorded as F-15; browser enforcement remains a Stage 3 test. (Resolved at design level.) |
| C-10 | Assignment AR-02 asks how a customer proves purchase, while Stage 1 correctly limits what the concealed code proves. | Requirement interpretation / assumption | Resolved for coursework by A-24: each sealed retail box carries one hidden, unique code, accepted as the operational proof-of-purchase credential. A-25 states that this is not independent receipt/POS verification. | Architecture and workflow now describe the chosen proxy and its residual limitation; no Stage 1 cryptographic decision changed. |
| C-11 | `PROJECT_STRUCTURE.md` did not list the assignment-required `AI_LOG.md`, and did not document the public-repository/meaningful-commit submission rules. | Repository deliverable gap | Added `AI_LOG.md` and a submission-deliverables section; AI entries must be factual and commit history is a repository practice. | No architecture change; final repository must satisfy the assignment's repository checklist. |
| C-12 | Technology constraints and several designed features were described as assignment requirements even though the uploaded PDF does not state them. | Source classification error | Section 2 separates the PDF's actual requirements from separately supplied project constraints and selected solution scope. | Architecture labels technology constraints as project constraints, not as requirements sourced from the assignment PDF. |
| C-13 | The test plan has no executed results yet, while the assignment requires tests or a manual test plan with results. | Final-submission completion gap | The empty result log is honestly retained for Stage 2; it is not presented as satisfying the final test-results requirement yet. | Add an explicit final-submission condition; do not fabricate results. |
| C-14 | Assignment requires `ASSUMPTIONS.md` to explain why each requirement gap was filled with its chosen option; the prior table recorded why confirmation was needed but not the rationale for the proposal. | Documentation gap | Added a rationale for every selected assumption A-01–A-35, explicitly distinguishing engineering reasoning from client-confirmed facts. | No architecture change. |
| C-15 | C-06 and TEST_PLAN DC-03 incorrectly pointed to DC-13 for the same-customer repeat-claim test, but DC-13 tests that claim-attempt rows contain no plaintext. | Cross-reference error | Corrected references to DC-01 (same-customer repeat claim) and CC-02 (concurrent double-click); both are marked as depending on A-23. | No architecture change. |
| C-16 | Duplicate test IDs (FN-19/FN-20 and CE-15) made traceability ambiguous; some tests still depended on business questions now answered by selected assumptions. | Test-plan consistency | IDs are made unique, expected outcomes updated to A-01–A-35, and tests remain Not run. | No architecture change; TEST_PLAN traceability updated. |
| C-17 | ARCH, database and workflow documents described overlapping write ownership for profiles, account status, rewards stock and reconciliation records. | Cross-document ownership contradiction | A single owning module/repository is now assigned to every table; admin and redemption workflows call the owning service methods instead of writing directly. | ARCH §3 and §3.2, DATABASE_DESIGN §2, WORKFLOWS §8 and PROJECT_STRUCTURE §4.3 now use the same map. (Completed.) |
| C-18 | The original key-revocation condition could revoke a retired key after all unclaimed codes were claimed/voided even though A-23 still needs to verify previously claimed codes for same-customer repeat feedback. | Security lifecycle contradiction | A-12 now retains retired keys for every retained code row; routine rotation never revokes them. Emergency revocation remains available for compromise. | DATABASE_DESIGN 7.2–7.4, WORKFLOWS 6.7, ARCH 13.11 and TEST_PLAN KR-05/KR-09/KR-18 align. (Completed.) |
| C-19 | The original A-11 said audit records were retained for a finite two-year period while the architecture/database made audit logs append-only with no delete privilege. | Retention contradiction | A-11 now retains audit logs without automatic deletion in the initial design; fraud/reconciliation records are kept at least two years; only attempt rows and technical logs are routinely pruned. | ASSUMPTIONS A-11, WORKFLOWS 6.8, PROJECT_STRUCTURE 4.4 and TEST_PLAN SC-11 align. (Completed.) |
| C-20 | TEST_PLAN reused `KR-18` for both historical-key repeat-claim behavior and backup-custody verification. | Test traceability | The backup-custody test is now `KR-19`; all test IDs are unique. | TEST_PLAN §4.11 and ASSUMPTIONS C-18 cross-references now distinguish the tests. (Completed.) |
| C-21 | Reward catalogue administration (create, edit, restock, deactivate and server-side authorization) was described in WORKFLOWS but lacked dedicated tests. | Test coverage gap | Added RS-14 to RS-18 for reward administration and customer-denial behavior. | TEST_PLAN §4.9 now covers the reward-management workflow and permissions. (Completed.) |
| C-22 | DATABASE_DESIGN required export plaintext to remain in memory but named `php://temp`, which can spill to a temporary file. | Storage-policy contradiction | Selected a string buffer or `php://memory`; explicitly prohibited `php://temp` for this export. Verified the stream behavior against the PHP manual (F-14). | DATABASE_DESIGN §8.2 and ASSUMPTIONS F-14 now agree. (Resolved at design level.) |

### 7.1 Cross-document checks already made

- ARCH references **DATABASE_DESIGN section 6** (transactions and READ COMMITTED): present.
- ARCH references **WORKFLOWS 2.3** (code format) and **section 3** (QR claim): present with the same numbers.
- ARCH references **TEST_PLAN section 2** (PHPUnit compatibility): present, marked pending.
- All 17 database tables in DATABASE_DESIGN §2 are represented in ARCHITECTURE §3 with exactly one owning module; ARCHITECTURE §3.1 includes a high-level data-model diagram, with the full ERD in DATABASE_DESIGN §4.2.
- Batch states, key states, ledger sign convention and lock order are specified in DATABASE_DESIGN and referenced consistently by workflows and tests. Retired-key verification remains available for all retained codes so A-23 repeat-claim feedback continues after rotation (C-18).
- Ledger writes are delegated to LedgerService; the workflow matrix now makes that delegation explicit.
- HMAC secrets remain outside the database; registry metadata, key IDs, rotation, backup association and custody are documented. Retired keys remain usable for historical codes; emergency revocation is explicitly treated as an incident response. (C-18)
- Attempt/log cleanup is distinct from immutable audit retention; the monthly CLI maintenance operation does not delete ledger or audit rows. (C-19)

---

## 8. Approval tracking

The project owner authorized the reviewer to select practical assumptions. Record that authorization without treating the resulting choices as facts confirmed by the factory client.

| ID | Decision | Date | Notes |
|---|---|---|---|
| A-01–A-35 | Adopted for coursework design under the project owner's instruction to make reasonable assumptions | 2026-11-25 | These are working design defaults, not independently client-confirmed facts; see each rationale and limitation in section 5. |

The coursework design assumptions A-01 to A-35 were selected under the project owner's instruction to make reasonable assumptions. They are adopted design defaults, not client-verified facts. Stage 2 document consistency is complete. The remaining open items Q-01, Q-02, Q-04, Q-17 and Q-19 are environment, domain or repository checks; they do not leave a functional assignment requirement without a designed solution. Assignment AR-02 is covered by A-24/A-25 with the stated proof limitation. Stage 5 ran the PHP foundation suite and database-independent code/HMAC/password tests in the build container; the live DB test was blocked because `pdo_mysql` was unavailable. These are implementation-environment checks, not student XAMPP/Ubuntu integration results. See `TEST_PLAN.md` section 7 and `tests/foundation_checks.md`.
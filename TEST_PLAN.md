# TEST_PLAN.md: Customer Loyalty Rewards System

Stage 2 design document. **This is a plan. No test has been run, no result is recorded, and no environment information has been invented.** Every test below has the status **Not run**.

Companion documents: [ARCHITECTURE.md](ARCHITECTURE.md), [DATABASE_DESIGN.md](DATABASE_DESIGN.md), [WORKFLOWS.md](WORKFLOWS.md), [PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md), [ASSUMPTIONS.md](ASSUMPTIONS.md).

Labels: **[Confirmed]**, **[Requirement]**, **[Project constraint]**, **[Fact]**, **[Recommendation]**, **[Assumption]**, **[Unresolved]**.

---

## 1. How to read this plan

### 1.1 Test types

| Code | Meaning |
|---|---|
| **M** | Manual test in a browser, following the steps |
| **A** | Good candidate for an automated PHPUnit test **if** PHPUnit is approved (section 2); otherwise run it by hand |
| **C** | Concurrency procedure: needs two or more simultaneous requests or database sessions (section 5) |
| **D** | Database-level check run directly against a **test database** (for example a privilege or constraint check) |

### 1.2 Rules for all testing

1. Use a **separate test database** and fake HMAC keys. Never use production data or production keys. [Recommendation, ARCH 6.1]
2. Tests that depend on rate limits use the selected initial values in ASSUMPTIONS A-04. These are coursework defaults and may be tuned after testing.
3. A test tied to a selected design assumption checks that the implementation follows that documented default; it does not prove the factory client independently confirmed the assumption.
4. Do not write "passed" anywhere until the test has really run. Use the result log in section 7.
5. Test through the **web server** (Apache), not only through the command line, on both XAMPP and Ubuntu. [ARCH 13.7]
6. A failed security test blocks Stage 3 sign-off for that feature.


### 1.3 Assignment 2 requirement coverage

| Assignment concern | Current design and test coverage | Design coverage status (tests not yet executed) |
|---|---|---|
| Customer proves they bought a product (AR-02) | Each retail box contains one concealed unique factory code; claiming that code after purchase is accepted as operational proof-of-purchase under A-24. A-25 explicitly excludes receipt upload and retailer/POS integration, so the system does not independently verify payment, retailer, purchase date or purchaser identity. FN-21, FN-22, FN-23, DC and batch-activation tests cover the selected model and its security boundaries. | **Designed under documented assumption A-24/A-25**; limitation disclosed. |
| Prevent fraud and code reuse (AR-03) | HMAC verification, one-time conditional state transition, unique ledger references, account/IP rate limiting, fraud-event recording and concurrency scenarios are covered by the claim/security/concurrency test catalogues. | Designed; all tests remain Not run. |
| Factory owner views, manages and monitors rewards (AR-04) | Admin authorization, product/batch administration, reward catalogue, fulfilment, audit, fraud review, reports and reconciliation have corresponding workflow and test-plan coverage. | Designed; all tests remain Not run. |

The assignment also requires the test submission to include actual results. Section 7 is intentionally blank at Stage 2; it must be populated from genuine test runs before final submission. No test result may be inferred from this plan.

---

## 2. PHPUnit and tooling compatibility (decision table)

ARCHITECTURE 8.2 and 13.8 require PHPUnit, Composer and any QR library to be chosen only after the real PHP environment is known. **No PHP environment information has been provided**, so every decision below is **Pending**. [Unresolved Q-01]

| Check | Information needed | Result |
|---|---|---|
| PHP version used by the **command line** (XAMPP) | `php -v` | **Pending** |
| PHP version used by **Apache** | A page showing the web-context version, or `phpinfo` in a protected local file | **Pending** |
| `pdo_mysql` extension present in both contexts | `php -m` and the web context | **Pending** |
| Other extensions that a chosen PHPUnit release requires | PHPUnit's published requirements for the chosen release | **Pending** |
| Composer present and version | `composer --version` | **Pending** |
| MySQL/MariaDB server version | Server version query | **Pending** |
| Which PHPUnit release is compatible | Match the PHP version above against PHPUnit's own published support table at that time | **Pending** (no release is named here on purpose) |
| Optional QR library compatibility | PHP version vs the library's declared requirement (ARCH 8.1: neither candidate has been tested) | **Pending** |

**Decision rule** [Confirmed, decision 2]: use PHPUnit only if a release compatible with the installed PHP is found. If not, run the same tests manually from this plan; the plan is written to work either way. PHPUnit would be a development dependency only. [ARCH 13.8]

**What PHPUnit can and cannot do here.** A single PHPUnit process runs tests one after another, so it can check rules and database constraints but cannot by itself create *true* simultaneous requests. Concurrency tests (section 5) use separate processes or database sessions. [Recommendation]

---

## 3. Test data to prepare (design only)

| Item | Purpose |
|---|---|
| Customers: C1, C2, C3 (active); C4 (suspended); C5 (closed) | Authentication, claiming, status checks |
| Administrator A1 | Admin tests; the first admin is created through the command-line procedure |
| Products P1 (active), P2 (inactive) | Batch tests |
| Batches in every state: draft, generated, exported, printed, active, voided | Lifecycle and claim tests |
| Codes known to the tester from a **test-only** export | Claim tests (the real system never stores plaintext) |
| Fake keys K1 (active) and K2 (retired) with registry rows | Key tests |
| Rewards R1 (stock 1), R2 (stock 5), R3 (inactive), R4 (stock 0) | Redemption tests |
| Customer with exactly enough points, one point too few, and a large balance | Ledger and redemption tests |

---

## 4. Test catalogue

Each row: **ID | what to do | expected result | type**. All status: Not run.

### 4.1 Functional tests (FN)

| ID | Steps | Expected | Type |
|---|---|---|---|
| FN-01 | Register a new customer with valid data | Account and profile created; customer logged in; role is `customer` | M, A |
| FN-02 | Admin creates a product | Product saved with SKU unique; audit row exists | M |
| FN-03 | Create a product with a duplicate SKU | Rejected with a validation message; no duplicate | M, A |
| FN-04 | Create a batch for an active product | Batch in `draft`; `points_per_code` copied from product; no codes exist | M |
| FN-05 | Change the product's default points after creating a batch | Existing batch's `points_per_code` is unchanged | M, A |
| FN-06 | Create a batch for an inactive product | Refused | M |
| FN-07 | Generate and export a draft batch | Batch ends `exported`; CSV downloads; `code_exports` row created | M |
| FN-08 | Mark printed, then activate | States change in order; audit rows exist | M |
| FN-09 | Claim a valid code in an active batch | Points added; code claimed; ledger entry exists; balance correct | M |
| FN-10 | View points history | Own entries only, newest first, readable descriptions | M |
| FN-11 | Redeem a reward with enough points | Redemption `pending`; stock −1; ledger debit | M |
| FN-12 | Admin fulfils a pending redemption | Status `fulfilled` with actor and time | M |
| FN-13 | Admin suspends and reactivates a customer | Status events recorded; customer blocked then restored | M |
| FN-14 | Reports page | Totals match known test data | M |
| FN-15 | Every page loads with no PHP warning or notice shown to the user | No error text visible | M |
| FN-16 | Repeat key workflows on XAMPP and on Ubuntu | Same behaviour; no path or case errors | M |
| FN-17 | Attempt to skip a batch state or repeat a completed transition | Conditional transition rejects the illegal skip; repeated permitted action is idempotent; audit outcome is correct | M, A |
| FN-18 | Void batches in draft, generated, exported, printed and active states | Void is allowed per documented policy and final; claims against voided batch refused | M, A |
| FN-19 | Request customer closure and apply the selected anonymisation/retention policy | Access disabled immediately; within 30 days closure can be reversed after identity check; after 30 days email becomes a unique `.invalid` address, name becomes `Closed account`, phone/contact fields are cleared and `anonymised_at` is set; ledger, redemption, audit, fraud and reconciliation references remain intact | M (A-11/A-17) |
| FN-20 | Void selected codes in a partially affected batch | Only selected unclaimed codes become `voided`; unaffected codes retain their documented state; actor, reason, scope and affected count audited | M, A |
| FN-21 | Claim a valid code concealed inside a sealed retail box after its batch is activated | The code is accepted as the per-box purchase-proof credential; points are credited once; no receipt/POS check is claimed (A-24/A-25) | M, A |
| FN-22 | Attempt to claim a code from a batch that has not been activated after packaging/release checks | Claim refused and no points credited; after authorized activation the same unclaimed code can be claimed | M, A |
| FN-23 | Void an active batch after some codes have been claimed | Remaining unclaimed codes are blocked; previously credited points remain unchanged unless a separate audited correction is performed | M, A |
| FN-24 | Attempt automatic activation after export/print without explicit admin activation | Batch remains non-active until the deliberate activation action (A-22) | M, A |
| FN-25 | Request a product default or batch points override below 1 or above 10,000 | Rejected with validation message; no product/batch write occurs (A-34) | M, A |
| FN-26 | Configure a reward cost below 1 or above 1,000,000 | Rejected; valid costs within the range save and display correctly (A-34) | M, A |
| FN-27 | Cancel a customer closure request within the 30-day recovery window after identity checks | Account returns to `active`; profile identifiers and all ledger/reward history remain unchanged (A-17) | M, A |
| FN-28 | Attempt to reverse a closure after the 30-day recovery window and anonymisation | Rejected; non-routable email/name placeholders and cleared contact fields remain; historical references are intact (A-17) | M, A |

### 4.2 Authentication and session tests (AU)

| ID | Steps | Expected | Type |
|---|---|---|---|
| AU-01 | Log in with correct credentials | Success; session ID **changes** after login | M, A |
| AU-02 | Log in with a wrong password | One generic message | M |
| AU-03 | Log in with an unknown email | **Identical** message and similar response time to AU-02 | M |
| AU-04 | Register with an already-used email | Generic message; no sign that the email exists | M |
| AU-05 | Stored password is not plaintext | Database holds only a hash | D |
| AU-06 | Submit a role field in the registration form | Ignored; account is a customer | M |
| AU-07 | Try to register an administrator through the web | Impossible; no such page | M |
| AU-08 | Suspended customer (C4) logs in | Refused with a neutral message; no internal reason shown | M |
| AU-09 | Suspend a customer who is logged in on another browser | Next request in that session is rejected | M |
| AU-10 | Logout through POST with CSRF token | Session ended; back button shows no private data | M |
| AU-11 | Logout through a plain link (GET) | Does not log out | M |
| AU-12 | Idle timeout | Session expires after the configured time | M |
| AU-13 | Cookie attributes | HttpOnly and SameSite present; Secure present when HTTPS is on | M |
| AU-14 | Post-login redirect with an external address in the "return" field | Ignored; internal page only | M |
| AU-15 | Closed (anonymised) account (C5) logs in | Refused | M |
| AU-16 | Forgot-password flow | No email-based reset flow exists; customer is directed to factory support for identity-checked assistance under A-33 | M |

### 4.3 Code generation tests (CG)

| ID | Steps | Expected | Type |
|---|---|---|---|
| CG-01 | Generate N codes | Exactly N rows in `codes`; `generated_count` = N | A |
| CG-02 | Check every generated code | Length 16 and only characters from `23456789ABCDEFGHJKMNPQRSTVWXYZ` | A |
| CG-03 | None of `0 1 I L O U` ever appears in a large sample | Zero occurrences | A |
| CG-04 | Distribution check over a large sample | Each of the 30 symbols appears roughly equally (no obvious bias); statistical judgement noted, not a strict pass/fail number | A |
| CG-05 | All hashes unique | `code_hash` unique; no duplicates | A, D |
| CG-06 | Force a duplicate hash insert (test database) | Unique index rejects it; whole generation rolls back | D |
| CG-07 | After generation, search the whole database for any plaintext code | Not found anywhere; only hashes | D |
| CG-08 | Hash is HMAC-SHA-256 with the stated input form | Recomputing from a known code and fake key gives the stored value | A |
| CG-09 | Each row carries the active `key_id` | Correct key ID on every row | A |
| CG-10 | Generate with registry/`.env` mismatch (wrong active ID or wrong secret) | Generation refused; no rows added | M, A |
| CG-11 | Generate a batch that is not `draft` | Refused | M |
| CG-12 | Generate a second time for the same batch | Refused; no extra codes | M |
| CG-13 | Simulate a failure halfway through insert | Nothing stored; batch still `draft` | A, D |
| CG-14 | Application and error logs after generation | No plaintext codes or keys | M |
| CG-15 | Quantity above the selected 1,000-code limit (A-10) | Refused with a clear message | M |
| CG-16 | Cross-key duplicate check | A candidate whose hash exists under another usable key is rejected | A |

### 4.4 Duplicate claim tests (DC)

| ID | Steps | Expected | Type |
|---|---|---|---|
| DC-01 | Claim a code, then claim it again as the same customer | Second attempt: "You have already claimed this code."; points added once (A-23) | M, A |
| DC-02 | Claim a code as C1, then try the same code as C2 | Generic invalid/used message; no points for C2 | M, A |
| DC-03 | Messages for unknown, used by another customer, voided-batch and not-yet-active-batch codes | The **same generic text** for these outcomes; a repeat claim by the same customer is tested separately under DC-01 | M |
| DC-04 | Try to insert a second ledger credit for the same `claim_code_id` (test database) | Unique constraint rejects it | D |
| DC-05 | Claim a code from a `draft`, `generated`, `exported` or `printed` batch | Refused (batch not active) | M, A |
| DC-06 | Claim a code from a voided batch | Refused; `voided_batch_code_claimed` fraud event raised | M |
| DC-07 | Claim a code marked `voided` (partial void) | Refused | A |
| DC-08 | Claim by a suspended customer | Refused; code stays `unclaimed` | M |
| DC-09 | Lowercase, spaces, hyphens in the code | Accepted and normalised | M, A |
| DC-10 | Wrong length, forbidden characters, empty input | Format message; counted as a failure for rate limiting | M, A |
| DC-11 | After a failed claim transaction (forced error) | Code still `unclaimed`; no ledger row | A, D |
| DC-12 | Ledger credit equals the batch's `points_per_code` | Equal | A |
| DC-13 | Claim attempt rows store no plaintext | Only IDs and outcome | D |

### 4.5 Concurrent claim tests (CC), method in section 5

| ID | Scenario | Expected | Type |
|---|---|---|---|
| CC-01 | Two customers submit the same valid code at the same moment | Exactly one success, one generic failure; **one** ledger credit | C |
| CC-02 | Same customer submits the same code twice at once (double-click) | One success; the other reports "You have already claimed this code."; one credit (A-23) | C |
| CC-03 | Many (for example 20) simultaneous submissions of one code from different accounts | Exactly one winner; one ledger row for that code | C |
| CC-04 | One customer claims 10 different valid codes at once | All succeed; balance equals the sum; no lost update | C |
| CC-05 | Claim a code while an administrator voids the batch | Either the claim completes first, or the claim is refused; **never** a credit after the void commits | C |
| CC-06 | Claim while an administrator suspends the customer | Either completes first or is refused; no credit after suspension is committed | C |
| CC-07 | Deadlock handling: force lock contention | At most one automatic retry; no half-finished state | C |
| CC-08 | After all concurrency tests, run the reconciliation report | No discrepancies | C, M |

### 4.6 Rate limiting tests (RL) (limits depend on A-04)

| ID | Steps | Expected | Type |
|---|---|---|---|
| RL-01 | Submit 10 invalid codes for one account within 15 minutes, then submit another | The next attempt is rate-limited; `rate_limited` does not extend the window (A-04) | M, A |
| RL-02 | A **valid** code submitted while the invalid-code account/IP lockout is active | Still refused until the 15-minute window passes; the success-count and IP-pattern alerts alone do not block it | M |
| RL-03 | Window expires | Claiming works again | M |
| RL-04 | Per-IP limit across several accounts | Limit applies to the IP | M |
| RL-05 | `rate_limited` rows are not counted toward the limit | Lockout does not extend itself | A |
| RL-06 | Submit 5 failed logins for one account or IP within 15 minutes, then submit another | Generic try-later message; `login_bruteforce` event (A-04). Unknown emails count against IP only; requests blocked by the active lockout add no further failed row and do not extend the window. | M |
| RL-07 | A limit exceeded on one account does not block a different customer | Others unaffected (except the per-IP rule) | M |
| RL-08 | Rate-limit messages and logs | No plaintext code; no hint whether the code exists | M |
| RL-09 | IP detection behind a proxy | Direct deployment uses the server client address; if a trusted proxy is added, only allowlisted proxy headers are accepted | M (A-29) |
| RL-10 | Reach 20 successful claims by one account in a rolling 24 hours | Fraud-review alert is created; valid subsequent claims are not automatically blocked by this alert alone (A-04) | M, A |
| RL-11 | Reach 10 distinct claiming accounts from one IP in one hour | IP-pattern fraud alert is created; threshold alone does not block claims (A-04) | M, A |

### 4.7 CSRF tests (CS)

| ID | Steps | Expected | Type |
|---|---|---|---|
| CS-01 | POST each state-changing form with **no** token | Rejected; no change | M, A |
| CS-02 | POST with a wrong or old token | Rejected | M, A |
| CS-03 | Token from another session | Rejected | M |
| CS-04 | Request that changes state via GET (any page) | None exist; GET never changes data | M |
| CS-05 | Open a QR link `claim.php#c=...` | Page loads; **nothing is claimed** until the button is pressed | M |
| CS-06 | Forms covered: register, login, logout, claim, redeem, profile, all admin forms, generate/export, activate, void, suspend, adjust | Every one enforces the token | M |
| CS-07 | Token compared constant-time | Code review item (`hash_equals`) | M |
| CS-08 | Cross-site form hosted on a different origin posts to the app | Blocked by token check (and SameSite cookie) | M |

### 4.8 Points ledger tests (PL)

| ID | Steps | Expected | Type |
|---|---|---|---|
| PL-01 | Balance equals the sum of `points_delta` | Matches | A |
| PL-02 | Claim then redeem then reverse | Sum is correct at each step | A |
| PL-03 | Attempt UPDATE or DELETE on a ledger row with the application's database user | Refused by privileges | D |
| PL-04 | Same for `audit_logs` | Refused | D |
| PL-05 | Reversal entry | Equal and opposite; linked to the original; original unchanged | A |
| PL-06 | Reverse the same entry twice | Second refused (unique `reverses_entry_id`) | A, D |
| PL-07 | Reversal of a reversal | Refused | A |
| PL-08 | Adjustment retried with the same idempotency key | Posted once; second reported as already applied | A |
| PL-09 | Adjustment without a reason | Refused | M, A |
| PL-10 | Column pattern per entry type (DATABASE_DESIGN 3.11) | Invalid combinations refused by the service | A |
| PL-11 | History pages | Own entries only, newest first, no duplicates or gaps across pages when new rows arrive | M |
| PL-12 | Request another user's history by changing a URL value | Impossible or ignored; session user only | M |
| PL-13 | Reconciliation run on clean data | Outcome `clean`; run row recorded | M |
| PL-14 | Deliberately create a mismatch in the test database | Finding recorded; nothing auto-corrected | M |
| PL-15 | Reverse points that the customer has already spent | Ledger balance may become negative; further redemptions are blocked until enough new points are earned (A-26) | A |
| PL-16 | Inspect the planned schema for a stored balance column | No balance column is defined; balance is derived from ledger entries | D |

| PL-17 | Correction/reversal flow: original entry remains unchanged, compensating entry links to original, retry is idempotent, audit is present | Original row remains unchanged; one linked compensating row is created; retry creates no second correction; audit row exists | M, A, D |
| PL-18 | Reconciliation run includes claim/code, pending/fulfilled redemption debits, cancelled redemption debit/reversal, other reversal links and balance/report checks | Expected discrepancies recorded; no auto-correction | M, A |
| PL-19 | Insert multiple adjustment rows with nullable claim/redemption/reversal references, then try a duplicate non-NULL reference | Multiple NULL values are accepted by the unique indexes; duplicate non-NULL business references are rejected (F-07) | D |
### 4.9 Reward stock tests (RS)

| ID | Steps | Expected | Type |
|---|---|---|---|
| RS-01 | Redeem with exactly enough points | Success; balance 0 | M, A |
| RS-02 | Redeem with one point too few | Refused; stock and ledger unchanged | M, A |
| RS-03 | Redeem reward with stock 0 | "Out of stock"; **no points taken** | M, A |
| RS-04 | Two customers redeem the last unit at once (R1) | One success; one "out of stock"; stock exactly 0, never negative | C |
| RS-05 | Many simultaneous redemptions of a reward with stock S | Exactly S succeed | C |
| RS-06 | One customer submits two redemptions at once with enough points for only one | Exactly one succeeds | C |
| RS-07 | Double-click on the confirmation form | One redemption (unique token); second shows the existing one | C, M |
| RS-08 | Redeem an inactive reward | Refused | M, A |
| RS-09 | Reward cost changed after the request | Redemption keeps the original cost | A |
| RS-10 | Cancel a pending redemption (A-08) | Reversal credit, stock +1, status `cancelled`, audit | M |
| RS-11 | Cancel and fulfil at once | Exactly one wins | C |
| RS-12 | Redeem while the customer is suspended | Refused | M |
| RS-13 | Verify each redemption status against its ledger entry and inspect stock after request, fulfilment and cancellation | Pending and fulfilled each have one debit and one reserved unit; cancellation has one exact reversal and returns one unit; repeated cancellation changes nothing | M, A |
| RS-14 | Admin creates a reward with valid name, cost and finite stock | Reward is saved; audit entry identifies actor and action | M, A |
| RS-15 | Admin changes a reward cost after one customer has redeemed it | New redemptions use the new cost; the existing redemption retains its original recorded cost | M, A |
| RS-16 | Admin restocks a reward | Stock increases by the entered amount; before/after values and actor are audited | M |
| RS-17 | Admin deactivates a reward that still has stock | Reward remains visible in admin records but cannot be newly redeemed by customers | M, A |
| RS-18 | Customer attempts to create, edit, restock or deactivate a reward by direct URL/POST | Denied server-side; reward data and stock remain unchanged | M, A |

### 4.10 Admin authorization tests (AA)

| ID | Steps | Expected | Type |
|---|---|---|---|
| AA-01 | Open every `admin/*.php` page without logging in | Redirected to login | M |
| AA-02 | Open every admin page as a customer | Denied (not just hidden) | M, A |
| AA-03 | POST to each admin action as a customer, with a valid customer CSRF token | Denied; nothing changes | M |
| AA-04 | Suspended administrator | Denied | M |
| AA-05 | Admin tries to claim or redeem for a customer | Not possible by default | M |
| AA-06 | Customer changes a `user_id` or `customer_id` field in a request | Ignored; session identity used | M |
| AA-07 | Direct access to private folders (`src/`, `.env`, `storage/`, `bootstrap/`) by URL | 404 or denied; contents never shown | M |
| AA-08 | Every admin action creates an audit row | Present with actor, time, reason where required | M |
| AA-09 | Audit and ledger tables cannot be edited from the application | See PL-03, PL-04 | D |
| AA-10 | Admin views a customer's data | Audited access | M |
| AA-11 | Additional administrator creation | CLI/operator procedure only; no public or web-based administrator creation (A-07/A-16) | M |
| AA-12 | Hiding a menu item is not the only protection | Typing the admin URL as a customer is denied (see AA-02) | M |

### 4.11 HMAC key rotation tests (KR)

| ID | Steps | Expected | Type |
|---|---|---|---|
| KR-01 | Registry has exactly one active key | Exactly one active key is accepted; a second active key is refused | A |
| KR-02 | `.env` active key ID differs from registry | Generation refused | M, A |
| KR-03 | Wrong secret for a key ID (check value mismatch) | Detected; that key not trusted; clear operator message with no secret shown | A |
| KR-04 | Registry row without a secret in `.env` | Reported | A |
| KR-05 | Rotate: K1 → retired, K2 → active | New batches carry K2's ID | M, A |
| KR-06 | Claim an old code hashed under retired K1 | Still verifies and claims | A |
| KR-07 | Claim a code under K2 | Claims | A |
| KR-08 | Emergency-revoke K1 after suspected compromise | No code under K1 can be verified; affected unclaimed codes/batches are voided and claimed-point impact is reviewed separately | A |
| KR-09 | Attempt routine revocation of retired K1 while any `codes` row still references it, including claimed/voided rows | Refused; routine rotation retains K1 for verification for as long as its code rows exist (A-12) | A |
| KR-10 | Wrong-key claim attempt | Behaves like an unknown code | A |
| KR-11 | Cross-key duplicate check at generation | Candidate colliding with another usable key's hash is rejected | A |
| KR-12 | Restore test: restore a database copy **without** the keys | Codes cannot be verified; the operator is told why | M |
| KR-13 | Restore with keys and registry | Representative test codes verify | M |
| KR-14 | Key secret and backup | Not in database dumps, not in logs, not in the repository, not in CSV | M |
| KR-15 | Registry rows are never deleted | Delete blocked by foreign keys | D |
| KR-16 | Rotation concurrent with a claim | Claim finishes correctly before or after, never half-way | C |
| KR-17 | Restore database and matching protected keyring backup into an isolated environment | Key IDs/check values match; representative old and current codes verify; ledger/code reconciliation is possible | M (operator recovery procedure) |
| KR-18 | A customer resubmits a code originally hashed under K1 after K1 is retired and K2 is active | K1 code still verifies; same customer receives the A-23 repeat-claim message; no second ledger entry is created | M, A |
| KR-19 | Verify backup custody and access restrictions | Only the factory owner as named custodian can access the encrypted key backup; no secret appears in logs or ordinary database backup (A-27) | M |

### 4.12 CSV export tests (CE)

| ID | Steps | Expected | Type |
|---|---|---|---|
| CE-01 | Export a draft batch through the generate action | CSV downloads once | M |
| CE-02 | Row count | Equals `generated_count` | M, A |
| CE-03 | Columns | Default order is `batch_code`, `row_number`, `product_sku`, `points`, `code`, `claim_url` (A-32) | M |
| CE-04 | Claim link format | `https://<site>/claim.php#c=<code>`; the code is in the **fragment**, not the query string | M, A |
| CE-05 | Opening a CSV link in a browser | Claim page loads; fragment never appears in the server's access log | M |
| CE-06 | Formula injection | No field starts with `=`, `+`, `-` or `@` | A |
| CE-07 | Headers | `text/csv`, attachment, `no-store` | M |
| CE-08 | Plaintext not stored | No export file on the server; no plaintext in logs, session or database | M, D |
| CE-09 | Try to export the same batch again | Refused (one export per batch); no codes re-disclosed | M, D |
| CE-10 | GET request to the export URL | Refused; POST only | M |
| CE-11 | Wrong password at re-entry | Refused; no export; no state change | M |
| CE-12 | Export as a customer or logged-out | Denied | M |
| CE-13 | Export audit and `code_exports` row | Present; contains no codes or hashes | M, D |
| CE-14 | Simulate a download failure after commit | Batch can be voided; a new batch can be created; nothing claimable | M |
| CE-15 | Verify generation and export are one HTTP request with two commits | No plaintext is stored between requests; normal success reaches `exported`; a failed/interrupted delivery follows the documented void-and-regenerate recovery | M, A |
| CE-16 | Inspect the downloaded CSV | No HMAC key or code hash appears in any field | M |
| CE-17 | Optional QR adapter absent | CSV export still works | M |
| CE-18 | Optional QR adapter present (if selected after compatibility review) | SVG output matches the claim link | M (optional) |
| CE-19 | Large batch at the selected 1,000-code limit | Completes within the actual PHP memory and execution limits; reduce A-10 if it does not | M (Q-17) |

### 4.13 Additional security and robustness tests (SC)

| ID | Steps | Expected | Type |
|---|---|---|---|
| SC-01 | Enter `<script>` and quote characters in every text field | Shown as text everywhere; no script runs | M |
| SC-02 | Enter SQL-like text in every field | Treated as data; no error leakage | M |
| SC-03 | Force a PHP or database error | Generic page with a correlation ID; no SQL, path or stack trace shown in production mode | M |
| SC-04 | Security headers on every page | `X-Content-Type-Options`, referrer policy, `no-store` on private pages | M |
| SC-05 | Inspect the claim page network requests | Only first-party resources load; no third-party scripts, fonts or images are requested | M |
| SC-06 | Open a QR link while already logged in | The page reads the fragment into the form, then removes it from the visible address bar; manual entry remains available (F-05) | M |
| SC-07 | Log review | No passwords, session IDs, CSRF tokens, plaintext codes or keys | M |
| SC-08 | Time handling | Stored times are UTC; display is correct | M |
| SC-09 | File name case on Ubuntu | No missing-file errors from case differences | M |
| SC-10 | `.env` permissions on Ubuntu | Not world-readable | M |
| SC-11 | Run monthly retention maintenance with old/new attempt and log records | Only `login_attempts`/`claim_attempts` older than 90 days and technical logs older than 30 days are removed; `points_ledger` and `audit_logs` remain untouched; fraud/reconciliation rows are not auto-deleted; run outcome is logged (A-11) | M, D |
| SC-12 | Request two separate HTML responses containing an approved inline script; inspect each CSP header and rendered script element, then attempt an un-nonced inline script and an inline event handler | Each response has a different unpredictable nonce; its script nonce matches that response's `script-src` nonce; un-nonced script and inline handler are blocked; no `unsafe-inline` or `eval` exception is present (F-15) | M |
| SC-13 | Open a QR link while logged out; inspect the redirect response and complete login | The `Location` contains the fixed `return=claim` token and an explicit empty fragment (`#`); the code is not inherited onto the login URL or returned claim URL; successful login returns to `claim.php` without the code; arbitrary return URLs are ignored; the customer must rescan or type (F-11/A-31) | M |

---

## 5. Method for concurrency tests

No special tools are assumed. [Unresolved: choose tools after Q-01]

1. **Two database sessions.** Open two separate database client sessions against the **test** database. In session 1 begin a transaction and lock the rows named in the test; in session 2 try the competing statement. Confirm that session 2 waits and then sees the committed result. This checks the lock order in DATABASE_DESIGN 6.3 without any application code.
2. **Parallel web requests.** Send the same POST many times at the same moment with separate processes (for example a small script that starts several requests together, or several browser sessions pressing the button together). Each request needs its own valid session and CSRF token.
3. **Repeat each scenario many times.** A race that passes once proves little. Record the number of runs.
4. **After every scenario** check: number of winners, number of ledger rows for that code or redemption, stock value, and run the reconciliation report.
5. Record results in section 7 only after real runs; never copy "expected" into "actual".

---

## 6. Traceability

| Design item | Tests |
|---|---|
| Decision 3: HMAC with key IDs | CG-07, CG-08, CG-09, KR-01 to KR-16 |
| Decision 4: 16-character alphabet, secure random | CG-01 to CG-04 |
| Decision 5: fragment and manual entry | CE-04, CE-05, CS-05, DC-09, SC-06 |
| Purchase-proof model A-24/A-25 | FN-21, FN-22, DC-01 to DC-07, CE-04/CE-05, batch lifecycle tests |
| Decision 1: hybrid QR | CE-17, CE-18 |
| Decision 2: PHPUnit if compatible | Section 2 |
| Duplicate claim prevention (five layers) | DC-01 to DC-13, CC-01 to CC-03 |
| Reward stock concurrency | RS-03 to RS-07, RS-11 |
| Append-only ledger | PL-03 to PL-08, PL-16 |
| CSRF | CS-01 to CS-08 |
| Admin authorization (ARCH 13.1) | AA-01 to AA-12 |
| Batch lifecycle and voiding (ARCH 13.9) | FN-07, FN-08, DC-05, DC-06, CC-05, CE-14 |
| Backup and key recovery (ARCH 13.6) | KR-12, KR-13, KR-14 |
| Error handling (ARCH 13.5) | SC-03, SC-07, CC-07 |

---

## 7. Result log (empty on purpose)

| Test ID | Date run | Environment (XAMPP / Ubuntu, PHP and DB versions) | Result | Evidence or notes |
|---|---|---|---|---|
| | | | | |

---

## 8. Entry conditions and what remains open

Before any test is run:

- The Stage 3 build exists. (Nothing has been built.)
- Q-01 and Q-02 environment checks have been completed and recorded here; if they have not, automated-tool and schema choices remain pending. (They are open verification items in ASSUMPTIONS section 6.)
- The selected assumptions affecting tests (A-04, A-08, A-10, A-12 and the other A-IDs) are used as the documented expected behaviour; revise tests if a later client fact changes a choice.
- The selected purchase-proof model is covered by FN-21/FN-22 and the code-claim security tests. If the client later requires receipt/POS proof, add a separately designed evidence workflow and tests before implementation.
- Before final assignment submission, execute the test plan and fill section 7 with actual results and environment details.

This plan has not been executed. It does not claim that any behaviour works.
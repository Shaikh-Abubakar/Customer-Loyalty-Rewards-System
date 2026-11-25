# DEMO_DATA_GUIDE.md — Customer Loyalty Rewards System

**Stage 3 deliverable.** This guide defines a coherent demonstration dataset for local college presentations. Every name, email, ID, count, date and value below is simulated. It does not describe a real factory, real customer, real sale or production batch.

## 1. Demo data rules

1. Use a separate local/demo database. Never import or reuse real customer, retailer, factory or production records.
2. Every screen containing records must label the environment or data as **Demo data** (a small consistent label in the header/footer is sufficient).
3. The system itself generates product codes using the approved secure generator. Do not place a reusable plaintext code in this guide, screenshots, source code, seed files, Git history or presentation slides.
4. The database stores only HMAC-SHA-256 code hashes and key IDs. The demo must preserve that rule.
5. Demo records must follow the approved product points, batch lifecycle, code claim, ledger, reward-stock and redemption rules.
6. Sample customers and administrators use `.test` email addresses. Do not use real phone numbers or actual email inboxes.
7. Do not invent a factory name, a logo, a support telephone number, a deployed URL or real reward supplier.
8. The sample numbers below are illustrative. They are not required production values and must not be described as factory history.

## 2. Sample environment label

| Item | Demo value |
|---|---|
| Application display name | Loyalty Rewards |
| Environment label | Demo data |
| Base URL | A local configured XAMPP/Apache host only; do not hard-code a public URL into demo records |
| Time display | UTC by default, matching Stage 2 configuration |
| Database | Separate local development database |
| External services | None required |
| QR behavior | Optional; manual entry remains the primary and complete workflow |

## 3. Sample users

Role values are exactly those in `DATABASE_DESIGN.md`: `customer` and `admin`. Account status values are `active`, `suspended` and `closed`. No password or password hash is included in this document; the application must create demo accounts with local test passwords at setup time.

| Reference | Display name | Email | Role | Status | Sample points balance | Purpose |
|---|---|---|---|---|---:|---|
| C1 | Demo Customer | `demo.customer@example.test` | customer | active | 100 | Main customer dashboard, claim history and redemption walkthrough |
| C2 | Demo Customer 2 | `demo.second@example.test` | customer | active | 30 | Demonstrates an affordable and fulfilled redemption |
| C3 | Demo Customer 3 | `demo.third@example.test` | customer | active | 0 | New-customer empty dashboard and no-history state |
| C4 | Demo Customer 4 | `demo.suspended@example.test` | customer | suspended | 15 | Demonstrates neutral suspended-account messaging; balance/history remain retained |
| C5 | Demo Customer 5 | `demo.closed@example.test` | customer | closed | 0 | Demonstrates closed-account protection; do not permit sign-in |
| A1 | Demo Factory Admin | `admin.demo@example.test` | admin | active | n/a | Administrator workspace; created through the protected CLI procedure, not public registration |

The email values use the reserved `.test` domain and must not be sent to. The demo database must not ship with a known or shared default administrator password.

## 4. Sample products

Only fields present in the approved `products` table are shown. Product images and categories are not part of the current schema.

| Reference | Product name | SKU | Default points per box | Status | Demo use |
|---|---|---|---:|---|---|
| P1 | Biscuit Box | `DEMO-BIS-01` | 10 | Active | Main sample product |
| P2 | Chocolate Box | `DEMO-CHO-01` | 15 | Active | Second sample product |
| P3 | Seasonal Box | `DEMO-SEA-01` | 8 | Inactive | Demonstrates product deactivation; no new batch may be created for it |

All values are within the adopted A-34 range of 1–10,000 points per box. Changing a product default does not change points stored on an existing batch.

## 5. Sample batches and code states

The sequence must be shown exactly as approved: `draft → generated → exported → printed → active`; `voided` is terminal and may be reached from any non-voided state.

| Batch reference | Product | Planned quantity | Points/code | Generated count | Status | Export record | Demo purpose |
|---|---|---:|---:|---:|---|---|---|
| B-001 | Biscuit Box | 100 | 10 | 100 | active | recorded | Customer claims are associated with this batch |
| B-002 | Chocolate Box | 50 | 15 | 50 | active | recorded | Customer claims are associated with this batch |
| B-003 | Seasonal Box | 20 | 8 | 20 | exported | recorded | Demonstrates waiting for the manual printed step; product was deactivated after this batch was created |
| B-004 | Chocolate Box | 25 | 15 | 25 | printed | recorded | Demonstrates activation still requires an explicit administrator action |
| B-005 | Biscuit Box | 30 | 10 | — | draft | none | Demonstrates batch creation before code generation |
| B-006 | Biscuit Box | 5 | 10 | 5 | generated | none/transaction boundary | Demonstrates the intermediate/recovery state only; it should not be a normal stable state after the one-request export flow |
| B-007 | Seasonal Box | 10 | 8 | 10 | voided | recorded | Demonstrates terminal void status and blocked remaining codes |

**Important interpretation:** the approved generation and export workflow occurs in one request. `generated` is normally a short-lived intermediate state. The table includes B-006 only to demonstrate the lifecycle/recovery edge case; do not design a separate normal `Download later` flow. A failed/interrupted one-time export follows the documented void-and-create-a-new-batch recovery path.

### 5.1 Code records

Use generated records in the local database, but do not document actual code strings. A test-only operator may obtain a code during the controlled export for a live walkthrough. Once the customer submits it, the application must retain only its HMAC hash and key ID. The customer UI must never list codes or reveal stored hashes.

| Code state | Demo setup | Customer-visible meaning |
|---|---|---|
| `unclaimed` in an active batch | At least one code in B-001/B-002 | May be claimed once |
| `claimed` | Several test codes already claimed by C1/C2/C4 | Customer points are shown through the ledger; code text is not shown |
| `voided` | One selected code in a test-only partial-recall scenario, or all remaining codes blocked by B-007 | Cannot be claimed |

The UI must not reveal whether an unknown code, a code claimed by another customer, a code in an inactive batch or a voided code exists.

## 6. Sample rewards

The current `rewards` table has no image column. Use a generic gift/coupon/mug placeholder icon in the UI; do not imply that a reward image is stored or uploaded.

| Reference | Reward name | Description | Points cost | Available stock | Status | Demo use |
|---|---|---|---:|---:|---|---|
| R1 | Gift Voucher | Sample voucher for the demonstration | 30 | 12 | Active | Affordable to C1 and C2 |
| R2 | Shopping Coupon | Sample coupon for the demonstration | 20 | 1 | Active | Used for a cancelled redemption; one unit is returned to stock |
| R3 | Coffee Mug | Sample physical reward | 150 | 4 | Active | Demonstrates insufficient points for C1/C2 |
| R4 | Seasonal Gift Box | Sample seasonal reward | 250 | 0 | Inactive | Demonstrates inactive/empty stock administration; not visible in the customer active catalogue |

Every reward has finite stock and one unit is reserved per redemption (A-06). Costs must be whole numbers from 1 to 1,000,000 (A-34). Stock never becomes negative.

## 7. Sample points ledger and balance calculation

The ledger is the only source of points balance. `points_delta` is signed: positive is a credit and negative is a debit. Existing rows are never edited or deleted.

### 7.1 C1 — Demo Customer

Illustrative entries (all dates are sample values):

| Sample entry | Type | Description | Delta |
|---|---|---|---:|
| L-001 | claim | Biscuit Box code from B-001 | +10 |
| L-002 | claim | Biscuit Box code from B-001 | +10 |
| L-003 | claim | Biscuit Box code from B-001 | +10 |
| L-004 | claim | Biscuit Box code from B-001 | +10 |
| L-005 | claim | Biscuit Box code from B-001 | +10 |
| L-006 | claim | Chocolate Box code from B-002 | +15 |
| L-007 | claim | Chocolate Box code from B-002 | +15 |
| L-008 | claim | Chocolate Box code from B-002 | +15 |
| L-009 | claim | Chocolate Box code from B-002 | +15 |
| L-010 | claim | Chocolate Box code from B-002 | +15 |
| L-011 | redemption | Redemption #1001 — Gift Voucher (pending) | -30 |
| L-012 | redemption | Redemption #1000 — Shopping Coupon (cancelled) | -20 |
| L-013 | reversal | Reversal linked to L-012 after cancellation | +20 |
| L-014 | adjustment | Sample administrator correction; reason recorded | +5 |

Calculation: claim credits `50 + 75 = 125`; redemption debits `-30 -20 = -50`; cancellation reversal `+20`; adjustment `+5`; current balance `125 - 50 + 20 + 5 = 100 points`.

The sample dashboard may show:

- Available balance: **100 points** (all ledger deltas summed).
- Points from product claims: **125 points** (claim entries only).
- Points redeemed: **50 points** (redemption debit entries; cancelled redemption remains a historical debit plus reversal).

If the interface shows a different definition for `Points redeemed`, label it clearly. Do not silently net the cancellation reversal into a new claim or erase the original redemption row.

### 7.2 C2 — Demo Customer 2

Four Chocolate Box claims from B-002: `4 × 15 = 60 points`. One fulfilled Gift Voucher redemption: `-30 points`. Current balance: **30 points**.

### 7.3 C4 — Demo Customer 4

One Chocolate Box claim: `+15 points`. Account is suspended. The balance and points history remain stored and are not silently reversed by suspension.

### 7.4 Other customers

C3 has no ledger entries and a zero balance. C5 is closed and has no current balance activity. They demonstrate empty/new and closed-account states only.

### 7.5 Sample points history view

The most recent C1 history may show a subset such as:

| Date | Description | Change |
|---|---|---:|
| 25 Nov 2026 | Claimed Biscuit Box | +10 |
| 25 Nov 2026 | Claimed Chocolate Box | +15 |
| 24 Nov 2026 | Redeemed Gift Voucher | -30 |
| 23 Nov 2026 | Reversal for cancelled Shopping Coupon redemption | +20 |
| 22 Nov 2026 | Administrator adjustment | +5 |

The actual history is newest-first and uses stable keyset pagination. The table above is representative, not an assertion of a real transaction timeline.

## 8. Sample redemption records

Use the exact statuses `pending`, `fulfilled` and `cancelled`.

| Redemption ID | Customer | Reward | Points cost | Status | Demo meaning |
|---|---|---|---:|---|---|
| #1001 | C1 Demo Customer | Gift Voucher | 30 | pending | Waiting for the administrator fulfilment step |
| #1000 | C1 Demo Customer | Shopping Coupon | 20 | cancelled | Administrator cancelled while pending; a +20 reversal is in the ledger and one stock unit was returned |
| #0999 | C2 Demo Customer 2 | Gift Voucher | 30 | fulfilled | Administrator marked the reward fulfilled/collected |

The customer view shows only that customer's records. Customers cannot cancel. A cancelled redemption is not erased and its original debit remains visible with the reversal entry.

## 9. Sample administrator dashboard metrics

Use a consistent local snapshot with the following illustrative values:

| Metric | Sample value | Definition/source |
|---|---:|---|
| Active batches | 2 | B-001 and B-002 |
| Claims today | 3 | Three successful sample code claims dated 25 Nov 2026 |
| Points issued | 200 | Total sample claim credits across C1, C2 and C4 (125 + 60 + 15) |
| Points spent (gross debits) | 80 | Redemption debit entries (C1: 30 + 20; C2: 30); cancellation reversal is displayed separately |
| Pending redemptions | 1 | Redemption #1001 |
| Open fraud events | 2 | F-001 and F-002 below |

These figures must be recalculated if any underlying demo record is changed. The dashboard is not a static set of hard-coded numbers in the final application.

## 10. Sample fraud events

Event values are illustrative. Use reserved documentation IP ranges (such as `192.0.2.0/24`) only in local test records. Event details must not contain a plaintext code, password, session ID, CSRF token, HMAC value or key.

| Event reference | Event type | Severity | Related reference | Review status | Safe summary |
|---|---|---|---|---|---|
| F-001 | `repeated_invalid_codes` | warning | C3 / `192.0.2.24` | open | Multiple invalid code attempts crossed the selected review threshold |
| F-002 | `voided_batch_code_claimed` | high | B-007 / `192.0.2.31` | open | A known code from a voided batch was submitted |
| F-003 | `login_bruteforce` | warning | `192.0.2.44` | reviewed | Repeated failed login attempts were reviewed by the administrator |
| F-004 | `many_accounts_one_ip` | warning | `192.0.2.55` | dismissed | A shared-IP pattern was reviewed and dismissed |

The selected A-04 defaults are: five failed logins per account or IP within 15 minutes blocks further logins; ten invalid code attempts per account or IP within 15 minutes blocks claims; twenty successful claims by one account in 24 hours and ten distinct accounts from one IP in one hour create review alerts only. These are adopted coursework defaults and remain configurable; the customer UI should not reveal thresholds.

Fraud review statuses are `open`, `reviewed`, `dismissed` and `escalated`. Review actions are audited; follow-up suspension, voiding or points correction is a separate workflow.

## 11. Sample audit activity

A compact recent-activity list may use safe audit metadata such as:

| Sample action | Actor | Entity | Safe display |
|---|---|---|---|
| `product.create` | A1 | Product P1 | Created product Biscuit Box |
| `batch.activate` | A1 | Batch B-001 | Activated batch after release checks |
| `reward.restock` | A1 | Reward R2 | Restocked Shopping Coupon; show before/after stock only if recorded |
| `redemption.fulfil` | A1 | Redemption #0999 | Marked redemption fulfilled |
| `customer.suspend` | A1 | C4 | Suspended demo account; reason category shown only to authorized admin |

Audit rows are read-only. Do not add edit/delete controls. Do not place secrets in the action detail text.

## 12. Sample report and reconciliation data

### 12.1 Reports

Use the same illustrative source set:

- Claims per batch: B-001 and B-002 show their successful claim counts.
- Points issued versus spent: 200 gross claim points issued and 80 gross redemption points debited; cancellation reversals are separately visible.
- Redemptions by status: one pending, one fulfilled and one cancelled.

Do not represent these numbers as factory sales, revenue, payment totals or retailer activity.

### 12.2 Reconciliation

The main C1/C2/C4 sample ledger should be internally consistent and can produce a `clean` run with zero findings. To demonstrate the discrepancy layout, use a separate test-only snapshot with one synthetic finding; do not corrupt the main live-demo balances to force an error. A sample finding may use `claim_vs_code` or `redemption_vs_debit` and display expected/actual values and `open` status. The UI must state that a finding is not an automatic correction.

Run outcomes are `clean`, `discrepancies` and `failed`. Finding statuses are `open`, `acknowledged` and `resolved_by_correction`; a correction is a separate append-only ledger action.

## 13. Demo setup and reset guidance

1. Use the separate local development database and a fake/demo HMAC key created specifically for the local environment.
2. Create the initial admin through `bin/create_admin.php`; never add an admin through the customer registration form.
3. Create the products and rewards through the normal application services/admin screens, or use a documented local-only seeding method once Stage 4 is approved.
4. Create batches through the app; generate/export test codes only through the protected one-time process. Do not paste a reusable code into this file or source control.
5. Claim selected test codes as C1/C2/C4 to build the ledger. Use the documented cancellation workflow for the cancelled redemption so the reversal and stock return are genuine records.
6. Create test fraud events through the documented controlled test scenario; do not insert secrets into event details.
7. Before a repeated presentation, reset only the local demo database using a controlled reset procedure. Do not use the production database or a customer's data.
8. Keep demo environment credentials and the fake HMAC secret outside Git. `.env.example` must contain placeholders only.

## 14. Explicit non-goals

This guide does not provide real purchase records, retailer/POS data, receipt images, customer personal information, payment amounts, cash conversion values, a real factory identity, valid production codes, real QR images, actual CSV exports, HMAC secrets or password values. It does not claim that a test has been run or that the application has been implemented.

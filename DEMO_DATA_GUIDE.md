# DEMO_DATA_GUIDE.md — Stage 3

Use synthetic records only. Every name, email, product, reward, point amount, batch and event below is illustrative and must be labelled **Demo data** in the UI or demo script. Do not use real factory/customer data or actual secrets.

## 1. Demo setup principles

- Demonstrate the Stage 2 purchase-proof assumption: one unique concealed code per sealed box. The code is the system’s operational proof-of-purchase credential, not independent proof of payment or retailer sale.
- No receipt upload, retailer/POS connection, real packaging, printer, external API, email delivery or public deployment is required.
- The first admin is created with `bin/create_admin.php`; there is no public admin registration. Use a locally configured demo admin email, not a committed password.
- Do not include plaintext codes, HMAC keys, code hashes, password hashes or live credentials in this guide, repository, screenshots or test fixtures. The code table stores only HMAC-SHA-256 hash + key ID. Generate a demo batch in the app; save its one-time CSV outside the repository for the live demonstration and delete it securely afterwards.

## 2. Sample accounts

| Demo reference | Name | Email | Role / status | Use |
|---|---|---|---|---|
| `CUS-101` | Demo Customer One | `customer.one@example.test` | customer / active | Main customer journey, claim and redemption |
| `CUS-102` | Demo Customer Two | `customer.two@example.test` | customer / active | History and fulfilled/cancelled examples |
| `CUS-103` | Demo Customer Three | `customer.three@example.test` | customer / suspended | Neutral inactive-account/login message and admin review |
| `ADM-001` | Demo Factory Admin | `admin@example.test` | admin / active | Created by CLI; access to all administrator pages |

These are display references, not required database IDs. Set passwords locally during setup using the approved password hashing flow; never place passwords in this file. No customer can select `admin` during registration.

## 3. Sample products and batches

### Products

| Demo product | SKU | Default points per box | Status |
|---|---|---:|---|
| Biscuit Box | `BIS-001` | 100 | Active |
| Chocolate Box | `CHO-001` | 150 | Active |
| Snack Box (retired demo item) | `SNK-001` | 80 | Inactive |

Product default points and batch override points are whole numbers from 1 to 10,000. Editing a product default does not rewrite existing batches.

### Batches

| Demo batch | Product | Quantity | Points/code | Example state |
|---|---|---:|---:|---|
| `B-26001` | Biscuit Box | 100 | 100 | active |
| `B-26002` | Chocolate Box | 50 | 150 | exported |
| `B-26003` | Biscuit Box | 25 | 100 | draft |
| `B-26004` | Snack Box | 20 | 80 | voided |

Show the complete lifecycle vocabulary: `draft`, `generated`, `exported`, `printed`, `active`, `voided`. Generation and export are one administrator POST request; plaintext exists only in memory and is streamed as a one-time CSV. If a download fails after export commits, void the batch and create a replacement. Mark printed and activate explicitly; only active batches may be claimed. Do not silently reverse credits already earned when a batch is voided.

Default CSV columns: `batch_code`, `row_number`, `product_sku`, `points`, `code`, `claim_url`. No example code/claim URL containing a secret is printed here. The code is displayed in four-character groups in the CSV; claim normalization strips spaces and hyphens.

## 4. Sample reward catalogue

| Demo reward | Points cost | Available stock | Status |
|---|---:|---:|---|
| Gift Voucher | 150 | 12 | Active |
| Shopping Coupon | 250 | 5 | Active |
| Premium Hamper | 500 | 0 | Active, out of stock |
| Retired Welcome Gift | 100 | 8 | Inactive |

Reward cost is a positive whole number up to 1,000,000. One unit per redemption; every reward has finite stock. The UI distinguishes inactive, out-of-stock and insufficient-points cases.

## 5. Sample points ledger

Balances are always calculated by summing `points_delta`; there is no stored balance. The data below is a synthetic illustration of ledger rows and should be inserted only through demo/test services or a controlled seed process consistent with the Stage 2 design.

| Customer | Ledger event | Type | Delta | Explanation |
|---|---|---|---:|---|
| CUS-101 | Claim A | claim | +250 | Two or more successful demo box claims; example total |
| CUS-101 | Claim B | claim | +100 | Successful claim from an active batch |
| CUS-101 | Redemption R-7001 | redemption | -150 | Pending Gift Voucher; stock reserved |
| CUS-101 | Admin correction | adjustment | -10 | Reason required and audit logged |
| CUS-102 | Claims | claim | +600 | Synthetic successful claim credits |
| CUS-102 | Redemption R-7002 | redemption | -250 | Fulfilled Shopping Coupon |
| CUS-102 | Redemption R-7003 | redemption | -150 | Later cancelled; original debit retained |
| CUS-102 | Reversal of R-7003 | reversal | +150 | Linked compensating entry; stock returned |

Derived sample balances: CUS-101 = **190 points** (350 - 150 - 10); CUS-102 = **350 points** (600 - 250 - 150 + 150). Example dashboard labels: points earned = claim credits; points spent = redemption debits; adjustments/reversals shown separately; current balance = sum of all entries. Never update or delete an old ledger row.

## 6. Sample redemptions

| Reference | Customer | Reward | Stored points cost | Status | Demo interpretation |
|---|---|---|---:|---|---|
| `R-7001` | CUS-101 | Gift Voucher | 150 | pending | Visible in customer list and admin fulfilment queue |
| `R-7002` | CUS-102 | Shopping Coupon | 250 | fulfilled | Completed at factory collection point |
| `R-7003` | CUS-102 | Gift Voucher | 150 | cancelled | Admin reason, linked ledger reversal and one stock unit returned |

Only `pending` can be fulfilled or cancelled. Customer cannot cancel. Cancelled redemption does not erase its original debit; a linked reversal is a new row. A fulfilled redemption is terminal in the normal workflow.

## 7. Sample fraud and audit records

| Event | Severity | Review status | Display purpose |
|---|---|---|---|
| `repeated_invalid_codes` | warning | open | Show repeated invalid attempts without revealing submitted codes |
| `voided_batch_code_claimed` | high | open | Show suspicious use of a voided-batch credential |
| `claim_rate_exceeded` | info | reviewed | Show review history for a rate signal |

Supported review statuses: `open`, `reviewed`, `dismissed`, `escalated`. Sample audit actions can include `product.create`, `batch.generate_export`, `batch.activate`, `customer.suspend`, `points.adjust`, `redemption.fulfil`, `redemption.cancel`, `fraud.review` and `reconciliation.run`. These demonstrate action/actor/entity/time/reason, not editable records. Never put plaintext code, HMAC key, password or session data in details.

## 8. Reports and reconciliation demo

Prepare sample outputs for the three approved reports only:

1. Claims per batch.
2. Points issued versus points spent.
3. Redemptions by status.

Prepare one clean reconciliation run (`outcome=clean`, zero findings) and optionally a controlled test run with a synthetic finding to show the read-only findings UI. The reconciliation checks claim credits against claimed codes, redemption debits, reversal links and customer ledger totals. It records results and **never automatically changes balances**. Demonstrate correction only as a separate audited ledger adjustment/reversal.

## 9. Suggested short demonstration sequence

1. Register or sign in as CUS-101 and inspect dashboard/profile.
2. As admin, show sample products and create a draft batch. Run the protected generate/export POST for a small demo quantity; keep the downloaded CSV outside the repository.
3. Mark the batch printed and activate it after confirming the demo packing/release step.
4. Sign in as a customer and manually claim one code from the active demo batch. Show points awarded and the history entry. Repeat the same code as the same customer to show the approved repeat message; test another account only with a safe invalid/used outcome.
5. Browse rewards, confirm a redemption and show pending status; as admin, fulfil or cancel a separate pending demo redemption.
6. Show fraud review, read-only audit, reports and reconciliation.

Reset or reseed demo data between runs as needed, but preserve ledger/audit integrity within each run. Do not commit the exported CSV, any usable code, `.env`, password or HMAC secret. This guide is not a test-result record; actual test outcomes belong in `TEST_PLAN.md` after execution.

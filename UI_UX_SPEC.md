# UI_UX_SPEC.md — Stage 3

**Project:** Customer Loyalty Rewards System  
**Status:** Design only; no application code is included.

## 1. Scope and design rules

This specification follows the six Stage 2 documents in this folder. They remain the authority for roles, data, transitions and security. If a visual detail appears to conflict with them, Stage 2 wins. This is a PHP 8 + MySQL/MariaDB, PDO, layered-monolith application. HTML is rendered from `.php` files; styles are separate CSS files; JavaScript is inline only when essential. `public/` is the only web root. No external service, real factory data, receipt upload or POS integration is required for the college demo.

Design for quick understanding, predictable actions and readable records. The customer’s primary task is **Claim Product Code**. The administrator’s primary work is to manage batches, customers, rewards and fulfilment. Use clear text labels, restrained color, brief feedback and consistent layouts. Do not add features or routes outside `PROJECT_STRUCTURE.md`.

**Purchase-proof wording:** the concealed, single-use 16-character code inside a sealed box is the system’s operational proof-of-purchase credential (A-24). It proves possession of a genuine factory-issued code; it does not independently prove payment, retailer, purchase date or purchaser identity. No receipt or POS workflow is included (A-25).

## 2. Roles and navigation

| Role | Access | Navigation |
|---|---|---|
| Visitor | Landing, register, login | Home, How it works, Sign in, Join |
| Customer | Own account only; session determines user ID | Overview, Claim a code, Points history, Rewards, My redemptions, Profile, POST Logout |
| Admin | One administrator tier; server-side `role=admin` and active-status check on every request | Overview, Products, Batches, Customers, Rewards, Redemptions, Fraud, Audit, Reports, Reconciliation, POST Logout |

There is no public administrator registration or web-based admin-user management. The first and any additional admin accounts are created through the controlled CLI process (A-07/A-16). Hiding navigation is not authorization; every protected page and action is guarded server-side.

## 3. Main workflows

### 3.1 Customer journey

1. Visitor opens `index.php`, chooses Join or Sign in.
2. Registration collects name, email, optional phone, password and confirmation. Email is the unique login identifier; registration is open and email verification is not required in the first version. A successful registration creates the customer and profile together, then signs the customer in.
3. Customer dashboard shows ledger-derived balance and recent activity. Claim is the most prominent action.
4. On `claim.php`, customer manually enters the code (primary path). Accept case-insensitive entry with spaces/hyphens; normalize to uppercase and remove separators. Require exactly 16 characters from `23456789ABCDEFGHJKMNPQRSTVWXYZ`. QR is optional and secondary: its fragment may prefill the form, but opening a link never submits a claim. If logged out, route through the fixed `return=claim` token and explicit empty fragment; after login, customer rescans or types the code (A-31).
5. A successful claim shows points awarded and the new ledger balance, with a link to history. Invalid, used-by-another-customer, inactive-batch and voided-code outcomes share one generic message. A same-customer repeat may show the approved repeat message.
6. Customer browses active rewards, sees cost and stock, opens `redeem.php`, reviews cost/balance/remaining balance and confirms. Redemption creates one pending request and reserves one stock unit atomically. The customer tracks pending, fulfilled or cancelled status in `redemptions.php`.
7. Customer views or edits profile details and may request closure. Closure disables login immediately; factory owner can reverse it after identity checks during the 30-day recovery window. After 30 days the profile is anonymised as specified by A-17.

### 3.2 Administrator journey

1. Admin signs in through the same `login.php`; active admin is redirected to `admin/index.php`.
2. Create/edit/deactivate products. Create a batch for an active product, quantity 1–1,000, and default or overridden whole-number points value.
3. Use `admin/batch_generate_export.php` as one protected POST with CSRF and password re-entry. Generation and one-time CSV export happen in the same request. Plaintext exists only in memory; it is never stored or downloadable again. CSV default columns are `batch_code, row_number, product_sku, points, code, claim_url`. If delivery fails after export is committed, void the batch and create a new one.
4. In batch details, mark `exported → printed`, then explicitly activate `printed → active` only after packing/release checks. A batch can be voided from any non-voided state with a reason. Voiding blocks remaining unclaimed codes; it does not silently reverse points already credited.
5. Search customers, review details and history, suspend/reactivate with reason, post an audited ledger adjustment/correction through the approved workflow, or review a closure request. Never edit prior ledger rows.
6. Manage rewards and finite stock. Review pending redemptions; fulfil or cancel only a pending redemption. Cancellation requires a reason, posts a linked ledger reversal and returns one stock unit. A fulfilled redemption cannot be cancelled through the normal workflow.
7. Review fraud events, audit records and read-only reports. Run reconciliation manually; inspect findings and resolve ledger issues only through a new compensating entry. Reconciliation never repairs the ledger automatically.

## 4. Page requirements

All routes below are defined by `PROJECT_STRUCTURE.md`. Logout is a POST endpoint, not a standalone page.

### 4.1 Visitor and customer pages

| Page | Required content and actions | Key behavior |
|---|---|---|
| `index.php` | App name/logo placeholder, concise program explanation, 3-step explanation (join → reveal/enter code → earn/redeem), benefits, Register and Login links, footer | No invented factory or brand name; main CTA is Join |
| `register.php` | Name, email, optional phone, password, confirm password, Register, Login link | Name/email required; email normalized lowercase; password minimum 12 characters; no role selector; generic duplicate-email response |
| `login.php` | Email, password, Login, registration link, neutral support note | Generic “Email or password is incorrect”; rate-limit message; never reveal account status or whether email exists |
| `dashboard.php` | Welcome, current balance, claimed points, spent points, Claim shortcut, Rewards shortcut, recent ledger entries, recent redemption status | Balance is sum of ledger deltas; no cached balance; claim CTA is visually primary |
| `claim.php` | Where to find box code, 16-character field, format/count guidance, Claim button, message area; optional secondary QR help | Manual entry works without JavaScript. QR fragment can prefill only; explicit customer submit is always required |
| `history.php` | Current balance, earned/spent/adjustments, paginated transactions with date, description and signed points change | Newest first; stable “older than last shown” pagination; never display code or HMAC; read-only |
| `rewards.php` | Active reward name, description, points cost, finite availability/stock status, affordability, Redeem link | Distinguish available, out of stock/inactive and insufficient points; no quantity selector (one per redemption) |
| `redeem.php` | Selected reward, cost, balance, projected balance after redemption, Confirm and Back | Explicit confirmation, POST + CSRF + one-time idempotency token; show existing request on retry |
| `redemptions.php` | Reference, reward, stored points cost, requested date, status and short explanation | Exact statuses: pending, fulfilled, cancelled; customer cannot cancel |
| `profile.php` | Name, email (login identifier), phone, optional contact details, Edit, password change while logged in, Request account closure | No delivery address; closure warning and state explanation; no email-based reset flow (A-33) |

### 4.2 Administrator pages

All `admin/` pages require active admin role. Sensitive changes use POST + CSRF and produce audit records.

| Page | Required content and actions | Key behavior |
|---|---|---|
| `admin/index.php` | Active batches, claims today, points issued/spent, pending redemptions, open fraud events; recent activity/claims/redemptions | Read-only metrics from approved reports; no unsupported chart or metric |
| `admin/products.php` | Search/list: name, SKU, default points, status; Add/Edit/Deactivate | Products are deactivated, never deleted |
| `admin/product_edit.php` | SKU, name, description, default points; Save/Cancel | SKU unique; points 1–10,000; changing a default does not alter existing batches |
| `admin/batches.php` | Search/list: product, batch code, count, points/code, status, created date; View/Create | Status labels follow approved lifecycle exactly |
| `admin/batch_create.php` | Active product, batch label, quantity, optional points override, confirmation | Quantity 1–1,000; points 1–10,000; starts as draft; no codes claimable yet |
| `admin/batch_view.php` | Batch reference, product, counts, points, state, timestamps, export/activation details; Mark printed, Activate, Void where valid | Conditional state actions only; void needs a reason; show already-claimed points are not silently reversed |
| `admin/batch_generate_export.php` | Batch being generated, points/count summary, security warning, password re-entry, Generate and Download | One POST, CSRF, admin only, one-time export; success is a browser CSV download; never show/retrieve old plaintext codes |
| `admin/customers.php` | Search by name/email; customer, email, status, balance, registration date; View | Personal data access is audited; no bulk edit |
| `admin/customer_view.php` | Profile/status history, ledger/balance, redemptions; Suspend/Reactivate, Adjustment/Correction, closure review | Read-only historical rows; reason required; all ledger changes are new adjustment/reversal rows; closure recovery follows A-17 |
| `admin/rewards.php` | List name, cost, stock, status; create/edit/deactivate/restock | Reward fields and stock bounds follow database/workflow spec; finite stock; restock audited |
| `admin/redemptions.php` | Queue/list reference, customer, reward, stored cost, date, status; fulfil/cancel | Only pending can transition; cancellation requires reason, linked reversal and stock return |
| `admin/fraud.php` | Event type, severity, account/IP/batch reference as permitted, time, review status, details; Review/Dismiss/Escalate | Never expose passwords, session IDs, CSRF tokens, plaintext codes or HMAC keys |
| `admin/audit.php` | Search/filter action, actor, entity, date; read-only details | Append-only; no edit/delete controls |
| `admin/reports.php` | Claims per batch, points issued vs spent, redemptions by status; supported date filters | Read-only; no invented analytics |
| `admin/reconciliation.php` | Scope, Run, prior runs, start/finish, outcome, finding count/list/status | Manual run; outcomes clean/discrepancies/failed; no automatic correction |

## 5. Shared interaction and validation rules

- All state changes are POST + CSRF. GET never claims, redeems, suspends, exports, fulfils or changes status.
- Show inline field errors beside the field and a summary at the top when several fields fail. Preserve safe, non-secret values after validation; never repopulate passwords or codes after an error.
- Use server-confirmed flash feedback after a successful action. Confirmation dialogs identify the exact object and consequence; destructive/irreversible actions require the administrator to state a reason where Stage 2 requires one.
- Use generic authentication and code-claim failures to prevent account/code enumeration. The only claim-specific exception is same-customer already-claimed feedback (A-23).
- Do not reveal code text, HMAC, secret key, password hash, session ID, CSRF token, internal suspension note, SQL or stack trace. Private pages use `Cache-Control: no-store`; output is escaped.

| Situation | User-facing message/feedback |
|---|---|
| Name/email missing or malformed | “Enter your name.” / “Enter a valid email address.” |
| Password shorter than 12 characters | “Use at least 12 characters.” |
| Password confirmation differs | “Passwords do not match.” |
| Login failure | “Email or password is incorrect.” |
| Login lockout | “Too many attempts. Please try again later.” |
| Code wrong format | “That code is not in the right format. Check it and try again.” |
| Code not found, used by someone else, inactive batch or voided | “This code is not valid or has already been used.” |
| Code already claimed by this same customer | “You have already claimed this code.” |
| Claim rate limit | “Too many attempts. Please try again later.” |
| Successful claim | “N points added.” Show new balance and Points history link |
| Redemption balance too low | “You do not have enough points for this reward.” |
| Reward stock exhausted | “This reward is out of stock.” |
| Reward no longer active | “This reward is no longer available.” |
| Successful redemption | Show reference, points spent, remaining balance and Pending status |
| Permission denied | Neutral “You do not have permission to view this page.” |
| Unexpected server error | Generic error with correlation ID; technical details only in protected logs |

## 6. Responsive behavior and accessibility

- Customer desktop: shared top header/navigation; centered content; dashboard metrics in a compact grid; forms and detail panels stay readable.
- Customer mobile: compact header; navigation becomes a stacked/collapsible list; one-column content; full-width primary actions; form fields use full available width.
- Admin desktop: fixed-width sidebar plus main content area and page header. Admin mobile: sidebar becomes a collapsible navigation region above content; filters stack; wide tables scroll horizontally with headers retained.
- At narrow widths, move secondary details below the primary action rather than hiding core status, points or error information. Avoid multi-column forms on phones.
- Use semantic headings, one page-level `h1`, actual `<label>` elements, visible keyboard focus, logical tab order, buttons for actions and links for navigation. Every status combines text/icon with color; contrast target is WCAG AA (4.5:1 normal text, 3:1 large text/UI boundaries). Announce dynamic success/error feedback using an appropriate live region. Respect `prefers-reduced-motion`.

## 7. State coverage checklist

| Page group | Required states |
|---|---|
| Register/login/profile forms | Initial, validation errors, server error, success, disabled/submitting; login lockout and suspended/closed neutral message |
| Dashboard/history | Loading, no activity, populated, pagination boundary, service error |
| Claim | Empty, invalid format, not found, used by another, already claimed by same customer, inactive/voided batch, rate limited, success, unexpected error, account inactive |
| Rewards/redeem/redemptions | Loading, empty catalogue, out of stock, insufficient balance, confirmation, success, duplicate submission, unavailable reward, each redemption status |
| Admin forms/lists | Loading, empty list, search no results, validation failure, permission denied, server error, successful save, disabled action |
| Batch/export | Each lifecycle state, invalid action for current state, password re-entry failure, one-time export refusal, download success, download failure recovery/void guidance |
| Customer/fraud/audit | Empty/search result, status reason required, action confirmation, completed action, read-only history |
| Reports/reconciliation | No data, filtered result, clean run, discrepancies, failed run, run in progress, findings list; no automatic repair |

## 8. Stage boundary

Deliverables in this stage are design documentation only. Do not create PHP pages, CSS implementation, JavaScript, SQL, authentication, code generation, CSV export, QR generation, reward transactions or tests. Do not begin Stage 4 without explicit approval.

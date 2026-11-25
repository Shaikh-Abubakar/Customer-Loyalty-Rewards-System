# UI_UX_SPEC.md — Customer Loyalty Rewards System

**Stage 3 deliverable: UI/UX design only.** This document translates the approved Stage 2 design into a page-level interface specification. It does not implement PHP, SQL, database connections, code generation, exports, QR generation, reward calculations or other backend behaviour.

## 1. Source of truth and design boundaries

The design is based on the Assignment 2 PDF and the six Stage 2 documents supplied with this project:

- `ARCHITECTURE.md`
- `DATABASE_DESIGN.md`
- `WORKFLOWS.md`
- `PROJECT_STRUCTURE.md`
- `ASSUMPTIONS.md`
- `TEST_PLAN.md`

Use the same source priority defined in the Stage 3 design brief. Assignment requirements are kept distinct from technology constraints and adopted coursework assumptions. The Assignment 2 brief requires a loyalty system for boxed physical products and asks the solution to address purchase proof, fraud/code reuse and factory-owner reward management. The Stage 2 design selects a concealed, single-use per-box code as the operational purchase-proof credential; it does **not** independently verify payment, retailer, purchase date or purchaser identity (A-24/A-25).

### 1.1 Approved technical constraints

- PHP 8, MySQL or MariaDB, PDO for database access.
- HTML markup lives inside `.php` files; CSS is kept in separate `.css` files.
- JavaScript is inline in PHP files only when necessary. There are no separate `.js` files.
- No React, Vue, Angular, Node.js or separate `.html` files.
- Windows XAMPP is the primary development environment; Ubuntu with Apache, PHP and MySQL/MariaDB must also be supported.
- Apache serves only `public/`. Templates, application classes, configuration, logs, database files, tests and documentation remain outside the web root.
- The proposed UI is implemented as PHP templates and ordinary browser controls; it must not require a front-end framework or a third-party CDN.

### 1.2 File and route mapping

Page names in the Stage 3 brief are logical page names. Their approved locations are the paths in `PROJECT_STRUCTURE.md`:

| Logical page | Approved file |
|---|---|
| Landing, registration, login, dashboard, profile, claim, history, rewards, redeem, redemptions | `public/<page>.php` |
| Administrator pages | `public/admin/<page>.php` |
| Shared stylesheet | `public/assets/css/base.css` |
| Customer stylesheet | `public/assets/css/customer.css` |
| Administrator stylesheet | `public/assets/css/admin.css` |
| Layouts and page partials | `templates/layout/`, `templates/customer/`, `templates/admin/`, `templates/partials/` |

The six existing Stage 2 Markdown files are at the project root in the supplied ZIP. Keep all five Stage 3 Markdown files beside them so their relative links remain simple. Do not move or rewrite the Stage 2 files as part of this stage.

## 2. UI/UX goals

1. Make the customer journey understandable without explaining database or security terminology.
2. Make **Claim Product Code** the most prominent customer action after login.
3. Make the code-entry workflow usable without QR scanning, JavaScript or a physical QR sheet.
4. Make points balance, points activity, reward affordability and redemption status easy to understand.
5. Give the single administrator permission tier a predictable workspace for products, batches, customers, rewards, redemptions, fraud review, audit, reports and reconciliation.
6. Present success, validation, business-rule failure and system failure as different states.
7. Use one visual language across customer and administrator pages while keeping the two navigation layouts distinct.
8. Work at phone, tablet and desktop widths.
9. Use only fields and workflow states supported by the approved design. Do not create new database fields to satisfy a visual preference.
10. Keep the interface practical for PHP templates and CSS3.

### 2.1 Visual direction

Use a calm, contemporary rewards-product style: deep indigo for primary actions, teal for secondary emphasis, warm amber as a restrained accent, pale neutral page backgrounds and white surfaces. Use clear typography, compact information cards, purposeful whitespace and simple line icons or neutral placeholders. Avoid large decorative gradients, animated backgrounds, oversized hero artwork and excessive card nesting.

The product must not be assigned an invented factory name or real brand. Use the temporary product identity **Loyalty Rewards** with a simple logo placeholder until the owner supplies a name and logo.

## 3. User roles and interface permissions

| Role | Interface access | Important limits |
|---|---|---|
| Visitor | Landing, registration and login | Cannot claim, view points, browse account-only pages or access administrator pages. |
| Customer | Own dashboard, own profile, manual code claim, own points history, active rewards, own redemption confirmation and own redemption status | Customer identity comes from the authenticated session, never a customer ID supplied by the browser. Cannot view or edit ledger rows. Cannot cancel a redemption. Cannot choose an administrator role. |
| Administrator | Separate administrator dashboard and all approved admin modules | One permission tier initially (A-16). Admin accounts are created through the controlled CLI process, not a web registration or account-management screen. Admin cannot claim codes or redeem rewards on behalf of a customer by default. |

**Authorization rule:** menu visibility is only a navigation aid. Every protected request must still apply the approved server-side login, active-account and role checks. A customer must never gain administrator access by changing a URL or form value.

## 4. Navigation structure

### 4.1 Public navigation

- Left: `Loyalty Rewards` logo placeholder linked to `public/index.php`.
- Right: `How it works`, `Sign in`, and the primary `Join the program` action.
- Footer: short program explanation and only informational links that are actually supplied. Do not invent privacy, support or legal URLs.

### 4.2 Customer navigation

Desktop header links: `Dashboard`, `Claim code`, `Rewards`, `Points history`, `My redemptions`, `Profile`. The `Claim code` action is visually primary. The account area includes the signed-in customer name and a **POST** sign-out form with CSRF protection.

On mobile, keep the brand and a compact menu control in the top bar. The menu expands to the same destinations and keeps `Claim code` easy to reach. Do not create a separate route or role switcher.

### 4.3 Administrator navigation

Use the administrator sidebar and a compact top bar:

1. Overview
2. Products
3. Batches
4. Customers
5. Rewards
6. Redemptions
7. Fraud monitoring
8. Audit records
9. Reports
10. Reconciliation

The top bar shows the current section, administrator identity and a **POST** sign-out form. Do not add web-based administrator creation or HMAC-key rotation pages; those are controlled command-line/operator procedures in Stage 2.

### 4.4 Navigation behavior

- Active navigation item is indicated by text, icon and a non-colour cue (for example, a left rule or `aria-current="page"`).
- Breadcrumbs are used on nested admin edit/detail pages. They supplement, not replace, the main navigation.
- Destructive or state-changing actions are forms using POST and CSRF, never state-changing links.
- GET requests only display information. They do not claim codes, redeem rewards, change a batch, suspend an account, fulfil an order or edit records.

## 5. Customer journeys

### 5.1 New customer joins

1. Visitor opens the landing page and understands the program in a few steps.
2. Visitor selects `Join the program` and completes name, email, optional phone, password and confirmation.
3. The account is created as `customer`; no role selector or email-verification step is shown (A-02).
4. On successful registration the customer is signed in and taken to the dashboard.
5. A duplicate-email response must remain generic to avoid confirming whether an account exists.

### 5.2 Existing customer signs in

1. Customer enters email and password.
2. A generic error is shown for invalid credentials or a suspended/closed account; neutral support guidance is available without exposing an internal reason.
3. Successful login regenerates the session ID and routes a customer to `dashboard.php`.
4. If the customer arrived through a QR claim link while logged out, the login return is the fixed `claim` route only. The original code is not kept in the session; the customer scans again or types it after login (A-31).

### 5.3 Customer claims a product code

1. Customer opens `Claim code` from the main navigation or dashboard.
2. The page explains that the unique 16-character code is inside the sealed box, under a scratch-off layer or on an insert.
3. Customer manually enters the code. This is the primary and complete workflow.
4. Spaces and hyphens are accepted as separators; lowercase letters are normalised. After normalisation the input must be exactly 16 characters from `23456789ABCDEFGHJKMNPQRSTVWXYZ`.
5. Customer explicitly presses `Claim points`; the request is a CSRF-protected POST.
6. Success confirms the points awarded and the new ledger-derived balance, and links to points history.
7. Failure messaging follows `WORKFLOWS.md` section 3.4. Do not reveal whether a code belongs to another customer.

**Optional QR path:** a QR claim URL uses the URL fragment `#c=<code>`. On an authenticated claim page, the optional inline script may prefill the manual input and remove the fragment from the address bar. It must never submit the form. If the customer is logged out, they must sign in and rescan or type the code. Manual entry remains usable when QR support or JavaScript is unavailable.

### 5.4 Customer browses and redeems

1. Customer opens the active rewards catalogue.
2. Each reward shows its name, description, points cost and stock/availability state.
3. Customer chooses a reward and reviews a separate confirmation screen showing the current balance, required points and estimated remaining balance.
4. Customer explicitly confirms with the one-time form token. The interface explains that one unit is redeemed per request.
5. A successful request creates a `pending` redemption and deducts points immediately.
6. Customer follows the status from `My redemptions`. Collection is at the factory's designated collection point; no delivery address or courier workflow is part of this version (A-14).
7. Only an administrator can fulfil or cancel a pending redemption. Cancellation returns one stock unit and posts a ledger reversal.

### 5.5 Administrator manages the program

1. Administrator signs in through the same login form and is routed to `admin/index.php` by role.
2. Administrator creates or edits products and rewards.
3. Administrator creates a draft batch, reviews the parameters, then runs the protected one-time generate-and-export action.
4. Administrator marks the batch printed after the production/vendor step and deliberately activates it after packaging/release checks.
5. Administrator reviews customer support data, fulfils or cancels redemptions, and investigates fraud events.
6. Administrator consults read-only reports and can run reconciliation. Reconciliation reports inconsistencies but never repairs the ledger automatically.

## 6. Page-by-page interface specification

The approved page basenames are preserved exactly. The detailed desktop/mobile low-fidelity layouts are in `PAGE_WIREFRAMES.md`.

### 6.1 Landing page — `public/index.php`

**Purpose:** explain the program and invite registration.

**Content hierarchy:**

- Header with `Loyalty Rewards` identity placeholder, sign-in link and join button.
- Hero title such as `Turn every eligible box into rewards`, one short explanation and a prominent `Join the program` button; secondary `Sign in` link.
- Three or four numbered steps: buy a sealed box; find the concealed code inside; sign in and enter the code; earn points and browse rewards.
- Benefits section: simple claim process, visible points history, choice of available rewards.
- Honest limitation note: possession of a valid concealed code is the system's operational purchase-proof credential; the demo does not independently verify retailer payment or a receipt.
- Footer with basic information. Do not invent a factory name, contact email or external destinations.

**Primary action:** registration. **Secondary action:** login. **States:** default, registration destination unavailable, and generic page/server error. There is no customer data on this page.

### 6.2 Registration — `public/register.php`

Fields: full name, email, optional phone, password and confirm password. Provide a visible 12-character minimum password hint (A-03); do not impose unsupported character-class rules. Email is the login identifier (A-01). The form has a clear submit action and link to login.

On successful registration, route the newly signed-in customer to the dashboard. Email verification is not shown. Duplicate-email handling uses the generic response in `WORKFLOWS.md`; do not say `email already exists`. There is no administrator role selector.

**States:** initial, required-field/format validation, password mismatch, generic registration conflict, server error with correlation ID, and successful account creation/redirect.

### 6.3 Login — `public/login.php`

Fields: email and password. Provide `Sign in`, a link to registration and a small help note. There is no self-service forgot-password flow in the first version (A-33). A customer who cannot regain access must contact the factory owner/support channel supplied outside the app.

Show one generic credential error (`Email or password is incorrect`) for invalid credentials and protected-account login failure, with neutral support guidance. Show a rate-limit message when login attempts are blocked. If `return=claim` is present, only the allowlisted claim route may be used after login; never accept arbitrary redirect URLs or keep a code in the session.

**States:** initial, field validation, generic credentials error, rate limited, neutral suspended/closed guidance, server error with correlation ID and successful role-based redirect.

### 6.4 Customer dashboard — `public/dashboard.php`

**Primary hierarchy:**

1. Welcome line with the customer's first name if available.
2. Prominent available-points panel. This value is calculated from the append-only ledger; it is not a cached database balance.
3. `Claim product code` primary action.
4. `Browse rewards` secondary action.
5. Optional summary: `Points from product claims` and `Points redeemed (gross debits)`, computed from ledger entry types. Reversals and manual adjustments remain separately visible in history and must not be silently folded into misleading totals.
6. Recent points activity and the latest redemption/status.

Do not show fake charts, a progress percentage or a streak unless supported by the approved data model. The main action remains claiming a code.

**States:** initial with sample/demo data, new customer with zero balance and no activity, account blocked by middleware, loading skeleton for the summary, activity unavailable/server error, and successful refresh after a claim or redemption.

### 6.5 Claim product code — `public/claim.php`

This is the highest-priority customer page. **Manual code entry is the primary and complete claim method**; QR is optional and secondary.

**Primary content:**

- Page title `Claim Product Code`.
- Short help text: reveal the code inside the sealed box, beneath the scratch-off layer or on the insert.
- One large, labelled code input. Placeholder may use the visual grouping `XXXX-XXXX-XXXX-XXXX`; placeholder is not a real code.
- Helper text: 16 characters from the approved alphabet; spaces and hyphens are optional separators; lowercase is accepted.
- Primary `Claim points` button.
- Inline validation and result area associated with the input.
- Small security/usage note: each code can be claimed only once; do not share it.
- Optional QR note/link as a visibly secondary alternative only when that option is available.

**Input behavior:** accept lowercase; normalise to uppercase; allow spaces/hyphens as separators; count and validate the normalised 16-character value. Do not require a QR scanner. A static input-format hint is sufficient; a live counter is optional and may be implemented only with necessary inline JavaScript.

**Required result states:**

| State | Customer-visible treatment |
|---|---|
| Empty | Neutral instruction; no error before submission. |
| Invalid format | Explain the 16-character format and allowed alphabet. |
| Not found, claimed by another customer, inactive batch or voided code | One identical generic message: `This code is not valid or has already been used.` |
| Already claimed by this same customer | `You have already claimed this code.` This is the only repeat-claim case allowed a specific response (A-23). |
| Rate limited | `Too many attempts. Please try again later.` |
| Successful claim | Confirm `N points added`, show new points balance and link to `Points history`. |
| Network/server error | Neutral failure message and correlation ID where available; never ask the customer to paste the code into support chat. |
| Account not active | Neutral status message and support instructions. |

After any failure, do not echo the submitted secret code back to the page or log. The internal outcomes remain hidden. The QR-prefill script, if present, only copies the fragment value into the form and removes it from the URL; it never submits the form.

**Selected rate limits (A-04):** 10 invalid code attempts per account or IP in 15 minutes block further claims for the window. Twenty successful claims by one account in a rolling 24 hours and ten distinct accounts using one IP within one hour raise review alerts but do not automatically block an otherwise valid claim. The UI may describe a temporary limit without revealing detection thresholds.

### 6.6 Points history — `public/history.php`

Show the current ledger-derived balance, optional totals for claim credits and redemption debits, and a newest-first list/table with transaction date, plain-language description and signed points change. Use plus/minus and text labels, not colour alone. Include adjustments and reversals as their own entries. Do not expose the code, HMAC hash or key ID. The customer sees only their own history.

Pagination uses the stable keyset approach in Stage 2 (`entries older than the last one shown`); the visible control can be `Older activity`. Do not invent a total page count if it is not known.

**States:** first history page, no transactions, older activity available, end of history, loading, read failure, and account not active.

### 6.7 Rewards catalogue — `public/rewards.php`

Show active rewards only. Each item contains a neutral image/icon placeholder, reward name, short description, points cost, stock/availability and an action. Do not infer or add a reward image database field: `rewards` in `DATABASE_DESIGN.md` contains name, description, points cost, stock and active status only.

Distinguish:

- Available and affordable: `Redeem` action enabled.
- Available but not affordable: show the points shortfall and a disabled `Not enough points` action.
- Out of stock: show `Out of stock`; no redeem action.
- Inactive/deactivated: omit from the customer catalogue.
- Empty catalogue: explain that no rewards are currently available; do not display fabricated products.

**States:** loaded catalogue, empty catalogue, loading, query/server error, insufficient points, stock unavailable and successful navigation to confirmation.

### 6.8 Redemption confirmation — `public/redeem.php`

Show the selected reward, description, points cost, current balance and projected balance after redemption. State that one unit is requested per redemption and collection is at the designated factory collection point; no delivery address is collected.

Provide a separate explicit confirmation form and a back/cancel navigation action. The one-time idempotency token is carried as a hidden form value; it is never displayed. On successful POST, show the redemption identifier and `Pending` status. If balance or stock changed between viewing and submission, show the corresponding business-rule message and leave the points untouched when the transaction rolls back.

**States:** ready to confirm, insufficient points, reward no longer active, out of stock, expired/duplicate form token (show existing redemption when the workflow identifies it), submission in progress, success and server error.

### 6.9 Customer redemptions — `public/redemptions.php`

List only the signed-in customer's redemption records. Show redemption ID as `#<id>` (the database has an integer ID, not a separate reference column), reward name, points cost, requested date and status. Include a concise status explanation:

- `Pending`: request received; waiting for the factory fulfilment step.
- `Fulfilled`: administrator recorded fulfilment/collection.
- `Cancelled`: administrator cancelled the pending request; points were reversed and stock returned.

Customers cannot cancel a pending redemption in this version. Do not show an action that implies otherwise.

**States:** list, no redemptions, loading, read failure and each of the three statuses.

### 6.10 Customer profile — `public/profile.php`

Show and edit only fields supported by `customer_profiles`: full name, optional phone and optional contact details. Display email as the login identifier but do not offer email editing because the approved workflow does not specify an email-change process. Include password change only through the supported own-account password workflow; current-password verification is required. Do not add an email-reset workflow.

Provide an account-closure request area with a confirmation warning. Under A-17, requesting closure immediately sets the account to `closed` and disables login. The factory owner may restore it after identity checks within 30 days; after that, profile identifiers are anonymised while ledger and audit references are retained. The customer must understand that closure is not a temporary sign-out. No self-service cancellation after closure is defined.

**States:** profile display, edit validation, save success, save error, closure warning/confirmation, closure request accepted and account not active.

## 7. Administrator page-by-page interface specification

All pages below require an active `admin` account on the server. The interface uses one administrator permission tier; administrator creation and HMAC-key rotation stay outside the web UI.

### 7.1 Administrator dashboard — `public/admin/index.php`

Display exactly the approved six summary metrics:

- Active batches
- Claims today
- Points issued
- Points spent
- Pending redemptions
- Open fraud events

Add compact recent lists for administrative activity (from audit records), claims and redemptions. Include quick links to products, batches, customers, rewards and fulfilment/review modules. Do not invent revenue, sales conversion, retail volume or other metrics. Use charts only when they add information beyond the six numbers; cards and concise lists are the default.

**States:** loaded dashboard, empty recent activity, loading, partial data unavailable, server error and permission denied.

### 7.2 Products list — `public/admin/products.php`

Search/list columns: product name, SKU, default points per box, status and actions. Actions: add product, edit and deactivate. Do not offer delete. The list uses pagination and a useful empty state.

**States:** products present, no products, no search matches, loading, validation/search error and server error.

### 7.3 Product create/edit — `public/admin/product_edit.php`

Fields are limited to `products`: SKU, name, optional description, default points and active status. Default points are whole numbers 1–10,000 (A-34). Deactivate rather than delete. Explain that a changed product default affects future batches only; existing batches retain their copied points value.

**States:** create form, edit form, required/format validation, duplicate SKU, value out of range, save success, save failure and deactivate confirmation/completed feedback.

### 7.4 Batch list — `public/admin/batches.php`

Search and filter the batches by product, batch reference and status. Columns: batch code/reference, product, planned/generated code count, points per code, status, creation date and view action. Pagination is required. Do not show code plaintext, HMAC hashes or key material.

Use the exact states `draft`, `generated`, `exported`, `printed`, `active`, `voided`. The generated state is normally a short-lived part of the one-request generation/export process; it must not imply that plaintext codes can later be retrieved.

**States:** rows, no batches, no filter results, loading, read error and permission denied.

### 7.5 Batch creation — `public/admin/batch_create.php`

Fields: active product selection, planned quantity (1–1,000), points per code with product default preselected and an optional permitted override (1–10,000). Show the resulting values and a review step before creating the `draft` batch. Explain that a batch copies the product's current default at creation and later product edits do not alter it.

**Important workflow alignment:** this page creates the draft batch. It does not generate codes or stream the CSV. The separate protected generate/export action is initiated from `batch_view.php`, as defined by Stage 2.

**States:** initial form, no active products, invalid quantity/points, confirmation/review, draft created, save error and permission denied.

### 7.6 Batch details — `public/admin/batch_view.php`

Show batch code, product, planned/generated count, points per code, creation actor/date, batch state, one-time export record/status and count, and state timestamps (printed, activated, voided) when present. Use a clear lifecycle stepper for `draft → generated → exported → printed → active → voided` with `voided` shown as terminal.

Actions depend on state:

- `draft`: open the protected generate/export confirmation.
- `exported`: mark printed (manual confirmation; no printer required), or void with a reason.
- `printed`: activate after production/release checks, or void with a reason.
- `active`: void remaining unclaimed codes with a required reason.
- `voided`: read-only; do not offer reactivation.
- `generated`: explain that it is an intermediate/recovery state; do not offer plaintext retrieval. Use the documented void-and-recreate recovery path if the export cannot be completed.

Confirm activation and voiding. Voiding is terminal, requires a reason and does not silently reverse previously credited points. A partial code-level recall is mentioned in Stage 2, but a customer-facing code lookup/selection UI is not specified; see unresolved decisions.

**States:** each lifecycle state, no export record, recorded/failed export status, confirmation open, completed transition, invalid transition and server error.

### 7.7 Generate/export action — `public/admin/batch_generate_export.php`

This is a **POST-only protected action endpoint**, not a separate GET page. The confirmation UI is displayed on `batch_view.php` before POST. The confirmation explains: the request generates the codes and streams the one-time CSV; codes will not be stored in plaintext and cannot be downloaded again; if the response is interrupted, the batch must be voided and replaced.

Require administrator password re-entry and CSRF confirmation. Show a processing/disabled state after submit. On success, the response streams the CSV from memory with `no-store`; it must not create a server file or display the actual plaintext codes in an HTML page. The CSV uses the approved default columns from A-32 (`batch_code`, `row_number`, `product_sku`, `points`, `code`, `claim_url`). Vendor-generated QR labels are the baseline; optional digital QR rendering is secondary and must not be required.

If validation, key-registry or database checks fail, show a safe error on the originating page. If export/download delivery is interrupted after the one-time export has been recorded, use the documented void-and-regenerate process. Never add a `Download again` or `Reveal previous codes` button.

**States:** ready-to-confirm, password/CSRF validation failure, processing, CSV response issued, pre-export failure, interrupted/failed delivery recovery guidance and permission denied. Because this route returns a file response, it does not have a normal HTML success page after the response begins.

### 7.8 Customers list — `public/admin/customers.php`

Search by customer name or email. Columns: name, email, account status, current ledger-derived points balance, registration date and view action. Keep personal information limited to what the support task requires. Access is administrator-only and customer data access is audited.

**States:** customer list, no customers, no search matches, loading, server error and permission denied.

### 7.9 Customer details — `public/admin/customer_view.php`

Sections: profile and account status; account status history; points balance and read-only points history; redemption history; status controls; points correction/adjustment controls.

- Suspend/reactivate: require reason category and explanatory text, then show confirmation and result. Available categories are those in A-30.
- Points correction: choose the original entry and record a reason; create a compensating ledger entry through the approved workflow.
- Manual adjustment: amount and reason are required; the operation creates a new ledger entry and audit record.
- Never edit or delete a historical ledger entry. A customer view is read-only except for explicit, separately audited lifecycle/ledger actions.
- If closure was requested, explain the 30-day recovery window to the admin; reactivation requires identity checks. Do not add an unsupported self-service closure-cancel action.

**States:** customer details, account active/suspended/closed, status history empty, points history empty, redemption history empty, suspend/reactivate confirmation, adjustment/correction form validation, action success, conflict and server error.

### 7.10 Administrator rewards — `public/admin/rewards.php`

List reward name, points cost, available stock and active status. Provide add, edit, deactivate and restock actions. Form fields are limited to reward name, optional description, points cost and finite stock quantity; no image field is added to the database design. Use a neutral image/icon placeholder in the UI. Cost range is 1–1,000,000 points (A-34). Restock requires a positive quantity and shows the before/after stock change in the confirmation/result. All stock changes use the RewardService flow and are audited.

**States:** reward list, empty list, zero stock, inactive reward, search/no matches, create/edit validation, restock confirmation, deactivation confirmation, success and server error.

### 7.11 Redemption management — `public/admin/redemptions.php`

Show the fulfilment queue and history with redemption ID, customer, reward, points spent, request date and exact status. Default view emphasizes `pending`. Open a row to see details. Fulfil action accepts an optional fulfilment note; cancel requires a reason. Both actions require confirmation and POST + CSRF.

Fulfil is only valid from `pending`. Cancel is only valid from `pending`; it creates the points reversal and restores one stock unit. A fulfilled redemption is not cancellable through the normal workflow. Do not offer a manual refund button separate from cancellation.

**States:** pending queue, empty queue, fulfilled/cancelled history, fulfil confirmation, cancel reason/confirmation, action conflict, success and server error.

### 7.12 Fraud monitoring — `public/admin/fraud.php`

List suspicious events with event type, severity, related customer/account reference where available, IP address/reference where permitted, associated batch/code internal reference, creation time and review status. Filters may use event type, severity, customer, IP, batch and review status.

Detail view shows only the safe event details and related internal records needed for investigation. Never expose passwords, session IDs, CSRF tokens, plaintext product codes, full HMAC values or HMAC keys.

Review actions use the exact states `open`, `reviewed`, `dismissed`, `escalated`. A review note is recorded. Follow-up actions (suspend customer, void batch, correct points) are separate workflows and require their own confirmation and reason.

**States:** open-event queue, no open events, filtered no-match, event detail, review form, reviewed/dismissed/escalated result, loading and server error.

### 7.13 Audit records — `public/admin/audit.php`

Read-only table with action, actor, entity type/ID, date/time and a details action. Search/filter by action, entity and actor. Detail panel shows the reason and safe before/after details where available. Audit records are append-only: no edit, delete or bulk-clear action is allowed.

**States:** list, no records, no matching records, detail panel, loading and server error.

### 7.14 Reports — `public/admin/reports.php`

Provide only the three approved report families:

1. Claims per batch.
2. Points issued versus points spent.
3. Redemptions by status.

Use tables and restrained summaries. Date filters may be shown only for timestamp-backed reports and only when implemented by the approved reporting queries; do not add region, retailer, payment or sales metrics that the data model cannot support. Label totals precisely so reversal and adjustment entries are not misrepresented as new claims or redemptions.

**States:** report with data, empty date range, loading, invalid date range and query/server error.

### 7.15 Reconciliation — `public/admin/reconciliation.php`

Show `Run reconciliation` with a clear warning that it checks for inconsistencies and **does not automatically change or repair points**. The supported scope choices are `all`, `customer` and `batch`; customer/batch scope requires the relevant reference. Require confirmation before running.

List previous runs with run date, scope, outcome (`clean`, `discrepancies`, `failed`) and finding count. A detail view lists finding type, affected customer/ledger entry where present, expected/actual values, finding status and note. Findings are informational/read-only in this UI; correction uses the separate audited points-correction workflow. Do not imply that clicking `Acknowledge` or `Resolve` repairs data.

**States:** no previous runs, run confirmation, running, clean result, discrepancy result, failed result, finding list empty, findings present, loading and server error.

## 8. Shared interaction rules

### 8.1 Forms and actions

- Label every input. Show required/optional state in text, not only by colour or a star.
- Use inline field errors and a concise error summary after submission.
- Keep entered non-secret values after validation failure when safe. Do not echo passwords, product codes, HMAC values or security tokens.
- All writes use POST and CSRF. Do not use GET links for state changes.
- Destructive actions (void batch, deactivate product/reward, suspend/close/reconciliation run where relevant) require a confirmation step with the consequence stated in plain language.
- Disable a submit button while a request is in progress to reduce accidental double submissions; server-side idempotency and transaction rules remain authoritative.
- Use one-time idempotency values for redemption confirmation as specified in Stage 2; do not display their values.

### 8.2 Status presentation

Every status uses text plus a visual treatment/icon; colour alone is never the only cue. Use the exact persisted status names in admin detail/audit views, with plain-language labels in customer-facing surfaces. Do not invent alternate database states.

### 8.3 Loading and feedback

- Use a small spinner or skeleton only for a request that is actually pending.
- Use a stable reserved space for feedback so the page does not jump after response.
- Success messages use a status region; validation/system errors use an alert region.
- Always show a correlation/reference ID when the central error handler supplies one; do not show SQL, stack traces or filesystem paths.

### 8.4 Dates and numbers

- Store and process timestamps in UTC as designed. Display the configured application timezone consistently and label the timezone in administrative audit/reconciliation contexts. The current configuration default is UTC.
- Format points as whole numbers with explicit `+` or `−` in transaction history.
- Show costs and balances with the word `points`; do not imply a cash value or currency conversion.
- Show IDs as references without adding a new reference column (for example `#123`).

## 9. Responsive behavior

- **Mobile (≤ 639 px):** single-column forms and content; compact customer header with expandable menu; admin sidebar becomes an accessible drawer; metric cards stack; data tables become labelled stacked rows or a carefully controlled horizontal scroll region; buttons are full-width where helpful.
- **Tablet (640–1023 px):** customer content uses a two-column grid only when cards remain readable; admin navigation may use a compact collapsible sidebar; detail pages and forms remain one column; tables may scroll horizontally with a visible cue and preserved headings.
- **Desktop (≥ 1024 px):** customer pages use a centred content width; admin uses a persistent sidebar and main content area; dashboard metrics form a responsive grid; tables display full columns.

Do not hide required actions or status fields at narrower widths. When a table becomes cards, each value must retain a visible label (for example `Status: Pending`).

## 10. Accessibility requirements

- Use semantic landmarks: header, navigation, main, complementary content where appropriate and footer.
- Maintain a logical heading hierarchy and one primary page heading.
- Provide persistent programmatic labels for all form controls; placeholder text is not a label.
- Ensure all actions can be reached and operated by keyboard; menus and confirmation dialogs must manage focus and return focus to the triggering control.
- Provide a visible focus indicator for keyboard users.
- Associate each error with its field using `aria-describedby`; add `aria-invalid="true"` when invalid.
- Announce asynchronous status using `role="status"`/polite live regions and urgent errors using `role="alert"` where appropriate.
- Use adequate text/background contrast, readable line length and scalable text.
- Do not use colour alone for success, error, warning, batch state, redemption state or fraud severity.
- Give meaningful accessible names to icon-only controls; decorative icons are hidden from assistive technology.
- Respect reduced-motion preferences; motion must not be required to understand a result.
- Use WCAG 2.2 AA as the accessibility design target; implemented-page conformance must be checked during later testing.
- Tables require captions and scoped column headers. Responsive card transformations must preserve header/value relationships.

## 11. Shared page state model

Every page uses only the states relevant to its operation. The global state definitions are:

| State | Presentation rule |
|---|---|
| Initial | Stable page structure with clear primary action; no false error before interaction. |
| Loading | Reserved skeleton/spinner, `aria-busy="true"`, no duplicate submit. |
| Empty | Explain why there is no content and show a relevant next action only when one exists. |
| Validation error | Field-specific message plus a concise summary; focus the summary or first invalid field after server response. |
| Business-rule failure | Plain-language reason without disclosing secrets or internal fraud outcomes. |
| Success | Confirm what changed and show the next useful destination. |
| Server/network error | Generic message and correlation ID; no technical details. |
| Disabled/unavailable | State why the action is unavailable; keep explanatory text visible. |
| Permission denied | Safe access-denied page; do not reveal whether an inaccessible record exists. |
| Confirmation | State the action, irreversible effect (if any), and cancel path before the POST. |

## 12. Unresolved design decisions and implementation hand-off

These items are deliberately not filled with invented fields or workflows:

1. **Reward imagery:** `DATABASE_DESIGN.md` has no image column for rewards. Stage 3 uses a neutral placeholder. A real image-upload or image-URL feature requires an approved schema and storage decision before implementation.
2. **Individual code recall UI:** Stage 2 supports code-level voiding for partial recall, but does not define a page, code-selection/search workflow or customer-support procedure for selecting individual codes. This Stage 3 defines the existing whole-batch void interaction only. Decide whether a partial-recall admin UI is required before implementing that capability.
3. **Vendor label layout:** A-32 defines a default CSV layout, but the actual vendor may request different columns. Keep the approved CSV as the baseline until a real vendor specification exists.
4. **Support contact details:** no factory support email, telephone number or URL was supplied. Use generic support wording without inventing contact details.
5. **Final visual identity:** factory/program name, approved logo and final brand colours have not been supplied. `Loyalty Rewards` and the palette in `DESIGN_SYSTEM.md` are provisional project UI choices, not a real factory identity.
6. **Application display timezone:** Stage 2 stores UTC and currently configures UTC. If a different display timezone is later chosen, it must be a configuration decision and used consistently.

## 13. Stage 3 boundary

This document is a design specification only. It does not create application pages, database tables, SQL, PHP, CSS, JavaScript files, QR output, CSV files, seed data or test results. It does not start Stage 4. Proceed to implementation only after explicit approval of Stage 3 and the unresolved decisions that affect implementation.

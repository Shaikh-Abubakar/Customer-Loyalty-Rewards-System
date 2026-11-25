# PAGE_WIREFRAMES.md — Stage 3

Low-fidelity structure for all 25 routes in `PROJECT_STRUCTURE.md`. These are content/layout blueprints, not screenshots or executable HTML. The shared shells below apply to every page; each route then shows its unique content. Desktop and mobile behavior are specified for every group/page.

## 1. Shared shells

### Public / authentication shell

```text
DESKTOP
┌──────────────────────────────────────────────────────────────┐
│ LOGO / APP NAME                    How it works  Sign in Join │
├──────────────────────────────────────────────────────────────┤
│                         PAGE CONTENT                          │
├──────────────────────────────────────────────────────────────┤
│ Footer: program info | help/contact placeholder | copyright  │
└──────────────────────────────────────────────────────────────┘

MOBILE
┌──────────────────────────┐
│ LOGO / APP NAME      Menu│
│ Navigation links (open)  │
├──────────────────────────┤
│       PAGE CONTENT       │
│      primary action      │
├──────────────────────────┤
│ Footer links stacked     │
└──────────────────────────┘
```

Auth pages keep the form centered and omit unrelated navigation. No factory/brand identity is invented.

### Customer shell

```text
DESKTOP
┌──────────────────────────────────────────────────────────────┐
│ LOGO    Overview  Claim  History  Rewards  Redemptions Profile│
├──────────────────────────────────────────────────────────────┤
│ Breadcrumb / Page title                         Main action  │
│                                                              │
│                       PAGE CONTENT                           │
│                                                              │
└──────────────────────────────────────────────────────────────┘

MOBILE
┌──────────────────────────┐
│ LOGO                 Menu│
│ Overview / Claim / ...   │  (stacked or collapsible)
├──────────────────────────┤
│ Page title               │
│ Main action              │
│ Full-width content       │
│ Stacked cards and forms  │
└──────────────────────────┘
```

### Administrator shell

```text
DESKTOP
┌──────────────┬────────────────────────────────────────────────┐
│ LOGO         │ Page title                         Main action │
│ Overview     ├────────────────────────────────────────────────┤
│ Products     │ Filters / summary                              │
│ Batches      │                                                │
│ Customers    │ Table, form or details                          │
│ Rewards      │                                                │
│ Redemptions  │                                                │
│ Fraud        │                                                │
│ Audit        │                                                │
│ Reports      │                                                │
│ Reconcile    │                                                │
│ Logout       │                                                │
└──────────────┴────────────────────────────────────────────────┘

MOBILE
┌──────────────────────────┐
│ LOGO                Menu │
│ Admin navigation (open)  │
├──────────────────────────┤
│ Page title / main action │
│ Filters (stacked)        │
│ Metrics (1–2 columns)    │
│ Form or scrollable table │
└──────────────────────────┘
```

## 2. Visitor and customer pages

### 2.1 `index.php` — Landing

```text
DESKTOP: [Public header]
  HERO: “A simple way to earn rewards” + short explanation + [Join now] [Sign in]
  HOW IT WORKS: [1 Join] [2 Reveal and enter box code] [3 Earn points and redeem]
  BENEFITS: clear points / reward / history summary
  FOOTER
MOBILE: Hero becomes one column; Join button first/full width; steps and benefits stack.
```

### 2.2 `register.php` — Registration

```text
DESKTOP: [Auth header]       MOBILE: [Auth header]
  [Create account panel]       [Create account]
  Name                         Name
  Email                        Email
  Phone (optional)             Phone (optional)
  Password (min 12)            Password
  Confirm password             Confirm password
  [Register]                   [Register - full width]
  Already registered? Sign in  Sign in link
```

### 2.3 `login.php` — Login

```text
DESKTOP: [Auth header]       MOBILE: centered/full-width panel
  [Welcome back]
  Email
  Password
  [Sign in]
  New here? Join
  Neutral support / account-status note
```

### 2.4 `dashboard.php` — Customer dashboard

```text
DESKTOP: [Customer shell]
  Welcome + short account greeting
  [Current points balance] [Points earned] [Points spent]
  [CLAIM PRODUCT CODE - primary] [Browse rewards]
  Recent points activity                 Recent redemption status
MOBILE: Balance first; Claim full width; other metrics 1–2 per row;
        activity and redemption panels stack newest first.
```

### 2.5 `claim.php` — Claim product code

```text
DESKTOP: [Customer shell]
  Claim Product Code
  “Find the unique code inside your purchased box.”
  [16-character code input]  (spaces/hyphens accepted)
  Format/count guidance
  [Claim points]
  [Validation / success / failure message region]
  Optional: “Prefer QR?” secondary help
MOBILE: Form is first and full width; QR note appears below it.
```

Claim states on this page: empty; invalid format; not found/used by another/inactive batch/voided (same generic message); already claimed by same customer; rate limited; success with points and new balance; system error with correlation ID; inactive account. QR may prefill from URL fragment only; it never submits.

### 2.6 `history.php` — Points history

```text
DESKTOP: [Customer shell]
  Current balance | Earned | Spent
  History filters only if supported; otherwise omit
  Table: Date | Description | Points change
  [Previous] Showing range [Next]
MOBILE: Summary cards stack; transaction rows become compact blocks;
        pagination stays below the last row.
```

### 2.7 `rewards.php` — Rewards catalogue

```text
DESKTOP: [Customer shell]
  Current balance
  Reward tiles: [image placeholder] Name / description / cost / stock
                [Redeem]
MOBILE: Single-column reward tiles; cost and availability above action.
```

### 2.8 `redeem.php` — Redemption confirmation

```text
DESKTOP: [Customer shell]
  Confirm your reward
  Selected reward | Description
  Points required | Current balance | Remaining after redemption
  [Confirm redemption] [Back to rewards]
MOBILE: Details stacked; confirm is full width; back link below.
```

Include insufficient points, stock exhausted, no longer active, duplicate-submit/existing redemption, and success states. A successful redemption is pending and reserves one unit.

### 2.9 `redemptions.php` — Customer redemptions

```text
DESKTOP: [Customer shell]
  My redemptions
  Table: Reference | Reward | Points | Requested | Status | Explanation
MOBILE: Each redemption becomes a stacked detail block; status is near top.
```

Statuses are exactly `pending`, `fulfilled`, `cancelled`. No customer cancel control.

### 2.10 `profile.php` — Customer profile

```text
DESKTOP: [Customer shell]
  Profile details: Name | Email | Phone | Optional contact details | [Save]
  Change password panel (authenticated user)
  Account closure panel + impact/recovery explanation + [Request closure]
MOBILE: Panels stack; closure action is visually separated and confirmed.
```

No delivery address or email-reset flow. A closure request disables login immediately; the admin recovery/anonymisation process follows A-17.

## 3. Administrator pages

All routes use the administrator shell and display a page-level title, primary action, and current filter/state. Tables scroll inside their own container on mobile.

### 3.1 `admin/index.php` — Dashboard

```text
DESKTOP: [Admin shell] 6 metrics: Active batches | Claims today | Points issued |
  Points spent | Pending redemptions | Open fraud events
  Recent admin activity | Recent claims | Recent redemptions
MOBILE: Metrics 1–2 columns; three recent lists stack.
```

### 3.2 `admin/products.php` — Products

```text
DESKTOP: [Admin shell] [Search] [Add product] Table: Product | SKU |
  Default points | Status | Edit/Deactivate
MOBILE: Search and Add stack; table scrolls horizontally.
```

### 3.3 `admin/product_edit.php` — Create/edit product

```text
DESKTOP: [Admin shell] Form: SKU | Name | Description | Default points |
  [Save product] [Cancel]
MOBILE: One field per row; save full width.
```

### 3.4 `admin/batches.php` — Batch list

```text
DESKTOP: [Admin shell] [Search/status filter] [Create batch]
  Table: Batch | Product | Codes | Points/code | State | Created | View
MOBILE: Filters stack; table has contained horizontal scroll.
```

### 3.5 `admin/batch_create.php` — Create batch

```text
DESKTOP: [Admin shell] Form: Active product | Batch label | Quantity |
  Points override (optional) | Preview summary | [Create draft] [Cancel]
MOBILE: Fields and summary stack; numeric bounds shown near fields.
```

### 3.6 `admin/batch_view.php` — Batch details

```text
DESKTOP: [Admin shell] Batch reference / product / quantity / points
  Lifecycle: draft → generated → exported → printed → active → voided
  Export details | Activation details | State-allowed actions
  [Mark printed] [Activate] [Void + reason] as applicable
MOBILE: Summary and lifecycle stack; actions grouped under current state.
```

### 3.7 `admin/batch_generate_export.php` — One-time generate/export

```text
DESKTOP: [Admin shell] Batch and generation summary
  Security note: one-time export; plaintext not stored or retrievable
  Password re-entry | [Generate and download CSV]
  Result/status message
MOBILE: Summary and warning above full-width password/submit form.
```

CSV download is one POST request; no plaintext code list or re-download control is shown. If delivery fails after commit, show guidance to void and create a new batch.

### 3.8 `admin/customers.php` — Customer search

```text
DESKTOP: [Admin shell] Search name/email
  Table: Customer | Email | Status | Balance | Registered | View
MOBILE: Search full width; table scrolls horizontally.
```

### 3.9 `admin/customer_view.php` — Customer detail

```text
DESKTOP: [Admin shell] Profile + account status + current ledger balance
  Status history | Points history (read only) | Redemptions
  [Suspend/Reactivate + reason] [Adjustment/Correction + reason]
  Closure/recovery status if relevant
MOBILE: Profile and status first; history panels stack; action forms separated.
```

Never edit old ledger entries. Customer detail access and sensitive actions are audited.

### 3.10 `admin/rewards.php` — Reward management

```text
DESKTOP: [Admin shell] [Add reward] Table: Reward | Cost | Stock | Status |
  Edit | Deactivate | Restock
  Create/edit/restock form in the page's approved layout
MOBILE: Form and list stack; action controls remain associated with each reward.
```

### 3.11 `admin/redemptions.php` — Fulfilment queue

```text
DESKTOP: [Admin shell] Status filter
  Table: Reference | Customer | Reward | Stored cost | Date | Status | Actions
  Pending row actions: [Fulfil] [Cancel + reason]
MOBILE: Filter first; rows stack or horizontally scroll; actions remain visible.
```

### 3.12 `admin/fraud.php` — Fraud monitoring

```text
DESKTOP: [Admin shell] Filters: type / status / severity
  Table: Event | Type | Severity | Account/IP reference | Time | Review status
  Detail panel: safe event context + [Review] [Dismiss] [Escalate] + note
MOBILE: Filters stack; event details open beneath selected row.
```

### 3.13 `admin/audit.php` — Audit records

```text
DESKTOP: [Admin shell] Filters: action / actor / entity / date
  Read-only table: Time | Actor | Action | Entity | Details
MOBILE: Filters stack; table scrolls; details render as read-only key/value rows.
```

### 3.14 `admin/reports.php` — Reports

```text
DESKTOP: [Admin shell] Supported date filter (only where defined)
  Claims per batch | Points issued vs spent | Redemptions by status
  Use concise tables and simple charts only if data remains clear
MOBILE: Reports stack vertically; charts never require horizontal page scroll.
```

### 3.15 `admin/reconciliation.php` — Ledger reconciliation

```text
DESKTOP: [Admin shell] Scope selector (all/customer/batch) | [Run reconciliation]
  Previous runs: Date | Scope | Outcome | Findings
  Selected run: finding count + check type + expected/actual + status + note
MOBILE: Run form first; runs and findings stack as compact detail blocks.
```

Outcomes are `clean`, `discrepancies`, `failed`. Findings are `open`, `acknowledged` or `resolved_by_correction`. Reconciliation reports only; correction is a separate audited workflow.

## 4. Shared page states

Every list supports loading, populated, empty and no-search-results views. Every form supports initial, validation error, submitting/disabled, server error and success. Protected pages also support permission-denied and inactive-account messages. Confirmation screens show the specific item and consequence before an action. Claim and redemption states are detailed in sections 2.5 and 2.8; batch states/actions are in 3.6–3.7.

# COMPONENT_CATALOG.md — Customer Loyalty Rewards System

**Stage 3 deliverable.** This catalogue describes reusable presentation components for PHP templates and CSS. Component names are conceptual UI patterns, not a mandate to create new services, JavaScript classes or standalone frontend files. Reuse the approved `templates/layout/`, `templates/partials/`, `templates/customer/` and `templates/admin/` structure in `PROJECT_STRUCTURE.md`.

## 1. Component rules

- Templates render data; pages call services; templates do not query repositories or contain business rules.
- Dynamic output is escaped with the approved helper.
- Form actions that change state use POST + CSRF. GET displays information only.
- A component never substitutes for server-side authorization, validation, idempotency or transaction rules.
- Do not add unsupported database fields to support a visual component.
- Every component has a visible keyboard focus state and an appropriate accessible name/role.
- Responsive variants preserve all information and actions.

## 2. Component catalogue

| Component | Purpose | Where used | Required content | Variants | States | Responsive behavior | Accessibility |
|---|---|---|---|---|---|---|---|
| **Public header** | Give visitors a simple identity and entry point | `public/index.php`, register/login shell | Loyalty Rewards placeholder; How it works; Sign in; Join action | Desktop horizontal; mobile compact menu | Default; menu open; destination error | Full navigation on desktop; labelled menu button on mobile | `<header>` and `<nav>` landmarks; menu button has `aria-expanded` and `aria-controls`; keyboard-operable links |
| **Customer header/navigation** | Keep customer tasks reachable and emphasize claiming | All customer pages | Brand; dashboard; claim; rewards; history; redemptions; profile; POST sign out | Desktop horizontal; mobile drawer/dropdown | Active route; menu open; signed-out | Desktop links; mobile expandable navigation with claim action prominent | `aria-current="page"`; sign out is a labelled POST form; focus managed on mobile menu |
| **Admin sidebar/navigation** | Provide consistent access to owner modules | All `public/admin/*.php` pages | Overview, products, batches, customers, rewards, redemptions, fraud, audit, reports, reconciliation | Desktop persistent; tablet compact/collapsible; mobile drawer | Active item; expanded/collapsed; drawer open; permission denied | Drawer on mobile; compact/expanded sidebar on tablet; full sidebar on desktop | Navigation landmark and list semantics; active item announced; drawer supports Escape and focus return |
| **Page header** | Establish page identity and contextual action | Every page | H1; optional description; optional primary/context action | Standard; compact; nested detail | Default; loading title placeholder; error page title | Stack title/action on small screens | One H1; description associated with page; action label is descriptive |
| **Breadcrumbs** | Show location in nested admin tasks | Product edit, batch detail, customer detail, similar admin views | Ordered path ending in current page | Short path; truncated path with full accessible labels | Normal; current page | Wrap or collapse to shorter path on mobile without hiding current location | `<nav aria-label="Breadcrumb">`; current item uses `aria-current="page"` |
| **Primary button** | Identify the main action in a region | Join, claim, save, confirm, generate/export trigger, run reconciliation | Verb-first label; optional icon; form type/action | Primary; secondary; tertiary; destructive; compact | Default; hover; focus; active; loading; disabled | Full-width where helpful on mobile; normal width on desktop | Native `<button>` for actions; explicit type; focus visible; disabled reason is adjacent; no icon-only primary action |
| **Text input** | Collect short text values | Registration, profile, products, rewards, reasons | Persistent label; value; optional hint; required/optional marker | Text; email; number; search; telephone | Default; focus; error; disabled; readonly | Full-width on mobile; paired only when space allows | Label connected by `for`; input type correct; errors/help linked through `aria-describedby`; `aria-invalid` on error |
| **Password input** | Collect authentication/secret confirmation | Registration, login, password change, export confirmation | Label; password control; hint where supported | Password; current password; new password | Default; focus; invalid; disabled | Full-width at all sizes | Persistent label; browser password manager allowed; no password echoed after error; optional reveal control must be labelled and keyboard-accessible if adopted |
| **Code-entry input** | Make manual product-code entry easy and primary | `public/claim.php` | Label; 16-character format guidance; placeholder; inline error; claim button | Empty; populated; validation error; disabled while request is pending | Default; focus; invalid format; submitting; success/failure feedback | Full-width, large tap target; avoid clipping grouped code placeholder | Label and helper linked; no reliance on placeholder; input accepts lower case and optional separators; code never echoed in response/logs |
| **Select control** | Choose from approved fixed options | Product selection, status filters, reconciliation scope, reason categories | Label; option list; selected value; optional help | Standard select; compact filter select | Default; focus; error; disabled; no options | Full-width on mobile; compact in desktop filter row | Native select preferred; label always visible; option text is meaningful without colour |
| **Search/filter bar** | Narrow list data without changing records | Products, batches, customers, redemptions, fraud, audit, reports | Search label/input; allowed filter controls; submit/reset | Search only; search + filters; date range where supported | Empty query; active filter; no results; loading; error | Stack filters on mobile; horizontal row on desktop | Each control labelled; submit button has clear text; selected filter state is announced |
| **Standard card** | Group related information with a clear heading | Landing benefits, customer panels, reward/admin detail groups | Heading; concise body; optional action | Informational; interactive only when whole-card action is clear | Default; hover if interactive; loading; empty | One column mobile; grid on wider screens | Use semantic heading; if interactive, use a real link/button and keyboard focus rather than click handlers on a generic container |
| **Metric card** | Present one supported count/total | Customer/admin dashboards and reports | Metric label; numeric value; period/context; optional small icon | Standard; highlighted primary balance | Loaded; loading; unavailable | Stack/2-column/3-6-column grid based on width | Label precedes value in reading order; values use tabular numerals; icon decorative; no colour-only meaning |
| **Points balance summary** | Explain spendable ledger-derived points | Customer dashboard, rewards, redeem, history, admin customer detail | Current balance; points label; optional claims/spent summaries | Main dashboard; compact inline; confirmation summary | Positive; zero; loading/error | Full-width on mobile; side panel or grid tile on desktop | Say `points`; do not imply currency; negative balance is textually explained; number is read as one labelled value |
| **Data table** | Show structured, read-only lists | History, admin lists, audit, reports, redemptions | Caption; column headings; rows; empty message; row actions where allowed | Standard; compact; sortable only if approved query supports it | Populated; empty; loading; error; filtered no-results | Full table desktop; labelled cards or scroll region mobile | Semantic table, caption, `scope="col"`; keyboard access to row actions; numeric values aligned; no false editable appearance |
| **Responsive record row** | Preserve table data on narrow screens | Mobile product/batch/customer/redemption/audit/fraud/history lists | Record title; labelled values; status; permitted action | Compact card; expanded details | Default; expanded; no action | Single stacked block on mobile; maps to table row on desktop | Every value carries a visible label; action order follows reading order; avoid nested interactive elements |
| **Status badge** | Make persisted status easy to scan | Account, batch, redemption, fraud, reconciliation, reward availability | Exact status text; optional icon | Success; warning; error; info; neutral | Each supported persisted status; temporary processing state | Inline; wraps when needed | Text is mandatory; colour is supplementary; icon has no redundant announcement |
| **Alert/feedback panel** | Display important result or warning | Forms, claim, redeem, admin actions, system errors | Short title; message; optional next action/correlation ID | Success; warning; error; info | Visible; dismissed only when safe; persistent validation | Full width; text wraps | Use `role="status"` for success/info and `role="alert"` for urgent error where appropriate; do not auto-dismiss important errors |
| **Field error/help text** | Explain how to correct a field | All forms | Human-readable message; link to field | Help; required hint; validation error | Hidden/default; visible error; corrected | Wraps beneath control | Linked with `aria-describedby`; field marked `aria-invalid`; never communicated by colour alone |
| **Confirmation dialog** | Require deliberate approval of consequential actions | Batch export/void/activate, account suspension, points correction, reward deactivation/restock, redemption fulfil/cancel, reconciliation | Title; action consequence; required reason/password where applicable; confirm; cancel | Standard; destructive; protected export | Open; submitting; validation error; completed/closed | Near full-width on mobile; centred dialog desktop | Dialog role/name; focus moved inside and returned after close; Escape handling; safer cancel focus for destructive actions; no hidden action labels |
| **Pagination control** | Navigate long lists | Points history and admin list pages | Previous/older and next/newer control; current cursor context | Keyset `Older activity`; previous/next; page-number only if count support exists | Available; first/last; loading; disabled | Wrap controls on mobile | Native links/buttons with descriptive labels; focus order predictable; announce result range only when actually known |
| **Empty state** | Explain a valid absence of records | No points activity, no redemptions, no batches, no fraud events, no reports | Clear heading; reason/meaning; one permitted next action | Customer; admin; filtered-no-results | Empty; no match; not yet created | Compact stacked layout | Heading and text announced in reading order; action is not shown if not permitted |
| **Loading state** | Communicate a pending request | Dashboard metrics, lists, forms, action endpoints | Spinner/skeleton; optional action label | Page skeleton; inline spinner; button loading | Loading only while pending | Skeleton follows the final content layout | Set `aria-busy="true"`; retain accessible status text; disable duplicate submissions while pending |
| **Reward tile** | Help customers compare rewards | Customer rewards catalogue and redeem summary | Placeholder icon; reward name; description; cost; stock/availability; action | Affordable; insufficient points; out of stock | Available; insufficient; unavailable; loading | Responsive tile grid; stacked on mobile | Name is heading; cost and stock are text; disabled action has adjacent reason; placeholder icon decorative |
| **Ledger transaction row** | Explain one immutable points change | Customer history and admin customer view | Date; description; signed points delta; entry category where appropriate | Claim; redemption; reversal; adjustment | Positive; negative; no edit state | Table row desktop; labelled stacked block mobile | Use explicit plus/minus and text label; do not use green/red alone; code/hash never shown |
| **Redemption status row** | Track reward fulfilment | Customer redemptions and admin fulfilment queue | ID; customer/reward; stored points cost; requested date; persisted status | Pending; fulfilled; cancelled | Same status states; action availability varies by role/state | Table desktop; stacked card mobile | Status label always visible; customer sees their own items only; customer cannot cancel |
| **Batch lifecycle stepper** | Show the controlled production sequence | Admin batch details | Draft, generated, exported, printed, active, voided; current state and timestamps when present | Active path; terminal voided path | Each approved state; no invented business states | Horizontal desktop; vertical mobile | State names are text; current state is announced; sequence not conveyed by colour alone |
| **Fraud event row/detail** | Support safe investigation and review | Admin fraud page | Event type; severity; subject/IP/batch reference when available; date; review status; safe details | Queue row; detail panel; review form | Open; reviewed; dismissed; escalated | Table desktop; compact list on mobile | Admin-only; no secret values; actions are explicitly labelled and reason/note is captured |
| **Audit detail panel** | Show evidence of who did what | Admin audit page and recent activity | Action; actor; entity; date; reason; safe details | Inline expansion; side panel | Read-only; no result | Full-width mobile panel or stacked section | Read-only semantics; no edit/delete affordance; labels for before/after values |
| **Reconciliation finding row** | Explain a mismatch found by a run | Admin reconciliation page | Check type; affected reference; expected/actual; status; note | List row; detailed finding | Open; acknowledged; resolved by correction | Table desktop; labelled block mobile | Values have clear labels and units; finding display does not imply a repair action |
| **Date range filter** | Limit supported reports by time | Admin reports only where approved query supports it | From date; to date; apply action; timezone context | Default range; custom range | Empty; valid; invalid range; loading | Stack date controls on mobile | Label each date control; keyboard picker access; visible format hint; validation associated with each field |
| **Sign-out control** | End the current session safely | Customer/admin header | `Sign out` label | Desktop text control; mobile menu item | Ready; submitting; failure | Remains available in responsive nav | Must be a POST form with CSRF, never a state-changing GET link |

## 3. Approved component compositions

### 3.1 Customer claim form

`Page header` → `Code-entry input` → `Field help/error` → `Primary button` → `Alert/feedback panel` → optional secondary QR note. Manual entry is always the central interaction. Do not add a QR-only card or scanner requirement.

### 3.2 Customer redemption confirmation

`Page header` → `Reward tile (summary variant)` + `Points balance summary` → collection/one-unit note → `Confirmation` primary form → `Alert/feedback panel`. The idempotency token is hidden and not rendered as user-facing text.

### 3.3 Administrator batch workflow

`Page header` → `Batch lifecycle stepper` → read-only batch detail table → state-appropriate action button → `Confirmation dialog`. The one-time export confirmation is initiated from batch details and posts to the POST-only export endpoint.

### 3.4 Administrator correction workflow

`Page header` → read-only `Ledger transaction row` list → correction/adjustment form with required reason → `Confirmation dialog` → success alert. No existing ledger row is edited or deleted.

### 3.5 Reconciliation workflow

`Page header` → scope filter → warning alert → confirmation dialog → run summary → read-only `Reconciliation finding row` list. There is no automatic repair action in the reconciliation component.

## 4. Data and security restrictions for components

The following content must never appear in a user-facing UI component or browser log:

- Passwords or password hashes.
- Session IDs or CSRF tokens.
- Plaintext product codes except in the one-time protected CSV response while that export is being streamed.
- HMAC secrets or full HMAC hashes.
- Internal stack traces, SQL messages, filesystem paths or environment values.

Customer-facing code failure messages must preserve the generic outcome rules from `WORKFLOWS.md`. Fraud event detail is administrator-only and excludes secret values. Audit/fraud/reconciliation records are not presented as editable records.

## 5. Component readiness checklist

- [x] Purpose and page usage documented for each component.
- [x] Required content documented.
- [x] Variants and states documented.
- [x] Mobile/desktop behavior documented.
- [x] Accessibility considerations documented.
- [x] Component compositions provided for the highest-risk workflows.
- [x] No component requires a new database field or a frontend framework.

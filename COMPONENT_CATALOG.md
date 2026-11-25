# COMPONENT_CATALOG.md — Stage 3

Reusable PHP-template/CSS components. Components are presentation patterns, not new services or business rules. Each remains accessible and responsive; all displayed dynamic values are escaped.

| Component | Purpose / used on | Required content | Variants and states | Responsive / accessibility |
|---|---|---|---|---|
| Public header | `index.php`, auth pages | App-name placeholder, primary links, sign-in/join | Visitor vs logged-in customer; current link | Mobile stacked/collapsible; semantic navigation landmark |
| Customer header/nav | Customer pages | Overview, Claim, History, Rewards, Redemptions, Profile, POST Logout | Current page; compact mobile | Collapses on mobile; logout is a form/button, never a GET link |
| Admin shell/sidebar | All `admin/*.php` | Admin navigation, main content slot, logout | Current section; collapsed mobile | Sidebar becomes collapsible above content; nav labels always visible when open |
| Page header/breadcrumb | All internal pages | One `h1`, short intro, optional breadcrumb and primary action | With/without action; nested admin page | Stacks on small screens; heading order remains logical |
| Button | All forms/actions | Verb-first text label | Primary, secondary, destructive, link-style; hover/focus/pressed/disabled/loading | 44px target; native button for actions, anchor for navigation |
| Form field | Register, login, product/reward/customer forms | Label, control, optional hint, error | Text, email, number, textarea, password; required/optional/invalid/disabled/read-only | Full width on mobile; label bound to control; error referenced by control |
| Password field | Register/login/profile password change | Label, value, hint, error | New password, current password, confirmation | Never refill after POST; use autocomplete purpose values |
| Code-entry field | `claim.php` | One 16-character code, format hint, optional count | Empty, editing, invalid format, submitting, success/failure | Full width, readable character spacing; allow paste, lowercase, spaces/hyphens; manual path always works |
| Select control | Product/reward/batch filters/forms | Visible label, options, current choice | Required, optional, disabled, invalid | Native select; no placeholder-only label |
| Search/filter bar | Product, batch, customer, fraud, audit lists | Search term, optional status/date filters, submit/reset | Empty, active filters, no results | Filters wrap/stack; labels remain visible |
| Summary/metric card | Customer/admin dashboards | Label, value, optional context | Standard, positive/negative contextual value | 1–2 columns on mobile, compact grid desktop; value text remains selectable |
| Content/reward card | Rewards catalogue, selected reward | Name, short description, points cost, stock/status, action | Available, insufficient points, out of stock, inactive | Single column on phone; image is optional local placeholder with useful alt text |
| Data table | History, products, batches, customers, redemptions, audit, reports | Header, rows, empty state, optional row action | Read-only vs controlled action; sortable only where designed | Contained horizontal scroll on phone; `<th>` headers and caption/label |
| Transaction row | Dashboard/history/customer detail | Date, description, type, signed points | Claim, redemption, reversal, adjustment | Stacks description/date above amount on narrow screens; plus/minus plus text |
| Status badge | All lifecycle and status lists | Exact status text | Active, suspended, closed; batch states; pending/fulfilled/cancelled; fraud and reconciliation states | Do not rely on color alone; text remains visible |
| Alert/flash message | All pages | Type, concise message, optional correlation ID | Success, info, warning, error | Full width; icon plus text; `role=status` or `role=alert` for dynamic messages |
| Form error summary | Validation-heavy forms | Summary and links to invalid controls | Single/multiple errors | Top of form; focus summary after server validation if practical |
| Confirmation panel | Redeem, export, activate/void, suspend, fulfil/cancel, reconcile | Exact object/action, consequence, confirm/cancel, reason if required | Standard/destructive; password re-entry for export | Stacks on mobile; explicit action label, keyboard operable |
| Batch-state tracker | `admin/batches.php`, `batch_view.php` | Current state and approved lifecycle steps | draft, generated, exported, printed, active, voided | Compact horizontal on desktop, vertical or wrapped on phone; text state labels |
| Pagination | History and admin tables | Current range, previous/next | First, middle, last, disabled direction | Wraps on phone; accessible navigation label |
| Empty state | Lists and history | What is missing and valid next action | No activity, no records, no search match | Avoid oversized artwork; message and CTA remain readable |
| Loading/submission state | Form submit/list load where applicable | Progress text and disabled action | Loading, submitted, server error | No layout jump; do not depend on animation |
| Audit/detail panel | Customer, fraud, audit and reconciliation detail | Actor, action, entity, time, reason/note | Read-only; reviewed status where applicable | Key/value rows stack on mobile; no edit/delete affordance |
| Inline stock control | `admin/rewards.php` | Stock quantity, add amount, reason if required | Restock, unavailable, zero stock | Clearly show current quantity and resulting action; confirm stock mutation |
| Points adjustment form | `admin/customer_view.php` | Adjustment/correction type, amount, reason | Credit/debit, reversal of original | Numeric amount bounded; never edits old transaction; confirm and audit |

## Component usage rules

1. Build shared page shells first: public/auth, customer, and administrator. Pages supply their own content to the shell.
2. Keep one visual definition per shared component in CSS; do not create page-specific button/input variants without a documented reason.
3. Links navigate; buttons submit or perform an action. Every state-changing form uses POST and a CSRF field.
4. Reusable templates must not contain SQL or decide permissions. Pages call services; services enforce rules; templates only render escaped values.
5. No component may expose secrets or internal-only detail: no plaintext codes, HMACs/keys, passwords, session IDs, CSRF tokens, password hashes, or technical stack traces.
6. Component names here are implementation guidance only; no new PHP classes, database tables or routes are introduced by this catalogue.

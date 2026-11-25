# DESIGN_SYSTEM.md — Stage 3

A compact implementation guide for the customer and administrator interfaces. Tokens are proposed UI decisions; all business rules and route definitions remain governed by the Stage 2 documents.

## 1. Visual direction

Use a clean, trustworthy rewards dashboard: deep blue as the primary action color, teal as a restrained accent, light neutral surfaces, clear typography and compact layouts. Prefer whitespace and alignment over gradients or decoration. No external fonts, icon CDNs, remote scripts or third-party images are required; use a system font stack and local placeholders.

## 2. Color tokens

| Token | Value | Usage |
|---|---|---|
| `--color-primary` | `#1D4ED8` | Primary actions, active links |
| `--color-primary-hover` | `#1E40AF` | Hover/pressed primary actions |
| `--color-accent` | `#0F766E` | Secondary highlights, points accent |
| `--color-bg` | `#F8FAFC` | App/page background |
| `--color-surface` | `#FFFFFF` | Cards, tables, forms |
| `--color-surface-muted` | `#F1F5F9` | Secondary panels, table header |
| `--color-text` | `#0F172A` | Main text |
| `--color-text-muted` | `#475569` | Helper text, metadata |
| `--color-border` | `#CBD5E1` | Inputs, dividers, card borders |
| `--color-success` | `#166534` | Success text/icon |
| `--color-success-bg` | `#F0FDF4` | Success alert surface |
| `--color-warning` | `#92400E` | Warning text/icon |
| `--color-warning-bg` | `#FFFBEB` | Warning alert surface |
| `--color-error` | `#991B1B` | Error text/icon |
| `--color-error-bg` | `#FEF2F2` | Error alert surface |
| `--color-info` | `#075985` | Informational text/icon |
| `--color-info-bg` | `#F0F9FF` | Information alert surface |
| `--color-focus` | `#2563EB` | Visible keyboard outline |

Always pair status color with a label/icon. Do not use color alone. Keep body copy at WCAG AA contrast (4.5:1); use stronger text colors for badges and alerts.

## 3. Typography

```css
:root {
  --font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  --font-size-xs: 0.75rem;   /* 12px */
  --font-size-sm: 0.875rem;  /* 14px */
  --font-size-md: 1rem;      /* 16px */
  --font-size-lg: 1.125rem;  /* 18px */
  --font-size-xl: 1.25rem;   /* 20px */
  --font-size-2xl: 1.5rem;   /* 24px */
  --font-size-3xl: 1.875rem; /* 30px */
  --line-body: 1.5;
  --line-heading: 1.2;
}
```

Use 600–700 weight for headings, 500–600 for buttons/labels and 400 for body copy. Body text defaults to 16px; metadata may use 14px, but never below 12px. Keep long descriptions to a comfortable line length.

## 4. Spacing, shape and elevation

| Token | Value |
|---|---:|
| `--space-1` | 4px |
| `--space-2` | 8px |
| `--space-3` | 12px |
| `--space-4` | 16px |
| `--space-5` | 20px |
| `--space-6` | 24px |
| `--space-8` | 32px |
| `--space-10` | 40px |
| `--space-12` | 48px |
| `--radius-sm` | 6px |
| `--radius-md` | 10px |
| `--radius-lg` | 14px |

Use 16–24px card padding, 12–16px form-field gaps, 24–32px section gaps and 24px minimum page gutters on desktop (16px on mobile). Content max width: about 1,200px. Use subtle borders as the default; reserve shadows for overlay/raised elements.

## 5. Layout and breakpoints

| Range | Layout |
|---|---|
| Mobile: `< 600px` | One column; compact header; stacked/collapsible customer/admin navigation; forms full width; metric cards 1–2 per row; tables horizontally scroll |
| Tablet: `600–959px` | Customer content uses 2 columns where helpful; admin navigation collapses; filters wrap; forms may use 2 columns only when fields remain readable |
| Desktop: `>= 960px` | Customer top navigation and centered content; admin sidebar (about 240px) plus main area; dashboard metrics in a compact grid |

At every size, keep the main action and key status visible without horizontal page scrolling. For wide data tables, allow a contained horizontal scroller rather than dropping fields. On mobile, form fields stack and buttons become full-width when that improves touch use.

## 6. Component standards

| Component | Standard and variants |
|---|---|
| Primary button | Solid primary, one per main task area; clear verb label; default/hover/focus/pressed/disabled/loading |
| Secondary button | Neutral surface + border; for back/cancel/secondary action |
| Destructive button | Error treatment; requires confirmation and reason when Stage 2 calls for one |
| Text input | 44px target height, visible label, optional hint, inline error, disabled/read-only state |
| Password input | Same field style; never repopulate value; show policy near registration |
| Code entry | Monospaced or spaced characters, 16-character guidance, optional count; accepts spaces/hyphens/case; manual entry always available |
| Select | Label, selected value, clear focus and disabled state; avoid placeholder-only labels |
| Search field | Visible label or accessible name, search button/submit, clear empty/no-result state |
| Card | Surface + border + 14px radius; use for summary or reward, not every table row |
| Metric card | Short label, prominent numeric value, optional context; no unsupported metric |
| Table | Semantic header, aligned columns, readable empty state, contained mobile scroll, action column last |
| Status badge | Compact text plus optional icon; approved state name, not color-only |
| Alert | Success/error/warning/info variants; icon + heading/message; live region for dynamic results |
| Confirmation panel | Exact action/object, consequence, cancel and confirm; reason field for audited state changes |
| Pagination | Previous/next plus current range; keyboard-accessible links/buttons |
| Empty state | What is empty + next valid action; no decorative illustration required |
| Loading state | Inline progress text/skeleton only where loading is asynchronous; controls disabled during submission |
| Page header | One `h1`, brief description, breadcrumb when nested, primary action aligned nearby |
| Navigation | Current page indication, accessible name, keyboard access and responsive collapse |
| Transaction row | Date, plain-language description, signed points; reversal/adjustment clearly identified |
| Reward tile | Local image placeholder, reward name, description, cost, stock/availability and redeem action |
| Batch lifecycle | Text step/status indicator for draft, generated, exported, printed, active, voided; illegal transitions absent/disabled |

## 7. Interaction and motion

- Use 120–180ms transitions for color, border and subtle elevation only.
- Buttons show hover, keyboard focus, pressed and disabled states. Forms show clear focus and validation states.
- Submit actions show a busy label/state and prevent repeat clicks; server idempotency remains authoritative.
- Success/error messages remain visible long enough to read and are announced through a polite/assertive live region as appropriate.
- Confirm before redemption, export, batch activation/voiding, account suspension/reactivation, reward stock changes, redemption fulfil/cancel and reconciliation run.
- Respect `prefers-reduced-motion`; no auto-playing, looping or decorative animation is needed.

## 8. Accessibility and content

Use semantic HTML, a single `h1`, associated `<label>` controls, clear button names, descriptive link text, table headers, keyboard operation and visible focus. Never communicate state by color alone. Error text identifies the field and correction; do not rely on placeholder text. Preserve text zoom and avoid fixed-height content that clips. Icons are decorative unless accompanied by an accessible name. Do not load third-party assets on the claim flow.

## 9. CSS file mapping

Follow `PROJECT_STRUCTURE.md`:

- `public/assets/css/base.css`: tokens, reset, typography, shared controls, alerts, tables, responsive utilities.
- `public/assets/css/customer.css`: public/customer navigation and customer page composition.
- `public/assets/css/admin.css`: admin sidebar, admin page grids, filters and wide tables.

No separate JavaScript file is permitted by the Stage 2 project constraints. Keep any necessary inline script in a PHP page, limited to the approved QR-fragment prefill/removal or a small nonessential interaction. The manual claim form must work with JavaScript disabled.

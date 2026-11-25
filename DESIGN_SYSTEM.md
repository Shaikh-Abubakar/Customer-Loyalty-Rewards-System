# DESIGN_SYSTEM.md — Customer Loyalty Rewards System

**Stage 3 design deliverable.** This file defines reusable visual and interaction tokens for the Customer Loyalty Rewards System. It is a specification, not an implementation stylesheet. Do not create CSS or PHP files until Stage 4 is approved.

## 1. Design principles

- **Clear before decorative:** hierarchy, labels and feedback take priority over ornament.
- **One visual identity:** customer and administrator pages share colours, typography, spacing and controls; their navigation layouts remain distinct.
- **Action clarity:** primary actions are visually obvious. `Claim Product Code` is the primary customer action.
- **Honest states:** availability, pending work, validation errors and terminal states are explicit.
- **Accessible by default:** colour is never the only status signal; keyboard focus, labels and error associations are part of each component.
- **Practical delivery:** use system fonts, CSS3, semantic HTML in PHP templates and no external UI framework or CDN.

## 2. Colour system

The palette uses deep indigo for primary actions, teal for supporting emphasis and restrained amber for highlights. Surfaces stay neutral to preserve readability. Values below are design tokens and may be refined only after a documented visual/accessibility review.

### 2.1 Brand and surface tokens

| Token | Value | Intended use |
|---|---|---|
| `--color-primary` | `#2546A8` | Primary buttons, active links, key focus treatment |
| `--color-primary-hover` | `#1D3786` | Hover/pressed primary controls |
| `--color-primary-soft` | `#EAF0FF` | Selected navigation, subtle primary callouts |
| `--color-secondary` | `#176B65` | Secondary actions, supporting emphasis |
| `--color-secondary-soft` | `#E7F4F2` | Supporting information panels |
| `--color-accent` | `#F0B34A` | Small highlights, numeric emphasis on dark text |
| `--color-accent-soft` | `#FFF4D6` | Attention callouts that are not errors |
| `--color-page` | `#F4F6FA` | Main page background |
| `--color-surface` | `#FFFFFF` | Cards, forms, menus and tables |
| `--color-surface-raised` | `#FFFFFF` | Dialogs and popovers with a stronger shadow |
| `--color-text` | `#182235` | Main body and heading text |
| `--color-text-muted` | `#4B5567` | Supporting text and metadata |
| `--color-text-inverse` | `#FFFFFF` | Text on dark primary/secondary buttons |
| `--color-border` | `#D6DCE6` | Decorative dividers and low-emphasis boundaries |
| `--color-control-border` | `#667085` | Inputs and controls requiring a visible boundary |
| `--color-focus` | `#2546A8` | Visible keyboard focus outline |

### 2.2 Semantic status tokens

| Status family | Foreground token | Foreground | Background token | Background | Border token | Border | Typical use |
|---|---|---|---|---|---|---|---|
| Success | `--color-success` | `#176B45` | `--color-success-soft` | `#E8F5EE` | `--color-success-border` | `#A8D6BB` | Claim accepted, profile saved, action completed |
| Warning | `--color-warning` | `#704400` | `--color-warning-soft` | `#FFF4D6` | `--color-warning-border` | `#E8C779` | Pending decision, low stock, approaching limit |
| Error | `--color-error` | `#AC2923` | `--color-error-soft` | `#FDEDEC` | `--color-error-border` | `#E6A6A2` | Invalid input, failed action, system error |
| Information | `--color-info` | `#2359A8` | `--color-info-soft` | `#EAF1FC` | `--color-info-border` | `#B6C9ED` | Guidance, neutral explanation, system note |
| Neutral | `--color-neutral` | `#344054` | `--color-neutral-soft` | `#F0F2F5` | `--color-neutral-border` | `#D0D5DD` | Draft, inactive, not yet reviewed |

The text/status label and, where appropriate, a small icon must accompany every colour. For example, a green pill alone is not sufficient; display `Active` as text.

### 2.3 Contrast guidance

- Normal text: target at least 4.5:1 against its background.
- Large text: target at least 3:1.
- Functional control boundaries, icons and focus indicators: target at least 3:1 against adjacent colours where the contrast requirement applies.
- The selected primary, secondary and semantic text colours are intended to provide strong contrast on their paired surfaces. Recheck final rendered combinations before release, including hover, disabled, focus and high-contrast environments.
- Disabled controls must remain understandable. Do not reduce opacity so far that the reason for the disabled state becomes unreadable.

## 3. Typography

### 3.1 Font family

Use a native system stack; do not load external font files or a Google Fonts CDN:

`system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif`

This keeps deployment independent of third-party network requests and supports the QR claim page's no-third-party-resource rule.

### 3.2 Type scale

| Token | Size | Weight | Line height | Use |
|---|---:|---:|---:|---|
| `--text-xs` | 12 px | 400–500 | 1.45 | Compact metadata, timestamps where space is tight |
| `--text-sm` | 14 px | 400–500 | 1.5 | Helper text, table secondary content |
| `--text-base` | 16 px | 400 | 1.55 | Default body, form labels and table values |
| `--text-lg` | 18 px | 500–600 | 1.45 | Intro text, emphasized card content |
| `--text-xl` | 20 px | 600 | 1.35 | Card headings, section titles |
| `--text-2xl` | 24 px | 600–700 | 1.3 | Page subsection headings |
| `--text-3xl` | 30 px | 650–700 | 1.2 | Page title on desktop |
| `--text-4xl` | 36 px | 700 | 1.15 | Landing hero heading only |

### 3.3 Hierarchy rules

- One visible H1 per page. Use the 30 px page-title size on desktop and scale down to 24–28 px on narrow screens.
- H2 identifies major page sections; H3 identifies cards or grouped content.
- Use weight and spacing before adding more font sizes.
- Use tabular numerals for point balances, IDs and table columns where alignment improves scanning.
- Avoid all-caps labels for long text. Short status tags may use normal title case.
- Body text should remain at least 16 px on mobile for the main reading area.

## 4. Spacing and sizing

### 4.1 Spacing scale

Use a 4 px base unit:

| Token | Value | Use |
|---|---:|---|
| `--space-1` | 4 px | Icon/text micro-gap |
| `--space-2` | 8 px | Input/help gap, compact list spacing |
| `--space-3` | 12 px | Tight card/form spacing |
| `--space-4` | 16 px | Default component padding and field gap |
| `--space-5` | 20 px | Compact section separation |
| `--space-6` | 24 px | Card padding, form groups |
| `--space-8` | 32 px | Major section gap |
| `--space-10` | 40 px | Page section separation |
| `--space-12` | 48 px | Large section separation |
| `--space-16` | 64 px | Landing-page section rhythm |

### 4.2 Layout sizing

| Token | Value | Use |
|---|---:|---|
| `--content-max` | 1160 px | Customer content maximum width |
| `--admin-content-max` | 1440 px | Administrator workspace content maximum |
| `--sidebar-width` | 248 px | Desktop admin sidebar |
| `--header-height` | 68 px | Customer/admin top bar |
| `--control-height` | 44 px | Standard inputs/buttons |
| `--control-height-compact` | 36 px | Dense table controls only |
| `--touch-target-min` | 44 px | Recommended minimum interactive target |

### 4.3 Shape and elevation

| Token | Value | Use |
|---|---:|---|
| `--radius-sm` | 6 px | Small fields and compact chips |
| `--radius-md` | 10 px | Inputs, buttons, status pills |
| `--radius-lg` | 14 px | Standard cards |
| `--radius-xl` | 18 px | Landing feature panels and major summaries |
| `--radius-pill` | 999 px | Status badges and compact tags |
| `--shadow-card` | `0 2px 10px rgba(24, 34, 53, 0.06)` | Low-elevation card |
| `--shadow-menu` | `0 8px 24px rgba(24, 34, 53, 0.12)` | Dropdowns and mobile navigation drawer |
| `--shadow-dialog` | `0 18px 48px rgba(24, 34, 53, 0.18)` | Confirmation dialog |

Borders remain the primary way to define card edges. Shadows are subtle and should not be layered on every nested element.

## 5. Layout system

### 5.1 Customer layout

- A single top header contains the brand, main navigation and account actions.
- Main content is centred and capped at `--content-max` with 24 px desktop side padding and 16 px mobile padding.
- Dashboard uses a prominent points summary and claim action before secondary activity.
- Forms use a readable single-column structure; only simple paired fields (for example name/email only if sufficient width) may share a row.
- Customer list/history content uses a table on desktop and labelled stacked rows on mobile.
- Landing page uses a constrained hero, concise steps and benefits; avoid full-screen empty hero sections.

### 5.2 Administrator layout

- Desktop: fixed or sticky left sidebar (248 px) with the main workspace to its right; top bar stays aligned with the main content.
- Tablet: sidebar may collapse to a compact accessible rail or remain collapsible; the selected state must be announced and labels remain available to assistive technology.
- Mobile: sidebar becomes a modal/drawer navigation opened by a labelled menu button; drawer closes on navigation and Escape and returns focus to the opener.
- Page content uses full-width tables where helpful, but filters and forms remain grouped and readable.
- Destructive actions appear in context and do not share the same visual treatment as routine navigation.

### 5.3 Grid patterns

- Customer dashboard summary: one column on mobile, two columns on tablet, up to three or four summary tiles on desktop as content requires.
- Admin dashboard: two columns on mobile only where each tile remains readable; three columns on tablet; up to six compact KPI tiles across desktop.
- Product/reward tiles: one column on narrow mobile, two columns on tablet, three columns on wide desktop.
- Forms: one column by default; use two columns only for short, independent fields. Long descriptions, passwords and code-entry controls span the full form width.

## 6. Responsive breakpoints

| Name | Width | Navigation | Content behavior |
|---|---|---|---|
| Mobile | 0–639 px | Customer menu expands from top bar; admin uses drawer | Single column, stacked controls, labelled card rows, full-width primary actions where useful |
| Tablet | 640–1023 px | Compact/collapsible admin sidebar; customer menu adapts to available width | Two-column cards where readable; forms mostly single column; tables may scroll horizontally |
| Desktop | 1024 px and above | Customer horizontal header; persistent admin sidebar | Centred customer content; multi-column metrics/cards; full data tables |

Do not design only for fixed device widths. Test between breakpoints and at 200% browser zoom. Avoid horizontal page overflow; a single table region may scroll when necessary, with all columns still accessible.

## 7. Reusable component catalogue

The full usage inventory is in `COMPONENT_CATALOG.md`. The rules below define the visual primitives used by all components.

### 7.1 Buttons

| Variant | Appearance | Use | Rules |
|---|---|---|---|
| Primary | Primary indigo fill, white text | One main action per region/page | Use for Claim points, Join, Save, Confirm and Run reconciliation |
| Secondary | White or teal-soft surface with dark text and visible border | Supporting action | Use for Browse rewards, Back and non-destructive secondary actions |
| Tertiary | Text-only with clear focus/hover underline | Low-priority navigation/action | Use sparingly; maintain a 44 px touch area |
| Destructive | Error fill or error outline with explicit text | Void, suspend, deactivate, cancel | Must have a confirmation step; do not use for routine dismiss/back actions |
| Disabled | Muted fill/border with readable text | Action unavailable | Explain why nearby; do not rely on opacity alone |

Standard height is 44 px with horizontal padding 16–20 px. Button labels use verbs. Avoid icon-only primary actions.

### 7.2 Form controls

- Inputs, selects and text areas use a white surface, visible control border, 10 px radius and 44 px minimum height.
- Labels sit above fields. Help text is immediately below the control.
- Error state uses an error border and an inline text message; do not rely on red alone.
- Focus state uses a visible 2 px outline with offset; it must not be clipped by parent containers.
- Password fields have a clear label and must not reveal values after validation failure.
- Code-entry uses a monospaced or tabular-numeral treatment for clarity, but does not add spacing to the submitted value. The helper describes normalisation rules.
- Search input includes a visible label or visually hidden label; placeholder text is not its only name.

### 7.3 Cards and metric cards

- Standard card: white surface, 1 px subtle border, 14 px radius, 20–24 px internal padding.
- Metric card: label above the number, supporting period/context below, and optional small icon. Use tabular numerals. Do not make a whole card clickable unless its action is clear and keyboard accessible.
- Avoid multiple nested card layers. Group related information with headings and dividers.

### 7.4 Tables

- Use a semantic table with caption and scoped column headers.
- Align numeric columns consistently; keep status and actions near the right side on desktop.
- Use restrained row separators and a readable hover state that does not imply editability.
- On mobile, transform rows into labelled blocks or permit horizontal scrolling within a named table region. Never drop the field labels.
- Read-only tables (points ledger and audit records) must not look editable.

### 7.5 Status indicators

Use a compact pill with text and optional icon. Persisted states remain exact:

- Batch: `Draft`, `Generated`, `Exported`, `Printed`, `Active`, `Voided`.
- Redemption: `Pending`, `Fulfilled`, `Cancelled`.
- Fraud review: `Open`, `Reviewed`, `Dismissed`, `Escalated`.
- Account: `Active`, `Suspended`, `Closed`.
- Reconciliation: `Clean`, `Discrepancies`, `Failed`; finding states `Open`, `Acknowledged`, `Resolved by correction`.

Do not invent `Processing` or `Expired` as persisted business states. `Processing` may be a temporary visual state while a request is in progress.

### 7.6 Alerts and feedback panels

- Inline field error: appears next to the relevant field and is programmatically associated.
- Page alert: placed near the page heading or form start, uses an icon plus clear heading/body.
- Success status: concise confirmation with the effect and next destination.
- Warning notice: communicates a risk or pending decision; avoid alarm styling for routine information.
- System error: generic message plus correlation ID when supplied. Technical diagnostics remain in protected logs.

### 7.7 Dialogs and confirmations

- Use a dialog only when an action has a meaningful consequence or requires deliberate confirmation.
- Dialog has a descriptive title, consequence text, a primary confirm action and a secondary cancel action.
- Keyboard focus enters the dialog, remains managed inside it, closes on Escape when safe and returns to the triggering control.
- Destructive confirm button uses the destructive variant. The default focus should be on the safer cancel action for irreversible actions.
- Do not rely on a browser `confirm()` popup as the final visual design.

### 7.8 Pagination and empty/loading states

- Points history uses keyset pagination with `Older activity` rather than requiring a total page count.
- Admin search/list tables may use page numbers only if count/query support is actually implemented; otherwise use previous/next controls.
- Empty states state what is empty and why, then show an action only when an action is permitted.
- Loading state uses skeletons for stable areas or a small spinner beside the action; the control must not allow duplicate submission.

## 8. Motion and interaction

| Interaction | Behavior | Motion |
|---|---|---|
| Button hover/active | Clear background/border change and pressed feedback | 120–160 ms ease-out |
| Input focus | Strong border and focus outline | No movement; optional 100 ms colour transition |
| Navigation drawer | Slide/fade with focus management | 160–200 ms; no parallax |
| Confirmation dialog | Subtle fade/scale from 98% to 100% | 140–180 ms |
| Loading feedback | Spinner or skeleton | Continuous spinner only while the request is pending |
| Success/error feedback | Region appears with icon/text and is announced | No bounce or confetti; optional 120 ms fade |

Respect `prefers-reduced-motion: reduce`; remove non-essential transitions and transforms. Motion must never be the only signal that an action completed.

## 9. Accessibility standards

- Target WCAG 2.2 AA as the design goal; final conformance requires implemented-page testing.
- Normal text contrast target: at least 4.5:1; large text: at least 3:1; functional controls/focus indicators: at least 3:1 where applicable.
- Keyboard operability for all navigation, menus, forms, tables and dialogs.
- Visible focus ring; do not remove browser focus styles without an equivalent.
- Programmatic labels and descriptions; field errors linked with `aria-describedby` and invalid state indicated.
- Status feedback announced with an appropriate live region.
- Semantic heading order and landmarks.
- No colour-only status communication.
- Responsive text and reflow at narrow widths and high zoom.
- Table captions and header scope; icon buttons have accessible names.
- Reduced-motion support and no flashing content.

## 10. Shared implementation notes for Stage 4

- Load the shared stylesheet and then the customer/admin stylesheet from `public/assets/css/`; do not create a separate JavaScript asset.
- Any inline script required for the QR fragment workflow must carry the response-specific CSP nonce described in `ARCHITECTURE.md`. Do not use `unsafe-inline`, inline event-handler attributes or `eval`.
- The QR claim page must not load third-party scripts, fonts or images and must use the approved no-store/referrer policy.
- Use local CSS-drawn placeholders or simple decorative symbols rather than adding a database image field. Any later image upload requires separate approval.
- All user-visible dynamic values are escaped through the approved helper.
- The design system is not evidence that the eventual interface has been implemented, visually checked or accessibility-tested.

# PAGE_WIREFRAMES.md — Customer Loyalty Rewards System

**Low-fidelity wireframes for Stage 3.** These text wireframes describe layout and hierarchy only. They are not HTML, PHP, CSS or executable code. Page paths follow `PROJECT_STRUCTURE.md`. The customer and administrator navigation shells are shared by the pages in their respective areas.

## 1. Wireframe conventions

- `[Primary]` = the one dominant action in the section.
- `[Secondary]` = supporting action.
- `[POST + CSRF]` = state-changing form; not a GET link.
- `[Read-only]` = data cannot be edited from that view.
- `Desktop` and `Mobile` layouts are shown for every required page.
- Shared loading, empty, validation, permission-denied and server-error patterns are defined in `UI_UX_SPEC.md` and `COMPONENT_CATALOG.md`. Page-specific states are listed below each wireframe.
- All sample names and values are demonstration placeholders, not real factory data.

## 2. Shared page shells

### 2.1 Public/visitor shell

**Desktop**

```text
┌──────────────────────────────────────────────────────────────────────────┐
│ Loyalty Rewards                    How it works     Sign in   [Join]      │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│                           PAGE CONTENT                                  │
│                                                                          │
├──────────────────────────────────────────────────────────────────────────┤
│ Program note / informational links                                      │
└──────────────────────────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Loyalty Rewards          [Menu]  │
├──────────────────────────────────┤
│                                  │
│          PAGE CONTENT            │
│                                  │
├──────────────────────────────────┤
│ Footer / informational links     │
└──────────────────────────────────┘
```

The menu expands to `How it works`, `Sign in` and `Join`. No real brand/contact link is invented.

### 2.2 Customer shell

**Desktop**

```text
┌──────────────────────────────────────────────────────────────────────────┐
│ Loyalty Rewards   Dashboard  Claim code  Rewards  History  Redemptions   │
│                                                            Profile  [Out] │
├──────────────────────────────────────────────────────────────────────────┤
│ Breadcrumb (when needed)                                                 │
│ PAGE TITLE                                      Optional contextual action│
│                                                                          │
│                         PAGE CONTENT                                     │
│                                                                          │
└──────────────────────────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Loyalty Rewards          [Menu]  │
├──────────────────────────────────┤
│ PAGE TITLE                       │
│                                  │
│       PAGE CONTENT               │
│                                  │
│ [Primary action when appropriate]│
└──────────────────────────────────┘
```

The mobile menu contains the same destinations as desktop. The claim action remains prominent. Sign out is a POST form with CSRF.

### 2.3 Administrator shell

**Desktop**

```text
┌──────────────────┬───────────────────────────────────────────────────────┐
│ Loyalty Rewards  │ Administrator workspace              Admin  [Sign out]│
│                  ├───────────────────────────────────────────────────────┤
│ Overview         │ Breadcrumb                                            │
│ Products         │ PAGE TITLE                          [Contextual action]│
│ Batches          │                                                       │
│ Customers        │                   PAGE CONTENT                        │
│ Rewards          │                                                       │
│ Redemptions      │                                                       │
│ Fraud monitoring │                                                       │
│ Audit records    │                                                       │
│ Reports          │                                                       │
│ Reconciliation   │                                                       │
└──────────────────┴───────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ [Menu]  Admin workspace   [Out]  │
├──────────────────────────────────┤
│ PAGE TITLE                       │
│ Breadcrumb (if needed)           │
│                                  │
│          PAGE CONTENT            │
└──────────────────────────────────┘

Menu drawer (opened):
┌──────────────────────────────────┐
│ Administrator navigation   [X]  │
│ Overview                         │
│ Products                         │
│ Batches                          │
│ Customers                        │
│ Rewards                          │
│ Redemptions                      │
│ Fraud monitoring                 │
│ Audit records                    │
│ Reports                          │
│ Reconciliation                   │
└──────────────────────────────────┘
```

The admin drawer is keyboard-operable, closes on Escape and returns focus to the menu button. All admin pages use the same shell and server-side role guard.

## 3. Customer pages

### 3.1 Landing page — `public/index.php`

**Desktop**

```text
┌──────────────────────────────────────────────────────────────────────────┐
│ [Logo] Loyalty Rewards                   How it works  Sign in  [Join]   │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Turn every eligible box into rewards             [Simple product       │
│  Short plain-language explanation                  illustration/icon    │
│  [Join the program]  Sign in                       placeholder]          │
│                                                                          │
├──────────────────────────────────────────────────────────────────────────┤
│ HOW IT WORKS                                                             │
│ [1 Buy sealed box] [2 Find code] [3 Enter code] [4 Earn points]         │
├──────────────────────────────────────────────────────────────────────────┤
│ WHY JOIN?                                                                │
│ [Clear points history] [Reward choices] [Simple claiming]               │
├──────────────────────────────────────────────────────────────────────────┤
│ Honest note: the code is the demo's operational purchase-proof credential│
│  It does not independently verify a retail payment.                    │
├──────────────────────────────────────────────────────────────────────────┤
│ Footer / supplied information links                                     │
└──────────────────────────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Loyalty Rewards          [Menu]  │
├──────────────────────────────────┤
│ Turn every eligible box into     │
│ rewards                          │
│ Short explanation                │
│ [Join the program]               │
│ Sign in                          │
│ [Small icon placeholder]         │
├──────────────────────────────────┤
│ HOW IT WORKS                     │
│ 1. Buy a sealed box              │
│ 2. Find the concealed code       │
│ 3. Enter the code                │
│ 4. Earn points                   │
├──────────────────────────────────┤
│ BENEFITS                         │
│ Benefit row                      │
│ Benefit row                      │
│ Benefit row                      │
├──────────────────────────────────┤
│ Purchase-proof limitation note  │
│ Footer                           │
└──────────────────────────────────┘
```

**States:** default landing page; registration/login destination; generic page error. No signed-in customer information appears here.

### 3.2 Registration — `public/register.php`

**Desktop**

```text
┌───────────────────────────────────────────────────────┐
│ Join the loyalty program                               │
│ Create your customer account                           │
│                                                       │
│ Full name                  [________________________]  │
│ Email                      [________________________]  │
│ Phone (optional)           [________________________]  │
│ Password (12+ characters)  [________________________]  │
│ Confirm password           [________________________]  │
│                                                       │
│ [Create account]                                       │
│ Already registered? Sign in                            │
│ Generic feedback / field errors                        │
└───────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Create your account              │
│ Full name                        │
│ [____________________________]   │
│ Email                            │
│ [____________________________]   │
│ Phone (optional)                 │
│ [____________________________]   │
│ Password                         │
│ [____________________________]   │
│ Confirm password                 │
│ [____________________________]   │
│ [Create account]                 │
│ Sign in                          │
│ Feedback / validation             │
└──────────────────────────────────┘
```

**States:** initial; required field/email/password validation; mismatch; generic duplicate-email response; successful registration and redirect; server error with correlation ID. No role selector or email verification.

### 3.3 Login — `public/login.php`

**Desktop**

```text
┌───────────────────────────────────────────────────────┐
│ Welcome back                                          │
│ Email                        [______________________] │
│ Password                     [______________________] │
│ [Sign in]                                              │
│                                                       │
│ New here? Create an account                            │
│ Help: contact factory support for identity-checked    │
│ assistance if access is blocked.                      │
│ Generic error / rate-limit / account-status message   │
└───────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Welcome back                     │
│ Email                            │
│ [____________________________]   │
│ Password                         │
│ [____________________________]   │
│ [Sign in]                        │
│ Create an account                │
│ Support guidance                 │
│ Feedback area                    │
└──────────────────────────────────┘
```

**States:** initial; generic credentials failure; rate limited; neutral suspended/closed support guidance; successful redirect to customer/admin dashboard or allowlisted claim route; server error. No forgot-password flow or arbitrary return URL.

### 3.4 Customer dashboard — `public/dashboard.php`

**Desktop**

```text
┌──────────────────────────────────────────────────────────────────────────┐
│ Good afternoon, Demo Customer                                            │
│                                                                          │
│ ┌──────────────────────────────────────┐  ┌────────────────────────────┐ │
│ │ AVAILABLE POINTS                     │  │ POINTS FROM BOX CLAIMS     │ │
│ │ 100 points                            │  │ 125 points                 │ │
│ │ [Claim product code]                 │  │                            │ │
│ └──────────────────────────────────────┘  └────────────────────────────┘ │
│ ┌──────────────────────────────────────┐  ┌────────────────────────────┐ │
│ │ POINTS REDEEMED                      │  │ QUICK ACTION               │ │
│ │ 50 points (gross debits)              │  │ [Browse rewards]           │ │
│ └──────────────────────────────────────┘  └────────────────────────────┘ │
│                                                                          │
│ RECENT POINTS ACTIVITY                 LATEST REDEMPTION                 │
│ Date | Activity | Change               #1001 Gift Voucher | Pending      │
│ Claim Biscuit Box             +10       [View redemptions]                │
│ Claim Chocolate Box           +15                                        │
│ Redemption                   -30                                         │
└──────────────────────────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Welcome, Demo Customer            │
│ AVAILABLE POINTS                  │
│ 100 points                        │
│ [Claim product code]              │
│ [Browse rewards]                  │
│ Points from claims: 125            │
│ Points redeemed (gross): 50       │
├──────────────────────────────────┤
│ RECENT POINTS ACTIVITY            │
│ Claim Biscuit Box          +10    │
│ Claim Chocolate Box        +15    │
│ Redemption                 -30    │
├──────────────────────────────────┤
│ LATEST REDEMPTION                 │
│ #1001 Gift Voucher                │
│ Pending                           │
│ [View redemptions]                │
└──────────────────────────────────┘
```

**States:** populated; new customer/zero points with empty activity; loading summary; no recent redemption; account inactive/permission denied; server error. Figures above are only example values and must be internally consistent in the demo data guide.

### 3.5 Claim product code — `public/claim.php`

**Desktop**

```text
┌──────────────────────────────────────────────────────────────────────────┐
│ Claim Product Code                                                       │
│ Find the unique code inside your sealed product box.                    │
│                                                                          │
│ ┌────────────────────────────────────────────────────────────────────┐  │
│ │ PRODUCT CODE                                                        │  │
│ │ [ XXXX-XXXX-XXXX-XXXX                                         ]    │  │
│ │ Enter 16 characters. Spaces/hyphens are optional; lowercase works.  │  │
│ │                                                                    │  │
│ │                    [Claim points]                                  │  │
│ │                                                                    │  │
│ │ Validation / success / failure feedback                             │  │
│ └────────────────────────────────────────────────────────────────────┘  │
│ Each code can be claimed once. Do not share your code.                  │
│                                                                          │
│ Optional alternative: QR link (secondary; manual entry always works).   │
└──────────────────────────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Claim Product Code                │
│ Find the code inside the sealed   │
│ box, under the scratch-off area   │
│ or on the insert.                 │
│ PRODUCT CODE                      │
│ [XXXX-XXXX-XXXX-XXXX________]     │
│ 16 characters; separators allowed│
│ [Claim points]                    │
│ Feedback area                     │
│ Each code is single-use.          │
│ Optional QR alternative           │
└──────────────────────────────────┘
```

**State wireframes:**

```text
Invalid format:  [Code input with error border]
                 Error: enter exactly 16 approved characters.

Generic failure: [Code input]
                 This code is not valid or has already been used.

Rate limited:    [Code input disabled for request window]
                 Too many attempts. Please try again later.

Success state example (C1 starts at 100 points and claims one 15-point box):
                 [Success icon] 15 points added
                 New balance: 115 points
                 [View points history]

Same customer:   You have already claimed this code.
                 [View points history]
```

Unknown, claimed by another customer, inactive-batch and voided-code outcomes share one generic message. Do not render a separate message for each hidden internal outcome. Optional QR prefill never auto-submits; if login is required, the customer must rescan or type after login.

### 3.6 Points history — `public/history.php`

**Desktop**

```text
┌──────────────────────────────────────────────────────────────────────────┐
│ Points History                                                           │
│ Current balance: 100 points                                              │
│ Claims: 125 points                 Redeemed (gross): 50 points                   │
│                                                                          │
│ DATE         ACTIVITY                         POINTS CHANGE              │
│ 25 Nov       Claim — Biscuit Box              +10                        │
│ 25 Nov       Claim — Chocolate Box            +15                        │
│ 22 Nov       Redemption — Gift Voucher        -30                        │
│ 21 Nov       Cancelled redemption reversal    +20                        │
│ 20 Nov       Admin adjustment                  +5                        │
│                                                                          │
│ [Older activity]                                                         │
└──────────────────────────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Points History                    │
│ Balance: 100 points               │
│ Claims: 125   Redeemed (gross): 50        │
├──────────────────────────────────┤
│ 25 Nov  Claim — Biscuit Box       │
│ +10 points                        │
├──────────────────────────────────┤
│ 25 Nov  Claim — Chocolate Box     │
│ +15 points                        │
├──────────────────────────────────┤
│ 22 Nov  Redemption — Gift Voucher │
│ -30 points                        │
├──────────────────────────────────┤
│ [Older activity]                  │
└──────────────────────────────────┘
```

**States:** entries present; no transactions; older entries available; end of history; loading; read error. Use newest-first keyset pagination. Do not show code plaintext or HMAC data.

### 3.7 Rewards catalogue — `public/rewards.php`

**Desktop**

```text
┌──────────────────────────────────────────────────────────────────────────┐
│ Rewards                                           Your balance: 100 pts  │
│ Browse the rewards available to you.                                    │
│                                                                          │
│ ┌────────────────┐  ┌────────────────┐  ┌────────────────┐             │
│ │ [Gift icon]    │  │ [Coupon icon]  │  │ [Mug icon]      │             │
│ │ Gift Voucher   │  │ Shopping       │  │ Coffee Mug     │             │
│ │ 30 points      │  │ Coupon         │  │ 150 points     │             │
│ │ In stock       │  │ 20 points      │  │ In stock       │             │
│ │ [Redeem]       │  │ In stock       │  │ Need 50 more   │             │
│ └────────────────┘  └────────────────┘  └────────────────┘             │
└──────────────────────────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Rewards                           │
│ Balance: 100 points               │
├──────────────────────────────────┤
│ [Gift icon] Gift Voucher          │
│ 30 points | In stock              │
│ [Redeem]                          │
├──────────────────────────────────┤
│ [Coupon icon] Shopping Coupon     │
│ 20 points | In stock              │
├──────────────────────────────────┤
│ [Mug icon] Coffee Mug             │
│ 150 points | Need 50 more         │
│ [Not enough points]               │
└──────────────────────────────────┘
```

**States:** available/affordable; available but insufficient points; out of stock; no active rewards; loading; read failure. Placeholders are generic; no image field is added to the database.

### 3.8 Redemption confirmation — `public/redeem.php`

**Desktop**

```text
┌──────────────────────────────────────────────────────────────────────────┐
│ Confirm your redemption                                                  │
│                                                                          │
│ ┌──────────────────────────────┐   REDEMPTION SUMMARY                    │
│ │ [Reward placeholder]         │   Reward: Gift Voucher                  │
│ │ Gift Voucher                 │   Points required: 30                  │
│ │ Short description            │   Current balance: 100                │
│ └──────────────────────────────┘   Remaining after request: 70           │
│                                                                          │
│ Collection: designated factory collection point.                         │
│ One unit is requested per redemption.                                    │
│ [Confirm redemption]    [Back to rewards]                                │
│ Feedback / stock / balance conflict                                      │
└──────────────────────────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Confirm redemption                │
│ [Reward placeholder]              │
│ Gift Voucher                      │
│ Points required: 30               │
│ Current balance: 100              │
│ Remaining: 70                     │
│ Collection at factory point       │
│ One unit per request              │
│ [Confirm redemption]              │
│ [Back to rewards]                 │
│ Feedback area                     │
└──────────────────────────────────┘
```

**States:** confirmation ready; insufficient balance; reward inactive; stock unavailable; duplicate/idempotent submission showing the existing redemption; submitting; success with redemption ID and Pending status; server error. The hidden one-time idempotency token is never visible.

### 3.9 Customer redemptions — `public/redemptions.php`

**Desktop**

```text
┌──────────────────────────────────────────────────────────────────────────┐
│ My Redemptions                                                           │
│ ID       REWARD          POINTS     REQUESTED       STATUS                │
│ #1001    Gift Voucher    30         25 Nov          [Pending]             │
│ #1000    Shopping Coupon 20         21 Nov          [Cancelled]           │

│                                                                          │
│ Selected status explanation / collection instruction                    │
└──────────────────────────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ My Redemptions                    │
├──────────────────────────────────┤
│ #1001 Gift Voucher                │
│ 30 points | 25 Nov                │
│ Status: Pending                   │
├──────────────────────────────────┤
│ #1000 Shopping Coupon             │
│ 20 points | 21 Nov                │
│ Status: Cancelled                 │
├──────────────────────────────────┤
└──────────────────────────────────┘
```

**States:** pending, fulfilled and cancelled; no redemptions; loading; server error. Customer cannot cancel.

### 3.10 Profile — `public/profile.php`

**Desktop**

```text
┌──────────────────────────────────────────────────────────────────────────┐
│ My Profile                                                               │
│ Account details                                                           │
│ Full name                 [____________________________]                  │
│ Email (login ID)          demo.customer@example.test [Read only]          │
│ Phone (optional)          [____________________________]                  │
│ Contact details           [____________________________]                  │
│ [Save profile]                                                            │
│                                                                          │
│ Change password                                                         │
│ Current password [___________] New password [___________]               │
│ [Update password]                                                        │
│                                                                          │
│ Account closure                                                          │
│ Closure immediately disables access; 30-day recovery is owner-mediated. │
│ [Request account closure]                                                 │
└──────────────────────────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ My Profile                        │
│ Full name                         │
│ [____________________________]   │
│ Email (read only)                 │
│ demo.customer@example.test        │
│ Phone (optional)                  │
│ [____________________________]   │
│ Contact details                   │
│ [____________________________]   │
│ [Save profile]                    │
│ Change password                   │
│ [Current password]                │
│ [New password]                    │
│ [Update password]                 │
│ Account closure notice             │
│ [Request account closure]          │
└──────────────────────────────────┘
```

**States:** view/edit; validation; save success/error; password validation; closure confirmation; closure accepted/redirect/sign-out; account not active. The confirmation states immediate closure and 30-day owner-mediated recovery. Email is not editable.

## 4. Administrator pages

### 4.1 Administrator dashboard — `public/admin/index.php`

**Desktop**

```text
┌──────────────────┬───────────────────────────────────────────────────────┐
│ Sidebar          │ Overview                         Admin / Sign out     │
│                  ├───────────────────────────────────────────────────────┤
│                  │ ADMIN SUMMARY                                        │
│                  │ [Active batches] [Claims today] [Points issued]      │
│                  │ [Points spent] [Pending redemptions] [Open fraud]     │
│                  │                                                       │
│                  │ RECENT ADMIN ACTIVITY       RECENT CLAIMS             │
│                  │ Action / Actor / Time       Customer / Batch / Points │
│                  │                                                       │
│                  │ RECENT REDEMPTIONS                                     │
│                  │ ID / Customer / Reward / Status                       │
│                  │                                                       │
│                  │ Quick links: Products | Batches | Rewards | Customers │
└──────────────────┴───────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ [Menu] Admin overview      [Out] │
├──────────────────────────────────┤
│ ADMIN SUMMARY                    │
│ Active batches       [number]    │
│ Claims today         [number]    │
│ Points issued        [number]    │
│ Points spent         [number]    │
│ Pending redemptions  [number]    │
│ Open fraud events    [number]    │
├──────────────────────────────────┤
│ RECENT ACTIVITY                  │
│ Compact activity rows            │
├──────────────────────────────────┤
│ RECENT CLAIMS                    │
│ Compact claim rows               │
├──────────────────────────────────┤
│ RECENT REDEMPTIONS               │
│ Compact redemption rows          │
└──────────────────────────────────┘
```

**States:** populated; no recent data; loading; partial metric read error; full server error; permission denied. Metrics remain exactly the six supported metrics.

### 4.2 Products — `public/admin/products.php`

**Desktop**

```text
┌──────────────────┬───────────────────────────────────────────────────────┐
│ Admin sidebar    │ Products                            [Add product]    │
│                  │ [Search name or SKU________________] [Search]         │
│                  │ NAME          SKU          DEFAULT PTS  STATUS ACTION │
│                  │ Biscuit Box   DEMO-BIS-01  10           Active  Edit  │
│                  │ Chocolate Box DEMO-CHO-01  15           Active  Edit  │
│                  │ Seasonal Box  DEMO-SEA-01   8          Inactive Edit  │
│                  │ [Deactivate from row action]                          │
│                  │ Pagination                                             │
└──────────────────┴───────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Products              [Add]       │
│ [Search products________] [Go]    │
├──────────────────────────────────┤
│ Biscuit Box                       │
│ SKU: DEMO-BIS-01                  │
│ Default points: 10 | Active      │
│ [Edit] [Deactivate]               │
├──────────────────────────────────┤
│ Chocolate Box                     │
│ SKU: DEMO-CHO-01                  │
│ Default points: 15 | Active      │
│ [Edit] [Deactivate]               │
└──────────────────────────────────┘
```

**States:** list, empty, no search matches, loading, server error, deactivate confirmation and completed feedback.

### 4.3 Product create/edit — `public/admin/product_edit.php`

**Desktop**

```text
┌──────────────────┬───────────────────────────────────────────────────────┐
│ Admin sidebar    │ Products / Add product                                │
│                  │ SKU                    [________________________]     │
│                  │ Name                   [________________________]     │
│                  │ Description            [________________________]     │
│                  │ Default points (1–10000) [______________________]     │
│                  │ Status                 [Active / Inactive]            │
│                  │ [Save product] [Cancel]                               │
│                  │ Hint: changes to default points affect future batches │
│                  │ only.                                                  │
└──────────────────┴───────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Add / Edit product                │
│ SKU                               │
│ [____________________________]   │
│ Name                              │
│ [____________________________]   │
│ Description                       │
│ [____________________________]   │
│ Default points (1–10000)          │
│ [____________________________]   │
│ Status                            │
│ [Active / Inactive]               │
│ [Save product]                    │
│ [Cancel]                          │
└──────────────────────────────────┘
```

**States:** create; edit; duplicate SKU; missing/invalid values; points out of range; save success; save error; deactivate confirmation. No delete action or unsupported fields.

### 4.4 Batches — `public/admin/batches.php`

**Desktop**

```text
┌──────────────────┬───────────────────────────────────────────────────────┐
│ Admin sidebar    │ Batches                            [Create batch]    │
│                  │ [Search batch/product__________] [Status filter v]    │
│                  │ BATCH      PRODUCT       CODES   PTS   STATUS   VIEW  │
│                  │ B-001      Biscuit Box    100     10    Active   View  │
│                  │ B-002      Chocolate Box   50     15    Active   View  │
│                  │ B-003      Seasonal Box    20      8    Exported View  │
│                  │ Pagination                                             │
└──────────────────┴───────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Batches                [Create]   │
│ [Search batches___________]       │
│ [Filter status v]                 │
├──────────────────────────────────┤
│ B-001 | Biscuit Box               │
│ 100 codes | 10 points/code        │
│ Status: Active       [View]       │
├──────────────────────────────────┤
│ B-002 | Chocolate Box             │
│ 50 codes | 15 points/code         │
│ Status: Exported     [View]       │
└──────────────────────────────────┘
```

**States:** rows in each approved batch state; no batches; no search matches; loading; read error. Do not display plaintext codes or hashes.

### 4.5 Batch creation — `public/admin/batch_create.php`

**Desktop**

```text
┌──────────────────┬───────────────────────────────────────────────────────┐
│ Admin sidebar    │ Batches / Create draft                                │
│                  │ Product (active only)  [Choose product v]             │
│                  │ Planned quantity        [____] (1–1000)               │
│                  │ Points per code         [10] default; override allowed │
│                  │ Range: 1–10000                                          │
│                  │                                                       │
│                  │ REVIEW                                                 │
│                  │ Product / quantity / points per box                   │
│                  │ [Create draft batch] [Cancel]                          │
│                  │ Note: generation + one-time CSV export is a next step.│
└──────────────────┴───────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Create draft batch                │
│ Product                           │
│ [Choose active product v]        │
│ Quantity (1–1000)                 │
│ [________]                        │
│ Points per code (1–10000)         │
│ [________] default shown          │
│ Review selected values            │
│ [Create draft batch]              │
│ [Cancel]                          │
│ Generation/export is next.       │
└──────────────────────────────────┘
```

**States:** initial form; no active products; invalid quantity/points; review; draft created; server error. This screen creates the draft only; it does not bypass the separate generate/export request.

### 4.6 Batch details — `public/admin/batch_view.php`

**Desktop**

```text
┌──────────────────┬───────────────────────────────────────────────────────┐
│ Admin sidebar    │ Batches / B-001                                      │
│                  │ Status: [EXPORTED]                                    │
│                  │ Batch summary                                          │
│                  │ Product / batch code / quantity / points per code     │
│                  │ Created by / created at / generated at / exported at  │
│                  │ Export record / count / status                        │
│                  │                                                       │
│                  │ LIFECYCLE                                             │
│                  │ Draft → Generated → Exported → Printed → Active       │
│                  │                                                       │
│                  │ [Mark printed] [Void batch]                           │
│                  │ Next action: manual mark printed; no printer required.│
└──────────────────┴───────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Batch B-001                       │
│ Status: Exported                  │
│ Product: Biscuit Box              │
│ Codes: 100 | Points/code: 10      │
│ Created: date/time                │
│ Export: recorded                  │
├──────────────────────────────────┤
│ LIFECYCLE                         │
│ Draft                             │
│ Generated                         │
│ Exported  [Current]               │
│ Printed                           │
│ Active                            │
│ Voided (terminal)                 │
├──────────────────────────────────┤
│ [Mark printed]                   │
│ [Void batch]                     │
└──────────────────────────────────┘
```

**States:** draft, transient/generated, exported, printed, active and voided; export record recorded/failed; confirmation for print/activate/void; success; illegal transition; server error. `Void batch` requires a reason. Previously credited points are not automatically reversed.

**Generate/export confirmation dialog** (opened from a draft batch):

```text
┌───────────────────────────────────────────────────────┐
│ Generate codes and export CSV?                        │
│ This is a protected, one-time action. Plaintext codes  │
│ will be streamed once and cannot be retrieved later.  │
│ Enter administrator password [____________________]   │
│ [Confirm and download] [Cancel]                        │
└───────────────────────────────────────────────────────┘
```

### 4.7 Generate/export action — `public/admin/batch_generate_export.php`

This route is POST-only. The confirmation dialog is part of `batch_view.php`; the action endpoint itself returns a CSV response, not an independently browsable GET page.

**Desktop response states**

```text
Pre-POST on batch_view.php:
[Confirmation dialog + password re-entry + CSRF]

During POST:
[Button disabled]  Generating codes and preparing one-time CSV…

Successful response:
Browser receives: text/csv attachment
Headers: no-store
CSV columns: batch_code | row_number | product_sku | points | code | claim_url
Plaintext not rendered in an HTML page and not retained on the server.

Failure before export:
Safe message on originating batch page + correlation ID if available.

Interrupted response after export recorded:
Void the batch and create a new one; no Download again/Reveal codes action.
```

**Mobile response states**

```text
Confirmation dialog fills viewport width:
Explain one-time export
Password field
[Confirm and download]
[Cancel]

Processing:
Generating and preparing CSV…

Success:
Browser download response

Failure/interruption:
Safe recovery guidance on batch detail page
```

**Important:** no separate success HTML page can be assumed after the CSV headers/body begin. Optional QR output is a secondary digital preview only; vendor CSV remains the baseline.

### 4.8 Customers — `public/admin/customers.php`

**Desktop**

```text
┌──────────────────┬───────────────────────────────────────────────────────┐
│ Admin sidebar    │ Customers                                             │
│                  │ [Search name/email________________] [Search]          │
│                  │ NAME          EMAIL                 STATUS BALANCE    │
│                  │ Demo Customer demo.customer@…       Active 100  View  │
│                  │ Demo Customer 2 demo.second@…        Active 30  View  │
│                  │ Pagination                                             │
└──────────────────┴───────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Customers                         │
│ [Search name or email_______]     │
├──────────────────────────────────┤
│ Demo Customer                     │
│ demo.customer@example.test        │
│ Active | Balance: 100 points      │
│ [View customer]                   │
├──────────────────────────────────┤
│ Demo Customer 2                   │
│ demo.second@example.test          │
│ Active | Balance: 30 points        │
│ [View customer]                   │
└──────────────────────────────────┘
```

**States:** rows; no customers; no search result; loading; read error; permission denied. Balances are ledger-derived.

### 4.9 Customer details — `public/admin/customer_view.php`

**Desktop**

```text
┌──────────────────┬───────────────────────────────────────────────────────┐
│ Admin sidebar    │ Customers / Demo Customer                             │
│                  │ Profile: name / email / phone / contact details       │
│                  │ Account status: Active                                │
│                  │ [Suspend]                                              │
│                  │ Points balance: 100                                   │
│                  │ Ledger history (read-only)                            │
│                  │ Redemptions / account status history                   │
│                  │                                                       │
│                  │ POINTS ACTIONS                                        │
│                  │ [Correct original entry] [Manual adjustment]          │
│                  │ Reason required for every action                       │
└──────────────────┴───────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Demo Customer                     │
│ Profile details                   │
│ Status: Active                    │
│ [Suspend]                         │
├──────────────────────────────────┤
│ Balance: 100 points               │
│ Points history (read-only)        │
├──────────────────────────────────┤
│ Redemption history                │
├──────────────────────────────────┤
│ Account status history             │
├──────────────────────────────────┤
│ [Correct entry]                   │
│ [Manual adjustment]               │
└──────────────────────────────────┘
```

**Action dialogs/forms:** suspend/reactivate requires reason category and explanatory text; correction selects an original entry and records a reason; manual adjustment requires signed amount and reason. These create append-only entries/status events and audit rows. No historical row has an edit button.

**States:** active/suspended/closed account; empty status history/ledger/redemptions; action form validation; confirmation; success; already-completed/conflict; server error.

### 4.10 Rewards management — `public/admin/rewards.php`

**Desktop**

```text
┌──────────────────┬───────────────────────────────────────────────────────┐
│ Admin sidebar    │ Rewards                              [Add reward]    │
│                  │ NAME            POINTS COST  STOCK  STATUS   ACTION   │
│                  │ Gift Voucher    30           12     Active   Edit     │
│                  │ Shopping Coupon 20            1     Active   Restock  │
│                  │ Coffee Mug      150           4     Active   Edit     │
│                  │ [Deactivate] / [Restock] per row                       │
└──────────────────┴───────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Rewards management      [Add]     │
├──────────────────────────────────┤
│ Gift Voucher                      │
│ 30 points | Stock 12 | Active     │
│ [Edit] [Restock] [Deactivate]     │
├──────────────────────────────────┤
│ Shopping Coupon                   │
│ 20 points | Stock 1 | Active      │
│ [Edit] [Restock] [Deactivate]     │
└──────────────────────────────────┘
```

**States:** list; no rewards; zero stock; inactive reward; add/edit form; points range validation; restock confirmation with before/after stock; deactivate confirmation; success; server error. No image upload field.

### 4.11 Redemption management — `public/admin/redemptions.php`

**Desktop**

```text
┌──────────────────┬───────────────────────────────────────────────────────┐
│ Admin sidebar    │ Redemptions                                            │
│                  │ [Status: Pending v] [Search customer/reward____]       │
│                  │ ID     CUSTOMER       REWARD         COST  STATUS     │
│                  │ #1001  Demo Customer  Gift Voucher    30   Pending     │
│                  │ #1000  Demo Customer  Shopping Coupon 20   Cancelled   │
│                  │ #0999  Demo Customer 2 Gift Voucher    30   Fulfilled   │
│                  │                                                       │
│                  │ Selected request details                              │
│                  │ [Fulfil] [Cancel]                                     │
└──────────────────┴───────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Redemptions                       │
│ [Filter status v]                 │
├──────────────────────────────────┤
│ #1001 Demo Customer               │
│ Gift Voucher | 30 points          │
│ Pending                           │
│ [View] [Fulfil] [Cancel]          │
├──────────────────────────────────┤
│ #1000 Demo Customer               │
│ Shopping Coupon | 20 points       │
│ Cancelled                         │
│ [View]                            │
├──────────────────────────────────┤
│ #0999 Demo Customer 2             │
│ Gift Voucher | 30 points          │
│ Fulfilled                         │
│ [View]                            │
└──────────────────────────────────┘
```

**States:** pending queue; empty queue; fulfilled/cancelled history; fulfil confirmation with optional note; cancel form with required reason; action conflict when no longer pending; success; server error. Only pending items offer the actions.

### 4.12 Fraud monitoring — `public/admin/fraud.php`

**Desktop**

```text
┌──────────────────┬───────────────────────────────────────────────────────┐
│ Admin sidebar    │ Fraud monitoring                                      │
│                  │ [Type v] [Severity v] [Review status v] [Search]      │
│                  │ TYPE                 SEVERITY CUSTOMER/IP  TIME STATUS│
│                  │ repeated_invalid…    Warning  Customer #5  10:20 Open  │
│                  │ voided_batch_code…   High     Batch B-007  09:10 Open  │
│                  │                                                       │
│                  │ Event detail: safe details / related attempts          │
│                  │ [Mark reviewed] [Dismiss] [Escalate]                   │
│                  │ Review note [____________________________________]   │
└──────────────────┴───────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Fraud monitoring                  │
│ [Type filter v]                   │
│ [Severity filter v]               │
│ [Review status v]                 │
├──────────────────────────────────┤
│ repeated_invalid_codes            │
│ Warning | Customer ref | Open     │
│ [View event]                      │
├──────────────────────────────────┤
│ voided_batch_code_claimed         │
│ High | Batch ref | Open           │
│ [View event]                      │
└──────────────────────────────────┘
```

**States:** open queue; no open events; filters no match; event detail; review note validation; reviewed/dismissed/escalated result; server error. Do not show plaintext code, password, session ID, CSRF token or HMAC key.

### 4.13 Audit records — `public/admin/audit.php`

**Desktop**

```text
┌──────────────────┬───────────────────────────────────────────────────────┐
│ Admin sidebar    │ Audit records                                         │
│                  │ [Action filter] [Actor filter] [Entity filter] [Search]│
│                  │ ACTION          ACTOR      ENTITY      DATE/TIME VIEW │
│                  │ batch.activate  Admin #1   batch #1    25 Nov    View │
│                  │ points.adjust   Admin #1   user #4     24 Nov    View │
│                  │                                                       │
│                  │ Detail drawer: reason / safe before-after details     │
│                  │ Read-only — no edit or delete controls                │
└──────────────────┴───────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Audit records                     │
│ [Filter action___________]        │
│ [Filter actor___________]         │
├──────────────────────────────────┤
│ batch.activate                    │
│ Admin #1 | Batch #1               │
│ 25 Nov | [View details]           │
├──────────────────────────────────┤
│ points.adjust                     │
│ Admin #1 | Customer #4            │
│ 24 Nov | [View details]           │
└──────────────────────────────────┘
```

**States:** list; no records; no matches; detail drawer; loading; read error. All records remain read-only.

### 4.14 Reports — `public/admin/reports.php`

**Desktop**

```text
┌──────────────────┬───────────────────────────────────────────────────────┐
│ Admin sidebar    │ Reports                                               │
│                  │ Date range (where supported): [From] [To] [Apply]     │
│                  │                                                       │
│                  │ CLAIMS PER BATCH                                      │
│                  │ Batch       Product        Claim count                │
│                  │ B-001       Biscuit Box      5                         │
│                  │ B-002       Chocolate Box  10                         │
│                  │                                                       │
│                  │ POINTS ISSUED VS POINTS SPENT                         │
│                  │ Issued: 200 points | Spent: 80 points (gross debits)   │
│                  │                                                       │
│                  │ REDEMPTIONS BY STATUS                                 │
│                  │ Pending: 1 | Fulfilled: 1 | Cancelled: 1              │
└──────────────────┴───────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Reports                           │
│ From [date]  To [date]            │
│ [Apply filters]                  │
├──────────────────────────────────┤
│ CLAIMS PER BATCH                  │
│ B-001 Biscuit Box: 5              │
│ B-002 Chocolate Box: 10           │
├──────────────────────────────────┤
│ POINTS ISSUED VS SPENT            │
│ Issued: 200                       │
│ Spent: 80 (gross debits)           │
├──────────────────────────────────┤
│ REDEMPTIONS BY STATUS             │
│ Pending 1                         │
│ Fulfilled 1                       │
│ Cancelled 1                       │
└──────────────────────────────────┘
```

**States:** data present; empty range; loading; invalid date range; query/server error. Illustrative figures are not actual business results. Do not add unsupported revenue or retailer metrics.

### 4.15 Reconciliation — `public/admin/reconciliation.php`

**Desktop**

```text
┌──────────────────┬───────────────────────────────────────────────────────┐
│ Admin sidebar    │ Reconciliation                                        │
│                  │ Checks ledger consistency; does not repair data.      │
│                  │ Scope [All v]   (Customer/Batch reference if needed)  │
│                  │ [Run reconciliation]                                  │
│                  │                                                       │
│                  │ PREVIOUS RUNS                                         │
│                  │ Date       Scope   Outcome          Findings   View   │
│                  │ 25 Nov     All     Discrepancies    2          View   │
│                  │ 24 Nov     All     Clean             0          View   │
│                  │                                                       │
│                  │ FINDINGS                                              │
│                  │ Check type / customer / ledger ref / expected / actual│
│                  │ Finding status / note                                │
└──────────────────┴───────────────────────────────────────────────────────┘
```

**Mobile**

```text
┌──────────────────────────────────┐
│ Reconciliation                    │
│ Checks only; never auto-repairs.  │
│ Scope [All v]                    │
│ [Run reconciliation]             │
├──────────────────────────────────┤
│ PREVIOUS RUNS                    │
│ 25 Nov | All | Discrepancies     │
│ 2 findings | [View]              │
│ 24 Nov | All | Clean             │
│ 0 findings | [View]              │
├──────────────────────────────────┤
│ FINDINGS                         │
│ Check type                       │
│ Expected / actual                │
│ Status / note                    │
└──────────────────────────────────┘
```

**Confirmation dialog**

```text
Run reconciliation now?
This compares ledger/business records and records findings.
It will not change points, rewards or code states.
[Run now] [Cancel]
```

**States:** no previous runs; confirmation; running; clean result; discrepancy result; failed result; findings present/empty; server error. Finding display is read-only; any correction uses the separately audited customer correction workflow.

## 5. Cross-page responsive and state notes

1. Every page inherits the relevant shared shell; the page wireframe focuses on its unique hierarchy and content.
2. Desktop tables become labelled cards or accessible horizontal-scroll regions on mobile. Do not remove critical columns such as status, date, amount or action.
3. Primary actions appear once in the most important place. Avoid repeating the same dominant action in every card and header.
4. Loading is temporary. A screen must not remain in a disabled state after a completed/failed request.
5. Empty states include helpful wording, but only show a create/claim action when the role and workflow permit it.
6. Errors must never reveal whether a code belongs to another person or expose secrets. The generic customer error states are specified on the claim and login pages.
7. All state-changing controls are POST forms with CSRF; all state-changing administrator actions require the reasons/confirmations defined in `WORKFLOWS.md`.
8. Dates follow the configured timezone (UTC by default). Point changes show explicit positive/negative signs and use the word `points`.

## 6. Wireframe completeness checklist

- [x] Landing page
- [x] Registration
- [x] Login
- [x] Customer dashboard
- [x] Claim product code
- [x] Points history
- [x] Rewards catalogue
- [x] Redemption confirmation
- [x] Customer redemptions
- [x] Customer profile
- [x] Administrator dashboard
- [x] Products list
- [x] Product create/edit
- [x] Batch list
- [x] Batch creation
- [x] Batch details
- [x] Batch generate/export action response
- [x] Customer list
- [x] Customer details
- [x] Rewards management
- [x] Redemption management
- [x] Fraud monitoring
- [x] Audit records
- [x] Reports
- [x] Reconciliation

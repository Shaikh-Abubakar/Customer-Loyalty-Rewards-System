Customer Loyalty Rewards System — Requirements Analysis
Steps 1–3: gap analysis, decisions and application requirements. Fixed stack: PHP 8+, MySQL 8 (InnoDB), PDO with prepared statements, server-rendered HTML5/CSS3, minimal vanilla JavaScript, PHP sessions. Prepared 23 November 2026.
Scope note
Your brief stops mid-sentence at “### 4. Factory”. I have treated it as the Factory Owner / Administration module, and I have also added a Fraud Monitoring module and non-functional requirements, because the brief asks for suspicious-activity monitoring and security elsewhere. If the original brief continued (database schema, architecture, code), paste the rest and I will carry on from this document.
How to read it: Section 1 records your three fixed design decisions. Section 2 covers Steps 1 and 2: the 26 gaps from your list (G1–G26) plus six more I found (G27–G32), each with two options, a recommendation and its impact. Section 3 is Step 3, the requirements by module. Section 4 lists assumptions and open questions for the factory owner.
1. Fixed design decisions
A. Purchase verification limitation
Documented limitation. A valid code proves that someone has access to the code. It does not prove that the person bought the product or holds it legitimately. The system rewards possession of a code, not a verified sale. State this in the README, in the terms and conditions, and in your viva.
Residual risks (not eliminated):
• Theft from warehouses, delivery vehicles or shops: the thief holds genuine, unclaimed codes.
• Boxes opened inside stores: the code is read or photographed, then the box is resealed or abandoned.
• Codes photographed and shared in chat groups or on social media: the first claimant wins.
• Resale of opened boxes: an opened box may be sold with an unclaimed code, or a code may be claimed and the product then resold.
• Insider exposure: printers, packers and staff handle codes before sale.
• Online guessing of codes: made impractical by code length and rate limiting (G6, G27).
Mitigations adopted (they reduce fraud and help detect it; they do not eliminate it):
1.  Activate batches only after dispatch. Codes taken before dispatch cannot be claimed, and attempts to claim them become a theft signal (G7, G18).
2.  Per-account and per-device claim limits (G19).
3.  Monitoring of suspicious claiming patterns with an admin review queue (G18).
4.  Batch revocation by the owner for stolen, recalled or compromised stock (G9).
One weakness I am challenging. Per-device limits are only as strong as device identification, and a plain web app cannot identify devices reliably: cookies can be cleared, a family may share one phone, and mobile networks share IP addresses. Device and IP data should therefore be treated as risk signals that flag or throttle, never as proof of identity or grounds for an automatic permanent ban. The per-account limit is the hard control.
B. Two separate security protections
Protection 1: single-use (enforced in the database)
• Prevents: the same code being credited twice; replay of a code after the legitimate claim; double credit caused by two simultaneous requests or a double-clicked button.
• Cannot prevent: someone who steals or photographs an unused code and claims it first. The real buyer then finds it already used.
• Mechanism: one atomic conditional update inside a transaction.
UPDATE codes
SET status = 'claimed', claimed_by = :customer_id, claimed_at = UTC_TIMESTAMP()
WHERE code_hash = :hash
  AND status = 'active'
  AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP());
PHP credits points only if rowCount() === 1. InnoDB takes a row lock on the UPDATE, so when two requests race, the second waits, re-evaluates status = 'active', finds it false and updates zero rows. The code update, the claim record and the ledger insert commit together or not at all.
• Second line of defence: a UNIQUE constraint on code_claims.code_id, and a UNIQUE (reference_type, reference_id) on the ledger, so even a PHP bug cannot create a second credit for the same code.
Protection 2: physical copy protection (in the packaging)
• What it is: the code is hidden under a tamper-evident scratch-off panel, or placed inside the lid or an inner flap, so it cannot be read without opening or damaging the pack.
• Prevents: casual reading or photographing of codes in shops or in transit, before purchase. Visible damage also lets a buyer reject a pack that was already scratched.
• Cannot prevent: reuse of a code once someone has it (scratch-and-reseal, theft of already opened boxes); exposure to insiders such as the printer before the panel is applied; a determined person defeating the panel.
• Practical note: ask the packaging vendor for tamper-evident scratch labels and restrict access to print files.
How they combine. They fail differently. Physical hiding lowers the chance that a code is seen before purchase. Single-use limits the damage after exposure to one successful claim. Neither stops theft of an intact box, which is why dispatch-time activation, revocation and monitoring are still needed.
C. Append-only points ledger
Principle. Points exist only as rows in points_ledger. A customer’s balance is SUM(points) over their rows. There is no editable balance column anywhere. If performance ever demands a cache, it must be a derived, rebuildable summary, never the source of truth.
Suggested shape (final schema comes in a later step): id, customer_id, entry_type (claim, redemption, reversal, adjustment, expiry reserved), points (signed, never zero), reference_type, reference_id, reverses_entry_id (nullable, UNIQUE), reason, created_by, created_at.
Rules
• Insert only. No updates, no deletes.
• A correction is a new row of opposite sign whose reverses_entry_id points at the entry it cancels. The UNIQUE constraint means an entry can be reversed only once.
• Every ledger write happens in the same InnoDB transaction as the business event it records (claim, redemption, cancellation).
• Manual adjustments need the owner role, a mandatory reason and an audit-log entry.
Enforcement (layered; each layer covers a different failure)
1.  Database privileges. The web application’s MySQL account gets only SELECT, INSERT on points_ledger. Schema changes use a separate migration account that the web app never uses. This defends against SQL injection and application bugs issuing UPDATE or DELETE.
2.  Triggers. BEFORE UPDATE and BEFORE DELETE triggers reject the change:
CREATE TRIGGER points_ledger_no_update BEFORE UPDATE ON points_ledger
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'points_ledger is append-only';
-- an identical BEFORE DELETE trigger: points_ledger_no_delete
3.  Constraints. CHECK (points <> 0), foreign keys, and the UNIQUE reversal link.
4.  Honest limit. A database administrator with sufficient privileges can still drop the triggers or edit data. The application account cannot run TRUNCATE (it needs DROP), but root can. So this is tamper-resistant against the application and attackers, not against a privileged DBA. Backups, the binary log and a periodic reconciliation report cover that gap.
Concurrency
• Lock-order rule. Every transaction that writes a customer’s ledger starts with SELECT id FROM customers WHERE id = :id FOR UPDATE as its first statement, then touches other rows in a fixed order (customer, then code or reward, then ledger). Retry once on a deadlock (SQLSTATE 40001).
• Claims. Protection 1 above, run after the customer lock so per-account limit checks are consistent.
• Redemptions.
    1.  BEGIN, then lock the customer row.
    2.  Read the balance: SELECT COALESCE(SUM(points), 0) FROM points_ledger WHERE customer_id = :id. Because the lock was the first statement, the snapshot is taken after the lock is granted and sees every earlier commit.
    3.  If balance < cost, roll back with “insufficient points”.
    4.  Reserve stock: UPDATE rewards SET stock = stock - 1 WHERE id = :r AND is_active = 1 AND stock >= 1. rowCount() must be 1.
    5.  Insert the redemption (status pending, address snapshot), then insert the ledger row (negative points, referencing the redemption).
    6.  COMMIT.
  Two simultaneous redemptions by one customer serialise at step 1; the second sees the reduced balance and fails cleanly.
• Cancellation. UPDATE redemptions SET status = 'cancelled' WHERE id = :id AND status = 'pending' (must affect one row), then insert the reversing ledger row and restore stock, all in one transaction. The UNIQUE reverses_entry_id blocks a second refund.
• Isolation level. Keep InnoDB’s default REPEATABLE READ. Correctness comes from explicit locks and conditional updates, not from raising the isolation level.
2. Steps 1 and 2: gaps, options and decisions
Each gap uses the same format. Recommendations are chosen for a single developer who must build, demonstrate and explain the system. G1–G26 match the numbered list in your brief; G27–G32 are additional gaps.
G1. User roles and permissions
• Missing decision: who can do what, and whether the owner and day-to-day staff share one level of access.
• Option A: one admin role with full access, plus customer.
• Option B: owner (batches, revocation, ledger adjustments, staff accounts), staff (fulfil redemptions, view customers and reports) and customer.
• Recommendation: B, because one role column and one require_role() helper give you least-privilege access very cheaply.
• Impact: admin accounts live in a separate table, are never created through public registration, and use a separate session namespace. Every admin page calls the same permission check, which limits the damage from a stolen staff login.
G2. Customer registration and authentication
• Missing decision: which identifier customers use, whether it is verified, and how passwords are protected.
• Option A: email plus password, with an emailed verification link.
• Option B: mobile number plus SMS one-time password.
• Recommendation: A, because it costs nothing, needs no SMS gateway and is easy to demonstrate.
• Impact: use password_hash() and password_verify(), call session_regenerate_id(true) on login, and throttle login attempts. Allow claiming only after email verification so mass account creation costs a fraudster a little more effort. Collect the minimum at registration: name, email, password; phone and address are asked only when needed for redemption.
G3. One unique code per box versus shared codes per product type
• Missing decision: whether a code identifies one physical box or a whole product type.
• Option A: one shared code per product type, printed on every box of that product.
• Option B: one unique random code per physical box.
• Recommendation: B, because a shared code can be claimed unlimited times, which makes single-use protection impossible.
• Impact: the codes table grows with production volume (one row per box; hundreds of thousands of indexed rows is comfortable for MySQL), and you need a batch generation, printing and dispatch workflow.
G4. Product registration and management
• Missing decision: where product identity lives and how it can change.
• Option A: a free-text product name typed into each batch.
• Option B: a products table (SKU, name, description, default points, active flag) referenced by batches.
• Recommendation: B, because a proper table keeps reports consistent and stops typos splitting your data.
• Impact: products are deactivated, never deleted, because historic batches and codes reference them.
G5. QR code and human-readable code formats
• Missing decision: what is printed on the box and what the QR code contains.
• Option A: QR code only.
• Option B: QR code plus a printed human-readable code in groups of four (for example K7QM-4TXD-9HPA) as a typing fallback.
• Recommendation: B, because scuffed or unscannable QR codes will happen and a typed code avoids support calls.
• Impact: the QR encodes a claim URL such as https://yourdomain/claim?c=CODE that only pre-fills the form. The claim itself is a logged-in POST with a CSRF token. Never claim on GET, because link previews and scanners fire GET requests. The phone’s own camera can scan it, so no JavaScript scanner is needed. Normalise typed input (uppercase, strip spaces and hyphens).
G6. Code generation and batch management
• Missing decision: how codes are produced, how they are stored, and how a batch is managed.
• Option A: random codes stored in plaintext (simple, searchable).
• Option B: random codes from random_int() (a cryptographically secure generator) over an alphabet without look-alike characters, storing only a keyed hash (HMAC-SHA-256 with a server-side secret).
• Recommendation: B, because a database leak or read-only SQL injection would otherwise expose every unclaimed code, and unclaimed codes are effectively cash.
• Impact: use 12 characters from a 31-character alphabet (letters and digits without 0, O, 1, I, L), about 7.9 × 10^17 combinations, so guessing is impractical. Generate in chunks inside transactions with a UNIQUE index on the hash. Export the plaintext codes once as a CSV for the printer (audited), because they cannot be read back later. A batch records product, quantity, points per code, status, creator and dates; each code also has a non-secret serial number for support references.
G7. Code activation and dispatch
• Missing decision: when a printed code becomes claimable.
• Option A: claimable as soon as it is generated.
• Option B: a lifecycle of generated → active (on dispatch) → claimed / expired / revoked, with the batch activated only when the cartons leave the factory.
• Recommendation: B, because codes stolen before dispatch cannot be claimed, and attempts to claim them become a theft signal.
• Impact: you need an audited “activate batch” action and an operational habit: keep batches small, roughly one per dispatch consignment, so one theft or recall affects limited stock.
G8. Code expiry
• Missing decision: whether codes can expire.
• Option A: codes never expire.
• Option B: an optional expires_at per batch, set at activation.
• Recommendation: B, because expiry narrows the window in which an old leaked code is useful and supports time-limited promotions.
• Impact: expiry is checked inside the same atomic claim UPDATE, so no scheduled job is needed. The customer sees the generic failure message described in G27.
G9. Stolen, recalled or compromised batches
• Missing decision: what revocation affects, and what happens to points already issued.
• Option A: revocation blocks only unclaimed codes.
• Option B: revocation blocks unclaimed codes and lists already claimed codes from the batch for admin review, where the owner may reverse points customer by customer.
• Recommendation: B, because automatic clawback would punish innocent buyers, so a human decides case by case.
• Impact: revocation is owner-only, needs a reason and is audited. Reversals are ordinary ledger reversals. A customer who already redeemed may end up with a negative balance, which blocks redemptions until points are earned back; the terms must say so (G31).
G10. Customer points allocation
• Missing decision: where the number of points per code comes from.
• Option A: fixed points per product.
• Option B: points set per batch, defaulting from the product.
• Recommendation: B, because it supports promotions (for example a double-points batch) and leaves old codes unaffected when a product’s default changes.
• Impact: points_per_code is locked once the batch is activated; the claim reads it through the code’s batch. Points are whole numbers.
G11. Points expiry
• Missing decision: whether points lapse.
• Option A: points never expire.
• Option B: points expire after N months, oldest first.
• Recommendation: A, because expiry needs lot tracking for each earning entry, which complicates the ledger for little demonstration value.
• Impact: the factory carries an open liability, which the dashboard reports (G20). Reserve an expiry entry type so a later job could add expiry as new negative rows without redesign.
G12. Points transferability
• Missing decision: whether customers can give points to each other.
• Option A: points are non-transferable.
• Option B: customers may transfer points.
• Recommendation: A, because transfers create a resale and laundering channel that undermines fraud monitoring.
• Impact: no extra tables and a simpler fraud model: points belong to the account that claimed the code. Households can share one account or gift the physical reward.
G13. Reward catalogue and reward types
• Missing decision: what customers can redeem.
• Option A: physical items only (factory products, merchandise).
• Option B: physical items plus digital vouchers or coupon codes.
• Recommendation: A, because digital vouchers need a second inventory of codes and a delivery mechanism, which is another thing to protect.
• Impact: the rewards table holds name, description, points cost, stock, status and image. Include a reward_type column so vouchers could be added later without schema change. Image upload is admin-only, with type and size validation and server-generated filenames.
G14. Reward inventory and availability
• Missing decision: whether rewards are limited and how availability is controlled.
• Option A: untracked, effectively unlimited availability.
• Option B: a stock count per reward, decremented atomically, with an is_active flag and optional availability dates.
• Recommendation: B, because physical goods are finite and overselling creates support problems.
• Impact: stock is reserved at redemption request with the conditional UPDATE (stock >= 1). Admin stock edits are written to the audit log. At zero stock the reward shows as out of stock.
G15. Reward redemption and fulfilment
• Missing decision: what happens between a customer requesting a reward and receiving it.
• Option A: instant redemption: points are deducted and the redemption is immediately marked done.
• Option B: a workflow pending → approved → fulfilled (or cancelled), with points deducted at the time of request.
• Recommendation: B, because physical items need a manual fulfilment step and deducting up front prevents the same points being spent twice.
• Impact: a redemptions table holds status, a delivery address snapshot (copied at request time, not linked to the live profile), a tracking note and timestamps. Every status change uses a conditional UPDATE (WHERE status = 'pending'), so two admins cannot process the same request. Decide early whether rewards are shipped or collected at the factory counter, because it changes what data you collect.
G16. Redemption cancellation and point reversal
• Missing decision: who may cancel a redemption and until when.
• Option A: only admins can cancel.
• Option B: customers can cancel while pending; admins can cancel before fulfilled; after fulfilment only an owner adjustment can correct it.
• Recommendation: B, because self-service cancellation is a small amount of code and reduces admin workload.
• Impact: cancellation is one transaction: conditional status update, then a reversing ledger entry (positive, referencing the original debit), then stock plus one. The UNIQUE reverses_entry_id prevents a double refund.
G17. Customer transaction history
• Missing decision: how much of the ledger customers see.
• Option A: current balance and a redemption list only.
• Option B: the full ledger with running balance, readable descriptions and date filters.
• Recommendation: B, because the ledger already holds the data, and visible history builds trust and cuts “where are my points” queries.
• Impact: a paginated query joining the reference tables; the running balance uses the MySQL 8 window function SUM(points) OVER (ORDER BY id). Reversals are shown as “Reversal of …” and never hidden.
G18. Fraud detection and suspicious-activity monitoring
• Missing decision: what counts as suspicious and what the system does about it.
• Option A: automatic hard blocks only.
• Option B: rule-based flags stored in a suspicious_events table with an admin review queue, plus automatic throttling only for repeated failed attempts.
• Recommendation: B, because simple threshold rules are easy to explain and flagging rather than banning protects honest customers from false positives.
• Impact: starter rules: a claim attempt on a not-yet-active or revoked code (possible theft, the highest-value signal); many failed attempts; more than N claims per account per day; several accounts sharing one device token or IP in a time window; a burst of claims on sequential serials; a new account that claims and redeems quickly; many accounts redeeming to the same address. Each flag has a status (open, dismissed, confirmed) and a reviewer note.
G19. Device and account restrictions
• Missing decision: what is limited per account, per device and per IP.
• Option A: per-account limits only.
• Option B: per-account limits plus soft device and IP signals.
• Recommendation: B, but device and IP only flag or throttle, because cookies clear, phones are shared and mobile IPs are shared.
• Impact: set a random first-party device token (stored only as a hash) and log it with every claim attempt. Thresholds (for example 10 claims per account per day) live in a settings table so you can tune them. The per-account limit stays the hard control.
G20. Admin dashboard and reporting
• Missing decision: which numbers the owner sees and in what form.
• Option A: basic counters only.
• Option B: counters plus filterable lists, CSV export and simple server-rendered bars.
• Recommendation: B without chart libraries, because CSS bars and tables satisfy a demonstration and avoid JavaScript dependencies.
• Impact: reports cover codes by status per batch, claim rate, points issued, redeemed and outstanding (the programme liability), pending redemptions, stock levels, top claimers and open fraud flags. Add indexes for these queries. CSV exports contain personal data, so they are audited.
G21. Customer notifications
• Missing decision: how and when customers are told about events.
• Option A: in-app messages only.
• Option B: in-app messages plus transactional email (verification, password reset, redemption status changes).
• Recommendation: B limited to transactional email, because verification and password reset need email anyway.
• Impact: write emails to an email_outbox table in the same transaction, and send them through SMTP afterwards (PHPMailer is a library, not a framework, if you want one; a local mail catcher works for the demo). A failed send never rolls back a claim or redemption. No marketing email without separate consent.
G22. Customer account deactivation or deletion
• Missing decision: what happens to a customer’s data and points when they leave.
• Option A: hard-delete the customer row on request.
• Option B: deactivate the account and anonymise personal data, keeping the customer ID and ledger rows.
• Recommendation: B, because an append-only ledger cannot lose rows and anonymisation still removes the personal data.
• Impact: replace name, email, phone and addresses with placeholders. Remaining points are forfeited after a warning (or redeemed first). Closed accounts cannot log in. Admins can also deactivate an account for fraud, with a reason. Address snapshots on old redemptions are anonymised after the retention period (G29).
G23. Password recovery
• Missing decision: how customers regain access.
• Option A: an emailed single-use reset link with an expiring token.
• Option B: security questions.
• Recommendation: A, because security questions are guessable and often public.
• Impact: generate the token with random_bytes(), store only its hash and expiry (for example 30 minutes), and invalidate it after use. Show the same message whether or not the email exists. End other sessions after a reset. The owner resets staff passwords by issuing a one-time link; no password is ever emailed or displayed.
G24. Security, privacy and audit logging
• Missing decision: what is recorded, where, and which baseline controls are mandatory.
• Option A: rely on web-server and PHP error logs.
• Option B: a dedicated audit_log table, append-only and protected like the ledger, plus error logging outside the webroot.
• Recommendation: B, because admins need an in-app trail of who did what, and the ledger-style protection is already designed.
• Impact: log admin logins and failed logins, batch create, activate, revoke and export, reward and stock edits, redemption status changes, adjustments and role changes. Baseline controls: HTTPS; PDO prepared statements with emulation disabled; output escaping with htmlspecialchars; CSRF tokens on every POST; session cookie flags HttpOnly, Secure, SameSite=Lax; session_regenerate_id; least-privilege database accounts; secrets in a config file outside the webroot; display_errors off in production; security headers including a Content Security Policy. Privacy: collect the minimum, record consent at registration, publish a short privacy notice.
G25. Application architecture and PHP framework considerations
• Missing decision: how the code is organised, and whether a framework is used.
• Option A: plain modular PHP: a front controller, a small router, service classes per module, a PDO wrapper and templates.
• Option B: a micro or full framework (for example Slim or Laravel).
• Recommendation: A, because it is your fixed constraint and you must be able to explain every line in the viva.
• Impact: a suggested structure follows. Business rules (claim, redeem, reverse) live in service classes, never in page files, so the transactional logic exists in exactly one place.
/public         index.php (front controller), assets/
/src
  Core/         Router, Db (PDO), Session, Csrf, View, Validator
  Auth/  Customers/  Catalog/  Codes/  Points/  Rewards/  Admin/  Fraud/
/templates      views (escaped output only)
/config         config.php (outside the webroot in production)
/database       schema.sql, triggers.sql, seed.sql
Framework as an alternative (for discussion only; the stack stays plain PHP). A framework would supply routing, CSRF protection, validation, migrations and authentication scaffolding. The cost is “magic” you would have to explain, plus a learning curve. It is a reasonable choice for a real production system, but not for this brief. Composer is optional and only useful for PSR-4 autoloading.
G26. Hosting, deployment and backups
• Missing decision: where the system runs and how data is protected.
• Option A: a local environment only (XAMPP, or Docker Compose with PHP-Apache and MySQL 8) for demonstration.
• Option B: public deployment on a shared host or small VPS with HTTPS.
• Recommendation: A for the submission, written so that B works, because the project is assessed on demonstration while a deployment checklist shows production thinking.
• Impact: document the requirements: PHP 8+, MySQL 8, pdo_mysql, HTTPS, and permission to create triggers and separate database users. Some shared hosts restrict this, and trigger creation can need extra privileges when binary logging is on, so verify early. Backups: a daily mysqldump --single-transaction kept off the server, with a tested restore. The ledger and audit tables matter most. Include a reconciliation query in the demo.
G27. Claim error messages and code-guessing protection
• Missing decision: how much a failed claim reveals, and how guessing is limited.
• Option A: specific messages (revoked, expired, already used, not active).
• Option B: one generic message for every failure, except “you already claimed this code”, with the true reason logged privately.
• Recommendation: B, because specific messages help guessers and thieves learn which codes exist and what state they are in.
• Impact: every attempt is logged in claim_attempts (customer, device hash, IP, outcome, time). Five failed attempts in 15 minutes trigger a short cool-down per account and per IP. The failure page offers a “contact support” link so honest customers with a misprinted code are not stranded.
G28. Support, disputes and manual corrections
• Missing decision: how the owner fixes a genuine problem such as a misprinted code or a missed credit.
• Option A: the owner edits the database directly.
• Option B: an owner-only adjustment tool that writes a ledger entry with a mandatory reason, plus an audit record.
• Recommendation: B, because direct database edits would break the append-only design.
• Impact: covers goodwill bonuses, missed credits and unclaimable codes. Keep a support note per customer and a configurable maximum per adjustment. If a misprinted code has to be consumed manually, the same transactional rules apply.
G29. Data retention and archiving
• Missing decision: how long data is kept.
• Option A: keep everything indefinitely.
• Option B: keep the ledger, redemptions and audit log for the period the owner’s accountant requires, and purge or anonymise operational logs (attempts, expired sessions, reset tokens) after roughly 90 days.
• Recommendation: B, because it limits personal data held and keeps tables small.
• Impact: a manual admin script is enough for the demo. Keep the periods configurable and never purge the ledger.
G30. Time handling
• Missing decision: which clock decides expiry and rate limits.
• Option A: PHP server time.
• Option B: UTC from the database server for every security-relevant comparison.
• Recommendation: B, because expiry and rate-limit checks then match the stored data even if the PHP and database clocks or zones differ.
• Impact: store UTC in all created_at and expires_at columns, convert to the factory’s local zone only for display, and set the session time zone explicitly when connecting with PDO.
G31. Terms and conditions and programme rules
• Missing decision: the written basis for fraud actions.
• Option A: informal rules on a web page.
• Option B: versioned terms accepted at registration.
• Recommendation: B, because revocation, reversals and bans need a documented basis, and it costs one page plus terms_version and terms_accepted_at columns.
• Impact: the terms should cover the code-only proof limitation, one account per person, the right to revoke codes and reverse points obtained fraudulently, negative balances, no cash value, the points expiry policy and data use. I am not a lawyer; have the factory owner, ideally with someone legally qualified, review the wording.
G32. Testing and demonstration data
• Missing decision: how you will prove the protections work.
• Option A: manual click-through testing only.
• Option B: a seed script (products, batches, codes, customers) plus small demonstrations: two parallel claims on one code, two parallel redemptions on one balance, and an attempted UPDATE or DELETE on the ledger.
• Recommendation: B, because these three protections are the heart of the project and a live demonstration is more convincing than a diagram.
• Impact: add seed.sql and a CLI or curl-based script with documented expected output for the viva. PHPUnit is optional.
Summary of recommendations
• G1 B: owner and staff roles
• G2 A: email and password, verified
• G3 B: unique code per box
• G4 B: products table
• G5 B: QR plus human-readable code
• G6 B: random codes, keyed hash stored
• G7 B: activate after dispatch
• G8 B: batch-level expiry
• G9 B: revoke, then review claimed codes
• G10 B: points set per batch
• G11 A: no points expiry in version 1
• G12 A: non-transferable points
• G13 A: physical rewards only
• G14 B: tracked stock
• G15 B: pending, approved, fulfilled workflow
• G16 B: customer may cancel while pending
• G17 B: full ledger history
• G18 B: rule-based flags with review queue
• G19 B: account limits hard, device and IP soft
• G20 B: counters, lists, CSV, CSS bars
• G21 B: transactional email via outbox
• G22 B: deactivate and anonymise
• G23 A: emailed reset link
• G24 B: audit log table
• G25 A: plain modular PHP
• G26 A: local demo, deployable
• G27 B: generic failure messages
• G28 B: owner adjustment tool
• G29 B: retention policy
• G30 B: UTC from the database
• G31 B: versioned terms
• G32 B: seed data and live demonstrations
3. Step 3: Application requirements
Priorities: [M] must have (core to the brief), [S] should have, [C] could have. IDs allow traceability from requirement to design to test. Gap references (G#) point to the decision that justifies each requirement.
3.1 Customer module (CUS)
• CUS-01 [M] Register with name, email and password, accept the current terms (version recorded), and verify the email address through a link before claiming any code (G2, G31).
• CUS-02 [M] Log in and out using PHP sessions: session ID regenerated on login, idle timeout, throttled login attempts.
• CUS-03 [M] Reset a forgotten password through an expiring, single-use emailed link (G23).
• CUS-04 [M] View and edit profile (name, phone, delivery addresses) and change password (current password required). Changing the email address requires re-verification.
• CUS-05 [M] Claim a code by typing it or by arriving from the QR link, which only pre-fills the form. The claim is a CSRF-protected POST; the confirmation shows points earned and the new balance (G5).
• CUS-06 [M] Failed claims show one generic message and are rate limited per account and IP (G27).
• CUS-07 [M] See the current points balance, always computed from the ledger.
• CUS-08 [M] See a paginated transaction history with running balance, including reversals (G17).
• CUS-09 [M] Browse active rewards with points cost and stock status, with an indication of what the customer can afford.
• CUS-10 [M] Redeem a reward, supplying delivery or pickup details. Points are deducted immediately, and a double-click or refresh cannot create a second redemption (a one-time form token plus the transactional checks).
• CUS-11 [M] List own redemptions with status, and cancel one while it is pending (G16).
• CUS-12 [S] Receive transactional emails for verification, password reset and redemption status changes (G21).
• CUS-13 [S] Request account closure, with a warning about forfeited points (G22).
• CUS-14 [M] Pages are responsive and usable on a phone, because claims will mostly happen there.
3.2 Product and QR code module (PRD)
• PRD-01 [M] Owner and staff create, edit and deactivate products (unique SKU, name, description, default points). Products are never deleted (G4).
• PRD-02 [M] The owner creates a batch for a product with quantity, points per code, optional expiry date and a note. Codes are generated with a cryptographically secure generator and only keyed hashes are stored (G6, G8, G10).
• PRD-03 [M] One-time export of the batch’s printable codes (human-readable code and QR payload URL), audited. After the export, plaintext codes cannot be retrieved.
• PRD-04 [S] QR images, or QR payload URLs for the printer’s software, are produced at export time (G5).
• PRD-05 [M] Batch lifecycle generated → active → expired / revoked is enforced in code, with every transition audited with actor, time and reason. revoked is terminal (G7).
• PRD-06 [M] Activating a batch records a dispatch reference and date. Quantity and points per code become immutable at activation.
• PRD-07 [M] Claim verification normalises the input, computes the keyed hash, performs the atomic conditional update (status active, not expired) and writes the ledger credit in the same transaction (section 1B).
• PRD-08 [M] Revoking a batch immediately blocks its unclaimed codes and lists its already claimed codes for owner review (G9).
• PRD-09 [S] Support lookup: an admin enters a code and sees status, batch, claimant and claim time, using the same hash lookup.
• PRD-10 [M] Every claim attempt, successful or not, is recorded (customer, device hash, IP, outcome, time).
• PRD-11 [M] Each code has a non-secret serial number, and each claim links code, customer, time and device hash.
3.3 Rewards module (RWD)
• RWD-01 [M] An insert-only points_ledger holds signed point entries with a type, a reference and an optional reversal link. A balance is the sum of a customer’s entries, and nothing stores a balance directly (section 1C).
• RWD-02 [M] Database-level enforcement: the application account has SELECT and INSERT only on the ledger, BEFORE UPDATE and BEFORE DELETE triggers reject changes, CHECK (points <> 0), and the reversal link is UNIQUE.
• RWD-03 [M] Each claim credit, redemption debit and reversal is written in the same transaction as the event it records.
• RWD-04 [M] Owner and staff create, edit and deactivate rewards (name, description, image, points cost, stock, availability window).
• RWD-05 [M] Stock is decremented atomically at request and restored on cancellation. Manual stock edits are audited (G14).
• RWD-06 [M] Redemption locks the customer row, checks the balance, reserves stock, and inserts the redemption and the debit in one transaction (section 1C).
• RWD-07 [M] Redemption statuses pending → approved → fulfilled or cancelled, each change a conditional update recording actor and timestamp, with a delivery address snapshot (G15).
• RWD-08 [M] Cancellation inserts a reversing ledger entry, and an entry can be reversed only once (G16).
• RWD-09 [S] Owner adjustments (positive or negative) require a reason and create an audit entry (G28).
• RWD-10 [M] Points are non-transferable, have no cash value and do not expire in version 1; the expiry entry type is reserved (G11, G12).
• RWD-11 [S] A reconciliation report checks that ledger sums per customer are consistent, every reversal references a valid entry, and every claim or redemption entry has its business record.
3.4 Factory owner and administration module (ADM)
• ADM-01 [M] A separate admin login with its own table and session namespace. Roles are owner and staff, and permissions are enforced on the server for every request, not merely by hiding menu items (G1).
• ADM-02 [M] A dashboard showing codes by status, claims today and this week, points issued, redeemed and outstanding, pending redemptions, low-stock rewards and open fraud flags (G20).
• ADM-03 [M] Customer management: search and list customers, view profile, balance, ledger, claims and redemptions, and deactivate or reactivate an account with a reason.
• ADM-04 [M] Batch management screens: create, activate and list batches with status counts. Revocation is owner-only.
• ADM-05 [M] A redemption queue filterable by status, with approve, mark fulfilled and cancel actions, each with a note.
• ADM-06 [M] Product and reward management screens (PRD-01, RWD-04).
• ADM-07 [S] Reports with date filters and audited CSV export: claims by day, product and batch; points issued versus redeemed; outstanding points; stock; fulfilment times.
• ADM-08 [M] A read-only, filterable audit log viewer (G24).
• ADM-09 [S] A settings page for thresholds (claims per day, failed attempts, cool-down, session timeouts). Every change is audited.
• ADM-10 [M] Staff account management, owner only: create, disable, and issue a one-time password reset link.
3.5 Fraud monitoring module (FRD)
• FRD-01 [M] Record every claim attempt, as in PRD-10.
• FRD-02 [M] Evaluate the rules in G18: cheap checks at claim time, heavier aggregate checks through an on-demand admin scan or a scheduled script.
• FRD-03 [M] Store suspicious events with severity, rule, subject (customer, device or batch), status and reviewer notes.
• FRD-04 [M] An admin review queue with actions: dismiss, confirm, deactivate customer, revoke batch, reverse points.
• FRD-05 [S] The per-account daily claim limit is a hard block; device and IP thresholds only flag or throttle (G19).
• FRD-06 [M] A claim attempt on a not-yet-active, revoked or expired code is logged as a high-severity event, because it may indicate theft or a leaked batch.
• FRD-07 [S] A batch-level claim-velocity view to spot batches being claimed abnormally fast.
3.6 Cross-cutting and non-functional requirements (NFR)
• NFR-01 [M] Security baseline: the controls listed in G24, applied to every page.
• NFR-02 [M] Privacy: minimal data collection, consent and terms acceptance, anonymisation on closure, retention periods (G22, G29).
• NFR-03 [M] Data integrity: InnoDB, foreign keys, utf8mb4, strict SQL mode, unique and check constraints, and every multi-table write in a transaction.
• NFR-04 [S] Performance: indexes on the code hash, on ledger customer_id, and on batch and status; claim and redemption should respond in well under a second at demonstration scale.
• NFR-05 [M] Maintainability: the modular structure in G25, business rules in service classes, configuration separate from code, and comments that help you explain each module.
• NFR-06 [S] Backup and recovery: the practices in G26.
• NFR-07 [M] Usability and JavaScript: mobile-first, accessible forms, clear messages. Vanilla JavaScript only for small helpers (disable a submit button after click, confirm dialogs, copy a value), and every function must still work with JavaScript off.
• NFR-08 [M] Documentation: a README stating the purchase-verification limitation, setup steps, a short threat model, the two protections explained separately, and a demo script.
• NFR-09 [S] Testing: seed data and the live demonstrations in G32.
4. Assumptions and open questions
Working assumptions
• One factory, one points programme, one time zone, and email as the customer identifier.
• Rewards are physical; volumes are modest (up to roughly a million codes).
• No integration with point-of-sale, ERP or shipping systems; dispatch is recorded manually when the owner activates a batch.
• The owner accepts that purchase cannot be verified and that fraud is reduced and detected, not eliminated.
Questions for the factory owner (the answers could change the design)
1.  How many points should a typical box earn, and what is the cost of the cheapest reward?
2.  Who prints the codes (in-house or a vendor), and can the vendor apply scratch-off panels or print inside lids?
3.  Are rewards shipped or collected at the factory?
4.  Is the owner willing to reverse points and allow negative balances after a recalled or stolen batch?
5.  How long must financial records be kept?
Suggested next steps: confirm or change the recommendations above, then move to the database schema (tables, constraints, triggers and state machines), then the page and route map, then the implementation of the claim and redemption services first, since they carry the core security logic.
# AI_LOG.md — working log

The assignment requires 5–10 key AI prompts/interactions and at least two cases where the AI was wrong, how the mistake was noticed and how it was corrected. This file records the Stage 4 work evidenced in this project only. Earlier prompts must be added from the actual conversation history; do not invent quotations or mistakes to fill the assignment requirement.

## Recorded interaction

| # | Stage | User direction | AI work / decision | Verification or correction |
|---|---|---|---|---|
| 1 | Stage 4 — project foundation | Implement configuration, PDO, sessions, shared helpers, schema, entry point and CSS without implementing later business workflows. | Inspected the Stage 2/3 documents; implemented the foundation and the 17-table schema without changing the approved table design. | PHP syntax and foundation checks are recorded in `tests/foundation_checks.md`. Live PDO connectivity depends on the local XAMPP/MariaDB installation. |

## Cross-document issue detected

`PROJECT_STRUCTURE.md` says the database directory/schema was planned for Stage 3, while the approved Stage 3 execution brief prohibited creating or modifying SQL. Stage 3 therefore delivered documentation only. The schema was deferred to the first later implementation stage (Stage 4) without changing any approved table, field, relationship or rule. This is recorded as a stage-timing conflict, not presented as a client decision.

## Completion needed for final assignment submission

Add the actual Stage 1–3 prompt interactions and any genuine AI mistakes/corrections from the conversation history. Keep dates and quotations accurate. This starter log is not yet a claim that the assignment's 5–10 interaction requirement is complete.


## Verified implementation correction

| # | Error found | How it was noticed | Correction |
|---|---|---|---|
| 1 | The first generated autoloader had an incorrectly escaped PHP namespace-prefix string and did not parse. | `php -l bootstrap/autoload.php` reported a parse error before any app testing. | Corrected the namespace-prefix literal and reran PHP lint across every PHP file; all files then parsed successfully. |

The cross-document schema timing mismatch above is a second documented issue detected during project inspection. It is a source-document conflict rather than a fabricated claim that a runtime defect occurred.

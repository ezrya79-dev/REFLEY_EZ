---
name: email-sequence-nurturing
description: Add an automated, editable email-sequence system to a Laravel app — a CRUD template library (bilingual subject/preheader/body, send-day offsets), a scheduled sender command, per-recipient opt-in, signed no-auth unsubscribe links, manual send and self-test, and a send journal. Use this whenever the user wants drip campaigns, patient/client follow-up emails, onboarding sequences, lifecycle emails, or any "send email J+N after event X" behavior.
---

# Email sequences (nurturing)

The calendar and the copy belong to the **users**, not the code: templates and send-days live in a database-backed library edited in the UI. Code only owns the engine.

## Data model

- `mail_templates`: chapter/category, audience filter, day offset (J+1, J+3…), bilingual subject/preheader/body, active flag. Seed the defaults with an **idempotent seeder** (safe to rerun on deploy) so fresh installs start populated and edits are never overwritten.
- `sends` journal: template, recipient, sent_at, status — the dedupe key that guarantees once-per-recipient-per-template.
- Recipient opt-in flag (default per compliance context; explicit opt-in for anything marketing-ish).

## Engine

1. A scheduled artisan command (e.g. `nurturing:send`, daily): select recipients whose anchor event (enrollment, appointment…) is exactly N days old for each active template, joined against the journal to exclude already-sent, filtered by opt-in + audience.
2. **Unsubscribe**: a signed URL (`URL::signedRoute`) requiring no authentication — one click, flips opt-in, confirms neutrally. Include it in every sequence email.
3. **Manual controls** on the recipient's record: send a step now; send a test **to yourself** with a `[TEST]` subject prefix that is *never journaled* (a test must not block the real send later).
4. Render through the app's branding (sender identity from settings), queue the sends, and record failures in the journal rather than throwing away the batch.

## Verification checklist

- [ ] Re-running the send command never double-sends (journal dedupe)
- [ ] Unsubscribe works logged-out and survives template edits
- [ ] `[TEST]` sends reach the operator and leave no journal trace
- [ ] Day-offset edits in the UI change scheduling with no deploy

## Reference implementation

OrthoZ: `app/Models/MailTemplate.php`, `app/Services/NurturingService.php`, `SendNurturingEmailsCommand`, library UI at `/nurturing/bibliotheque`.

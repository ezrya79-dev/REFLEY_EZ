---
name: audit-gdpr-toolkit
description: Add an audit log and GDPR data-subject toolkit to a Laravel app — an AuditLogger service tracing who did what on what, an admin audit viewer, scheduled retention purges, per-person JSON export, and idempotent anonymization that survives external re-syncs. Use this whenever the user mentions audit trail, traceability, GDPR/RGPD/CNIL compliance, data retention, "right to be forgotten", data export rights, or handles personal/medical data in an app.
---

# Audit log & GDPR toolkit

Compliance is cheap when built in and brutal to retrofit. This module gives the app the four primitives regulators actually ask about: traceability, retention, access/portability, erasure.

## Audit log

One tiny service, called everywhere it matters:

```php
class AuditLogger
{
    public function log(string $action, ?Model $subject = null, array $context = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,                      // 'patients.viewed', 'settings.updated'…
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'context' => $context ?: null,            // metadata ONLY
            'ip' => request()?->ip(),
            'created_at' => now(),                    // real clock, even under a frozen test clock
        ]);
    }
}
```

Rules that make it defensible: `context` **never** contains secrets or sensitive payloads (references, counters, field labels only); record-*consultations* of sensitive records are logged, not just writes; timestamps use the real clock. Admin viewer behind an `audit.view` permission, filterable by action/user/period.

## Retention purges

One artisan command per data family (`audit:prune`, `movements:prune`, …), each reading its retention (days) from settings, scheduled daily. Deleting on schedule is a GDPR requirement, not housekeeping — surface the retentions in an admin "GDPR" settings panel.

## Data-subject rights

- **Export** (`records.export` permission): a JSON document of everything the app holds about the person, generated server-side, and the export itself audited.
- **Anonymization** (`records.anonymize`): overwrite identifying fields in place, keep business history (movements, stats). Make it **idempotent** and — when the record originates from an external sync — keep the external reference so the sync layer can *exclude* the person from re-hydration forever. Anonymize-then-resync-restores is the classic failure; test exactly that.

## Verification checklist

- [ ] Sensitive-record views appear in the audit log with real timestamps
- [ ] Purge commands scheduled and driven by settings-configurable retentions
- [ ] Anonymization is idempotent and survives a full external re-sync
- [ ] No secret/medical payload ever stored in audit context

## Reference implementation

OrthoZ: `app/Services/AuditLogger.php`, `app/Console/Commands/*PruneCommand.php`, GDPR actions in the Patients module, `docs/AUDIT.md`.

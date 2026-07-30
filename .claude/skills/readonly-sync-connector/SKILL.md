---
name: readonly-sync-connector
description: Connect a Laravel app to an external system of record in strict read-only mode — encrypted write-only credentials configured in-app, a test-connection probe, manual and scheduled synchronization into local tables, a sync-run journal, overridable source queries, and a degraded mode when the driver/connection is absent. Use this whenever the app must mirror data from an existing business system (ERP, practice management, CRM, legacy database) without ever writing back — phrases like "sync from", "import from our existing software", "connect to the legacy DB".
---

# Read-only sync connector

The external system stays the **system of record**; the app keeps a local, queryable mirror. The invariant that buys you trust: **never a write back** — enforce it structurally (a connection user with read-only grants, and no write method on the client contract), not by discipline.

## Architecture

1. **Contract + DTOs** — an `ExternalClient` interface (`testConnection(): ConnectionStatus`, `fetchX(): iterable<XRecord>`); immutable DTOs per record type. The app depends on the contract; a fake client drives tests and local dev without the driver.
2. **Credentials** — host/port/db/user/password entered in an admin panel, stored encrypted write-only (`app-settings-branding` machinery). Server-side only; the browser never talks to the external system.
3. **Source queries as config** — default extraction queries live in config and are **overridable per installation** (each customer's system exposes different views). Document each query's expected columns next to the override point.
4. **Sync engine** — upsert by external reference into local tables; mark-missing rather than delete (source rows vanish for operational reasons); **skip anonymized records permanently** (GDPR erasure must survive every future sync — coordinate with `audit-gdpr-toolkit`). Wrap ID mapping so renumbering upstream doesn't duplicate downstream.
5. **Runs journal** — every run (manual button or schedule): started/finished, counts (created/updated/skipped), outcome, truncated error message **with secrets scrubbed**. Surface the last run's status on the connector panel.
6. **Degraded mode** — missing driver or unreachable host must yield a *journaled, secret-free failure* and an app that keeps working on local data. A sync connector that can take the app down inverts the dependency it was built to isolate.

## Verification checklist

- [ ] Connection user physically lacks write grants on the source
- [ ] Anonymized records are never re-hydrated by a full sync (test it)
- [ ] Driverless environment: app boots, sync fails cleanly into the journal
- [ ] No credential fragment in logs, journal entries, or error pages

## Reference implementation

OrthoZ: Orthokis connector (SAP SQL Anywhere via `pdo_odbc`) — `app/Contracts/OrthokisClient.php`, `app/Services/Orthokis/`, `app/Models/SyncRun.php`, overridable queries in `config/orthoz.php`, panel at `/systeme`.

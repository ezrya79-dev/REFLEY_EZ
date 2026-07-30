---
name: admin-observability-suite
description: Give admins eyes on a Laravel app from inside the app — a KPI/metrics dashboard with time series and recent activity, an error-log reader over storage/logs (level/period/full-text filters, stack-trace grouping, bounded reads of large files, audited purge), and a guarded database administration tool (whitelisted SQL editor, table CRUD by introspection, import/export, saved queries). Use this whenever the user asks for an admin dashboard, "see the logs from the app", database admin UI, metrics/KPIs screen, or self-service operations without SSH.
---

# Admin observability suite

Three admin-only screens that remove SSH from daily operations. Each has a sharp safety edge — build the guard before the feature.

## 1. Metrics dashboard (`metrics.view`)

KPIs + time series over a selectable period, recent activity feed, alert summaries. Compute in a `DashboardService` with per-request caching; charts client-side from a JSON endpoint. Start with the 5 numbers the owner actually asks for weekly — not a BI suite.

## 2. Error-log reader (`logs.view`, admin)

Reads `storage/logs/*.log`: file picker, level/period filters, full-text search across messages **and** stack traces, expandable/copyable traces, recurring-error grouping (hash on normalized message), download, and purge (audited).

The safety edge: **bounded reads**. Log files grow unbounded — read only the last N MB (seek from the end), parse at most M entries, and **say so in the UI** ("showing the end of a 2.1 GB file") rather than silently pretending completeness. An observability screen that lies about its window is worse than none.

## 3. Database admin tool (`database.manage`, admin only — never another role)

- **SQL editor**: statement whitelist (SELECT freely; INSERT/UPDATE/DELETE allowed; DDL blocked), and any write **without a WHERE clause requires a signed confirmation step**. Log every executed query (`QueryRun`).
- **Table browser**: navigation + CRUD generated from **live column introspection** (no per-table code); system tables (migrations, sessions, jobs…) read-only.
- **Import/export**: CSV/SQL import with dry-run preview; export CSV/XLSX/JSON/SQL.
- **Saved queries**: per-user.
- **Business-data reset**: wipes domain tables but preserves accounts and settings — for the "we polluted the demo data" day; double-confirm and audit.

## Verification checklist

- [ ] Non-admin roles cannot reach any of the three screens (route + gate tests)
- [ ] `DELETE FROM x` without WHERE demands the signed confirmation
- [ ] 1 GB log file loads in bounded time and the UI reports the window
- [ ] Every SQL execution and log purge lands in the audit trail

## Reference implementation

OrthoZ: `/administration` (dashboard), `/administration/journal-erreurs` (bounded log reader), `/administration/base-de-donnees` (guarded DB tool), controllers under `app/Http/Controllers/Admin/`.

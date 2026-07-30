---
name: crud-resource-module
description: Build a complete resource module in a Laravel app the OrthoZ way — searchable/filterable/sortable list, detail page with actionable info, permission-gated create/edit/archive, audited writes, CSV export, and soft-delete archiving that detaches relations without touching history. Use this whenever the user asks for a management screen for any entity (suppliers, products, clients, contacts, projects…), a list+detail+edit set of pages, or "CRUD for X".
---

# CRUD resource module (the repeatable recipe)

Every entity screen in the app should feel identical. This recipe is that consistency, captured. Instantiate it per entity; never improvise a new layout.

## The recipe

1. **List page** — free-text search across the fields users actually remember (name, contact, email, notes…), toggle filters for domain flags, column sorting via clickable headers (whitelist sortable columns server-side), pagination. Read access is often broader than write access — gate them separately.
2. **Detail page** — everything actionable: `tel:`/`mailto:`/website links, related records with counts, recent activity involving the entity. The detail page answers "what do I need to know before I call this supplier", not "what columns exist".
3. **Create/Edit** — Form Requests for validation, one shared form partial for both, permission-gated (e.g. `suppliers.manage`), every write audited via `AuditLogger` with metadata only.
4. **Quick toggles** — single-field state flips (preferred/reliable/active) as dedicated POST endpoints with instant UI feedback, also gated and audited. Cheaper than a full edit round-trip and used 10× more.
5. **Archive, don't delete** — soft delete with a required motive, audited; on archive, **detach the entity's attachments** (memberships in kits/lists, code bindings, offers) but never touch historical records (movements, orders). History is accounting; attachments are configuration.
6. **CSV export** — a shared `CsvWriter` service; export respects current filters. Excel-compatible separators/encoding matter more than elegance here.
7. **Tests** — per gate: allowed/forbidden; search/sort behave; archive detaches-but-preserves; export streams the filtered set.

## Verification checklist

- [ ] A user without the manage permission can read but hits 403 on every write
- [ ] Archiving removes the entity from pickers but leaves history intact
- [ ] Sorting/searching can't be abused via query params (whitelisted columns)
- [ ] Export of a filtered list contains exactly the filtered rows

## Reference implementation

OrthoZ: Suppliers module (list `/fournisseurs`, detail, edit, toggles), Products (`/stocks`, motivated archiving), `app/Services/CsvWriter.php`.

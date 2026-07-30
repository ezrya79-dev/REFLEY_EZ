---
name: auth-rbac-permissions
description: Add authentication and role-based access control with atomic permissions to a Laravel app — named accounts, configurable roles, a Permission enum with zero hardcoded role checks, Gates, login throttling, session policy, and user administration with anti-lockout guards. Use this whenever the user needs login/accounts, roles, permissions, "who can do what", CNIL/GDPR-compliant access control, or a user management screen — even if they just say "add users to the app".
---

# Auth & RBAC with atomic permissions

Give the app named accounts, roles and permissions the OrthoZ way. The core rule: **application code never compares roles** (`$user->role === 'admin'` is forbidden). Code asks for an atomic permission; roles are just permission bundles. Why: adding a role or moving a capability then touches exactly one file, and the permission matrix is testable and displayable.

## Parameters to collect first

- Role names (default: `admin`, `manager`, `staff` — rename to the organisation's real jobs)
- The atomic permissions: one per business action, named `domain.verb` (e.g. `records.export`)
- Session lifetime (OrthoZ uses 60 min — a CNIL-friendly default) and login throttle limits

## Method

1. **Enums first** — copy `templates/Permission.php.stub` and `templates/UserRole.php.stub` into `app/Enums/`, replace the sample permissions with the app's real capabilities. `UserRole::permissions()` is the single source of truth of the matrix.
2. **Register Gates** — in `AppServiceProvider::boot()`, use `templates/gates.php.stub`: a `Gate::before` granting everything to Admin, then one `Gate::define` per permission delegating to `UserRole::hasPermission()`. Everywhere else use `Gate::authorize(...)`, `$user->can(...)`, `@can(...)`.
3. **Accounts** — nominative accounts only (one human = one account, a compliance requirement when personal data is involved). `User` carries `role` (cast to `UserRole`) and `is_active`. Login: rate-limit attempts (Laravel `RateLimiter`, e.g. 5/min per email+IP), audit successful and failed logins, set `SESSION_LIFETIME` from the parameter.
4. **User administration screen** (gate: `users.manage`) — list, create, edit role/activation/password, delete, with **anti-lockout guards**: refuse to deactivate/demote/delete the last active admin, and refuse letting users remove their own admin access. Enforce these in the controller/service layer, not just the UI, and cover them with feature tests — being locked out of your own app is the most expensive bug this module can have.
5. **First admin** — a console command (like OrthoZ's `orthoz:admin`) creates the first administrator interactively in production, because the database starts empty.
6. **Tests** — for each permission: allowed role passes, forbidden role gets 403. Plus the anti-lockout scenarios and the throttle.

## Verification checklist

- [ ] `grep -r "role ===" app resources` returns nothing (no hardcoded role comparisons)
- [ ] Permission matrix visible somewhere admin-facing (settings screen)
- [ ] Anti-lockout tests pass; last-admin deactivation is impossible
- [ ] Login throttled; sessions expire per policy

## Reference implementation

OrthoZ: `app/Enums/Permission.php` (18 atomic permissions), `app/Enums/UserRole.php`, Gates in `app/Providers/AppServiceProvider.php`, user admin under `app/Http/Controllers/Admin/`.

## Beyond Laravel

Keep the shape: a permission enum, a role→permissions map as data, one authorization primitive the whole codebase uses. Any framework's policy/guard system can host it.

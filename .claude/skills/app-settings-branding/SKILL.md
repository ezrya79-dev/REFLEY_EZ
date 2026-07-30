---
name: app-settings-branding
description: Add database-driven application settings with a typed SettingsService (cache + encrypted values) and an admin settings screen — branding (app name, accent palette, logos derived into favicons and PWA icons, email sender identity, business hours) and connector panels with write-only encrypted credentials and a "test connection" button. Use this whenever an app needs runtime-configurable settings, white-labeling, brand/identity configuration, or admin-entered credentials for SMTP or external services — instead of hardcoding config values.
---

# App settings & branding

Make identity and integrations **data, not code**: the same codebase serves any practice/company because name, colors, logos and connectors are edited in an admin screen and stored in the database. This is the skill that makes every future app "have its own setup" without forking.

## The settings backbone

A `settings` table (`key`, `value` JSON, `is_encrypted`) + a singleton `SettingsService`:

```php
public function get(string $key, mixed $default = null): mixed
{
    // per-request memoization → Cache::rememberForever('settings.'.$key)
    //   → Setting row (Crypt::decryptString if is_encrypted)
    //   → fallback to config('{{APP_SLUG}}.'.$key, $default)
}

public function set(string $key, mixed $value, bool $encrypted = false): void
{
    Setting::updateOrCreate(['key' => $key], [
        'value' => $encrypted ? Crypt::encryptString(json_encode($value)) : json_encode($value),
        'is_encrypted' => $encrypted,
    ]);
    // then bust the request memo + cache entry
}
```

Three layers matter: per-request memoization (layout + sidebar reread the same keys; with a database cache store every cache read is a SELECT), a persistent cache, and a **config fallback** so a fresh database still boots with sane defaults. Keys mirror config paths (`branding.app_name`). On `DecryptException` (rotated `APP_KEY`), treat the value as missing rather than crashing.

## Branding panel (admin)

- App name, organisation identity, email sender name/address.
- **Accent palette**: preset keys (e.g. `teal`, `indigo`, …) plus a `custom` hex; expose as a CSS variable (`--accent`) via a `data-accent` attribute on `<html>`, and derive strong/tint variants with `color-mix()` in CSS rather than storing every shade.
- **Logos**: one upload each for app, emails, favicon — derive favicon sizes (16/32/48/180) and PWA icons (192/512) server-side at upload time so the manifest and `<head>` never depend on a designer exporting ten files.
- Business hours/closures if the domain needs them.
- A `BrandingService` reads all of this for layouts, emails and the PWA manifest.

## Connector panels (credentials done right)

For each external service (SMTP, APIs…): store credentials with `set($key, $value, encrypted: true)`, render them **write-only** (the form never echoes the secret back — show only "configured ✓ / replace"), and provide a **read-only "test connection"** action that reports success/failure without persisting anything else. Audit saves and tests. Never put admin-entered secrets in `.env` — they belong to the database, encrypted under `APP_KEY`.

## Verification checklist

- [ ] Fresh DB boots with defaults (config fallback works)
- [ ] Secrets never appear in any HTTP response after saving
- [ ] Changing accent/logo updates UI + manifest without a deploy
- [ ] Settings writes bust the cache (no stale reads)

## Reference implementation

OrthoZ: `app/Services/SettingsService.php`, `app/Services/BrandingService.php`, `app/Models/Setting.php`, settings screens under `/reglages`.

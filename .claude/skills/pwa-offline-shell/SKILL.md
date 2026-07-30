---
name: pwa-offline-shell
description: Turn a Laravel web app into an installable PWA with a settings-driven dynamic manifest, service worker, offline page, home-screen shortcuts, and an offline action queue in localStorage that resyncs without silent loss. Use this whenever the user wants the app installable on phones/tablets, "add to home screen", offline support, or field/workshop usage where the network drops — even if they don't say "PWA".
---

# PWA & offline shell

Two independent halves: **installability** (manifest + service worker) and **offline resilience** (an action queue). Ship both for field-use apps; the first alone for desk apps.

## Installability

1. **Dynamic manifest** — serve `/manifest.webmanifest` from a Laravel route reading `BrandingService` (name, colors, icons 192/512 derived at logo upload). Hardcoding a static manifest breaks the white-labeling promise of `app-settings-branding`.
2. **Service worker** — precache the app shell (CSS/JS build assets, logo, offline page); network-first for HTML, cache-first for fingerprinted `/build/` assets; a branded offline fallback page. Bump the cache version on deploy (derive from the release version) so clients pick up new builds.
3. **Shortcuts** — manifest `shortcuts` for the 2–3 highest-frequency screens (OrthoZ: Scanner, Stocks).

## Offline action queue

For write actions that must survive dead network (stock movements, form submissions):

- On failed POST (or `navigator.onLine === false`), push `{action, payload, client_uuid, queued_at}` into a localStorage queue and tell the user it's queued — **visible pending state, never silent**.
- On `online` event + app start, replay in order; remove items only after server confirmation; surface per-item errors back to the user (an item the server rejects must not vanish — that's data loss wearing a trench coat).
- Make server endpoints **idempotent** via the `client_uuid` so a replay after a lost response doesn't double-apply.
- Unit-test the queue logic in Vitest with jsdom (localStorage without a browser).

## Verification checklist

- [ ] Lighthouse: installable; icons/name follow settings changes without redeploy
- [ ] Airplane mode: writes queue visibly, replay on reconnect, server dedupes by uuid
- [ ] New deploy invalidates old cached assets

## Reference implementation

OrthoZ: manifest route + service worker under `public/`, offline queue in `resources/js/scanner.js`, queue tests in `tests/js/`.

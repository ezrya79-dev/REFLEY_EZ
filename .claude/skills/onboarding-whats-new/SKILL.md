---
name: onboarding-whats-new
description: Add first-run onboarding and release communication to a Laravel app — a guided tour spotlighting interface landmarks at first login, a per-release "what's new" modal shown once per user, per-user tracking, replay from the profile page, and a scheduled release-detection command with no network calls during requests. Use this whenever the user wants a product tour, feature discovery, release notes in-app, "show users what changed", or better adoption of a shipped app.
---

# Onboarding & what's-new

Two small features with outsized adoption impact: nobody reads the manual, and nobody notices shipped improvements unless the app tells them.

## Guided tour (first login)

1. A step list defined in one JS/JSON structure: selector to spotlight, title, body (translated). Steps walk the shell: navigation, page help, sync status, notifications, account, preferences… — landmarks, not features.
2. Overlay + spotlight implementation is ~100 lines of Alpine/vanilla JS; skip heavy tour libraries.
3. Track completion **per user** (`tour_seen_at` on users or a keyed table). First login after account creation triggers it; "later" pauses it; it never re-fires once completed.
4. Replayable from the profile page — the tour is also documentation.

## What's-new modal (per release)

1. A scheduled command (e.g. `releases:notify`, cron on the VPS) fetches the repo's latest release notes and stores them locally. **Requests never call GitHub** — release detection is batch, the request path stays fast and offline-safe.
2. At login, if the stored release is newer than the user's `last_seen_release`, show the notes once, then stamp. Individual tracking: one colleague reading it must not consume it for the team.
3. Write release notes for operators, not developers (the deploy pipeline's tag/version can drive which notes to show — see `vps-deploy-pipeline`'s `APP_RELEASE_VERSION`).
4. Replayable from the profile page ("see the latest news again").

## Verification checklist

- [ ] New account sees the tour exactly once; replay works from profile
- [ ] Release modal shows once per user per release, tracked individually
- [ ] Zero outbound HTTP during normal requests (detection is scheduled)
- [ ] Both features fully translated

## Reference implementation

OrthoZ: Discovery controllers, `app/Services/ReleaseAnnouncer.php`, `NotifyNewReleasesCommand`, replay switches at `/profil`.

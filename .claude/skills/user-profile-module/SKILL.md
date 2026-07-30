---
name: user-profile-module
description: Build a "My profile" page where each user manages their own avatar/photo (with an in-browser editor), changes their password with proof of the current one, and sets personal preferences like light/dark theme — no special permission required. Use this whenever the user asks for a profile page, avatar upload, "change my password" flow, per-user preferences, or account self-service in a web app.
---

# User profile module

A self-service `/profil` page every authenticated user gets, with no special permission — it only ever touches the user's **own** row. Keep it strictly separated from user *administration* (that's `auth-rbac-permissions` territory): profile = me, admin = others.

## Scope

1. **Profile photo** — upload + client-side crop/zoom editor before submit (OrthoZ: `resources/js/avatar-editor.js` with a canvas-based editor). Server side: validate mime/size, strip EXIF, resize to fixed dimensions (e.g. 256px), store on the public disk via an `AvatarService`, delete the previous file. Never trust the client's crop alone.
2. **Password change** — require proof of the **current** password, validate the new one against a single shared password policy (the same rule object used at account creation), and write an audit entry (`profile.password_changed`) without ever logging the password itself. Why the proof: an unattended open session must not be enough to steal the account.
3. **Appearance & preferences** — theme (light/dark), locale if multilingual, and re-play switches for onboarding artifacts (guided tour, release notes) when the `onboarding-whats-new` module is installed. Persist on the user row (or a JSON `preferences` column), apply via a shared Blade layout attribute.

## Method

1. Routes under `auth` middleware only: `GET /profil`, `POST /profil/photo`, `POST /profil/password`, `POST /profil/preferences`.
2. One controller, thin; an `AvatarService` for file handling; Form Requests for validation (`current_password` rule for the proof).
3. Audit the sensitive action (password change) if the audit module exists; keep photo/preference changes out of the audit (noise).
4. Feature tests: wrong current password rejected, policy enforced, photo replaced not accumulated, a user can never reach another user's profile endpoints.

## Verification checklist

- [ ] Password change fails without correct current password, and is audited
- [ ] Old avatar file removed on replacement; uploads validated server-side
- [ ] Theme preference survives logout/login
- [ ] No profile endpoint accepts a user id parameter (always `auth()->user()`)

## Reference implementation

OrthoZ: profile controller (routes `/profil`), `app/Services/AvatarService.php`, `resources/js/avatar-editor.js`.

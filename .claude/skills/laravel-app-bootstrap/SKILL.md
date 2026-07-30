---
name: laravel-app-bootstrap
description: Bootstrap a new production-grade Laravel application with TDD quality gates (Pest + blocking coverage, Pint, PHPStan/Larastan, Rector), Vite, Vitest, Playwright and clean project hygiene. Use this whenever the user starts a new Laravel or PHP web application, asks to scaffold a project "like OrthoZ", or wants a fresh app set up with tests, linting and static analysis from day one — even if they only say "create a new app".
---

# Laravel app bootstrap

Set up a new Laravel application the way OrthoZ is built: test-driven, statically analysed, formatted, and structured so business logic never piles up in controllers. The goal is that quality gates exist **before** the first feature, because retrofitting them later is 10× the cost.

## Parameters to collect first

Ask the user (or infer from context) before writing files:

| Placeholder | Meaning | Example |
|---|---|---|
| `{{APP_NAME}}` | Display name | `MediStock` |
| `{{APP_SLUG}}` | kebab/snake identifier (repo, DB, paths) | `medistock` |
| Locale pair | primary/fallback locales | `fr` / `en` |
| Coverage minimum | blocking test-coverage % | `90` |

## Method

1. **Create the project** — `composer create-project laravel/laravel {{APP_SLUG}}` (or `laravel new`), PHP ≥ 8.4. Install dev tooling:
   `composer require --dev pestphp/pest pestphp/pest-plugin-laravel larastan/larastan laravel/pint rector/rector driftingly/rector-laravel laravel/pail`
2. **Copy the config templates** from `templates/` (strip the `.stub` suffix, then replace `{{…}}` tokens):
   - `phpstan.neon.stub` → `phpstan.neon` (level 5 + Larastan, `checkModelProperties: true`)
   - `pint.json.stub` → `pint.json`, `rector.php.stub` → `rector.php`
   - `editorconfig.stub` → `.editorconfig`, `gitattributes.stub` → `.gitattributes`, `gitignore.stub` → `.gitignore`
   - `vitest.config.js.stub` → `vitest.config.js`, `playwright.config.js.stub` → `playwright.config.js` (only if the app has client-side JS / E2E flows)
   - `env.example.stub` → `.env.example` — keep it complete and secret-free; it doubles as documentation.
3. **Establish the folder architecture** under `app/`: `Services/` (business logic), `Data/` (immutable DTOs), `Enums/` (domain enums), `Contracts/` (interfaces for external systems). Controllers stay thin: validate, delegate to a service, return a view/response. Why: services and DTOs are unit-testable without HTTP, and contracts let you fake external systems in tests.
4. **Wire composer scripts** — add a `dev` script running server + queue + logs + Vite concurrently (see OrthoZ `composer.json`), so `composer dev` is the only command a developer needs.
5. **Set the test strategy** — Pest, with the pyramid: Unit (services/DTOs), Feature (HTTP + DB), E2E (Playwright, critical paths only). Enforce in CI: `php artisan test --coverage --min={{COVERAGE_MIN}}`. Freeze the clock in tests (`Carbon::setTestNow`) against a fixed reference date.
6. **Separate seeders** — `DemoSeeder` (fictional dev/test dataset, never runs in production) vs. real-data seeders that are explicitly invoked and rerunnable. Never mix demo data into migrations.
7. **First commit** — after `vendor/bin/pint`, `vendor/bin/phpstan analyse` and `php artisan test` all pass.

## Verification checklist

- [ ] `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test` all green
- [ ] `.env.example` contains every variable the app reads, no secrets
- [ ] `composer dev` starts the full dev stack
- [ ] Demo data lives only in `DemoSeeder`, guarded from production

## Follow-ups

Immediately after bootstrap, chain the sibling skills: `auth-rbac-permissions` (accounts before features), `app-settings-branding`, `blade-design-system`, then `ci-quality-gates` from the app-delivery plugin.

## Reference implementation

OrthoZ (`github.com/dylanPerinetti/OrthoZ`, private): root configs, `composer.json` scripts, `docs/TESTING.md`. Consult it when available; this skill is self-sufficient without it.

## Beyond Laravel

The method transfers: one formatter + one static analyser + one test runner with a blocking coverage gate, service/DTO layering, and a complete env example — pick the stack's equivalents (e.g. Ruff/mypy/pytest, ESLint/tsc/vitest).

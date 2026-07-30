---
name: ci-quality-gates
description: Install a blocking GitHub Actions CI pipeline for a Laravel app — formatting check (Pint), static analysis (PHPStan/Larastan), tests with a minimum-coverage gate, Composer/npm security audits, migrations validated against MySQL, front build (Vite/Vitest) and Playwright E2E, aggregated into one required status check. Use this whenever the user asks for CI, GitHub Actions, "block PRs when tests fail", pipeline setup, or before enabling automatic deployment on any repo.
---

# CI quality gates

A CI that **blocks merges** is the precondition for automatic deployment: `vps-deploy-pipeline` only ships commits this workflow has validated. Install this before wiring any deploy.

## Method

1. **Instantiate the template** — copy `templates/ci.yml.stub` to `.github/workflows/ci.yml`, replace tokens:
   - `{{RUNNER_LABELS}}`: `[self-hosted, vps]` for a self-hosted runner (free — see note below) or `ubuntu-latest` (billed on private repos; a blocked billing account silently stops ALL CI and deploys).
   - `{{PHP_VERSION}}` (e.g. `8.4`), `{{NODE_VERSION}}` (e.g. `20`), `{{COVERAGE_MIN}}` (e.g. `90`), `{{APP_SLUG}}`.
2. **Understand the job graph** before trimming it: `quality` (Pint `--test` + PHPStan + `composer audit --locked` + `npm audit`), `coverage` (Pest with pcov, blocking threshold), `migrations` (MySQL 8 service container: `migrate:fresh --seed`, no-pending-drift check, rollback check `migrate:rollback --step=5` then re-up), `frontend` (Vitest + Vite build, artifact shared to E2E), `e2e` (Playwright against a server started by the config), and `ci-success` — the **aggregation gate**.
3. **Branch protection** — require the single `CI réussie` / `ci-success` check on `main`. One aggregated check means adding/removing jobs never requires touching repo settings.
4. **Trim to fit the app** — no client JS? drop `frontend`/`e2e` and remove them from `ci-success.needs`. No MySQL in prod? swap the service. Keep `quality` + `coverage` + `migrations` always: they're the cheap 80%.
5. **Self-hosted runner notes** (encoded in the template's comments): skip `setup-php`/`actions/cache` when the host already has warm toolchains; use **ephemeral host ports** for service containers (fixed `3306:3306` collides when two runs overlap); merge jobs that share a `composer install` on small runners.

## Scheduling & cost

- PR pushes cancel superseded runs (`concurrency` with `cancel-in-progress` on PRs only — never cancel `main`, it feeds the deploy chain).
- Put slow non-blocking advisors (e.g. Rector dry-run) in a separate nightly workflow, not in the blocking path.

## Verification checklist

- [ ] A PR with a failing test cannot be merged (branch protection on the aggregate job)
- [ ] Coverage below the threshold fails the run
- [ ] Migrations job catches a migration without a working `down()`
- [ ] `main` runs are never cancelled

## Reference implementation

OrthoZ: `.github/workflows/ci.yml` (optimised for a 2-vCPU self-hosted runner), `.github/workflows/rector.yml` (nightly advisor), `docs/CI-CD.md`.

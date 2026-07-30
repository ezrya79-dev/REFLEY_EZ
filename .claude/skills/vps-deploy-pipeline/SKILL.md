---
name: vps-deploy-pipeline
description: Wire automated deployment from ANY GitHub repository to a VPS — a generic deploy.sh (DB backup before migrations, build, caches, /up health check, automatic rollback), rollback.sh, and a deploy.yml GitHub Actions workflow with three trigger modes - continuous (every green-CI merge on main), scheduled (cron, deploys the latest CI-validated commit), or manual. Use this whenever the user wants deployment automation, "deploy on merge", nightly/scheduled deploys, auto-deploy to their server, or asks how to ship a repo to production safely.
---

# VPS deploy pipeline (any repo → the VPS)

Deployment is a **safety pipeline**, not a `git pull`: only CI-validated SHAs ship, the database is backed up before every migration, health is verified after build, and the code rolls back automatically when health fails. All of it is parameterized — nothing in these files is specific to one app.

Prerequisites: `ci-quality-gates` installed (the workflow gates on it) and `vps-provision` done for this app.

## Files to instantiate (strip `.stub`, replace `{{…}}`)

| Template | Destination | Role |
|---|---|---|
| `deploy.sh.stub` | `deploy/deploy.sh` (chmod +x) | server-side deploy with backup/health/rollback |
| `rollback.sh.stub` | `deploy/rollback.sh` (chmod +x) | manual return to last-known-good or a given SHA/tag |
| `deploy.yml.stub` | `.github/workflows/deploy.yml` | the trigger + SSH orchestration |

Tokens: `{{APP_SLUG}}`, `{{APP_NAME}}`, `{{PHP_VERSION}}`, `{{RUNNER_LABELS}}`.

## The three trigger modes (choose per repo)

1. **Continuous** (default, keep as-is): `workflow_run` on a successful "CI" on `main` — every merge ships itself. Deploys the exact `head_sha` CI validated, never `origin/main` HEAD (a later unvalidated push must not sneak in).
2. **Scheduled**: uncomment the `schedule:` cron (UTC!). On fire, the workflow resolves **the latest commit on main with a successful CI run** via the GitHub API and deploys that SHA — so a red main simply keeps yesterday's version. Use when releases should batch (e.g. nightly at 01:00 UTC). Optionally remove the `workflow_run` trigger to make scheduled the *only* automatic mode.
3. **Manual**: `workflow_dispatch` — always available from the Actions tab; also resolves the latest CI-green SHA.

## Setup steps

1. Instantiate the three files; commit.
2. **SSH access**: generate a dedicated keypair locally (`ssh-keygen -t ed25519 -f ~/.ssh/{{APP_SLUG}}_deploy -N "" -C "github-actions-{{APP_SLUG}}"`), install the public key on the VPS deploy user, keep the private key ONLY in GitHub secrets.
3. **Repo secrets**: `SSH_HOST`, `SSH_USER`, `SSH_PRIVATE_KEY`, optional `SSH_PORT`. **Repo variables**: `APP_DIR` (`/var/www/{{APP_SLUG}}`), `APP_URL`, `PHP_BIN`/`COMPOSER_BIN` if the host default is wrong, `PHP_FPM_SERVICE`, `HEALTH_URL` (public URL on multi-vhost hosts — `127.0.0.1` hits the default vhost).
4. Create the `production` GitHub environment if a manual approval step is wanted (Required reviewers).
5. First run via **Run workflow**, then merge something trivial to watch the continuous path.

## What deploy.sh guarantees (why each step exists)

- Captures the currently-deployed SHA **before** `git reset` → that's the rollback target.
- Maintenance mode with an EXIT trap → the site always comes back up, even on failure.
- **DB backup before migrations** (mysqldump via a 0600 temp defaults-file — credentials never on the command line or environment; SQLite copied). A failed backup **aborts** the deploy: no migration without a restore point.
- Build: composer no-dev, npm build, `migrate --force`, optional `POST_MIGRATE_SEEDER`, storage link verified (not just attempted), caches rebuilt, `queue:restart`, FPM reload.
- Health = HTTP 200 on `/up` AND `migrate:status` readable. Failure → automatic `git reset` to the previous SHA + rebuild. Migrations are deliberately NOT auto-reverted (data-loss risk); the pre-migration backup is the recovery path — say so in the runbook.
- Release version exported from the git tag (`APP_RELEASE_VERSION`/`APP_RELEASE_DATE`) before `config:cache`, so the footer can display it.

## Verification checklist

- [ ] Merge → green CI → app updated, `storage/app/deploy/current_sha` matches
- [ ] Break `/up` on purpose in a test deploy → automatic rollback restores the site
- [ ] `deploy/rollback.sh` returns to the previous version on demand
- [ ] No secret anywhere in the repo (grep for the host/IP before committing)

## Reference implementation

OrthoZ: `deploy/deploy.sh`, `deploy/rollback.sh`, `.github/workflows/deploy.yml`, `DEPLOYMENT.md` §2–6 (secrets table, troubleshooting matrix).

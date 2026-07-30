---
name: vps-provision
description: Provision a VPS to host one more Laravel application alongside existing ones — PHP-FPM, Nginx vhost, MariaDB database, Node, a restricted deploy user with minimal sudoers, the Laravel scheduler cron, a systemd queue worker, HTTPS via certbot, off-server backups and health probes. Use this whenever the user wants to put a new app on their server/VPS, set up hosting, add a site to an existing server, or prepare infrastructure before enabling automated deployment.
---

# VPS provisioning (one more app on the same server)

One VPS hosts several apps if each gets: its own directory, database, Nginx vhost, queue service — and shares the PHP/Node toolchain. Run these steps **once per application**. Never write secrets into the repo; everything secret lands in the server-side `.env` or GitHub secrets.

## Parameters

`{{APP_SLUG}}`, `{{APP_NAME}}`, `{{DOMAIN_OR_IP}}`, `{{PHP_VERSION}}` (e.g. 8.4), DB engine (MariaDB default, SQLite for single-seat demos).

## Method (as root on the VPS)

1. **Shared toolchain** (skip if already present from a previous app): PHP-FPM {{PHP_VERSION}} + extensions (`mbstring xml curl zip intl gd sqlite3 mysql bcmath`), Nginx, MariaDB, git, Composer, Node LTS.
2. **Database** — create `{{APP_SLUG}}` database + dedicated user bound to `127.0.0.1`, strong generated password (goes only into the server `.env`).
3. **Code & env** — clone to `/var/www/{{APP_SLUG}}`, `cp` the instantiated `templates/env.production.stub` to `.env`, fill it, `php artisan key:generate`. Production rules: `APP_ENV=production`, `APP_DEBUG=false`, exact `APP_URL`, `SESSION_SECURE_COOKIE=true` once HTTPS is on.
4. **First build by hand** — `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, `php artisan migrate --force`, first-admin command, `storage:link`, cache warmup (`config:cache route:cache view:cache event:cache`). Then `chown -R www-data:www-data storage bootstrap/cache`.
5. **Nginx vhost** — instantiate `templates/nginx.conf.stub` into `/etc/nginx/sites-available/{{APP_SLUG}}`, symlink into `sites-enabled`, `nginx -t && systemctl reload nginx`. Root MUST point at `/public`. Verify `curl -I http://{{DOMAIN_OR_IP}}/up` → 200.
6. **HTTPS** — `certbot --nginx -d {{DOMAIN}}` as soon as a domain exists; then flip `APP_URL` to https (server `.env` AND the repo's GitHub `APP_URL` variable) and set `SESSION_SECURE_COOKIE=true`.
7. **Scheduler cron** (required for purges/syncs/sequences): `* * * * * cd /var/www/{{APP_SLUG}} && php artisan schedule:run >> /dev/null 2>&1`.
8. **Queue worker** — instantiate `templates/queue.service.stub` to `/etc/systemd/system/{{APP_SLUG}}-queue.service`, `systemctl daemon-reload && systemctl enable --now {{APP_SLUG}}-queue`. Deploys call `artisan queue:restart`; systemd relaunches with fresh code.
9. **Deploy user (recommended over root)** — `deploy` user in `www-data` group owning the app dir, sudoers limited to exactly one command: `systemctl reload php{{PHP_VERSION}}-fpm`. The deploy pipeline's SSH key logs in as this user.
10. **Backups & probes** — deploy.sh already snapshots the DB before each migration locally; add an **off-server** nightly `mysqldump` + remote copy (local backups don't survive a dead server), and point an external uptime probe at `/up`.

## Multi-app runner note

GitHub self-hosted runners are per-repo: hosting a second app's CI/deploy on the same VPS means installing a second runner instance (each ~1 process; label them per app or share labels deliberately).

## Verification checklist

- [ ] `/up` returns 200 over HTTPS; cookies secure
- [ ] `crontab -l` shows schedule:run; `systemctl status {{APP_SLUG}}-queue` active
- [ ] Deploy user can: pull, build, reload FPM — and nothing else via sudo
- [ ] A restore from the off-server backup has been tested once

## Reference implementation

OrthoZ: `DEPLOYMENT.md` §1–2 and §7 (full command-by-command walkthrough), `deploy/nginx.conf.example`, `deploy/orthoz-queue.service`.

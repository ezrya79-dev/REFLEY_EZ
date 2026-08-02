#!/usr/bin/env bash
#
# Démarre l'application pour les tests E2E Playwright, sur une base SQLite
# dédiée (database/e2e.sqlite, ignorée par git) remplie par le DemoSeeder.
# Jamais la base de développement : les tests E2E la videraient.
#
set -euo pipefail

cd "$(dirname "$0")/.."

export APP_ENV=local
export DB_CONNECTION=sqlite
export DB_DATABASE="$(pwd)/database/e2e.sqlite"

# Base repartant de zéro à chaque run : les tests sont reproductibles.
rm -f "$DB_DATABASE"
touch "$DB_DATABASE"

php artisan migrate:fresh --force --seed --seeder=DemoSeeder --quiet
php artisan content:scan > /dev/null

exec php artisan serve --host=127.0.0.1 --port="${E2E_PORT:-8000}"

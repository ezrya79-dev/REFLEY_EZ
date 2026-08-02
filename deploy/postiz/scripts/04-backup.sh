#!/usr/bin/env bash
# ============================================================================
# 04-backup.sh — Sauvegarde Postiz (base de données + uploads + config)
#   - dump PostgreSQL compressé (données Postiz : comptes, canaux, posts…)
#   - archive des volumes postiz-uploads et postiz-config
#   - (la stack Temporal n'est pas sauvegardée : état de planification
#     reconstruit depuis la base au redémarrage)
#
# Usage : sudo bash scripts/04-backup.sh [dossier_destination]
#         (défaut : <dossier postiz>/backups/AAAAMMJJ-HHMMSS)
# À planifier ensuite via cron, ex. tous les jours à 04h30 :
#   30 4 * * * root bash /opt/postiz/scripts/04-backup.sh >> /var/log/postiz-backup.log 2>&1
# ============================================================================
set -euo pipefail

DIR="$(cd "$(dirname "$0")/.." && pwd)"
ENV_FILE="$DIR/.env"
STAMP=$(date +%Y%m%d-%H%M%S)
DEST="${1:-$DIR/backups}/$STAMP"

getenv() { grep -E "^$1=" "$ENV_FILE" 2>/dev/null | head -1 | cut -d= -f2- ; }
PG_USER="$(getenv POSTGRES_USER)"; PG_USER="${PG_USER:-postiz-user}"
PG_DB="$(getenv POSTGRES_DB)";     PG_DB="${PG_DB:-postiz-db-local}"

docker ps --format '{{.Names}}' | grep -qx postiz-postgres \
  || { echo "ERREUR : conteneur postiz-postgres introuvable/arrêté" >&2; exit 1; }

mkdir -p "$DEST"
echo "== Dump PostgreSQL ($PG_DB) =="
docker exec postiz-postgres pg_dump -U "$PG_USER" -d "$PG_DB" | gzip > "$DEST/postiz-db.sql.gz"

echo "== Archive des volumes =="
docker run --rm -v postiz_postiz-uploads:/uploads:ro -v "$DEST":/backup alpine \
  tar czf /backup/uploads.tar.gz -C /uploads .
docker run --rm -v postiz_postiz-config:/config:ro -v "$DEST":/backup alpine \
  tar czf /backup/config.tar.gz -C /config .

echo
echo "Sauvegarde terminée dans : $DEST"
ls -lh "$DEST"
echo
echo "Restauration (résumé) :"
echo "  1. docker compose up -d postiz-postgres"
echo "  2. gunzip -c postiz-db.sql.gz | docker exec -i postiz-postgres psql -U $PG_USER -d $PG_DB"
echo "  3. restaurer les tar dans les volumes, puis docker compose up -d"
# Rotation (optionnel) : décommenter pour garder 14 jours
# find "$(dirname "$DEST")" -maxdepth 1 -mindepth 1 -type d -mtime +14 -exec rm -rf {} +

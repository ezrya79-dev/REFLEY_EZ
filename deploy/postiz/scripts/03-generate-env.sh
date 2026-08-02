#!/usr/bin/env bash
# ============================================================================
# 03-generate-env.sh — Génère le fichier .env avec des secrets forts
#
# Usage :
#   bash scripts/03-generate-env.sh postiz.mondomaine.com   # production HTTPS
#   bash scripts/03-generate-env.sh --local                 # pas encore de domaine
#                                                             (accès via tunnel SSH)
# Options :
#   --force   écrase un .env existant (sinon refus, pour ne rien perdre)
#
# Le .env est créé à la racine du dossier postiz/ avec chmod 600.
# Il n'est JAMAIS commité (voir .gitignore) : ce dépôt Git est public.
# ============================================================================
set -euo pipefail

DIR="$(cd "$(dirname "$0")/.." && pwd)"
ENV_FILE="$DIR/.env"
EXAMPLE="$DIR/.env.example"

FORCE=0
DOMAIN=""
for arg in "$@"; do
  case "$arg" in
    --force) FORCE=1 ;;
    --local) DOMAIN="__local__" ;;
    -*) echo "Option inconnue : $arg" >&2; exit 1 ;;
    *) DOMAIN="$arg" ;;
  esac
done

if [ -z "$DOMAIN" ]; then
  echo "Usage : bash $0 <sous-domaine.domaine.tld> | --local   [--force]" >&2
  exit 1
fi

if [ -f "$ENV_FILE" ] && [ "$FORCE" -ne 1 ]; then
  echo "ERREUR : $ENV_FILE existe déjà. Relancer avec --force pour l'écraser" >&2
  echo "         (les secrets actuels seraient PERDUS — la base garderait l'ancien mot de passe)." >&2
  exit 1
fi

command -v openssl >/dev/null 2>&1 || { echo "ERREUR : openssl requis" >&2; exit 1; }

JWT_SECRET=$(openssl rand -hex 64)
PG_PASSWORD=$(openssl rand -hex 24)

if [ "$DOMAIN" = "__local__" ]; then
  MAIN_URL="http://localhost:4007"
  BACKEND_URL="http://localhost:4007/api"
else
  MAIN_URL="https://${DOMAIN}"
  BACKEND_URL="https://${DOMAIN}/api"
fi

cp "$EXAMPLE" "$ENV_FILE"
# Portable (GNU sed) — on remplace les valeurs par défaut du .env.example
sed -i \
  -e "s|^MAIN_URL=.*|MAIN_URL=${MAIN_URL}|" \
  -e "s|^FRONTEND_URL=.*|FRONTEND_URL=${MAIN_URL}|" \
  -e "s|^NEXT_PUBLIC_BACKEND_URL=.*|NEXT_PUBLIC_BACKEND_URL=${BACKEND_URL}|" \
  -e "s|^JWT_SECRET=.*|JWT_SECRET=${JWT_SECRET}|" \
  -e "s|^POSTGRES_PASSWORD=.*|POSTGRES_PASSWORD=${PG_PASSWORD}|" \
  "$ENV_FILE"
chmod 600 "$ENV_FILE"

echo "OK : $ENV_FILE généré (chmod 600)."
echo "  MAIN_URL                = ${MAIN_URL}"
echo "  NEXT_PUBLIC_BACKEND_URL = ${BACKEND_URL}"
echo "  JWT_SECRET              = (128 caractères hex, généré)"
echo "  POSTGRES_PASSWORD       = (48 caractères hex, généré)"
echo
echo "Vérifier ensuite la config complète avec :"
echo "  docker compose config --quiet && echo 'compose OK'"

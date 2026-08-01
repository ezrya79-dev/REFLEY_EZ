#!/usr/bin/env bash
# ============================================================================
# 00-audit.sh — Audit du serveur AVANT installation Postiz
# LECTURE SEULE : ce script ne modifie rien.
# Usage : bash 00-audit.sh   (de préférence en root, sinon certains blocs
#         afficheront "non accessible", ce n'est pas bloquant)
# ============================================================================
set -u

section() { echo; echo "==================== $1 ===================="; }

echo "AUDIT SERVEUR POSTIZ — $(date '+%Y-%m-%d %H:%M:%S') — $(hostname)"

section "OS"
if [ -r /etc/os-release ]; then . /etc/os-release && echo "$PRETTY_NAME"; fi
echo "Noyau  : $(uname -r) ($(uname -m))"
echo "Uptime : $(uptime -p 2>/dev/null || uptime)"

section "CPU"
echo "vCPU : $(nproc)"
lscpu 2>/dev/null | grep -E 'Model name|Nom de modèle' | head -1 || true

section "RAM"
free -h

section "SWAP"
swapon --show 2>/dev/null | grep -q . && swapon --show || echo "Aucun swap actif"

section "DISQUE"
df -h / 2>/dev/null

section "DOCKER"
if command -v docker >/dev/null 2>&1; then
  docker --version
  docker compose version 2>/dev/null || echo "Plugin docker compose v2 : ABSENT"
  systemctl is-active docker >/dev/null 2>&1 && echo "Service docker : actif" || echo "Service docker : inactif"
else
  echo "Docker : ABSENT (sera installé à l'étape B)"
fi

section "CONTENEURS EXISTANTS (à ne pas toucher)"
docker ps -a --format 'table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}' 2>/dev/null \
  || echo "(docker absent, ou lancer le script en root)"

section "PORTS EN ÉCOUTE (22 80 81 443 3000 4007 5000 5432 6379 7233 8080 9200)"
SS_OUT=$(ss -tlnp 2>/dev/null || ss -tln 2>/dev/null)
echo "$SS_OUT" | awk 'NR==1 || $4 ~ /:(22|80|81|443|3000|4007|5000|5432|6379|7233|8080|9200)$/'

section "REVERSE PROXY — services système"
FOUND_PROXY=0
for s in nginx apache2 caddy traefik haproxy; do
  if systemctl is-active "$s" >/dev/null 2>&1; then echo "$s : ACTIF (paquet système)"; FOUND_PROXY=1; fi
done
[ "$FOUND_PROXY" -eq 0 ] && echo "Aucun reverse proxy en service système"

section "REVERSE PROXY — conteneurs"
docker ps --format '{{.Names}}  {{.Image}}' 2>/dev/null | grep -Ei 'nginx|traefik|caddy|proxy|npm|swag' \
  || echo "Aucun conteneur reverse proxy détecté"

section "FIREWALL (UFW)"
if command -v ufw >/dev/null 2>&1; then
  ufw status verbose 2>/dev/null || echo "(lancer en root pour voir l'état UFW)"
else
  echo "UFW non installé"
fi

section "RÉSUMÉ / VERDICT"
MEM_MB=$(free -m | awk '/^Mem/{print $2}')
SWAP_MB=$(free -m | awk '/^Swap/{print $2}')
DISK_GB=$(df -BG --output=avail / 2>/dev/null | tail -1 | tr -dc '0-9')
echo "RAM totale   : ${MEM_MB} Mo | Swap : ${SWAP_MB} Mo | Disque dispo / : ${DISK_GB} Go"
if [ "${MEM_MB:-0}" -ge 7500 ]; then
  echo "RAM  : OK pour la stack officielle (Postiz + Temporal + Elasticsearch)"
elif [ "${MEM_MB:-0}" -ge 3500 ]; then
  echo "RAM  : LIMITE — swap de 4 Go fortement conseillé (scripts/02-swap.sh)"
else
  echo "RAM  : INSUFFISANTE pour la stack officielle (~2,5-3,5 Go utilisés) — prévoir un plan VPS plus grand ou swap + surveillance"
fi
if [ "${DISK_GB:-0}" -ge 20 ]; then
  echo "Disque : OK (>= 20 Go dispo)"
else
  echo "Disque : JUSTE — la stack (images ~6 Go + données) demande >= 20 Go libres"
fi
for p in 80 443; do
  if echo "$SS_OUT" | awk -v p=":$p" '$4 ~ p"$"' | grep -q .; then
    echo "Port $p : OCCUPÉ — un service web tourne déjà (reverse proxy existant ?)"
  else
    echo "Port $p : libre"
  fi
done
if echo "$SS_OUT" | awk '$4 ~ /:4007$/' | grep -q .; then
  echo "Port 4007 : OCCUPÉ — changer POSTIZ_BIND_PORT dans .env"
else
  echo "Port 4007 : libre (port local Postiz par défaut)"
fi
echo
echo "Audit terminé. Copier TOUTE la sortie ci-dessus et la transmettre."

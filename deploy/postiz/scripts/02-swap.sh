#!/usr/bin/env bash
# ============================================================================
# 02-swap.sh — Ajoute un fichier de swap (par défaut 4G) si aucun swap n'existe
#   - crée /swapfile, l'active, le rend permanent via /etc/fstab
#   - règle vm.swappiness=10 (le swap sert de filet de sécurité, pas de RAM bis)
# Usage : sudo bash 02-swap.sh [taille]     ex. : sudo bash 02-swap.sh 4G
# ============================================================================
set -euo pipefail

SIZE="${1:-4G}"
SWAPFILE=/swapfile

if [ "$(id -u)" -ne 0 ]; then
  echo "ERREUR : lancer en root (sudo bash $0)" >&2
  exit 1
fi

if swapon --show 2>/dev/null | grep -q .; then
  echo "Un swap est déjà actif — aucune modification :"
  swapon --show
  exit 0
fi

if [ -e "$SWAPFILE" ]; then
  echo "ERREUR : $SWAPFILE existe déjà mais n'est pas actif. Vérifier manuellement." >&2
  exit 1
fi

# Vérifie l'espace disque : taille demandée + 5 Go de marge
SIZE_G=$(echo "$SIZE" | tr -dc '0-9')
AVAIL_G=$(df -BG --output=avail / | tail -1 | tr -dc '0-9')
if [ "$AVAIL_G" -lt $((SIZE_G + 5)) ]; then
  echo "ERREUR : seulement ${AVAIL_G} Go libres, il en faut au moins $((SIZE_G + 5)) (swap ${SIZE_G} Go + marge). Abandon." >&2
  exit 1
fi

echo "== Création du swap ${SIZE} =="
fallocate -l "$SIZE" "$SWAPFILE" || dd if=/dev/zero of="$SWAPFILE" bs=1M count=$((SIZE_G * 1024)) status=progress
chmod 600 "$SWAPFILE"
mkswap "$SWAPFILE"
swapon "$SWAPFILE"

if ! grep -qE "^${SWAPFILE}\s" /etc/fstab; then
  echo "${SWAPFILE} none swap sw 0 0" >> /etc/fstab
fi

if ! grep -q '^vm.swappiness' /etc/sysctl.d/99-postiz-swap.conf 2>/dev/null; then
  echo 'vm.swappiness=10' > /etc/sysctl.d/99-postiz-swap.conf
  sysctl -p /etc/sysctl.d/99-postiz-swap.conf
fi

echo
echo "== Swap actif =="
free -h
swapon --show

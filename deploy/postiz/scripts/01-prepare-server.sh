#!/usr/bin/env bash
# ============================================================================
# 01-prepare-server.sh — Prépare Ubuntu pour Postiz
#   - met à jour l'index des paquets + outils de base (curl, git, ca-certificates)
#   - installe Docker Engine + plugin Compose v2 via le dépôt APT OFFICIEL Docker
#   - active le service docker au démarrage
# Idempotent : ne réinstalle pas ce qui est déjà présent.
# Ne touche PAS au firewall (voir README, étape B3 — pour ne pas couper SSH).
# Usage : sudo bash 01-prepare-server.sh
# ============================================================================
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
  echo "ERREUR : lancer en root (sudo bash $0)" >&2
  exit 1
fi

. /etc/os-release
if [ "${ID:-}" != "ubuntu" ]; then
  echo "ATTENTION : ce script vise Ubuntu (détecté : ${ID:-inconnu}). Abandon." >&2
  exit 1
fi

echo "== Mise à jour de l'index des paquets =="
apt-get update -y

echo "== Outils de base =="
apt-get install -y ca-certificates curl git

if command -v docker >/dev/null 2>&1; then
  echo "== Docker déjà présent : $(docker --version) =="
else
  echo "== Installation de Docker (dépôt APT officiel) =="
  install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
  chmod a+r /etc/apt/keyrings/docker.asc
  echo \
    "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] \
https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable" \
    > /etc/apt/sources.list.d/docker.list
  apt-get update -y
  apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
fi

if ! docker compose version >/dev/null 2>&1; then
  echo "== Plugin Compose v2 manquant, installation =="
  apt-get install -y docker-compose-plugin
fi

systemctl enable --now docker

echo
echo "== Vérification finale =="
docker --version
docker compose version
systemctl is-active docker
echo
echo "OK. Serveur prêt pour l'étape C (installation Postiz)."

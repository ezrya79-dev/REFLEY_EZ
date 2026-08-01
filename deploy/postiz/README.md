# Postiz self-hosted — Kit d'installation VPS (Ubuntu + Docker Compose)

Installation production légère de [Postiz](https://postiz.com) (gestion/planification
de posts sur les réseaux sociaux) sur un VPS Ubuntu, avec connexion **Claude via MCP**
en objectif final.

- Basé sur la stack **officielle** [gitroomhq/postiz-docker-compose](https://github.com/gitroomhq/postiz-docker-compose)
  (relevée le 2026-08-01) — voir l'en-tête de `docker-compose.yml` pour les
  adaptations (secrets via `.env`, ports en loopback, réseau nommé).
- **Ce dépôt Git est public : aucun secret n'y est commité.** Le `.env` est généré
  sur le VPS (`scripts/03-generate-env.sh`) et ignoré par Git.

## Architecture

```
Internet ──HTTPS 443──> Reverse proxy (Caddy / NPM / Nginx)   ── choix à l'étape E
                              │ http
                              ▼
                     postiz (127.0.0.1:4007 → :5000)          ── UI + API + MCP
                        │            │            │
                postiz-postgres  postiz-redis  temporal ── temporal-postgresql
                                                  │      └─ temporal-elasticsearch
                                                  └─ temporal-ui (127.0.0.1:8080)
```

**Important dimensionnement** : depuis la v2.12, Postiz embarque **Temporal**
(planificateur de workflows) + Elasticsearch → **7 conteneurs, ~2,5 à 3,5 Go de RAM**.
Officiel : minimum 2 Go (mono-utilisateur léger), **8 Go recommandés** ; disque ≥ 20 Go
libres. Entre 4 et 8 Go de RAM : swap de 4 Go obligatoire (`scripts/02-swap.sh`).

## Arborescence du kit

| Fichier | Rôle |
|---|---|
| `docker-compose.yml` | Stack Postiz complète (officielle, paramétrée par `.env`) |
| `.env.example` | Modèle de configuration — ne pas remplir à la main |
| `dynamicconfig/` | Config Temporal requise (copie officielle) |
| `scripts/00-audit.sh` | Audit serveur (lecture seule) |
| `scripts/01-prepare-server.sh` | MAJ paquets + installation Docker officielle |
| `scripts/02-swap.sh` | Ajout d'un swap 4G si RAM < 8 Go |
| `scripts/03-generate-env.sh` | Génère `.env` avec secrets forts |
| `scripts/04-backup.sh` | Sauvegarde base + uploads + config |
| `reverse-proxy/caddy/` | Option HTTPS automatique (recommandée si aucun proxy) |
| `reverse-proxy/npm/` | Option Nginx Proxy Manager (interface web) |
| `reverse-proxy/nginx/` | Option Nginx système + certbot |

---

## Étape A — Audit du serveur (lecture seule)

```bash
curl -fsSL -o /tmp/00-audit.sh https://raw.githubusercontent.com/ezrya79-dev/REFLEY_EZ/claude/postiz-self-hosted-vps-jurv3f/deploy/postiz/scripts/00-audit.sh
bash /tmp/00-audit.sh
```

Le script ne modifie rien. Il vérifie : OS, CPU, RAM, swap, disque, Docker,
conteneurs existants, ports occupés, reverse proxy déjà en place, UFW — et donne
un verdict RAM/disque/ports. **Analyser la sortie avant de continuer.**

## Étape B — Préparation système

**B1. Paquets + Docker** (idempotent, n'écrase rien) :

```bash
cd /opt
git clone https://github.com/ezrya79-dev/REFLEY_EZ.git refley
ln -s /opt/refley/deploy/postiz /opt/postiz
cd /opt/postiz
sudo bash scripts/01-prepare-server.sh
```

> Tant que la branche n'est pas fusionnée sur `main` :
> `git clone -b claude/postiz-self-hosted-vps-jurv3f https://github.com/ezrya79-dev/REFLEY_EZ.git refley`

**B2. Swap** (seulement si l'audit dit RAM < 8 Go) :

```bash
sudo bash scripts/02-swap.sh 4G
```

**B3. Firewall UFW** — commandes à passer **dans cet ordre** (la règle SSH
d'abord, sinon on se coupe l'accès) :

```bash
sudo ufw allow OpenSSH     # 1. TOUJOURS en premier
sudo ufw allow 80/tcp      # 2. HTTP (validation Let's Encrypt + redirection)
sudo ufw allow 443/tcp     # 3. HTTPS
sudo ufw enable            # 4. répondre "y" — la règle OpenSSH protège la session
sudo ufw status verbose
```

> **Hostinger** : hPanel a aussi son propre pare-feu (VPS → Paramètres → Pare-feu).
> S'il est activé, y ouvrir également 80/443 (et laisser 22).

## Étape C — Configuration Postiz

**C1. DNS** : créer un enregistrement `A` pour le sous-domaine choisi
(ex. `postiz.mondomaine.com`) → IP du VPS. TTL court (300 s) pour aller vite.

**C2. Générer le `.env`** :

```bash
cd /opt/postiz
bash scripts/03-generate-env.sh postiz.mondomaine.com   # ou --local si pas encore de domaine
docker compose config --quiet && echo "compose OK"
```

Secrets générés : `JWT_SECRET` (hex 128), `POSTGRES_PASSWORD` (hex 48), fichier en `chmod 600`.

## Étape D — Démarrage

```bash
cd /opt/postiz
docker compose pull
docker compose up -d
watch -n 5 'docker compose ps'    # attendre que tout passe à healthy (2-5 min, Ctrl+C pour sortir)
```

Vérifications :

```bash
docker compose ps                         # tous les services healthy ?
curl -sI http://127.0.0.1:4007 | head -3  # attendu : HTTP/1.1 200 OK
docker compose logs --tail=50 postiz      # pas d'erreur en boucle ?
```

Accès temporaire sans domaine (depuis ton PC) : `ssh -L 4007:localhost:4007 root@IP_DU_VPS`
puis ouvrir <http://localhost:4007>.

## Étape E — Exposition publique HTTPS

**Cas 1 — un reverse proxy existe déjà** (détecté à l'audit) : ne pas en installer
un deuxième. Ajouter un vhost/host qui pointe vers `127.0.0.1:4007` (proxy système)
ou vers `postiz:5000` après l'avoir attaché au réseau `postiz-network` (proxy en
conteneur). S'inspirer de `reverse-proxy/nginx/postiz.conf.example` (WebSockets ON,
`client_max_body_size 100M`, **streaming/chunked activé — requis pour MCP**).

**Cas 2 — aucun proxy** : trois options prêtes dans `reverse-proxy/` :

| Option | Pour qui | Effort |
|---|---|---|
| **Caddy** (recommandée) | HTTPS auto, zéro entretien, config 5 lignes | ~2 min |
| **Nginx Proxy Manager** | Interface web de gestion | ~10 min |
| **Nginx + certbot** | Habitude de l'admin classique | ~15 min |

Exemple Caddy :

```bash
cd /opt/postiz/reverse-proxy/caddy
cp Caddyfile.example Caddyfile
nano Caddyfile          # domaine + email
docker compose up -d
docker compose logs -f caddy   # vérifier l'obtention du certificat
```

Test final : <https://postiz.mondomaine.com> doit afficher la page de connexion,
et `curl -sI https://postiz.mondomaine.com/api/auth/can-register` doit répondre.

## Étape F — Premier compte, API, MCP

**F1. Compte admin** : ouvrir `https://postiz.mondomaine.com` → *Register*.
Le **premier compte créé est superadmin**. Puis fermer les inscriptions :

```bash
cd /opt/postiz
sed -i 's/^DISABLE_REGISTRATION=.*/DISABLE_REGISTRATION=true/' .env
docker compose up -d postiz
```

**F2. Clé API** : dans Postiz → **Settings → Developers → Public API** → copier la clé.
API REST : base `https://postiz.mondomaine.com/api/public/v1`, header
`Authorization: <clé>`. Limite : `API_LIMIT` requêtes/h (30 par défaut, cf. `.env`).

**F3. MCP pour Claude** — l'endpoint MCP fait partie du backend Postiz
([doc officielle](https://docs.postiz.com/mcp/introduction)) :

| Méthode | Valeur |
|---|---|
| URL (clé dans l'URL) | `https://postiz.mondomaine.com/api/mcp/<CLE_API>` |
| URL (Bearer, préférable) | `https://postiz.mondomaine.com/api/mcp` + header `Authorization: Bearer <CLE_API>` |

- **claude.ai / Claude Desktop** : Settings → Connectors → *Add custom connector*
  → coller l'URL avec la clé intégrée.
- **Claude Code** :
  `claude mcp add --transport http postiz "https://postiz.mondomaine.com/api/mcp/<CLE_API>"`

Outils MCP exposés (9) : `integrationList`, `groupList`, `integrationSchema`,
`triggerTool`, `schedulePostTool`, `generateImageTool`, `generateVideoOptions`,
`videoFunctionTool`, `generateVideoTool`. Aucune clé OpenAI nécessaire pour MCP.

**F4. Plusieurs apps métier sur un seul Postiz** — bonnes pratiques :

1. **Un seul Postiz, une organisation** : mutualise serveur et maintenance.
2. **Un utilisateur Postiz par app métier** → chaque app a **sa propre clé API/MCP**
   (révocable indépendamment, quotas séparés).
3. **Groupes/« customers »** : regrouper les canaux par app métier ; côté MCP,
   `integrationList` filtre par groupe → Claude ne voit que les canaux du domaine concerné.
4. **Nommage des canaux** préfixé par app (ex. `refley-linkedin`, `autreapp-x`).
5. Augmenter `API_LIMIT` dans `.env` quand plusieurs apps publient (ex. 120).

## Maintenance

```bash
# Mise à jour Postiz (faire un backup avant)
cd /opt/postiz && bash scripts/04-backup.sh && docker compose pull && docker compose up -d

# Sauvegarde quotidienne (cron)
echo '30 4 * * * root bash /opt/postiz/scripts/04-backup.sh >> /var/log/postiz-backup.log 2>&1' | sudo tee /etc/cron.d/postiz-backup
```

Conseil prod : figer `POSTIZ_IMAGE_TAG` sur une version précise dans `.env`
plutôt que `latest`, et mettre à jour volontairement.

## Dépannage rapide

| Symptôme | Cause probable | Correctif |
|---|---|---|
| `elasticsearch` redémarre en boucle / code 137 | RAM insuffisante | ajouter le swap (`02-swap.sh`), vérifier `free -h` |
| Page blanche / redirection vers `localhost` | URLs du `.env` incohérentes | les 3 URLs doivent porter le domaine public, puis `docker compose up -d postiz` |
| 502 depuis le proxy | Postiz pas encore healthy | `docker compose ps`, attendre `healthy`, vérifier port 4007 |
| Erreur 413 à l'upload | proxy limite la taille | `client_max_body_size 100M` (déjà dans nos configs) |
| MCP se connecte mais coupe | buffering du proxy | `proxy_buffering off` (nginx) / WebSockets ON (NPM) |
| `port is already allocated` | conflit de port | changer `POSTIZ_BIND_PORT`/`TEMPORAL_UI_BIND_PORT` dans `.env` |

Docs : [installation](https://docs.postiz.com/installation/docker-compose) ·
[référence configuration](https://docs.postiz.com/configuration/reference) ·
[reverse proxies](https://docs.postiz.com/reverse-proxies/nginx) ·
[API publique](https://docs.postiz.com/public-api/introduction) ·
[MCP](https://docs.postiz.com/mcp/introduction) ·
[dépannage self-host](https://docs.postiz.com/troubleshooting/self-host)

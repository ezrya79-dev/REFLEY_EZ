# Déploiement de Refley

Ce document est le mode d'emploi complet pour mettre Refley en production sur
un VPS, puis pour que chaque fusion sur `main` se déploie toute seule.

**État actuel : rien n'est déployé.** Tous les fichiers décrits ici sont
prêts dans le dépôt ; les étapes 1 à 4 demandent un accès au serveur et ne
peuvent être faites que par une personne disposant de cet accès.

- **Domaine visé** : `refley.joefr.cloud`
- **Pile serveur** : Nginx + PHP-FPM 8.4 + MariaDB + Node 20

---

## Vue d'ensemble de la chaîne

```
push/PR ──► CI (.github/workflows/ci.yml)
              qualité · couverture · migrations MySQL · front · E2E
              └─► « CI réussie » (case unique de protection de branche)
                    │
        merge sur main + CI verte
                    │
                    ▼
        Deploy (.github/workflows/deploy.yml)
              └─ SSH ─► deploy/deploy.sh sur le VPS
                          sauvegarde BDD → build → migrations → caches
                          → contrôle /up → ROLLBACK AUTO si échec
```

Le déploiement ne part **que** sur un commit que la CI a validé : jamais le
HEAD de `main` (une poussée non testée ne peut pas se glisser en production).

---

## Ce qui est déjà dans le dépôt

| Fichier | Rôle |
|---|---|
| `.github/workflows/ci.yml` | CI bloquante (6 jobs agrégés) |
| `.github/workflows/deploy.yml` | Déclenchement + orchestration SSH |
| `deploy/deploy.sh` | Déploiement serveur : sauvegarde, build, santé, rollback |
| `deploy/rollback.sh` | Retour manuel à la version précédente |
| `deploy/nginx.conf.example` | Vhost Nginx à adapter |
| `deploy/refley-queue.service` | Unité systemd du worker de queue |
| `deploy/env.production.example` | Modèle de `.env` de production (sans secret) |

---

## Étape 1 — Provisionner le VPS *(accès serveur requis)*

À exécuter **en root sur le serveur**. Voir `.claude/skills/vps-provision/SKILL.md`
pour le détail de chaque point.

```bash
# 1. Outillage partagé (à sauter si un autre site l'a déjà installé)
apt update && apt install -y nginx mariadb-server git curl unzip \
  php8.4-fpm php8.4-{mbstring,xml,curl,zip,intl,gd,sqlite3,mysql,bcmath}
# Composer + Node 20 LTS selon vos habitudes

# 2. Base de données dédiée
mysql -e "CREATE DATABASE refley CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER 'refley'@'127.0.0.1' IDENTIFIED BY '<MOT-DE-PASSE-FORT>';"
mysql -e "GRANT ALL PRIVILEGES ON refley.* TO 'refley'@'127.0.0.1'; FLUSH PRIVILEGES;"

# 3. Code
git clone https://github.com/ezrya79-dev/REFLEY_EZ.git /var/www/refley
cd /var/www/refley
cp deploy/env.production.example .env
# → éditer .env : APP_URL, identifiants base, mail…
php8.4 artisan key:generate

# 4. Premier build à la main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php8.4 artisan migrate --force
php8.4 artisan storage:link
php8.4 artisan content:scan          # carte des zones éditables du CMS
php8.4 artisan refley:admin          # crée le premier administrateur
php8.4 artisan config:cache route:cache view:cache event:cache
chown -R www-data:www-data storage bootstrap/cache

# 5. Vhost Nginx (la racine DOIT pointer sur /public)
cp deploy/nginx.conf.example /etc/nginx/sites-available/refley
ln -s /etc/nginx/sites-available/refley /etc/nginx/sites-enabled/refley
nginx -t && systemctl reload nginx
curl -I http://refley.joefr.cloud/up      # doit répondre 200

# 6. HTTPS
certbot --nginx -d refley.joefr.cloud
# puis dans .env : APP_URL=https://…  et  SESSION_SECURE_COOKIE=true

# 7. Planificateur (purges, séquences…)
crontab -e
* * * * * cd /var/www/refley && php8.4 artisan schedule:run >> /dev/null 2>&1

# 8. Worker de queue
cp deploy/refley-queue.service /etc/systemd/system/refley-queue.service
systemctl daemon-reload && systemctl enable --now refley-queue

# 9. Utilisateur de déploiement (préférable à root)
adduser --disabled-password --gecos "" deploy && usermod -aG www-data deploy
chown -R deploy:www-data /var/www/refley
# sudoers limité à UNE commande :
echo 'deploy ALL=(root) NOPASSWD: /bin/systemctl reload php8.4-fpm' \
  > /etc/sudoers.d/refley-deploy && chmod 440 /etc/sudoers.d/refley-deploy
```

**Prérequis DNS** : `refley.joefr.cloud` doit pointer (enregistrement A) sur
l'IP du VPS *avant* de lancer certbot.

## Étape 2 — Clé SSH de déploiement *(à faire sur votre poste)*

⚠️ **Générez cette clé vous-même. La clé privée ne doit jamais transiter par
une conversation, ni être écrite dans le dépôt.**

```bash
ssh-keygen -t ed25519 -f ~/.ssh/refley_deploy -N "" -C "github-actions-refley"

# clé PUBLIQUE → sur le serveur
ssh-copy-id -i ~/.ssh/refley_deploy.pub deploy@<IP-DU-VPS>

# clé PRIVÉE → uniquement dans les secrets GitHub (étape 3)
cat ~/.ssh/refley_deploy
```

## Étape 3 — Secrets et variables GitHub

**Settings → Secrets and variables → Actions**

Onglet **Secrets** :

| Nom | Valeur |
|---|---|
| `SSH_HOST` | IP ou nom d'hôte du VPS |
| `SSH_USER` | `deploy` |
| `SSH_PRIVATE_KEY` | contenu de `~/.ssh/refley_deploy` (clé privée) |
| `SSH_PORT` | *(facultatif, défaut 22)* |

Onglet **Variables** :

| Nom | Valeur |
|---|---|
| `APP_DIR` | `/var/www/refley` |
| `APP_URL` | `https://refley.joefr.cloud` |
| `HEALTH_URL` | `https://refley.joefr.cloud/up` |
| `PHP_BIN` | `php8.4` |
| `PHP_FPM_SERVICE` | `php8.4-fpm` |

> `HEALTH_URL` doit être l'URL **publique** quand le serveur héberge plusieurs
> sites : `http://127.0.0.1/up` tomberait sur le vhost par défaut.

## Étape 4 — Protection de branche

**Settings → Branches → Add rule** sur `main` :

- ✅ Require status checks to pass → cocher **« CI réussie »** (cette seule case)
- ✅ Require branches to be up to date before merging

C'est ce qui rend la CI réellement bloquante.

## Étape 5 — Premier déploiement

1. Onglet **Actions** → workflow **Deploy** → **Run workflow**.
2. Vérifier `https://refley.joefr.cloud/up` → 200.
3. Puis fusionner un changement anodin sur `main` pour valider le mode continu.

---

## Modes de déclenchement

`.github/workflows/deploy.yml` en propose trois (voir ses commentaires) :

1. **Continu** *(actif par défaut)* — chaque CI verte sur `main` déploie.
2. **Planifié** — décommenter le bloc `schedule:` (cron **UTC**) ; le workflow
   déploie alors le dernier commit de `main` validé par la CI. Un `main` rouge
   garde simplement la version de la veille.
3. **Manuel** — bouton *Run workflow*, toujours disponible.

## Rollback

Automatique : si `/up` ne répond pas 200 après le build, `deploy.sh` revient
au commit précédent et reconstruit.

Manuel, sur le serveur :

```bash
cd /var/www/refley
./deploy/rollback.sh                # version précédente
./deploy/rollback.sh <sha-ou-tag>   # version précise
```

⚠️ **Les migrations ne sont jamais annulées automatiquement** (risque de perte
de données). Si le schéma a changé, restaurez depuis
`storage/app/backups/db-*.sql.gz`, créé avant chaque migration.

## Sauvegardes

`deploy.sh` sauvegarde la base avant chaque migration, **sur le serveur** —
ce qui ne protège pas d'un serveur mort. Ajoutez une copie **hors serveur** :

```bash
# à adapter et mettre en cron nocturne
mysqldump --defaults-extra-file=/root/.refley.cnf refley | gzip \
  > /tmp/refley-$(date +%F).sql.gz
# puis rsync/rclone vers un stockage distant
```

## Dépannage

| Symptôme | Piste |
|---|---|
| Deploy échoue sur la connexion SSH | `SSH_HOST`/`SSH_USER` ; clé publique bien posée sur le serveur |
| `/up` ne répond pas 200 | `nginx -t`, racine du vhost sur `/public`, droits `storage/` |
| Photos/logos cassés | `php artisan storage:link`, puis `chown -R www-data storage` |
| Erreur 500 après déploiement | `php artisan optimize:clear` puis relire `storage/logs/` |
| CI rouge sur la couverture | le seuil est à 90 % : ajouter des tests ou ajuster `--min` dans `ci.yml` |
| Le déploiement ne part pas | la CI de `main` est-elle verte ? le mode continu n'agit que sur succès |

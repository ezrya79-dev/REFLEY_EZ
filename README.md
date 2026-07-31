# Refley

Refley est une application web bilingue (FR/EN) d'espace d'équipe : comptes
nominatifs avec rôles et permissions atomiques, identité de marque configurable
à chaud (nom, couleurs, logo), et espace personnel (photo, mot de passe, thème)
pour chaque utilisateur.

- **Domaine (production)** : `refley.joefr.cloud`
- **Pile** : PHP 8.4 · Laravel · Blade + Alpine.js · Vite · SQLite/MySQL
- **Qualité** : Pest (couverture bloquante ≥ 90 % en CI) · Pint · PHPStan
  (niveau 5 + Larastan) · Rector

## Démarrer

```bash
composer setup   # install + .env + clé + migrations + npm + build
composer dev     # serveur + queue + logs + Vite en parallèle
```

Premier compte administrateur (la base démarre vide) :

```bash
php artisan refley:admin
```

Jeu de démonstration (jamais en production) :

```bash
php artisan db:seed --class=DemoSeeder
# admin@demo.test / manager@demo.test / membre@demo.test — Demo-Password-1234
```

## Qualité

```bash
composer quality   # pint --test + phpstan + pest
composer fix       # pint + rector
```

## Rôles et permissions

Le code ne compare jamais un rôle : il interroge une permission atomique via
les Gates (`users.manage`, `settings.manage`, `connectors.manage`,
`metrics.view`, `audit.view`). La matrice rôle → permissions vit dans
`app/Enums/UserRole.php` et s'affiche dans l'écran Réglages.

| Permission | Admin | Manager | Membre |
|---|---|---|---|
| `users.manage` | ✓ | — | — |
| `settings.manage` | ✓ | — | — |
| `connectors.manage` | ✓ | — | — |
| `metrics.view` | ✓ | ✓ | — |
| `audit.view` | ✓ | ✓ | — |

## Architecture

- `app/Services/` — logique métier (réglages, marque, comptes, avatars)
- `app/Data/` — DTO immuables
- `app/Enums/` — enums de domaine (`Permission`, `UserRole`, `Theme`)
- `app/Contracts/` — interfaces vers les systèmes externes (simulables en test)
- Les contrôleurs restent minces : valider, déléguer à un service, répondre.

Les compétences réutilisables qui ont guidé la construction vivent dans
`.claude/skills/` et sont chargées automatiquement par les sessions Claude.

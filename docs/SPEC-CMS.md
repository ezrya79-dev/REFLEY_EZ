# Spécification — Gestion de contenu Refley (« micro-CMS »)

> Statut : **spécification à valider avant implémentation**
> Périmètre : édition des textes et des images du site depuis la console
> d'administration, sans déploiement, en FR/EN.
> Principes directeurs : le plus léger possible, le plus simple possible,
> le plus sûr possible. La *structure* des pages reste du code ; le
> *contenu* devient de la donnée.

---

## 1. État des lieux (ce qui existe aujourd'hui)

| Aspect | Aujourd'hui | Modifiable sans déploiement ? |
|---|---|---|
| Nom de l'application | Réglages → Marque (base de données) | ✅ Oui |
| Couleur d'accent (palette + hex libre) | Réglages → Marque | ✅ Oui |
| Logo + favicons + icônes PWA dérivées | Réglages → Marque | ✅ Oui |
| Identité d'expéditeur e-mail | Réglages → Marque | ✅ Oui |
| Photo de profil de chaque utilisateur | Mon profil | ✅ Oui (chacun la sienne) |
| **Textes des écrans** (libellés, titres, messages) | Fichiers `lang/fr/*.php` + `lang/en/*.php` | ❌ Non — modification de code + déploiement |
| **Images de contenu** (illustrations, photos de pages) | N'existe pas | ❌ Rien à gérer pour l'instant |
| **Pages éditoriales** (accueil public, à-propos, mentions légales, confidentialité, aide…) | N'existent pas | ❌ — |
| Menus / navigation | Codés dans le layout, pilotés par les permissions | ❌ (choix assumé, voir §2) |
| SEO (title/description/og:image par page) | N'existe pas | ❌ — |
| Bibliothèque de médias | N'existe pas | ❌ — |
| Historique / retour arrière sur un contenu | N'existe pas | ❌ — |

**Diagnostic.** La console actuelle couvre l'*identité* (marque) mais pas le
*contenu*. Refley est aujourd'hui une application à écrans fonctionnels
(tableau de bord, utilisateurs, profil, réglages) dont les textes sont des
traductions figées dans le code. Il manque une couche de contenu éditorial :
c'est normal à ce stade, et c'est l'objet de cette spec.

---

## 2. Ce qu'on ne construira PAS (le « challenge » léger/simple/sûr)

Décisions d'architecture, avec les alternatives écartées et pourquoi :

1. **Pas de CMS complet** (Statamic, Filament, WordPress headless…).
   Poids énorme, deuxième paradigme d'admin à maintenir, surface d'attaque
   multipliée, et 90 % des fonctionnalités inutiles ici.
2. **Pas de page builder / drag-and-drop.** Créer des pages arbitraires avec
   des blocs libres = complexité exponentielle (layout, responsive, thème
   sombre, i18n par bloc) et résultat incohérent avec le design system.
   **La structure d'une page est du Blade ; seuls les emplacements déclarés
   sont éditables.** Une nouvelle page = une petite PR (rare), un nouveau
   texte = l'admin (fréquent). On optimise le cas fréquent.
3. **Pas de HTML libre dans l'admin.** Un champ WYSIWYG HTML est la première
   porte d'entrée XSS d'une application. Le texte riche sera du **Markdown
   restreint**, rendu côté serveur avec échappement du HTML brut.
4. **Pas de workflow brouillon/validation/publication.** Sur-ingénierie pour
   une petite équipe : on publie immédiatement, et la sécurité vient des
   **révisions** (retour arrière en un clic). Un « draft » pourra s'ajouter
   plus tard sans casser le modèle.
5. **Pas d'édition des menus en base.** La navigation dépend des permissions
   et des routes : la piloter en données créerait des liens morts. Elle reste
   du code.
6. **Pas d'édition des libellés fonctionnels** (boutons, erreurs de
   validation, en-têtes de tableaux). Ils restent dans `lang/` : ils font
   partie du produit, pas du contenu éditorial. Critère de tri simple —
   *« est-ce qu'on voudra le reformuler sans développeur ? »* Oui → contenu.
   Non → traduction dans le code.

---

## 3. Concepts (3 objets, pas plus)

```
Page (registre en code)          ex. « accueil », « a-propos », « mentions-legales »
 └── Zone de contenu (déclarée dans le gabarit Blade)
      ├── type text      : une ligne, sans mise en forme
      ├── type markdown  : texte riche restreint (titres, gras, listes, liens)
      └── type image     : référence vers un média de la bibliothèque
Média (bibliothèque d'images partagée, dérivés responsive automatiques)
Révision (historique par zone, retour arrière)
```

- **Le gabarit déclare, l'admin remplit.** Une zone n'existe que si un
  composant `<x-content …>` la déclare dans une vue, **avec sa valeur par
  défaut dans le code**. Une base vide affiche donc toujours un site complet
  (même philosophie que `SettingsService` → repli sur `config/`).
- **L'écran d'admin est généré** à partir du registre des pages et des zones
  découvertes : aucune saisie de « clé » à la main, aucun contenu orphelin.

---

## 4. Modèle de données

### 4.1 `content_blocks`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `page` | string | slug du gabarit, ex. `accueil` |
| `key` | string | ex. `hero.titre` |
| `locale` | string(5) | `fr` ou `en` |
| `type` | string | `text` \| `markdown` \| `image` |
| `value` | text (JSON) | texte, markdown source, ou `{"media_id": 12, "alt": "…"}` |
| `updated_by` | FK users nullable (nullOnDelete) | traçabilité |
| timestamps | | |

Unique : (`page`, `key`, `locale`).

### 4.2 `media`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `path` | string | original ré-encodé, disque `public`, dossier `media/` |
| `alt_fr` / `alt_en` | string nullable | accessibilité + SEO |
| `width` / `height` / `size_bytes` | int | renseignés à l'upload |
| `checksum` | string(64) | sha256 — déduplication des uploads identiques |
| `uploaded_by` | FK users nullable (nullOnDelete) | |
| timestamps | | |

Dérivés générés à l'upload (comme `BrandIconService`) : largeurs **480, 960,
1600 px** en WebP → `media/{id}/w480.webp`, etc. Jamais de redimensionnement
à la volée pendant une requête.

### 4.3 `content_revisions`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `content_block_id` | FK cascadeOnDelete | |
| `value` | text (JSON) | valeur **précédente** |
| `updated_by` | FK users nullable | auteur du remplacement |
| `created_at` | timestamp | |

Rétention : **20 révisions max par bloc** (élagage au moment de l'écriture —
pas de tâche planifiée à maintenir).

---

## 5. Registre des pages (code, pas base)

`config/content.php` :

```php
return [
    'pages' => [
        'accueil' => ['title' => 'content.pageAccueil', 'route' => 'home'],
        'a-propos' => ['title' => 'content.pageApropos', 'route' => 'about'],
        'mentions-legales' => ['title' => 'content.pageMentions', 'route' => 'legal'],
        'confidentialite' => ['title' => 'content.pagePrivacy', 'route' => 'privacy'],
    ],
];
```

Les zones de chaque page sont découvertes en **scannant les déclarations**
des gabarits (voir §7.2) et mises en cache. Ajouter une page = 1 entrée ici
+ 1 vue Blade + 1 route. C'est volontairement une opération développeur.

Chaque page porte aussi deux zones SEO implicites : `seo.title` (text) et
`seo.description` (text), plus `seo.image` (image, optionnelle) — rendues
dans `partials/head.blade.php`.

---

## 6. Services (API interne)

### 6.1 `ContentService` (singleton, calqué sur `SettingsService`)

```php
// Lecture : mémo par requête → cache persistant → BDD → défaut du gabarit.
// Repli de locale : en → fr si la traduction EN n'existe pas.
public function get(string $page, string $key, string $locale, ContentType $type, string|array|null $default = null): mixed;

// Écriture : validation par type, révision de l'ancienne valeur, purge cache.
public function set(string $page, string $key, string $locale, ContentType $type, mixed $value, User $author): void;

// Retour arrière : restaure une révision (l'état actuel devient lui-même une révision).
public function revert(ContentRevision $revision, User $author): void;
```

Rendu Markdown : `league/commonmark` en mode **sûr** —
`html_input: 'escape'`, `allow_unsafe_links: false`, extensions limitées à
la syntaxe de base (titres h2/h3, gras, italique, listes, liens, citations).
Le HTML produit est mis en cache avec le bloc (on stocke la source, on cache
le rendu).

### 6.2 `MediaService`

```php
public function store(UploadedFile $file, User $author): Media;   // ré-encode (EXIF supprimé), dérive 480/960/1600 WebP, déduplique par checksum
public function delete(Media $media): void;                       // refusé si le média est référencé par un content_block
public function usages(Media $media): Collection;                 // blocs qui le référencent
```

Contraintes upload : `jpeg|png|webp`, 8 Mo max, dimensions max 8000×8000
(protection « decompression bomb » avant décodage GD via `getimagesize`).

---

## 7. Composants Blade (déclaration des zones)

### 7.1 Usage dans un gabarit

```blade
{{-- Texte simple --}}
<h1><x-content page="accueil" key="hero.titre">Bienvenue sur Refley</x-content></h1>

{{-- Texte riche --}}
<x-content-markdown page="accueil" key="hero.corps">
Refley centralise **votre équipe** : comptes, rôles et espace personnel.
</x-content-markdown>

{{-- Image (rend <picture> + srcset des dérivés + alt localisé) --}}
<x-content-image page="accueil" key="hero.visuel" class="hero-img" />
```

- Le slot est la **valeur par défaut versionnée dans git** — le site n'est
  jamais vide, et la valeur par défaut sert de documentation de la zone.
- La locale rendue est celle de la requête (`app()->getLocale()`), repli FR.
- `<x-content-image>` sans média choisi ne rend rien (ou un slot par défaut).

### 7.2 Découverte des zones pour l'admin

Une commande `php artisan content:scan` (exécutée au déploiement et
appelable depuis l'écran d'admin) parcourt `resources/views`, extrait les
déclarations `<x-content*>` (page, key, type, défaut) et écrit le registre
dans `bootstrap/cache/content-map.php`. L'admin liste exactement ces zones.
Zéro table de « définition », zéro dérive entre code et base.

---

## 8. Console d'administration

### 8.1 Permissions (ajout à l'enum existante)

- `content.manage` — éditer les contenus. Attribuée à **Admin + Manager**
  (le manager devient éditeur du site sans accéder aux réglages système).
- `media.manage` — bibliothèque (upload/suppression). Admin + Manager.

Mise à jour de `Permission`, `UserRole::permissions()`, matrice affichée et
tests — mécanique déjà en place.

### 8.2 Écrans (routes sous `auth` + gate `content.manage`)

```
GET  /contenu                         → liste des pages du registre (+ bouton « rescanner »)
GET  /contenu/{page}                  → formulaire de la page, onglets FR | EN
PUT  /contenu/{page}                  → enregistre les zones soumises (transaction)
GET  /contenu/{page}/historique/{key} → révisions de la zone (diff simple avant/après)
POST /contenu/revisions/{revision}    → restaurer cette révision

GET    /medias                        → grille de la bibliothèque (recherche, tri par date)
POST   /medias                        → upload (retour JSON pour le sélecteur)
PUT    /medias/{media}                → éditer alt FR/EN
DELETE /medias/{media}                → supprimer (409 si utilisé, avec la liste des usages)
```

Formulaire de page :
- zone `text` → `<input>` ;
- zone `markdown` → `<textarea>` avec **aperçu** (rendu serveur via un
  endpoint `POST /contenu/apercu`, pas de lib JS de markdown côté client) ;
- zone `image` → vignette actuelle + bouton « choisir » ouvrant le sélecteur
  de médias (modal existante du design system) + champ alt ;
- lien « historique » par zone.

Chaque page publique gagne pour les admins/éditeurs connectés un lien
discret « Modifier cette page » vers `/contenu/{page}` (pas d'édition
inline : simplicité).

### 8.3 Pages publiques livrées avec le module

Routes **publiques** (hors middleware `auth`), gabarits sur le design
system : `/` (accueil marketing → remplace la redirection actuelle vers le
tableau de bord pour les visiteurs non connectés ; les connectés continuent
vers `/tableau-de-bord`), `/a-propos`, `/mentions-legales`,
`/confidentialite`. Sélecteur de langue FR/EN pour visiteurs anonymes
(session), robots/sitemap simples.

---

## 9. Sécurité (exigences bloquantes)

1. **Aucun HTML brut ne traverse jamais** : markdown rendu avec
   `html_input: escape` ; les zones `text` sont échappées par Blade (`{{ }}`).
   Test automatisé : injecter `<script>` dans chaque type de zone et
   vérifier l'échappement dans la réponse.
2. **Uploads** : mêmes règles que les avatars — validation MIME serveur,
   ré-encodage GD (supprime EXIF et charges utiles), taille et dimensions
   plafonnées, noms de fichiers générés (jamais le nom client), servis
   depuis `storage` public sans exécution possible.
3. **Autorisation** : toutes les routes d'écriture derrière
   `can:content.manage` / `can:media.manage` ; tests 403 pour le rôle membre.
4. **Traçabilité** : `updated_by` sur chaque bloc + révisions ; entrée dans
   le canal de log `auth` → sera repris par `audit-gdpr-toolkit` (phase 2).
5. **Anti-CSRF** : formulaires classiques Blade (déjà couvert).
6. **Liens markdown** : `allow_unsafe_links: false` (pas de `javascript:`),
   `rel="noopener"` sur les liens externes.
7. **Suppression de média** : refusée tant qu'un bloc l'utilise (contrainte
   applicative + test) — pas d'image cassée possible en production.

---

## 10. Performance

- Lecture : mémo par requête + `Cache::rememberForever` par
  (`page`,`key`,`locale`) ; écriture = purge ciblée. Une page publique ne
  fait **aucune** requête contenu une fois le cache chaud.
- Le HTML markdown est caché rendu (pas de parsing par requête).
- Les dérivés d'images sont pré-générés à l'upload ; `<picture>` +
  `loading="lazy"` par défaut.

---

## 11. Tests (critères d'acceptation)

**Unitaires** — `ContentService` (repli défaut/locale, purge cache,
révisions plafonnées à 20, revert) ; `MediaService` (dérivés 480/960/1600,
déduplication checksum, refus de suppression si utilisé) ; rendu markdown
sûr (`<script>`, `javascript:` neutralisés).

**Feature** — matrice de permissions (admin/manager 200, membre 403,
visiteur redirigé) ; édition d'une zone visible immédiatement sur la page
publique ; onglet EN vide → repli FR ; historique + restauration ;
upload/alt/suppression média avec cas 409 ; pages publiques accessibles
sans compte dans les deux langues ; `content:scan` détecte une zone
ajoutée dans un gabarit.

**Design system** — les nouvelles pages publiques passent les tests
existants (aucun hex hors design system, rendu clair + sombre).

---

## 12. Découpage d'implémentation (petits commits)

1. `media` : migration + modèle + `MediaService` + tests (réutilise les
   patterns de `BrandIconService`).
2. `content_blocks`/`content_revisions` : migrations + `ContentService` +
   markdown sûr + tests.
3. Composants `<x-content>`, `<x-content-markdown>`, `<x-content-image>` +
   `content:scan` + tests.
4. Admin `/contenu` (+ aperçu, historique) et `/medias` + permissions
   `content.manage`/`media.manage` + traductions FR/EN + tests.
5. Pages publiques (accueil, à-propos, mentions, confidentialité) + SEO +
   sélecteur de langue visiteur + tests.

Dépendance nouvelle : `league/commonmark` uniquement.
Volume estimé : ~3 migrations, 2 services, 3 composants, 2 contrôleurs,
1 commande, ~35 tests. Aucun changement aux modules existants hors ajout
des deux permissions.

---

## 13. Questions ouvertes (à trancher avant le commit 1)

1. **Refley a-t-il un site public ?** La spec suppose oui (accueil
   marketing + pages légales). Si l'app est 100 % privée, le module se
   réduit aux mêmes écrans appliqués à des pages internes (aide, annonces)
   — le modèle ne change pas, seule la liste du registre change.
2. **Le rôle Manager doit-il éditer le contenu ?** Spec : oui. Sinon,
   retirer `content.manage`/`media.manage` de sa liste (1 ligne + tests).
3. **Faut-il une page « aide » interne dès le lot 5 ?** Coût marginal
   (1 entrée de registre + 1 gabarit).

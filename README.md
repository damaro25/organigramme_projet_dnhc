# Organigramme et suivi des projets — DNH

Application web de la **Direction Nationale des Hydrocarbures** (Ministère de l'Assainissement, de l'Hydraulique et des Hydrocarbures) :

- **Organigramme interactif** du personnel, en arbre généalogique (d3-org-chart) : DNH, services rattachés, DNAH, divisions, sections, chargés d'études et services déconcentrés ; recherche d'un agent, zoom, dépliage par branche.
- **Fiche de chaque agent** au clic sur sa photo : coordonnées, projets affectés avec leurs étapes et leur taux d'exécution ; pour un chef, les projets consolidés de sa structure.
- **Administration** : agents et photos (recadrage dans le navigateur), projets et étapes pondérées, structures, comptes utilisateurs, notifications.
- **Tableau de bord** (indicateurs, graphique des divisions, classement des sections), **exports PDF** et **journal des modifications**.

L'interface est entièrement en français.

## Pile technique

| Côté serveur | Côté navigateur |
| --- | --- |
| Laravel 13, PHP 8.3+ | Blade, Livewire 3, Alpine.js, Tailwind CSS |
| spatie/laravel-permission (rôles) | d3-org-chart (arbre) |
| spatie/laravel-activitylog (journal) | Cropper.js (recadrage des photos) |
| intervention/image v3 (photos WebP) | SortableJS (ordre des étapes et structures) |
| barryvdh/laravel-dompdf (PDF) | Chart.js (tableau de bord) |

## Prérequis

- PHP **8.3 ou plus** avec les extensions `gd` (avec prise en charge **WebP**), `mbstring`, `pdo_mysql` (ou `pdo_sqlite`), `fileinfo`, `zip`
- Composer 2
- Node.js 20 ou plus et npm
- MySQL 8 / MariaDB 10.6 ou plus (SQLite convient pour un essai)

Vérifier la prise en charge WebP : `php -r 'var_dump(gd_info()["WebP Support"] ?? false);'`

## Installation

```bash
git clone git@github.com:damaro25/organigramme_projet_dnhc.git
cd organigramme_projet_dnhc

# 1. Dépendances
composer install
npm install

# 2. Configuration
cp .env.example .env
php artisan key:generate
```

Renseigner ensuite la base de données dans `.env` :

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dnh_organigramme
DB_USERNAME=…
DB_PASSWORD=…
```

> Pour un essai rapide sans MySQL : `DB_CONNECTION=sqlite`, supprimer les autres lignes `DB_*`, puis `touch database/database.sqlite`.

```bash
# 3. Base de données : tables + organigramme, comptes et projets de démonstration
php artisan migrate --seed

# 4. Lien public vers les photos des agents (storage/app/public → public/storage)
php artisan storage:link

# 5. Compilation des fichiers CSS et JavaScript
npm run build

# 6. Lancement
php artisan serve
```

L'application est alors disponible sur <http://127.0.0.1:8000> : l'organigramme public s'affiche à l'accueil, l'administration est sur `/admin` (connexion sur `/login`).

### Notifications

Quand une étape est cochée, le responsable du projet reçoit une notification (dans l'application et par e-mail). Elles passent par la file d'attente :

```bash
php artisan queue:work
```

En développement, `MAIL_MAILER=log` écrit les e-mails dans `storage/logs/laravel.log`. En production, configurer le serveur SMTP (`MAIL_*`) et faire tourner `queue:work` sous un superviseur (Supervisor, systemd).

### Développement

`composer run dev` lance en une commande le serveur, la file d'attente, les journaux et Vite (rechargement à chaud).

## Comptes de démonstration

Mot de passe commun : **`Dnh@2026`**

| E-mail | Rôle | Agent rattaché | Ce qu'il peut faire |
| --- | --- | --- | --- |
| `admin@dnh.local` | Administrateur | Chef du service Informatique | Tout : agents, projets, structures, comptes, journal |
| `dnh@dnh.local` | Direction | Directeur National (DNH) | Tout consulter, valider les projets, journal |
| `dnah@dnh.local` | Direction | Directrice Nationale Adjointe (DNAH) | Idem |
| `chef1.division@dnh.local` | Chef de structure | Chef de la Division Planification / Économie | Gérer agents, photos et projets de sa division et de ses sections |
| `chef2.division@dnh.local` | Chef de structure | Cheffe de la Division Aval Pétrolier | Gérer agents, photos et projets de sa division et de ses sections |
| `chef3.division@dnh.local` | Chef de structure | Chef de la Division Amont Pétrolier | Gérer agents, photos et projets de sa division et de ses sections |
| `chef.section@dnh.local` | Chef de structure | Cheffe de la section SSTD | Gérer agents, photos et projets de sa section |
| `charge.etudes@dnh.local` | Chargé d'études | Premier chargé d'études de la SSTD | Cocher les étapes de ses projets, modifier sa propre photo |
| `consultation@dnh.local` | Consultation | Chef du SAF | Lecture seule |

L'organigramme public, les fiches des agents et les exports PDF sont accessibles sans connexion.

> ⚠️ Les noms, e-mails et téléphones des données de démonstration sont fictifs. Avant la mise en production, remplacer les données et **changer ou supprimer ces comptes** (menu *Utilisateurs*).

## Règles de calcul

- **Taux d'exécution d'un projet** = somme des poids des étapes terminées ÷ somme des poids de toutes les étapes × 100, arrondi à l'entier. Un projet sans étape est à 0 %.
- **Taux moyen d'un agent** = moyenne simple des taux de ses projets non archivés.
- **Taux consolidé d'une structure** = moyenne simple des taux des projets non archivés de la structure et de ses sous-structures.
- **En retard** : la date de fin prévue est dépassée et le taux est inférieur à 100 %.
- **Couleurs** : rouge sous 30 %, orange de 30 à 70 %, vert au-delà.

## Fonctionnalités par écran

| Écran | Adresse |
| --- | --- |
| Organigramme public | `/` |
| Export PDF de l'organigramme (A3 paysage) | `/organigramme.pdf` |
| Fiche PDF d'un agent | `/agents/{id}/fiche.pdf` |
| Tableau de bord | `/admin` |
| Organigramme en mode édition | `/admin/organigramme` |
| Agents, projets, structures, utilisateurs | `/admin/agents`, `/admin/projets`, `/admin/structures`, `/admin/utilisateurs` |
| Journal des modifications | `/admin/journal` |

## Tests

```bash
php artisan test
```

Les tests utilisent une base SQLite en mémoire (voir `phpunit.xml`) : ils ne touchent pas à la base de développement. Ils couvrent notamment :

- le calcul du taux d'exécution, des retards et des taux consolidés (`tests/Feature/TauxExecutionTest.php`) ;
- les permissions par rôle (`tests/Feature/Admin/MatriceRolesTest.php`, `AutorisationsTest.php`) ;
- l'envoi, le remplacement et la suppression des photos (`tests/Feature/Admin/PhotoAgentTest.php`) ;
- l'organigramme public, le tableau de bord, les exports PDF et le journal.

## Charte graphique

L'interface applique la [charte graphique du Gouvernement de la République de Guinée](https://dgd.gov.gn/wp-content/uploads/2023/10/CHARTE-GRAPHIQUE-RG.pdf) (Cellule de Communication du Gouvernement, 2021) :

- **Couleurs** : rouge `#ce1126`, jaune `#fcd116`, vert `#009460` (classes Tailwind `guinee-*`), avec du blanc et du gris. La couleur principale de l'application (`dnh-*`, `tailwind.config.js`) est le vert national et ses nuances foncées.
- **Police** : Century Gothic, installée avec Windows et Microsoft Office. Sur un poste qui ne l'a pas, le navigateur prend une police proche (URW Gothic) ou la police système. Les PDF restent en DejaVu Sans, la seule police fournie avec dompdf qui couvre tous les accents.
- **En-tête** : armoiries en haut à gauche sur fond blanc, « République de Guinée », la devise « Travail – Justice – Solidarité », le Ministère et la Direction, puis la bande tricolore à bandes égales (`resources/views/components/bandeau-republique.blade.php` et `tricolore.blade.php`). Les PDF reprennent la bande et la devise.
- **Pied de page** : lien vers le portail officiel du Gouvernement, `gouvernement.gov.gn`.
- **Divisions** (données de démonstration) : Planification / Économie en vert, Aval en rouge, Amont en jaune foncé (`#a37f00`, plus lisible que le jaune du drapeau en texte sur fond blanc).

## Personnalisation

- **Armoiries** : déposer le fichier officiel fourni par la Cellule de Communication du Gouvernement dans `public/images/armoiries.png` (ou `armoiries.svg`). Il remplace automatiquement l'emplacement provisoire `public/images/logo-ministere.svg` dans l'en-tête, dans les PDF et dans l'icône des onglets du navigateur (en attendant, l'onglet affiche le drapeau national : `public/images/icone-drapeau.svg` et `public/favicon.ico`). La charte interdit de déformer, incliner, recolorer ou ombrer les armoiries, et d'écrire au-dessus ou en dessous.
- **Avatar par défaut** : `public/images/avatar-defaut.svg` (écran) et `avatar-defaut.png` (PDF).
- **Couleurs des divisions** : menu *Structures* de l'administration. Une base déjà remplie garde ses anciennes couleurs : les modifier dans ce menu, ou relancer `php artisan migrate:fresh --seed` sur une base de démonstration.

## Pièces jointes

- **Documents du projet** : sur la fiche du projet, à la création comme en modification. On peut ajouter des fichiers en plusieurs fois ou les glisser-déposer, et retirer un fichier avant l'enregistrement.
- **Justificatifs d'une étape** : sous chaque étape cochée, bouton *Joindre des justificatifs* (plusieurs fichiers à la fois). Peuvent joindre : les personnes qui peuvent cocher l'étape. Peuvent supprimer : l'auteur du fichier ou un gestionnaire du projet.
- **Formats** : PDF, Word, Excel, PowerPoint, OpenDocument et photos (JPG, PNG, WEBP), 10 Mo maximum par fichier, 10 fichiers par envoi.
- **Visualisation** : le bouton *Voir* ouvre les PDF et les photos dans une visionneuse, sans téléchargement ; les autres formats se téléchargent. Fichiers réservés aux comptes connectés.
- **Journal** : chaque ajout ou suppression de fichier y est tracé.

**Limites de PHP.** Par défaut, PHP refuse les fichiers de plus de 2 Mo (`upload_max_filesize`) et les envois de plus de 8 Mo au total (`post_max_size`), avant même que l'application ne les voie. L'application affiche la limite réelle sous chaque zone d'envoi. Pour accepter 10 fichiers de 10 Mo, dans le `php.ini` (indiqué par `php --ini`) :

```ini
upload_max_filesize = 10M
post_max_size = 110M
```

Puis relancer `php artisan serve` (ou le serveur web).

## Mise en production (rappel)

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Avec `APP_ENV=production` et `APP_DEBUG=false` dans `.env`, sans `--seed` si les données réelles sont déjà saisies.

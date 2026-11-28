# Dossier d'Exploitation Applicative — CinéTech (TMA)

> Runbook de Tierce Maintenance Applicative (TMA) du projet **CinéTech**.
> Document vivant : à mettre à jour à chaque incident, évolution ou changement d'environnement.

---

## Sommaire

1. [Présentation & Contacts](#1-présentation--contacts)
2. [Engagements Contractuels (SLA)](#2-engagements-contractuels-sla)
3. [Environnement Technique (Stack)](#3-environnement-technique-stack)
4. [Procédure d'Installation Locale](#4-procédure-dinstallation-locale)
5. [Stratégie Git & Lotissement (SemVer)](#5-stratégie-git--lotissement-semver)
6. [Definition of Done (DoD)](#6-definition-of-done-dod)
7. [Journal des Diagnostics (RCA — Root Cause Analysis)](#7-journal-des-diagnostics-rca--root-cause-analysis)

---

## 1. Présentation & Contacts

| Élément | Information |
| --- | --- |
| **Application** | CinéTech — Catalogue culturel et avis de films (Symfony) |
| **Dépôt GitHub** | `lotfichabane50-crypto/cinetech-Souhail` |
| **Référente Métier** | Claire Moreau — Responsable Exploitation & Marketing |
| **Équipe TMA** | Prestataire Tierce Maintenance Applicative (développement, exploitation, qualité) |

### Périmètre TMA

La prestation couvre les quatre maintenances, sur le **code source**, les **dépendances** et les **données applicatives** :

- **Corrective** : traitement des anomalies et incidents de production.
- **Évolutive** : ajout ou adaptation de fonctionnalités (lotissement en versions mineures).
- **Adaptative** : mise à jour du socle technique (PHP, Symfony, dépendances, sécurité).
- **Préventive** : audit de dépendances, analyse statique, surveillance des logs et de la performance.

---

## 2. Engagements Contractuels (SLA)

| Priorité | Sévérité (label) | GTI (Garantie de Temps d'Intervention) | GTR (Garantie de Temps de Rétablissement) |
| --- | --- | --- | --- |
| **P0 — Incident bloquant** | `sev:blocker` | **< 1 heure** | **< 4 heures** |
| P1 — Majeur | `sev:major` | À définir contractuellement | À définir contractuellement |
| P2 — Mineur | `sev:minor` | À définir contractuellement | À définir contractuellement |

### Règle d'ingénierie — Hotfix vs Correction définitive

Tout **correctif palliatif (Hotfix)** déployé en urgence doit **obligatoirement** être suivi de l'ouverture d'un **ticket de correction définitive** (analyse de cause racine / RCA, voir [section 7](#7-journal-des-diagnostics-rca--root-cause-analysis)).

Un hotfix ne clôt jamais un incident : il le **neutralise temporairement**, le temps de produire une correction pérenne, testée et versionnée.

---

## 3. Environnement Technique (Stack)

Stack détectée à la lecture de [`composer.json`](file:///c:/laragon/www/cinetech-Souhail/composer.json) :

| Composant | Version / Contrainte | Détail |
| --- | --- | --- |
| **PHP** | `>=8.4` (requis) | Plateformes cibles **PHP 8.4 / 8.5** |
| **Symfony** | `8.1.*` | Tous les bundles Symfony alignés sur la 8.1 |
| **Doctrine ORM** | `doctrine/orm ^3.8` | `doctrine-bundle ^3.4`, `doctrine-migrations-bundle ^4.0` |
| **Base de données** | **MySQL** | Dev local (Laragon) ; un provisionnement **PostgreSQL 16** est fourni via [`compose.yaml`](file:///c:/laragon/www/cinetech-Souhail/compose.yaml) |
| **Twig** | `twig/twig ^2.12\|^3.0` | + `twig/extra-bundle` |
| **Messenger** | `symfony/doctrine-messenger 8.1.*` | Transport `MESSENGER_TRANSPORT_DSN=doctrine://default` |
| **Mailer** | `symfony/mailer 8.1.*` | `MAILER_DSN=null://null` (dev) |
| **Sécurité** | `symfony/security-bundle 8.1.*` | Authentification |
| **Fixtures** | `doctrine/doctrine-fixtures-bundle ^4.3` + `fakerphp/faker ^1.24` | Jeu de données de démo (`src/DataFixtures/AppFixtures.php`) |
| **Front** | `symfony/asset-mapper`, `stimulus-bundle ^3.7`, `ux-turbo ^3.7` | Assets mappés + Stimulus / Turbo |

### Outils qualité

| Outil | État | Usage |
| --- | --- | --- |
| **PHPUnit** (`^13.4`) | ✅ Installé | Tests unitaires / fonctionnels — config [`phpunit.dist.xml`](file:///c:/laragon/www/cinetech-Souhail/phpunit.dist.xml) |
| **`composer audit`** | ✅ Disponible (commande Composer native) | Audit de sécurité des dépendances |
| **PHPStan** | ⚠️ **À installer** | Analyse statique — `phpstan/phpdoc-parser ^2.4` est présent, mais l'outil `phpstan/phpstan` ne l'est pas encore |

> **Écarts à traiter (prérequis DoD)**
> - **PHP** : la contrainte `composer.json` est `>=8.4`. La roadmap **v1.1.0** prévoit la migration effective du parc vers **PHP 8.4/8.5** (fin de support de la version 8.2) — à coordonner avec la maintenance adaptative.
> - **PHPStan** : requis par la [Definition of Done](#6-definition-of-done-dod) mais absent de `require-dev`. À ajouter avant toute mise en `Done` : `composer require --dev phpstan/phpstan`.
> - **php-cs-fixer** : non installé (PHPUnit est configuré avec `failOnDeprecation`/`failOnNotice`/`failOnWarning`). Le respect PSR-12 est vérifié manuellement en revue de code ou via l'outillage à ajouter.

---

## 4. Procédure d'Installation Locale

### Prérequis

- PHP **8.4+** avec les extensions `ctype` et `iconv`
- [Composer](https://getcomposer.org/) 2.x
- [Symfony CLI](https://symfony.com/download) (pour `symfony server:start`)
- Un serveur **MySQL** (ou PostgreSQL 16 via le [`compose.yaml`](file:///c:/laragon/www/cinetech-Souhail/compose.yaml) fourni)

### Étapes

```bash
# 1. Cloner le dépôt
git clone https://github.com/lotfichabane50-crypto/cinetech-Souhail.git
cd cinetech-Souhail

# 2. Installer les dépendances PHP
composer install

# 3. Configurer l'environnement local
#    Créer .env.local (non versionné) à partir de .env et y définir les valeurs locales :
#      APP_SECRET=<valeur locale>
#      DATABASE_URL="mysql://<user>:<password>@127.0.0.1:3306/cinetech?serverVersion=8.0&charset=utf8mb4"
cp .env .env.local   # puis éditer .env.local

# 4. Créer la base de données
php bin/console doctrine:database:create

# 5. Jouer les migrations
php bin/console doctrine:migrations:migrate

# 6. Charger les fixtures de démonstration
php bin/console doctrine:fixtures:load

# 7. Démarrer le serveur de développement en arrière-plan
symfony server:start -d
```

### Base de test (`_test`) pour PHPUnit

```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction
php bin/phpunit
```

---

## 5. Stratégie Git & Lotissement (SemVer)

### Branches permanentes

| Branche | Rôle | Version |
| --- | --- | --- |
| **`main`** | Reflet **exact de la production** | `v1.0.0` |
| **`develop`** | Branche d'**intégration** de la prochaine version mineure | `v1.1.0` |

### Branches de travail

| Type | Branche | Part de | Cible de version |
| --- | --- | --- | --- |
| **Hotfix** (correctif urgent prod) | `hotfix/issue-XX` | `main` | `v1.0.1` (patch) |
| **Correction** | `fix/issue-XX` | `develop` | `v1.1.0` (mineure) |
| **Fonctionnalité** | `feat/issue-XX` | `develop` | `v1.1.0` (mineure) |

> `XX` correspond au **numéro d'issue GitHub** traité.

### Conventions

- **Conventional Commits** obligatoires : `fix(...)`, `feat(...)`, `docs(...)`, `refactor(...)`, `test(...)`.
  - Exemple : `fix(catalog): corrige le filtre par catégorie (#42)`
- **Mention obligatoire `Closes #XX`** dans la description de chaque **Pull Request**, afin d'auto-fermer l'issue liée au merge.
- Toute Pull Request est **relue** avant merge (voir DoD).

---

## 6. Definition of Done (DoD)

Aucune tâche ne peut passer en `Done` avant que **toutes** les cases suivantes ne soient cochées :

- [ ] Branche dédiée créée depuis `main` ou `develop`
- [ ] Code conforme aux standards **PSR-12** et relu via Pull Request
- [ ] Analyse statique sans erreur (**PHPStan**)
- [ ] Audit de sécurité des dépendances propre (`composer audit`)
- [ ] Test de non-régression écrit et validé (**PHPUnit** au vert avec base `_test`)

---

## 7. Journal des Diagnostics (RCA — Root Cause Analysis)

> **Modèle à compléter** lors de l'analyse d'un incident (correctif définitif suite à hotfix, ou anomalie en production).
> Dupliquer le bloc ci-dessous par incident.

---

### RCA-001 — Issue #13 : module Legacy « Rapport Box-Office » (HTTP 500)

| Champ | Valeur |
| --- | --- |
| **Date de détection** | `2026-11-28` |
| **Environnement** | `dev` (reproduction locale) — défaut générique sur toute instance |
| **Priorité / Sévérité** | `P0 / sev:blocker` (route inaccessible) |
| **Issue liée** | `#13` — Sauvetage du module Legacy |
| **Associé à un hotfix ?** | Non — correction définitive directe sur branche dédiée `fix/issue-13-legacy-report` |

**1. Contexte**

La route `GET /legacy/rapport` (`app_legacy_report`) du module Legacy de reporting box-office renvoyait une **erreur HTTP 500** dès le premier rendu. Le module est repris en TMA ; il repose sur `LegacyBoxOfficeService` (calculs badges / moyennes / tarifs) alimenté par des données de démonstration dans `LegacyReportController`.

Trois anomalies d'ingénierie *silencieuses* (règles du cahier des charges de Claire Moreau) ont également été identifiées en marge du plantage : elles ne produisaient pas d'erreur mais un résultat métier faux.

**2. Étapes pour reproduire**

1. Se placer sur la branche `develop` puis ouvrir la branche `fix/issue-13-legacy-report`.
2. Vérifier l'enregistrement de la route : `php bin/console debug:router` → `app_legacy_report  GET  /legacy/rapport`.
3. Appeler la route : `GET /legacy/rapport` (navigateur, ou `WebTestCase`).
4. Pour le contrôle métier : lire les valeurs affichées pour l'entrée sans tag (`Anatomie d'une chute`), l'entrée aux notes comportant un `null` (`Parasite`), et la simulation tarifaire « Senior (65 ans) » et « Étudiant membre (16 ans, abonné) ».

**3. Résultat obtenu vs Résultat attendu**

| Résultat obtenu (bug) | Résultat attendu (correct) |
| --- | --- |
| HTTP **500** (exception fatale) sur `/legacy/rapport` | HTTP **200** avec le rapport affiché |
| Tag `null` → `TypeError` (`strtoupper(): Argument #1 must be of type string, null given`) | Badge `[STANDARD]` |
| Notes `[]` → `DivisionByZeroError` ; `null` compté dans le diviseur | `null` ignoré ; aucune note valide → `0` |
| 65 ans non remisé ; abonné non cumulé | `age >= 65` remisé `-25 %`, cumulable avec `-20 %` abonné |

**4. Cause racine identifiée**

> Evidence collectée en **environnement `dev`** : trace d'exception PHP du `LegacyReportController`, relecture statique de `src/Service/LegacyBoxOfficeService.php` (le service porte `declare(strict_types=1)`), et couverture par tests unitaires ciblés.

- **Anomalie B1 — Typage / valeur nulle (bloquante, cause du 500)**
  - Élément déclencheur : entrée `['title' => "Anatomie d'une chute", 'tag' => null]`.
  - Localisation : `LegacyBoxOfficeService::formatBadge()` → `strtoupper($entry['tag'])`.
  - Analyse : en mode `strict_types`, passer `null` à `strtoupper()` (paramètre `string` non nullable) lève une `TypeError` → exception non gérée → HTTP 500. La donnée `tag` est nullable par conception.

- **Anomalie B2 — Calcul de moyenne sans garde-fou (silencieuse)**
  - Élément déclencheur : entrée `ratings => []` et entrée contenant `null`.
  - Localisation : `LegacyBoxOfficeService::calculateAverageRating()` → `array_sum($ratings) / count($ratings)`.
  - Analyse : division par zéro (`DivisionByZeroError`) quand la liste est vide ; et `count()` incluait les `null` (comptés au diviseur) alors que `array_sum()` les ignore → moyenne **faussée**.

- **Anomalie B3 — Règles tarifaires erronées (silencieuse)**
  - Élément déclencheur : âge = 65 et client à la fois senior/mineur **et** abonné.
  - Localisation : `LegacyBoxOfficeService::calculateTicketPrice()` → `if ($age < 18 || $age > 65) { … } elseif ($isMember) { … }`.
  - Analyse : la borne `> 65` **exclut** les 65 ans (attendu : `>= 65`) ; l'usage d'un `elseif` rend les remises **exclusives**, ce qui empêche le cumul `-25 %` + `-20 %` exigé par le cahier des charges.

- **Anomalie B4 — Contrôle N+1 catalogue (vérification préventive)**
  - Localisation : `MovieRepository::findAllWithCategory()`, `MovieController::index()`, `templates/movie/index.html.twig`.
  - Analyse : **aucun N+1 détecté**. La boucle Twig lit `movie.category.name`, mais la requête embarque déjà `->leftJoin('movie.category', 'category')` + `->addSelect('category')`, ce qui hydrate la relation en **une seule requête**. `movie/show.html.twig` ne rend qu'un film (conversion de paramètre) : un lazy-load unique, pas une boucle N+1. `category/index.html.twig` n'accède qu'à `id`/`name`. **Aucun `leftJoin` supplémentaire n'était nécessaire.**

**5. Correctif & Test de non-régression (TNR) associé**

| Élément | Détail |
| --- | --- |
| **Correctif appliqué** | `formatBadge()` : tag `null`/vide/espaces → `[STANDARD]`. `calculateAverageRating()` : filtrage des `null`, retour `0.0` si aucune note valide. `calculateTicketPrice()` : borne `>= 65` + remises **cumulatives** (deux `if` indépendants). |
| **Branche / Pull Request** | `fix/issue-13-legacy-report` — PR à ouvrir (réf. `Closes #13`) |
| **Test TNR (PHPUnit)** | `tests/Service/LegacyBoxOfficeServiceTest.php` (règles métier : badge, moyenne, tarifs — dont cas 65 ans, 64 ans, cumul abonné) et `tests/Controller/LegacyReportControllerTest.php` (HTTP 200 + valeurs rendues). Exécution : `php bin/phpunit` → **14 tests, 19 assertions, OK**. |
| **Commit(s)** | `fix(legacy): corrige le HTTP 500 et les règles métier du rapport` (réf. `Closes #13`) |
| **Vérification post-déploiement** | `GET /legacy/rapport` → HTTP 200 ; badge `[STANDARD]` pour l'entrée sans tag ; moyenne `0/5` sans note valide ; « Senior (65 ans) » = `9 €`, « Étudiant membre (16 ans, abonné) » = `7.2 €`. |

**6. Suites à donner**

- Aucune dette technique bloquante restante sur ce module.
- Les gabarits orphelins `templates/catalog/*.twig` (non référencés par un contrôleur) sont identifiés comme *code mort* applicable à la maintenance préventive — hors périmètre de l'Issue #13.

---

### RCA-XXX — <Titre court de l'incident>

| Champ | Valeur |
| --- | --- |
| **Date de détection** | `AAAA-MM-JJ HH:MM` |
| **Environnement** | `prod` / `preprod` / `dev` |
| **Priorité / Sévérité** | `P0 / sev:blocker` — `P1 / sev:major` — `P2 / sev:minor` |
| **Issue liée** | `#XX` |
| **Associé à un hotfix ?** | Oui / Non — (si oui, référence du hotfix : …) |

**1. Contexte**
> Décrire la situation d'exploitation, le périmètre fonctionnel impacté et les utilisateurs concernés.

**2. Étapes pour reproduire**

1. …
2. …
3. …

**3. Résultat obtenu vs Résultat attendu**

| Résultat obtenu (bug) | Résultat attendu (correct) |
| --- | --- |
| … | … |

**4. Cause racine identifiée**

> Evidence collectée via le **Web Profiler** (`/_profiler`), `var/log/dev.log` / logs de production, requêtes SQL, traces d'exceptions.
> Expliquer le **pourquoi** (et non le symptôme).

- Élément déclencheur : …
- Localisation (fichier / ligne / service) : …
- Analyse : …

**5. Correctif & Test de non-régression (TNR) associé**

| Élément | Détail |
| --- | --- |
| **Correctif appliqué** | … |
| **Branche / Pull Request** | `fix/issue-XX` — PR #… |
| **Test TNR (PHPUnit)** | Chemin du test : `tests/…` — cas couvert : … |
| **Commit(s)** | `fix(...): …` (réf. `Closes #XX`) |
| **Vérification post-déploiement** | … |

---


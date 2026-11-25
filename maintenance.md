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


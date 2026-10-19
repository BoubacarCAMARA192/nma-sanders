# NMA Sanders — Plateforme de commande et de suivi des livraisons de farine

Application web complète pour la société **NMA Sanders (Sénégal)** : les boulangeries clientes commandent leur farine en toute autonomie, le service commercial valide et pilote les livraisons, l'administration gère les comptes, le catalogue et les paramètres — le tout tracé et sécurisé.

Trois espaces selon le rôle, responsive (mobile first), avec un cycle de vie de commande entièrement traçable : `reçue → validée → en préparation → en livraison → livrée` (ou `refusée` / `annulée`).

---

## Sommaire

- [Aperçu des fonctionnalités](#aperçu-des-fonctionnalités)
- [Comptes de démonstration](#comptes-de-démonstration)
- [Stack technique](#stack-technique)
- [Installation locale (XAMPP)](#installation-locale-xampp)
- [Structure du projet](#structure-du-projet)
- [Sécurité](#sécurité)
- [Tests fonctionnels](#tests-fonctionnels)
- [Déploiement](#déploiement)
- [Licence](#licence)

---

## Aperçu des fonctionnalités

### Authentification (F01 – F03)

| Fonction | Détail |
| --- | --- |
| Connexion sécurisée | Mots de passe hachés (bcrypt), verrouillage après 5 tentatives pendant 15 min, expiration de session (45 min) |
| Inscription en ligne | Page d'accueil avec choix **Créer un compte / Se connecter** ; la demande boulanger est enregistrée **inactive** et les gestionnaires sont notifiés pour activation |
| Double vérification (2FA) | Code à 6 chiffres pour gestionnaire et administrateur (optionnel), affiché dans l'interface en mode debug |
| Mot de passe oublié | Jeton à usage unique valable 1 heure, transmis par l'administrateur (pas de SMTP en local) |

### Espace boulanger (F04 – F08)

- Catalogue produits à jour (disponibilité en temps réel, produits inactifs masqués).
- Panier en session puis commande avec **date de livraison souhaitée** (délai minimum paramétrable, 24 h par défaut).
- **Suivi en temps réel** : frise horodatée avec auteur de chaque changement de statut.
- Modification / annulation **tant que la commande est « reçue »**, historique complet, réapprovisionnement express, profil et changement de mot de passe.

### Espace gestionnaire (F09 – F16)

- Réception des commandes + notifications, filtres par statut, détail complet avec contrôle de stock.
- **Validation** (stock vérifié puis décrémenté), **refus avec motif obligatoire**, progression des statuts dans l'ordre imposé.
- Planification des livraisons (date, livreur) puis sortie en livraison et livraison effective.
- Fiches clients boulangers, statistiques (taux de livraison, délai moyen) et **export CSV compatible CRM/SAP**.

### Espace administrateur (F17 – F18)

- Gestion des comptes : création (boulanger/gestionnaire/admin), modification, activation/désactivation, réinitialisation de mot de passe.
- Gestion du catalogue : création/modification produit, ajustement de stock (jamais négatif), activation/désactivation.
- **Journal des opérations** horodaté et filtrable, **paramètres métier** (délai minimum, tentatives, blocage, session, rétention).

### Interface

- Page d'accueil animée avec parcours guidé (choix **inscription** ou **connexion**, étapes 1-2-3).
- Cartes interactives (effet de brillance, icônes animées), fond à halos mouvants, comptes de démonstration cliquables.
- Tableaux de bord avec compteurs animés, barres de progression animées, entrées échelonnées, mode d'affichage adapté au mobile (barre de navigation basse).
- Notifications intégrées (cloche) et messages flash auto-nettoyés.

---

## Comptes de démonstration

| Rôle | Adresse e-mail | Mot de passe |
| --- | --- | --- |
| Administrateur | `admin@nmasanders.sn` | `Admin@2026` |
| Gestionnaire | `gestionnaire@nmasanders.sn` | `Gestion@2026` |
| Boulanger | `boulanger@demo.sn` | `Boulanger@2026` |

> Les comptes de démonstration sont proposés sur la page de connexion (encart cliquable) quand `NMA_DEMO=1`
> (activé par défaut en mode debug, et forcé à `1` sur la démo Vercel).

---

## Stack technique

| Couche | Choix |
| --- | --- |
| Langage | PHP 8 (POO, `declare(strict_types=1)`, `match`) |
| Base de données | MySQL / MariaDB (PDO, requêtes préparées) |
| Architecture | MVC en 3 couches : vues ↔ contrôleurs/noyau ↔ modèles/MySQL |
| Front | Bootstrap 5.3 + Bootstrap Icons (CDN), CSS et JS vanille maison (aucune dépendance build) |
| Serveur local | XAMPP (Apache + PHP 8.0 + MariaDB 10.4) |

Aucun gestionnaire de paquets n'est nécessaire : le projet se déploie en copiant les fichiers.

---

## Installation locale (XAMPP)

1. **Récupérer le projet** dans `htdocs` :

   ```bash
   cd C:\xampp\htdocs
   git clone <URL_DU_DEPOT> nma-sanders
   ```

   *(sinon, une jonction Windows fait aussi l'affaire : `mklink /J C:\xampp\htdocs\nma-sanders "chemin\vers\le\projet"`)*

2. **Importer la base** (phpMyAdmin : `http://localhost/phpmyadmin` → Importer → `database/nma_sanders.sql`), ou en ligne de commande :

   ```bat
   C:\xampp\mysql\bin\mysql.exe -u root < database\nma_sanders.sql
   ```

3. **Vérifier la configuration** : par défaut `127.0.0.1:3306`, base `nma_sanders`, utilisateur `root` sans mot de passe (valeurs XAMPP). Pour un autre environnement, définir les variables d'environnement :

   | Variable | Rôle | Défaut |
   | --- | --- | --- |
   | `NMA_DB_HOST` | Hôte MySQL | `127.0.0.1` |
   | `NMA_DB_PORT` | Port MySQL | `3306` |
   | `NMA_DB_NAME` | Nom de la base | `nma_sanders` |
   | `NMA_DB_USER` | Utilisateur MySQL | `root` |
   | `NMA_DB_PASS` | Mot de passe MySQL | *(vide)* |
   | `NMA_DEBUG` | Mode debug (1/0) | `1` |
   | `NMA_DEMO` | Afficher les comptes de démonstration sur la connexion (1/0) | `NMA_DEBUG` |
   | `NMA_BASE_URL` | URL de base imposée (déploiements particuliers) | *(auto)* |

4. **Lancer** Apache + MySQL depuis le panneau XAMPP puis ouvrir :

   ```
   http://localhost/nma-sanders/
   ```

> La page d'accueil propose **Créer un compte** (demande d'inscription) ou **Se connecter**, puis redirige vers l'espace correspondant au rôle connecté.

---

## Structure du projet

```
nma-sanders/
├── index.php                  # Front controller (en-têtes de sécurité, routage, erreurs)
├── api/index.php              # Point d'entrée serverless (Vercel)
├── vercel.json                # Configuration de déploiement Vercel
├── .htaccess                  # Réécriture d'URL + protection des fichiers sensibles
├── app/
│   ├── bootstrap.php          # Autoload, constantes, helpers
│   ├── config/config.php      # Configuration (BDD, sessions, URL, debug)
│   ├── core/                  # Noyau : Router, Database (PDO), Auth, Csrf, View,
│   │                          #   Validator, Flash, Journal, Notifier, Parametre, helpers
│   ├── controllers/           # Auth, Boulanger, Gestionnaire, Admin, Notification
│   ├── models/                # Utilisateur, Boulanger, Produit, Commande,
│   │                          #   HistoriqueStatut, Livraison
│   └── views/                 # Vues par espace + layouts (public / connecté)
├── assets/                    # css/app.css, js/app.js, img/favicon.svg
├── database/                  # nma_sanders.sql (local) · nma_sanders_import_hebergement.sql (hébergeurs)
└── storage/logs/              # Journaux applicatifs
```

---

## Sécurité

- Mots de passe **bcrypt** (`password_hash` / `password_verify`), jamais stockés en clair.
- **Requêtes préparées PDO** partout (aucune concaténation SQL).
- **Jeton CSRF** sur toutes les soumissions POST ; rejet → code 403, page dédiée et journalisation.
- Contrôle d'accès par rôle côté serveur sur chaque route (un boulanger ne peut pas atteindre l'espace gestionnaire, etc.) et **cloisonnement des données** (un boulanger ne voit que ses commandes).
- Sessions durcies : cookie `HttpOnly` + `SameSite=Lax`, flag `Secure` automatique en HTTPS, expiration par inactivité, renouvellement d'identifiant à la connexion.
- Journal d'opérations horodaté (connexions, refus, changements de statut, modifications du catalogue et des paramètres).
- En-têtes de sécurité (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, …).

---

## Tests fonctionnels

Une suite de tests de bout en bout (77 vérifications) couvre : contrôle d'accès et CSRF, parcours boulanger complet, cycle de vie gestionnaire (validation, stock, refus avec motif, séquence des statuts, livraisons, export CSV), administration (comptes, catalogue, stock, paramètres, journal), traçabilité, notifications, cloisonnement des données, inscription autonome (compte créé inactif, refus de connexion, doublon) et nettoyage automatique.

La suite a été utilisée pendant le développement ; elle peut être rejouée avec :

```bat
C:\xampp\php\php.exe tests\parcours.php
```

---

## Déploiement

### Hébergement PHP / MySQL classique (recommandé)

1. Copier les fichiers par FTP ou `git clone` sur l'hébergement.
2. Importer `database/nma_sanders.sql` via phpMyAdmin.
3. Renseigner `NMA_DB_*` (variables d'environnement de l'hébergeur) ou adapter `app/config/config.php`.
4. Passer `NMA_DEBUG=0` en production.

### Vercel

Le dépôt contient une configuration prête pour Vercel (`vercel.json` + `api/index.php`, runtime [`vercel-php`](https://www.npmjs.com/package/vercel-php)) qui permet d'afficher le site. **À savoir** :

- Vercel ne fournit **pas de serveur MySQL** : il faut une base MySQL hébergée (Railway, Aiven, Clever Cloud, PlanetScale…) et renseigner les variables `NMA_DB_HOST`, `NMA_DB_PORT`, `NMA_DB_NAME`, `NMA_DB_USER`, `NMA_DB_PASS` dans les réglages du projet Vercel.
- Les fonctions serverless sont **éphémères** : les sessions PHP reposent sur `/tmp` d'une instance. Pour une mise en production réelle, privilégier un hébergement PHP classique.

Déploiement en ligne de commande :

```bash
vercel            # préversion
vercel --prod     # production
```

---

## Licence

Projet développé pour **NMA Sanders (Sénégal)** — tous droits réservés.

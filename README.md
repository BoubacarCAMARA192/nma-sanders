# Sanders Connect — Plateforme de commande et de suivi des livraisons de farine

Application web complète pour la société **Sanders Connect (Sénégal)** : les boulangeries clientes commandent leur farine en toute autonomie, le service commercial valide et pilote les livraisons, l'administration gère les comptes, le catalogue et les paramètres — le tout tracé et sécurisé.

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

- Page d'accueil animée : **scène SVG interactive** (champ de blé ondulant, soleil, nuages, camion Sanders Connect en tournée, sacs de farine), parallaxe douce à la souris et cartes inclinables.
- Parcours guidé (choix **inscription** ou **connexion**), cartes « univers » illustrées (Fournil, Livraison, Suivi) et chiffres clés animés, bandeau défilant.
- Cartes interactives (effet de brillance, icônes animées), fond à halos mouvants, comptes de démonstration cliquables.
- Tableaux de bord avec compteurs animés, barres de progression animées, entrées échelonnées, mode d'affichage adapté au mobile (barre de navigation basse).
- Notifications intégrées (cloche) et messages flash auto-nettoyés.

---

## Comptes de démonstration

| Rôle | Adresse e-mail | Mot de passe |
| --- | --- | --- |
| Administrateur | `admin@sandersconnect.sn` | `Admin@2026` |
| Gestionnaire | `gestionnaire@sandersconnect.sn` | `Gestion@2026` |
| Boulanger | `boulanger@sandersconnect.sn` | `Boulanger@2026` |

> Les comptes de démonstration sont proposés sur la page de connexion (encart cliquable) quand `SC_DEMO=1`
> (activé par défaut en mode debug, et forcé à `1` sur la démo Vercel).

---

## Stack technique

| Couche | Choix |
| --- | --- |
| Langage | PHP 8 (POO, `declare(strict_types=1)`, `match`) |
| Base de données | MySQL / MariaDB en local (PDO, requêtes préparées) — bascule automatique sur **SQLite** côté serverless, avec persistance **Vercel Blob** |
| Architecture | MVC en 3 couches : vues ↔ contrôleurs/noyau ↔ modèles/MySQL |
| Front | Bootstrap 5.3 + Bootstrap Icons (CDN), CSS et JS vanille maison (aucune dépendance build) |
| Serveur local | XAMPP (Apache + PHP 8.0 + MariaDB 10.4) |

Aucun gestionnaire de paquets n'est nécessaire : le projet se déploie en copiant les fichiers.

---

## Installation locale (XAMPP)

1. **Récupérer le projet** dans `htdocs` :

   ```bash
   cd C:\xampp\htdocs
   git clone <URL_DU_DEPOT> sanders-connect
   ```

   *(sinon, une jonction Windows fait aussi l'affaire : `mklink /J C:\xampp\htdocs\sanders-connect "chemin\vers\le\projet"`)*

2. **Importer la base** (phpMyAdmin : `http://localhost/phpmyadmin` → Importer → `database/sanders_connect.sql`), ou en ligne de commande :

   ```bat
   C:\xampp\mysql\bin\mysql.exe -u root < database\sanders_connect.sql
   ```

3. **Vérifier la configuration** : par défaut `127.0.0.1:3306`, base `sanders_connect`, utilisateur `root` sans mot de passe (valeurs XAMPP). Pour un autre environnement, définir les variables d'environnement :

   | Variable | Rôle | Défaut |
   | --- | --- | --- |
   | `SC_DB_HOST` | Hôte MySQL | `127.0.0.1` |
   | `SC_DB_PORT` | Port MySQL | `3306` |
   | `SC_DB_NAME` | Nom de la base | `sanders_connect` |
   | `SC_DB_USER` | Utilisateur MySQL | `root` |
   | `SC_DB_PASS` | Mot de passe MySQL | *(vide)* |
   | `SC_DB_DRIVER` | Pilote imposé (`mysql` ou `sqlite`) | *(auto)* |
   | `SC_DEBUG` | Mode debug (1/0) | `1` |
   | `SC_DEMO` | Afficher les comptes de démonstration sur la connexion (1/0) | `SC_DEBUG` |
   | `SC_BASE_URL` | URL de base imposée (déploiements particuliers) | *(auto)* |

4. **Lancer** Apache + MySQL depuis le panneau XAMPP puis ouvrir :

   ```
   http://localhost/sanders-connect/
   ```

> La page d'accueil propose **Créer un compte** (demande d'inscription) ou **Se connecter**, puis redirige vers l'espace correspondant au rôle connecté.

---

## Structure du projet

```
sanders-connect/
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
├── assets/                    # css/app.css, js/app.js, img/favicon.png
├── database/                  # sanders_connect.sql (schéma + jeu de données)
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

Pour rejouer la suite dans les conditions serverless (SQLite, sans MySQL), lancer l'application avec
`SC_DB_DRIVER=sqlite` puis la suite avec `SC_TEST_SQLITE=1` (et `SC_TEST_BASE` si le port diffère).

---

## Déploiement

### Hébergement PHP / MySQL classique (recommandé)

1. Copier les fichiers par FTP ou `git clone` sur l'hébergement.
2. Importer `database/sanders_connect.sql` via phpMyAdmin.
3. Renseigner `SC_DB_*` (variables d'environnement de l'hébergeur) ou adapter `app/config/config.php`.
4. Passer `SC_DEBUG=0` en production.

### Vercel (application complète)

Le dépôt contient une configuration Vercel (`vercel.json` + `api/index.php`, runtime [`vercel-php`](https://www.npmjs.com/package/vercel-php)) qui expose **l'application complète** (page d'accueil animée, connexion, inscription, commandes, tableaux de bord).

- En environnement serverless, l'application **bascule automatiquement sur SQLite** (aucun réglage MySQL requis) : le fichier de base est créé dans le stockage éphémère de la fonction.
- Pour **conserver les données entre les exécutions**, connecter un magasin **Vercel Blob** au projet : le jeton `BLOB_READ_WRITE_TOKEN` est alors fourni automatiquement et le fichier SQLite est téléversé/restauré à chaque exécution.
- `SC_DEMO=1` affiche les comptes de démonstration sur la page de connexion.
- En local, la base **MySQL (XAMPP)** reste la référence de développement ; il suffit de renseigner `SC_DB_HOST`, `SC_DB_PORT`, `SC_DB_NAME`, `SC_DB_USER`, `SC_DB_PASS` (ou `SC_DB_DRIVER=mysql`) pour l'utiliser partout.
- Sans base joignable, les pages concernées affichent une page « base de données injoignable » claire (au lieu d'une erreur brute).

Déploiement en ligne de commande :

```bash
vercel            # préversion
vercel --prod     # production
```

---

## Licence

Projet développé pour **Sanders Connect (Sénégal)** — tous droits réservés.

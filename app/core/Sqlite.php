<?php
/**
 * Base SQLite autonome pour les deploiements serverless (Vercel) :
 * fichier cree dans le dossier temporaire, schema et donnees identiques
 * a la base MySQL locale, persistance optionnelle via Vercel Blob.
 */

declare(strict_types=1);

namespace Core;

use PDO;
use Throwable;

final class Sqlite
{
    private const NOM_BLOB = 'sanders_connect.sqlite';
    private const NOM_VERROU = 'sanders_connect.lock';
    private const VERSION_CATALOGUE_SC = '2';
    private const DUREE_VERROU = 30;
    private const CLE_VERROU_NEON = 9042001;
    private const ID_ETAT_NEON = 1;

    private static ?PDO $pdoPersistance = null;
    private static ?PDO $pdoNeon = null;
    private static string $fichierPersistance = '';
    private static int $modificationsPersistance = 0;
    private static bool $initialisationPersistance = false;
    private static bool $synchronisationEffectuee = true;
    private static ?string $etagPersistance = null;
    private static bool $verrouAcquis = false;
    private static string $verrouProprietaire = '';
    private static bool $finalisationEnregistree = false;

    public static function ouvrir(): PDO
    {
        $fichier = rtrim(sys_get_temp_dir(), '/\\') . '/sanders_connect.sqlite';
        $persistance = self::neonConfigure();

        if (!$persistance && self::neonUrl() !== '') {
            throw new \RuntimeException('Extension PostgreSQL indisponible pour la base partagee.');
        }

        if ($persistance) {
            self::enregistrerFinalisation();
            self::supprimerFichiersLocaux($fichier);

            if (self::neonTelecharger($fichier) === 'erreur') {
                throw new \RuntimeException('La base de donnees partagee est momentanement indisponible.');
            }
        }

        $existait = is_file($fichier);

        $pdo = new PDO('sqlite:' . $fichier, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 10000');
        $pdo->exec('PRAGMA journal_mode = WAL');

        $modificationsAvant = (int) $pdo->query('SELECT total_changes()')->fetchColumn();
        $schemaAvant = (int) $pdo->query('PRAGMA schema_version')->fetchColumn();

        self::fonctionsSql($pdo);
        self::initialiser($pdo);

        $modificationsApres = (int) $pdo->query('SELECT total_changes()')->fetchColumn();
        $schemaApres = (int) $pdo->query('PRAGMA schema_version')->fetchColumn();
        $initialisationModifiee = !$existait
            || $modificationsApres > $modificationsAvant
            || $schemaApres !== $schemaAvant;

        self::$pdoPersistance = $pdo;
        self::$fichierPersistance = $fichier;
        self::$modificationsPersistance = $modificationsApres;
        self::$initialisationPersistance = $initialisationModifiee;
        self::$synchronisationEffectuee = !$persistance;

        return $pdo;
    }

    /** Indique si la base doit etre synchronisee avec Neon. */
    public static function persistanceActive(): bool
    {
        return self::neonConfigure();
    }

    /**
     * Verrou global des ecritures : evite que deux instances Vercel
     * ecrasent mutuellement leurs commandes (SQLite n'a pas de verrou
     * distribue). A appeler avant le routeur pour chaque requete.
     */
    public static function verrouiller(): void
    {
        if (self::neonUrl() === '') {
            return;
        }

        if (!self::neonConfigure()) {
            throw new \RuntimeException('Extension PostgreSQL indisponible pour la base partagee.');
        }

        self::enregistrerFinalisation();
        $pdo = self::neonPdo();
        $limite = microtime(true) + 8.0;

        do {
            $acquis = $pdo->query('SELECT pg_try_advisory_lock(' . self::CLE_VERROU_NEON . ')')->fetchColumn();

            if ($acquis === true || $acquis === 't' || $acquis === 1 || $acquis === '1') {
                self::$verrouAcquis = true;
                return;
            }

            usleep(250000);
        } while (microtime(true) < $limite);

        throw new \RuntimeException('La base partagee est occupee. Merci de reessayer.');
    }

    /** Fonctions MySQL reproduites en SQLite (memes requetes des modeles). */
    private static function fonctionsSql(PDO $pdo): void
    {
        $pdo->sqliteCreateFunction('NOW', static fn (): string => date('Y-m-d H:i:s'));
        $pdo->sqliteCreateFunction('CURDATE', static fn (): string => date('Y-m-d'));

        $pdo->sqliteCreateFunction('DATE_FORMAT', static function (?string $valeur, ?string $format): ?string {
            if ($valeur === null || $format === null) {
                return null;
            }
            $horodatage = strtotime($valeur);
            if ($horodatage === false) {
                return null;
            }

            return date(strtr($format, ['%Y' => 'Y', '%m' => 'm', '%d' => 'd', '%H' => 'H', '%i' => 'M', '%s' => 'S']), $horodatage);
        });

        $pdo->sqliteCreateFunction('GREATEST', static fn (...$valeurs) => $valeurs === [] ? null : max($valeurs), -1);
        $pdo->sqliteCreateFunction('LEAST', static fn (...$valeurs) => $valeurs === [] ? null : min($valeurs), -1);

        $pdo->sqliteCreateFunction('TIMESTAMPDIFF', static function (?string $unite, ?string $debut, ?string $fin): ?int {
            if ($debut === null || $fin === null) {
                return null;
            }
            $a = strtotime($debut);
            $b = strtotime($fin);
            if ($a === false || $b === false) {
                return null;
            }
            $secondes = $b - $a;

            return match (strtoupper((string) $unite)) {
                'HOUR'   => intdiv($secondes, 3600),
                'MINUTE' => intdiv($secondes, 60),
                'DAY'    => intdiv($secondes, 86400),
                default  => $secondes,
            };
        });
    }

    private static function initialiser(PDO $pdo): void
    {
        $creations = [
            "CREATE TABLE IF NOT EXISTS utilisateur (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nom VARCHAR(150) NOT NULL,
                email VARCHAR(190) NOT NULL UNIQUE,
                telephone VARCHAR(30) DEFAULT NULL,
                mot_de_passe VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'boulanger',
                actif INTEGER NOT NULL DEFAULT 0,
                double_verification INTEGER NOT NULL DEFAULT 0,
                code_securite VARCHAR(255) DEFAULT NULL,
                tentatives_connexion INTEGER NOT NULL DEFAULT 0,
                bloque_jusqua DATETIME DEFAULT NULL,
                reset_token VARCHAR(64) DEFAULT NULL,
                reset_expire_le DATETIME DEFAULT NULL,
                derniere_connexion DATETIME DEFAULT NULL,
                cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                maj_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS boulanger (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                utilisateur_id INTEGER NOT NULL UNIQUE REFERENCES utilisateur (id) ON DELETE CASCADE,
                nom_boulangerie VARCHAR(190) NOT NULL,
                adresse VARCHAR(255) NOT NULL,
                ville VARCHAR(100) NOT NULL DEFAULT 'Dakar',
                zone_livraison VARCHAR(100) DEFAULT NULL,
                cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS produit (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                reference VARCHAR(50) NOT NULL UNIQUE,
                libelle VARCHAR(150) NOT NULL,
                description VARCHAR(255) DEFAULT NULL,
                image VARCHAR(255) DEFAULT NULL,
                unite VARCHAR(30) NOT NULL DEFAULT 'sac de 50 kg',
                prix_unitaire DECIMAL(12,2) DEFAULT NULL,
                stock_disponible INTEGER NOT NULL DEFAULT 0,
                seuil_alerte INTEGER NOT NULL DEFAULT 20,
                actif INTEGER NOT NULL DEFAULT 1,
                cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                maj_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS commande (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                reference VARCHAR(20) NOT NULL UNIQUE,
                boulanger_id INTEGER NOT NULL REFERENCES boulanger (id) ON DELETE RESTRICT,
                gestionnaire_id INTEGER DEFAULT NULL REFERENCES utilisateur (id) ON DELETE SET NULL,
                date_commande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                date_livraison_souhaitee DATE NOT NULL,
                adresse_livraison VARCHAR(255) NOT NULL,
                statut VARCHAR(20) NOT NULL DEFAULT 'recue',
                commentaire TEXT DEFAULT NULL,
                mode_paiement VARCHAR(30) DEFAULT NULL,
                motif_refus VARCHAR(255) DEFAULT NULL,
                cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                maj_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS ligne_commande (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                commande_id INTEGER NOT NULL REFERENCES commande (id) ON DELETE CASCADE,
                produit_id INTEGER NOT NULL REFERENCES produit (id) ON DELETE RESTRICT,
                quantite INTEGER NOT NULL CHECK (quantite >= 1),
                prix_unitaire DECIMAL(12,2) DEFAULT NULL,
                UNIQUE (commande_id, produit_id)
            )",
            "CREATE TABLE IF NOT EXISTS livraison (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                commande_id INTEGER NOT NULL UNIQUE REFERENCES commande (id) ON DELETE CASCADE,
                date_livraison DATE NOT NULL,
                livreur VARCHAR(150) DEFAULT NULL,
                vehicule VARCHAR(100) DEFAULT NULL,
                etat VARCHAR(20) NOT NULL DEFAULT 'planifiee',
                commentaire VARCHAR(255) DEFAULT NULL,
                cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                maj_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS historique_statut (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                commande_id INTEGER NOT NULL REFERENCES commande (id) ON DELETE CASCADE,
                statut VARCHAR(20) NOT NULL,
                date_heure DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                utilisateur_id INTEGER DEFAULT NULL REFERENCES utilisateur (id) ON DELETE SET NULL,
                motif VARCHAR(255) DEFAULT NULL
            )",
            "CREATE TABLE IF NOT EXISTS notification (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                utilisateur_id INTEGER NOT NULL REFERENCES utilisateur (id) ON DELETE CASCADE,
                titre VARCHAR(150) NOT NULL,
                message VARCHAR(500) NOT NULL,
                lien VARCHAR(255) DEFAULT NULL,
                type VARCHAR(20) NOT NULL DEFAULT 'info',
                lu INTEGER NOT NULL DEFAULT 0,
                cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS journal_operation (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                utilisateur_id INTEGER DEFAULT NULL REFERENCES utilisateur (id) ON DELETE SET NULL,
                action VARCHAR(80) NOT NULL,
                entite VARCHAR(50) DEFAULT NULL,
                entite_id INTEGER DEFAULT NULL,
                details VARCHAR(500) DEFAULT NULL,
                adresse_ip VARCHAR(45) DEFAULT NULL,
                date_heure DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS parametre (
                cle VARCHAR(50) NOT NULL PRIMARY KEY,
                valeur VARCHAR(255) NOT NULL,
                libelle VARCHAR(190) NOT NULL
            )",
            "CREATE TRIGGER IF NOT EXISTS trg_utilisateur_maj AFTER UPDATE ON utilisateur
                FOR EACH ROW WHEN NEW.maj_le = OLD.maj_le
                BEGIN UPDATE utilisateur SET maj_le = CURRENT_TIMESTAMP WHERE id = NEW.id; END",
            "CREATE TRIGGER IF NOT EXISTS trg_produit_maj AFTER UPDATE ON produit
                FOR EACH ROW WHEN NEW.maj_le = OLD.maj_le
                BEGIN UPDATE produit SET maj_le = CURRENT_TIMESTAMP WHERE id = NEW.id; END",
            "CREATE TRIGGER IF NOT EXISTS trg_commande_maj AFTER UPDATE ON commande
                FOR EACH ROW WHEN NEW.maj_le = OLD.maj_le
                BEGIN UPDATE commande SET maj_le = CURRENT_TIMESTAMP WHERE id = NEW.id; END",
            "CREATE TRIGGER IF NOT EXISTS trg_livraison_maj AFTER UPDATE ON livraison
                FOR EACH ROW WHEN NEW.maj_le = OLD.maj_le
                BEGIN UPDATE livraison SET maj_le = CURRENT_TIMESTAMP WHERE id = NEW.id; END",
        ];

        foreach ($creations as $creation) {
            $pdo->exec($creation);
        }

        self::assurerColonnes($pdo);

        if ((int) $pdo->query('SELECT COUNT(*) FROM utilisateur')->fetchColumn() === 0) {
            self::donneesInitiales($pdo);
        }

        self::assurerClientsDemo($pdo);
        self::assurerCatalogueSc($pdo);
    }

    /** Ajoute les colonnes recentes aux bases SQLite deja initialisees. */
    private static function assurerColonnes(PDO $pdo): void
    {
        $colonnes = [
            'produit'  => [
                'image' => 'ALTER TABLE produit ADD COLUMN image VARCHAR(255) DEFAULT NULL',
            ],
            'commande' => [
                'mode_paiement' => 'ALTER TABLE commande ADD COLUMN mode_paiement VARCHAR(30) DEFAULT NULL',
            ],
        ];

        foreach ($colonnes as $table => $ajouts) {
            $existantes = [];
            foreach ($pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll() as $colonne) {
                $existantes[(string) $colonne['name']] = true;
            }

            foreach ($ajouts as $nom => $sql) {
                if (!isset($existantes[$nom])) {
                    $pdo->exec($sql);
                }
            }
        }
    }

    /** Aligne le catalogue sur les 5 farines Sanders Connect, une seule fois. */
    private static function assurerCatalogueSc(PDO $pdo): void
    {
        $version = $pdo->query("SELECT valeur FROM parametre WHERE cle = 'catalogue_sc_version'")->fetchColumn();

        if ($version === self::VERSION_CATALOGUE_SC) {
            return;
        }

        $produits = [
            ['FAR-T55', 'Farine Boulangère', 'Farine de blé tendre T55 complémentée, idéale pour le pain courant.', 'assets/img/produits/farine-boulangere.png', 'sac de 50 kg', 15200.00, 480, 60],
            ['FAR-AMEL', 'Farine Boulangère Améliorée', 'Farine T55 enrichie en fer et acide folique, pour les professionnels.', 'assets/img/produits/farine-boulangere-amelioree.png', 'sac de 50 kg', 15700.00, 400, 60],
            ['FAR-TAPA', 'Farine Tapalapa', 'Farine de blé T80 spécialement conçue pour le pain tapalapa.', 'assets/img/produits/farine-tapalapa.png', 'sac de 50 kg', 15200.00, 300, 50],
            ['FAR-PAT', 'Farine Pâtissière Type 45', 'Farine Farima T45 extra-fine pour pâtisserie, viennoiserie et pains industriels.', 'assets/img/produits/farine-patissiere.png', 'sac de 50 kg', 24400.00, 120, 30],
            ['FAR-BISC', 'Farine Biscuitière', 'Farine de blé tendre spécialement sélectionnée pour biscuits, cakes et pâtisseries sèches.', 'assets/img/produits/farine-biscuitiere.png', 'sac de 50 kg', 16600.00, 200, 40],
        ];
        $references = array_column($produits, 0);

        $mettreAJour = $pdo->prepare(
            'UPDATE produit SET libelle = ?, description = ?, image = ?, unite = ?, prix_unitaire = ?, actif = 1
             WHERE reference = ?'
        );
        $creer = $pdo->prepare(
            'INSERT INTO produit (libelle, description, image, unite, prix_unitaire, stock_disponible, seuil_alerte, actif, reference)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)'
        );
        $chercher = $pdo->prepare('SELECT id FROM produit WHERE reference = ?');

        foreach ($produits as [$reference, $libelle, $description, $image, $unite, $prix, $stock, $seuil]) {
            $chercher->execute([$reference]);
            $existe = $chercher->fetchColumn();
            $chercher->closeCursor();

            if ($existe !== false) {
                $mettreAJour->execute([$libelle, $description, $image, $unite, $prix, $reference]);
            } else {
                $creer->execute([$libelle, $description, $image, $unite, $prix, $stock, $seuil, $reference]);
            }
        }

        $marques = implode(',', array_fill(0, count($references), '?'));
        $pdo->prepare(
            'DELETE FROM produit WHERE reference NOT IN (' . $marques . ')
             AND id NOT IN (SELECT produit_id FROM ligne_commande)'
        )->execute($references);
        $pdo->prepare('UPDATE produit SET actif = 0 WHERE reference NOT IN (' . $marques . ')')
            ->execute($references);

        $pdo->prepare(
            "INSERT INTO parametre (cle, valeur, libelle) VALUES ('catalogue_sc_version', ?, 'Version du catalogue Sanders Connect')
             ON CONFLICT(cle) DO UPDATE SET valeur = excluded.valeur"
        )->execute([self::VERSION_CATALOGUE_SC]);
    }

    /** Memes parametres, comptes de demonstration et catalogue que MySQL. */
    private static function donneesInitiales(PDO $pdo): void
    {
        $parametres = [
            ['delai_minimum_heures', '24', 'Delai minimum entre la commande et la livraison (heures)'],
            ['max_tentatives_connexion', '5', 'Nombre de tentatives de connexion avant blocage'],
            ['duree_blocage_minutes', '15', 'Duree du blocage apres echecs repetes (minutes)'],
            ['session_duree_minutes', '45', "Duree d'inactivite avant expiration de la session (minutes)"],
            ['jours_historique_par_defaut', '90', "Profondeur par defaut de l'historique affiche (jours)"],
        ];
        $insert = $pdo->prepare('INSERT INTO parametre (cle, valeur, libelle) VALUES (?, ?, ?)');
        foreach ($parametres as $ligne) {
            $insert->execute($ligne);
        }

        $utilisateurs = [
            [1, 'Fatma Kébé', 'admin@sandersconnect.sn', '+221 33 839 00 00', '$2y$10$GWt.FvL0dLPMtbeTYWSCa.xzr9LBUCv/dvUqPx.OOrbL0cG5C7XLe', 'administrateur'],
            [2, 'Moussa Ndiaye', 'gestionnaire@sandersconnect.sn', '+221 77 123 45 67', '$2y$10$6643pMK.dvzQHm1n5nkRSO64JGfSeaCPxvBA6NuFAKVNweoLIVPfu', 'gestionnaire'],
            [3, 'Boubacar', 'boulanger@sandersconnect.sn', '+221 76 987 65 43', '$2y$10$jtGFqmr/cOJ5DP98/fXpiORu96y3KYiwO6BcEIOYMRXF/ZOvU/oay', 'boulanger'],
            [4, 'Booba', 'client1@sandersconnect.sn', '+221 77 801 23 45', '$2y$10$2vTPzRVyr6BB4.GsaOneS.EksMNgztvuz0vq9mB3BYzkXJV6uSjO.', 'boulanger'],
            [5, 'Omar Sow', 'client2@sandersconnect.sn', '+221 78 902 34 56', '$2y$10$QZng4SuDjEjkborDdIJVQen85X8EYwX5JuwFLuArhhz9K7r.rSvne', 'boulanger'],
        ];
        $insert = $pdo->prepare(
            'INSERT INTO utilisateur (id, nom, email, telephone, mot_de_passe, role, actif)
             VALUES (?, ?, ?, ?, ?, ?, 1)'
        );
        foreach ($utilisateurs as $ligne) {
            $insert->execute($ligne);
        }

        $pdo->prepare('INSERT INTO boulanger (utilisateur_id, nom_boulangerie, adresse, ville, zone_livraison) VALUES (?, ?, ?, ?, ?)')
            ->execute([3, 'Boulangerie Demo', 'Rue 12 x Avenue Bourguiba', 'Dakar', 'Dakar Plateau']);
        $pdo->prepare('INSERT INTO boulanger (utilisateur_id, nom_boulangerie, adresse, ville, zone_livraison) VALUES (?, ?, ?, ?, ?)')
            ->execute([4, 'Boulangerie du Marche', 'Cite Fass, rue 22', 'Dakar', 'Fass / Colobane']);
        $pdo->prepare('INSERT INTO boulanger (utilisateur_id, nom_boulangerie, adresse, ville, zone_livraison) VALUES (?, ?, ?, ?, ?)')
            ->execute([5, 'Patisserie Teranga', 'Liberte 6, villa 41', 'Dakar', 'Liberte 6']);

        $produits = [
            ['FAR-T55', 'Farine Boulangère', 'Farine de blé tendre T55 complémentée, idéale pour le pain courant.', 'assets/img/produits/farine-boulangere.png', 'sac de 50 kg', 15200.00, 480, 60],
            ['FAR-AMEL', 'Farine Boulangère Améliorée', 'Farine T55 enrichie en fer et acide folique, pour les professionnels.', 'assets/img/produits/farine-boulangere-amelioree.png', 'sac de 50 kg', 15700.00, 400, 60],
            ['FAR-TAPA', 'Farine Tapalapa', 'Farine de blé T80 spécialement conçue pour le pain tapalapa.', 'assets/img/produits/farine-tapalapa.png', 'sac de 50 kg', 15200.00, 300, 50],
            ['FAR-PAT', 'Farine Pâtissière Type 45', 'Farine Farima T45 extra-fine pour pâtisserie, viennoiserie et pains industriels.', 'assets/img/produits/farine-patissiere.png', 'sac de 50 kg', 24400.00, 120, 30],
            ['FAR-BISC', 'Farine Biscuitière', 'Farine de blé tendre spécialement sélectionnée pour biscuits, cakes et pâtisseries sèches.', 'assets/img/produits/farine-biscuitiere.png', 'sac de 50 kg', 16600.00, 200, 40],
        ];
        $insert = $pdo->prepare(
            'INSERT INTO produit (reference, libelle, description, image, unite, prix_unitaire, stock_disponible, seuil_alerte)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($produits as $ligne) {
            $insert->execute($ligne);
        }
    }

    /**
     * Garantit la presence des clients d'essai sur une base qui existe deja
     * (utile pour la base persistee en ligne avant l'ajout de ces comptes).
     */
    private static function assurerClientsDemo(PDO $pdo): void
    {
        $clients = [
            ['Booba', 'client1@sandersconnect.sn', '+221 77 801 23 45', '$2y$10$2vTPzRVyr6BB4.GsaOneS.EksMNgztvuz0vq9mB3BYzkXJV6uSjO.', 'Boulangerie du Marche', 'Cite Fass, rue 22', 'Fass / Colobane'],
            ['Omar Sow', 'client2@sandersconnect.sn', '+221 78 902 34 56', '$2y$10$QZng4SuDjEjkborDdIJVQen85X8EYwX5JuwFLuArhhz9K7r.rSvne', 'Patisserie Teranga', 'Liberte 6, villa 41', 'Liberte 6'],
        ];

        $creerUtilisateur = $pdo->prepare(
            'INSERT OR IGNORE INTO utilisateur (nom, email, telephone, mot_de_passe, role, actif)
             VALUES (?, ?, ?, ?, \'boulanger\', 1)'
        );
        $chercher = $pdo->prepare('SELECT id FROM utilisateur WHERE email = ?');
        $creerBoulanger = $pdo->prepare(
            'INSERT OR IGNORE INTO boulanger (utilisateur_id, nom_boulangerie, adresse, ville, zone_livraison)
             VALUES (?, ?, ?, \'Dakar\', ?)'
        );

        foreach ($clients as [$nom, $email, $telephone, $motDePasse, $boulangerie, $adresse, $zone]) {
            $creerUtilisateur->execute([$nom, $email, $telephone, $motDePasse]);
            $chercher->execute([$email]);
            $id = (int) $chercher->fetchColumn();
            $chercher->closeCursor();

            if ($id > 0) {
                $creerBoulanger->execute([$id, $boulangerie, $adresse, $zone]);
            }
        }
    }

    /** --- Persistance Vercel Blob (optionnelle) ----------------------- */

    /**
     * Retourne le contexte d'authentification Blob :
     * - OIDC Vercel (VERCEL_OIDC_TOKEN + BLOB_STORE_ID), sans secret ;
     * - ou jeton lecture/ecriture historique (BLOB_READ_WRITE_TOKEN).
     *
     * @return array{type:string, token:string, store:string, base:string}|null
     */
    private static function blobJetonOidc(): string
    {
        $oidc = trim((string) getenv('VERCEL_OIDC_TOKEN'));

        if ($oidc !== '') {
            return $oidc;
        }

        $oidc = trim((string) ($_SERVER['HTTP_X_VERCEL_OIDC_TOKEN'] ?? ''));

        if ($oidc !== '' || !function_exists('getallheaders')) {
            return $oidc;
        }

        foreach (getallheaders() as $nom => $valeur) {
            if (strtolower((string) $nom) === 'x-vercel-oidc-token') {
                return trim((string) $valeur);
            }
        }

        return '';
    }

    private static function blobContexte(): ?array
    {
        $oidc = self::blobJetonOidc();
        $store = trim((string) getenv('BLOB_STORE_ID'));

        if ($oidc !== '' && $store !== '') {
            return [
                'type'  => 'oidc',
                'token' => $oidc,
                'store' => str_starts_with($store, 'store_') ? substr($store, 6) : $store,
                'base'  => 'https://vercel.com/api/blob',
            ];
        }

        $jeton = trim((string) getenv('BLOB_READ_WRITE_TOKEN'));

        if ($jeton !== '') {
            $parties = explode('_', $jeton);
            $store = $parties[3] ?? '';

            return [
                'type'  => 'rw',
                'token' => $jeton,
                'store' => $store,
                'base'  => 'https://blob.vercel-storage.com',
            ];
        }

        return null;
    }

    private static function blobConfigure(): bool
    {
        return self::blobContexte() !== null && function_exists('curl_init');
    }

    /** Supprime le fichier SQLite et ses journaux WAL/SHM avant un telechargement. */
    private static function supprimerFichiersLocaux(string $fichier): void
    {
        foreach ([$fichier, $fichier . '-wal', $fichier . '-shm'] as $chemin) {
            if (is_file($chemin)) {
                @unlink($chemin);
            }
        }
    }

    /** Enregistre une seule fois la finalisation (synchronisation + verrou). */
    private static function enregistrerFinalisation(): void
    {
        if (self::$finalisationEnregistree) {
            return;
        }

        self::$finalisationEnregistree = true;
        register_shutdown_function([self::class, 'finaliser']);
    }

    /** Synchronise les donnees puis libere le verrou d'ecriture. */
    public static function finaliser(): void
    {
        self::synchroniser();
        self::libererVerrou();
    }

    /** Contenu et ETag du verrou distant, ou [null, null] s'il est absent. */
    private static function verrouDistant(array $contexte): array
    {
        $url = $contexte['base'] . '/?prefix=' . urlencode(self::NOM_VERROU) . '&limit=5';
        [$code, $corps] = self::blobRequete('GET', $url, self::blobEntetes($contexte));

        if ($code !== 200) {
            return [null, null];
        }

        foreach (json_decode($corps, true)['blobs'] ?? [] as $blob) {
            if (($blob['pathname'] ?? '') !== self::NOM_VERROU || empty($blob['downloadUrl'])) {
                continue;
            }

            [$code, $contenu] = self::blobRequete(
                'GET',
                (string) $blob['downloadUrl'],
                ['Authorization: Bearer ' . $contexte['token']]
            );

            return [
                isset($blob['etag']) ? (string) $blob['etag'] : null,
                $code === 200 ? json_decode($contenu, true) : null,
            ];
        }

        return [null, null];
    }

    /** Libere le verrou PostgreSQL de la requete courante. */
    private static function libererVerrou(): void
    {
        if (!self::$verrouAcquis) {
            return;
        }

        self::$verrouAcquis = false;

        try {
            if (self::$pdoNeon instanceof PDO) {
                self::$pdoNeon->query('SELECT pg_advisory_unlock(' . self::CLE_VERROU_NEON . ')');
            }
        } catch (Throwable $e) {
            error_log('Neon verrou: liberation impossible - ' . $e->getMessage());
        }
    }

    /** Envoie la base vers Blob si la requete a modifie des donnees. */
    public static function synchroniser(): void
    {
        if (self::$synchronisationEffectuee || self::$pdoPersistance === null) {
            return;
        }

        try {
            $modifications = (int) self::$pdoPersistance->query('SELECT total_changes()')->fetchColumn();

            if (!self::$initialisationPersistance && $modifications <= self::$modificationsPersistance) {
                self::$synchronisationEffectuee = true;
                return;
            }

            self::$pdoPersistance->exec('PRAGMA wal_checkpoint(TRUNCATE)');

            if (self::neonEnvoyer(self::$fichierPersistance)) {
                self::$synchronisationEffectuee = true;
                self::$modificationsPersistance = $modifications;
                self::$initialisationPersistance = false;
                return;
            }

            error_log('Neon: echec de la synchronisation de ' . self::NOM_BLOB);
        } catch (Throwable $e) {
            error_log('Blob: erreur de synchronisation - ' . $e->getMessage());
        }
    }

    /** Entetes communs aux appels Blob. */
    private static function blobEntetes(array $contexte): array
    {
        $entetes = ['Authorization: Bearer ' . $contexte['token']];

        if ($contexte['type'] === 'oidc') {
            $entetes[] = 'x-vercel-blob-store-id: ' . $contexte['store'];
            $entetes[] = 'x-api-version: 12';
        }

        return $entetes;
    }

    private static function blobRequete(string $methode, string $url, array $entetes, ?string $corps = null): array
    {
        $session = curl_init($url);
        curl_setopt_array($session, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $methode,
            CURLOPT_HTTPHEADER     => $entetes,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        if ($corps !== null) {
            curl_setopt($session, CURLOPT_POSTFIELDS, $corps);
        }

        $reponse = curl_exec($session);
        $code = (int) curl_getinfo($session, CURLINFO_HTTP_CODE);
        curl_close($session);

        return [$code, is_string($reponse) ? $reponse : ''];
    }

    private static function blobTelecharger(string $fichier): string
    {
        $contexte = self::blobContexte();

        if ($contexte === null || !function_exists('curl_init')) {
            return 'erreur';
        }

        self::$etagPersistance = null;
        $listeUrl = $contexte['base'] . '/?prefix=' . urlencode(self::NOM_BLOB) . '&limit=10';
        $blob = null;

        for ($essai = 0; $essai < 3 && $blob === null; $essai++) {
            [$code, $corps] = self::blobRequete('GET', $listeUrl, self::blobEntetes($contexte));

            if ($code === 200) {
                $blobs = json_decode($corps, true)['blobs'] ?? [];

                if ($blobs === []) {
                    return 'absent';
                }

                foreach ($blobs as $candidat) {
                    if (($candidat['pathname'] ?? '') === self::NOM_BLOB) {
                        $blob = $candidat;
                        break;
                    }
                }
            } else {
                error_log('Blob list: HTTP ' . $code);
            }

            if ($blob === null) {
                usleep(200000);
            }
        }

        if ($blob === null) {
            return 'erreur';
        }

        $url = (string) ($blob['downloadUrl'] ?? $blob['url'] ?? '');

        if ($url === '') {
            return 'erreur';
        }

        self::$etagPersistance = isset($blob['etag']) ? (string) $blob['etag'] : null;

        for ($essai = 0; $essai < 3; $essai++) {
            [$code, $contenu] = self::blobRequete('GET', $url, ['Authorization: Bearer ' . $contexte['token']]);

            if ($code === 200 && $contenu !== '') {
                self::supprimerFichiersLocaux($fichier);

                if (file_put_contents($fichier, $contenu) !== false) {
                    return 'ok';
                }

                error_log('Blob download: ecriture locale impossible');
                return 'erreur';
            }

            error_log('Blob download: HTTP ' . $code . ' taille=' . strlen($contenu));
            usleep(200000);
        }

        return 'erreur';
    }

    private static function blobEnvoyer(string $fichier): bool
    {
        $contexte = self::blobContexte();

        if ($contexte === null || !function_exists('curl_init') || !is_file($fichier)) {
            return false;
        }

        $url = $contexte['type'] === 'oidc'
            ? $contexte['base'] . '/?pathname=' . rawurlencode(self::NOM_BLOB)
            : $contexte['base'] . '/' . self::NOM_BLOB;
        $entetes = array_merge(self::blobEntetes($contexte), [
            'Content-Type: application/octet-stream',
            'x-content-type: application/octet-stream',
            'x-add-random-suffix: 0',
            'x-allow-overwrite: 1',
            'x-vercel-blob-access: private',
        ]);

        if (self::$etagPersistance !== null && self::$etagPersistance !== '') {
            $entetes[] = 'x-if-match: ' . self::$etagPersistance;
        }

        $contenu = (string) file_get_contents($fichier);

        for ($essai = 0; $essai < 3; $essai++) {
            [$code, $corps] = self::blobRequete('PUT', $url, $entetes, $contenu);

            if ($code === 200) {
                $etag = json_decode($corps, true)['etag'] ?? null;
                self::$etagPersistance = is_string($etag) ? $etag : null;

                return true;
            }

            if ($code === 412) {
                error_log('Blob upload: conflit de version (412), nouvelle tentative au prochain passage');
                return false;
            }

            error_log('Blob upload: HTTP ' . $code . ' ' . substr($corps, 0, 200));

            if ($code !== 0 && $code < 500) {
                return false;
            }

            usleep(250000);
        }

        return false;
    }

    /** URL de la base Neon, avec priorite a la connexion directe. */
    private static function neonUrl(): string
    {
        foreach (['DATABASE_URL_UNPOOLED', 'DATABASE_URL', 'POSTGRES_URL_NON_POOLING', 'POSTGRES_URL'] as $cle) {
            $valeur = trim((string) getenv($cle));

            if ($valeur !== '') {
                return $valeur;
            }
        }

        return '';
    }

    private static function neonConfigure(): bool
    {
        return self::neonUrl() !== ''
            && in_array('pgsql', PDO::getAvailableDrivers(), true);
    }

    /**
     * Construit le DSN PostgreSQL. Le suffixe "options=endpoint=..." dans
     * dbname est le contournement Neon pour les anciennes versions de libpq.
     *
     * @return array{0:string, 1:string, 2:string}
     */
    private static function neonDsn(): array
    {
        $url = self::neonUrl();
        $parties = parse_url($url);

        if ($parties === false || empty($parties['host'])) {
            throw new \RuntimeException('URL PostgreSQL invalide.');
        }

        parse_str($parties['query'] ?? '', $query);
        $endpoint = (string) preg_replace('/-pooler$/', '', explode('.', (string) $parties['host'])[0]);
        $base = ltrim((string) ($parties['path'] ?? ''), '/') ?: 'neondb';
        $dsn = 'pgsql:host=' . $parties['host']
            . ';port=' . ($parties['port'] ?? 5432)
            . ';dbname=' . $base . ' options=endpoint=' . $endpoint
            . ';sslmode=' . ($query['sslmode'] ?? 'require')
            . ';connect_timeout=10';

        if (($query['channel_binding'] ?? '') === 'require') {
            $dsn .= ';channel_binding=require';
        }

        return [
            $dsn,
            rawurldecode((string) ($parties['user'] ?? '')),
            rawurldecode((string) ($parties['pass'] ?? '')),
        ];
    }

    private static function neonPdo(): PDO
    {
        if (self::$pdoNeon instanceof PDO) {
            return self::$pdoNeon;
        }

        if (self::neonUrl() === '') {
            throw new \RuntimeException('Base PostgreSQL non configuree.');
        }

        [$dsn, $utilisateur, $motDePasse] = self::neonDsn();

        self::$pdoNeon = new PDO($dsn, $utilisateur, $motDePasse, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        self::$pdoNeon->exec("SET statement_timeout = '12000'");
        self::$pdoNeon->exec(
            'CREATE TABLE IF NOT EXISTS sc_state (
                id INTEGER PRIMARY KEY,
                data TEXT NOT NULL,
                updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
            )'
        );

        return self::$pdoNeon;
    }

    private static function neonTelecharger(string $fichier): string
    {
        try {
            $pdo = self::neonPdo();
            $donnees = $pdo->query('SELECT data FROM sc_state WHERE id = ' . self::ID_ETAT_NEON)->fetchColumn();

            if ($donnees === false) {
                return 'absent';
            }

            $binaire = base64_decode((string) $donnees, true);

            if ($binaire === false || $binaire === '') {
                return 'erreur';
            }

            self::supprimerFichiersLocaux($fichier);

            if (file_put_contents($fichier, $binaire) === false) {
                error_log('Neon download: ecriture locale impossible');
                return 'erreur';
            }

            return 'ok';
        } catch (Throwable $e) {
            error_log('Neon download: ' . $e->getMessage());
            return 'erreur';
        }
    }

    private static function neonEnvoyer(string $fichier): bool
    {
        try {
            if (!is_file($fichier)) {
                return false;
            }

            $contenu = file_get_contents($fichier);

            if ($contenu === false || $contenu === '') {
                return false;
            }

            $pdo = self::neonPdo();
            $requete = $pdo->prepare(
                'INSERT INTO sc_state (id, data, updated_at)
                 VALUES (:id, :data, now())
                 ON CONFLICT (id) DO UPDATE
                 SET data = EXCLUDED.data, updated_at = now()'
            );
            $requete->execute([
                ':id'   => self::ID_ETAT_NEON,
                ':data' => base64_encode($contenu),
            ]);

            return true;
        } catch (Throwable $e) {
            error_log('Neon upload: ' . $e->getMessage());
            return false;
        }
    }
}

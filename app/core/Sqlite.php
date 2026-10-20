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
    private const NOM_BLOB = 'nma_sanders.sqlite';
    private const VERSION_CATALOGUE_NMA = '1';

    private static ?PDO $pdoPersistance = null;
    private static string $fichierPersistance = '';
    private static int $modificationsPersistance = 0;
    private static bool $initialisationPersistance = false;
    private static bool $synchronisationEffectuee = true;

    public static function ouvrir(): PDO
    {
        $fichier = rtrim(sys_get_temp_dir(), '/\\') . '/nma_sanders.sqlite';
        $presentEnLigne = false;

        if (self::blobConfigure()) {
            $presentEnLigne = in_array(self::blobTelecharger($fichier), ['ok', 'absent'], true);
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
        self::$synchronisationEffectuee = !$presentEnLigne;

        if ($presentEnLigne) {
            register_shutdown_function([self::class, 'synchroniser']);
        }

        return $pdo;
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
        self::assurerCatalogueNma($pdo);
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

    /** Aligne le catalogue sur les 4 farines NMA Sanders, une seule fois. */
    private static function assurerCatalogueNma(PDO $pdo): void
    {
        $version = $pdo->query("SELECT valeur FROM parametre WHERE cle = 'catalogue_nma_version'")->fetchColumn();

        if ($version === self::VERSION_CATALOGUE_NMA) {
            return;
        }

        $produits = [
            ['FAR-T55', 'Farine Boulangère', 'Farine de blé tendre T55 complémentée, idéale pour le pain courant.', 'assets/img/produits/farine-boulangere.png', 'sac de 50 kg', 15200.00, 480, 60],
            ['FAR-AMEL', 'Farine Boulangère Améliorée', 'Farine T55 enrichie en fer et acide folique, pour les professionnels.', 'assets/img/produits/farine-boulangere-amelioree.png', 'sac de 50 kg', 15700.00, 400, 60],
            ['FAR-TAPA', 'Farine Tapalapa', 'Farine de blé T80 spécialement conçue pour le pain tapalapa.', 'assets/img/produits/farine-tapalapa.png', 'sac de 50 kg', 15200.00, 300, 50],
            ['FAR-PAT', 'Farine Pâtissière Type 45', 'Farine Farima T45 extra-fine pour pâtisserie, viennoiserie et pains industriels.', 'assets/img/produits/farine-patissiere.png', 'sac de 50 kg', 26000.00, 120, 30],
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
            "INSERT INTO parametre (cle, valeur, libelle) VALUES ('catalogue_nma_version', ?, 'Version du catalogue NMA')
             ON CONFLICT(cle) DO UPDATE SET valeur = excluded.valeur"
        )->execute([self::VERSION_CATALOGUE_NMA]);
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
            [1, 'Administrateur NMA', 'admin@nmasanders.sn', '+221 33 839 00 00', '$2y$10$GWt.FvL0dLPMtbeTYWSCa.xzr9LBUCv/dvUqPx.OOrbL0cG5C7XLe', 'administrateur'],
            [2, 'Moussa Ndiaye', 'gestionnaire@nmasanders.sn', '+221 77 123 45 67', '$2y$10$6643pMK.dvzQHm1n5nkRSO64JGfSeaCPxvBA6NuFAKVNweoLIVPfu', 'gestionnaire'],
            [3, 'Boulangerie Demo', 'boulanger@demo.sn', '+221 76 987 65 43', '$2y$10$jtGFqmr/cOJ5DP98/fXpiORu96y3KYiwO6BcEIOYMRXF/ZOvU/oay', 'boulanger'],
            [4, 'Aissatou Fall', 'client1@demo.sn', '+221 77 801 23 45', '$2y$10$2vTPzRVyr6BB4.GsaOneS.EksMNgztvuz0vq9mB3BYzkXJV6uSjO.', 'boulanger'],
            [5, 'Omar Sow', 'client2@demo.sn', '+221 78 902 34 56', '$2y$10$QZng4SuDjEjkborDdIJVQen85X8EYwX5JuwFLuArhhz9K7r.rSvne', 'boulanger'],
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
            ['FAR-PAT', 'Farine Pâtissière Type 45', 'Farine Farima T45 extra-fine pour pâtisserie, viennoiserie et pains industriels.', 'assets/img/produits/farine-patissiere.png', 'sac de 50 kg', 26000.00, 120, 30],
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
            ['Aissatou Fall', 'client1@demo.sn', '+221 77 801 23 45', '$2y$10$2vTPzRVyr6BB4.GsaOneS.EksMNgztvuz0vq9mB3BYzkXJV6uSjO.', 'Boulangerie du Marche', 'Cite Fass, rue 22', 'Fass / Colobane'],
            ['Omar Sow', 'client2@demo.sn', '+221 78 902 34 56', '$2y$10$QZng4SuDjEjkborDdIJVQen85X8EYwX5JuwFLuArhhz9K7r.rSvne', 'Patisserie Teranga', 'Liberte 6, villa 41', 'Liberte 6'],
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

    /** Envoie la base vers Blob si la requete a modifie des donnees. */
    public static function synchroniser(): void
    {
        if (self::$synchronisationEffectuee || self::$pdoPersistance === null) {
            return;
        }

        self::$synchronisationEffectuee = true;

        try {
            $modifications = (int) self::$pdoPersistance->query('SELECT total_changes()')->fetchColumn();

            if (!self::$initialisationPersistance && $modifications <= self::$modificationsPersistance) {
                return;
            }

            self::$pdoPersistance->exec('PRAGMA wal_checkpoint(TRUNCATE)');

            if (!self::blobEnvoyer(self::$fichierPersistance)) {
                self::$synchronisationEffectuee = false;
                error_log('Blob: echec de la synchronisation de ' . self::NOM_BLOB);
            }
        } catch (Throwable $e) {
            self::$synchronisationEffectuee = false;
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

        $listeUrl = $contexte['base'] . '/?prefix=' . urlencode(self::NOM_BLOB) . '&limit=10';
        [$code, $corps] = self::blobRequete('GET', $listeUrl, self::blobEntetes($contexte));

        if ($code !== 200) {
            error_log('Blob list: HTTP ' . $code);
            return 'erreur';
        }

        $blobs = json_decode($corps, true)['blobs'] ?? [];

        if ($blobs === []) {
            return 'absent';
        }

        foreach ($blobs as $blob) {
            if (($blob['pathname'] ?? '') !== self::NOM_BLOB) {
                continue;
            }

            $url = (string) ($blob['downloadUrl'] ?? $blob['url'] ?? '');

            if ($url === '') {
                return 'erreur';
            }

            [$code, $contenu] = self::blobRequete('GET', $url, ['Authorization: Bearer ' . $contexte['token']]);

            if ($code !== 200 || $contenu === '' || file_put_contents($fichier, $contenu) === false) {
                error_log('Blob download: HTTP ' . $code . ' taille=' . strlen($contenu));
                return 'erreur';
            }

            return 'ok';
        }

        return 'absent';
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

        [$code, $corps] = self::blobRequete('PUT', $url, array_merge(self::blobEntetes($contexte), [
            'Content-Type: application/octet-stream',
            'x-content-type: application/octet-stream',
            'x-add-random-suffix: 0',
            'x-allow-overwrite: 1',
            'x-vercel-blob-access: private',
        ]), (string) file_get_contents($fichier));

        if ($code !== 200) {
            error_log('Blob upload: HTTP ' . $code . ' ' . substr($corps, 0, 200));
        }

        return $code === 200;
    }
}

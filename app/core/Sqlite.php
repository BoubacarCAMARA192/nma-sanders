<?php
/**
 * Base SQLite autonome pour les deploiements serverless (Vercel) :
 * fichier cree dans le dossier temporaire, schema et donnees identiques
 * a la base MySQL locale, persistance optionnelle via Vercel Blob.
 */

declare(strict_types=1);

namespace Core;

use PDO;

final class Sqlite
{
    private const NOM_BLOB = 'nma_sanders.sqlite';

    public static function ouvrir(): PDO
    {
        $fichier = rtrim(sys_get_temp_dir(), '/\\') . '/nma_sanders.sqlite';
        $presentEnLigne = false;
        $empreinte = '';

        if (self::blobToken() !== '') {
            $presentEnLigne = in_array(self::blobTelecharger($fichier), ['ok', 'absent'], true);
            $empreinte = is_file($fichier) ? (string) md5_file($fichier) : '';
        }

        $pdo = new PDO('sqlite:' . $fichier, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 10000');
        $pdo->exec('PRAGMA journal_mode = WAL');

        self::fonctionsSql($pdo);
        self::initialiser($pdo);

        if ($presentEnLigne) {
            register_shutdown_function(function () use ($pdo, $fichier, $empreinte): void {
                $pdo->exec('PRAGMA wal_checkpoint(TRUNCATE)');
                clearstatcache(true, $fichier);

                if (is_file($fichier) && (string) md5_file($fichier) !== $empreinte) {
                    self::blobEnvoyer($fichier);
                }
            });
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

        if ((int) $pdo->query('SELECT COUNT(*) FROM utilisateur')->fetchColumn() > 0) {
            return;
        }

        self::donneesInitiales($pdo);
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

        $produits = [
            ['FAR-T55', 'Farine de ble tendre T55', 'Farine boulangere standard pour le pain courant', 'sac de 50 kg', 22500.00, 480, 60],
            ['FAR-T65', 'Farine de ble tendre T65', 'Farine superieure, forte hydratation, pain de tradition', 'sac de 50 kg', 24000.00, 310, 50],
            ['FAR-COMP', 'Farine de ble complet', 'Farine complete pour pain bis et pain de campagne', 'sac de 50 kg', 21500.00, 175, 40],
            ['FAR-PAT', 'Farine patissiere', 'Farine de gruau pour viennoiserie et patisserie', 'sac de 50 kg', 26000.00, 120, 30],
            ['FAR-MAIS', 'Farine de mais', 'Farine de mais locale pour melanges et galettes', 'sac de 50 kg', 19500.00, 90, 20],
            ['SEM-BD', 'Semoule de ble dur', 'Semoule fine pour pain special et couscous', 'sac de 50 kg', 25000.00, 140, 30],
            ['SON-BLE', 'Son de ble', "Sous-produit de mouture pour l'alimentation animale", 'sac de 50 kg', 8500.00, 260, 40],
            ['FAR-AMEL', 'Farine boulangere amelioree NMA', "Melange pret a l'emploi avec ameliorants homologues", 'sac de 50 kg', 23500.00, 400, 60],
        ];
        $insert = $pdo->prepare(
            'INSERT INTO produit (reference, libelle, description, unite, prix_unitaire, stock_disponible, seuil_alerte)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($produits as $ligne) {
            $insert->execute($ligne);
        }
    }

    /** --- Persistance Vercel Blob (optionnelle) ----------------------- */

    private static function blobToken(): string
    {
        $jeton = getenv('BLOB_READ_WRITE_TOKEN');

        return $jeton === false ? '' : trim($jeton);
    }

    private static function blobRequete(string $methode, string $url, array $entetes, ?string $corps = null): array
    {
        $session = curl_init($url);
        curl_setopt_array($session, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $methode,
            CURLOPT_HTTPHEADER     => $entetes,
            CURLOPT_TIMEOUT        => 20,
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
        $jeton = self::blobToken();

        if ($jeton === '' || !function_exists('curl_init')) {
            return 'erreur';
        }

        [$code, $corps] = self::blobRequete(
            'GET',
            'https://blob.vercel-storage.com?prefix=' . urlencode(self::NOM_BLOB) . '&limit=10',
            ['Authorization: Bearer ' . $jeton]
        );

        if ($code !== 200) {
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

            [$code, $contenu] = self::blobRequete('GET', $url, ['Authorization: Bearer ' . $jeton]);

            if ($code !== 200 || $contenu === '' || file_put_contents($fichier, $contenu) === false) {
                return 'erreur';
            }

            return 'ok';
        }

        return 'absent';
    }

    private static function blobEnvoyer(string $fichier): bool
    {
        $jeton = self::blobToken();

        if ($jeton === '' || !function_exists('curl_init') || !is_file($fichier)) {
            return false;
        }

        [$code] = self::blobRequete('PUT', 'https://blob.vercel-storage.com/' . self::NOM_BLOB, [
            'Authorization: Bearer ' . $jeton,
            'x-content-type: application/octet-stream',
            'x-add-random-suffix: 0',
            'x-allow-overwrite: 1',
            'x-vercel-blob-access: private',
        ], (string) file_get_contents($fichier));

        return $code === 200;
    }
}

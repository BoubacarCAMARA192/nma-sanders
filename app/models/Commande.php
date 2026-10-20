<?php
/**
 * Modele COMMANDE : cycle de vie complet de la commande (F05 a F16).
 *
 * Statuts (ordre impose par le cahier des charges) :
 *   recue -> validee -> en_preparation -> en_livraison -> livree
 *   refusee / annulee interrompent la sequence.
 */

declare(strict_types=1);

namespace Model;

use Core\Database;
use Core\Journal;
use Core\Notifier;
use Core\Parametre;

final class Commande
{
    public const STATUTS = ['recue', 'validee', 'en_preparation', 'en_livraison', 'livree', 'refusee', 'annulee'];

    /** Modes de paiement disponibles. */
    public const MODES_PAIEMENT = ['carte', 'wave', 'orange_money'];

    /** Statuts editables par le boulanger (F06). */
    public const STATUTS_MODIFIABLES = ['recue'];

    /** Prochain statut logique de la sequence de livraison. */
    public const SEQUENCE = [
        'recue'          => 'validee',
        'validee'        => 'en_preparation',
        'en_preparation' => 'en_livraison',
        'en_livraison'   => 'livree',
    ];

    // -----------------------------------------------------------------
    // Creation (F05)
    // -----------------------------------------------------------------
    /**
     * Enregistre une commande et ses lignes dans une transaction.
     * @param array $lignes  [[produit_id, quantite], ...]
     */
    public static function creer(int $boulangerId, array $d, array $lignes): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $reference = self::referenceUnique();

            $commandeId = Database::insert(
                'INSERT INTO commande (reference, boulanger_id, date_livraison_souhaitee, adresse_livraison, statut, commentaire, mode_paiement)
                 VALUES (:reference, :boulanger, :date_livraison, :adresse, :statut, :commentaire, :mode_paiement)',
                [
                    'reference'     => $reference,
                    'boulanger'     => $boulangerId,
                    'date_livraison' => $d['date_livraison_souhaitee'],
                    'adresse'       => $d['adresse_livraison'],
                    'statut'        => 'recue',
                    'commentaire'   => $d['commentaire'] ?? null,
                    'mode_paiement' => $d['mode_paiement'] ?? 'carte',
                ]
            );

            foreach ($lignes as $ligne) {
                $produit = Produit::trouver((int) $ligne['produit_id']);
                if ($produit === null) {
                    throw new \RuntimeException('Produit introuvable dans le panier.');
                }
                Database::run(
                    'INSERT INTO ligne_commande (commande_id, produit_id, quantite, prix_unitaire)
                     VALUES (:commande, :produit, :quantite, :prix)',
                    [
                        'commande' => $commandeId,
                        'produit'  => (int) $ligne['produit_id'],
                        'quantite' => (int) $ligne['quantite'],
                        'prix'     => $produit['prix_unitaire'],
                    ]
                );
            }

            HistoriqueStatut::tracer($commandeId, 'recue', \Core\Auth::id(), 'Commande transmise par le boulanger');
            Journal::enregistrer('commande_creee', 'commande', $commandeId, 'Référence ' . $reference . ' - ' . count($lignes) . ' ligne(s)');
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        // Notification aux gestionnaires (F09)
        Notifier::gestionnaires(
            'Nouvelle commande ' . $reference,
            'Une nouvelle commande a été déposée et attend une validation.',
            url('gestionnaire/commande', ['id' => $commandeId]),
            'alerte'
        );

        return $commandeId;
    }

    /**
     * Modifie une commande encore au statut « recue » (F06).
     */
    public static function modifier(int $commandeId, array $d, array $lignes): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            Database::run(
                'UPDATE commande SET date_livraison_souhaitee = :date_livraison, adresse_livraison = :adresse,
                        commentaire = :commentaire, mode_paiement = :mode_paiement
                 WHERE id = :id',
                [
                    'date_livraison' => $d['date_livraison_souhaitee'],
                    'adresse'        => $d['adresse_livraison'],
                    'commentaire'    => $d['commentaire'] ?? null,
                    'mode_paiement'  => $d['mode_paiement'] ?? 'carte',
                    'id'             => $commandeId,
                ]
            );

            Database::run('DELETE FROM ligne_commande WHERE commande_id = :id', ['id' => $commandeId]);

            foreach ($lignes as $ligne) {
                $produit = Produit::trouver((int) $ligne['produit_id']);
                Database::run(
                    'INSERT INTO ligne_commande (commande_id, produit_id, quantite, prix_unitaire)
                     VALUES (:commande, :produit, :quantite, :prix)',
                    [
                        'commande' => $commandeId,
                        'produit'  => (int) $ligne['produit_id'],
                        'quantite' => (int) $ligne['quantite'],
                        'prix'     => $produit['prix_unitaire'] ?? null,
                    ]
                );
            }

            HistoriqueStatut::tracer($commandeId, 'recue', \Core\Auth::id(), 'Commande modifiée par le boulanger');
            Journal::enregistrer('commande_modifiee', 'commande', $commandeId, 'Modification avant validation');
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Notifier::gestionnaires(
            'Commande modifiée',
            'Une commande encore au statut « Reçue » a été modifiée par le boulanger.',
            url('gestionnaire/commande', ['id' => $commandeId]),
            'info'
        );
    }

    // -----------------------------------------------------------------
    // Lecture
    // -----------------------------------------------------------------
    public static function trouver(int $id): ?array
    {
        return Database::fetch(
            "SELECT c.*, b.nom_boulangerie, b.adresse AS boulanger_adresse, b.ville, b.zone_livraison,
                    u.nom AS boulanger_nom, u.email AS boulanger_email, u.telephone AS boulanger_telephone,
                    g.nom AS gestionnaire_nom,
                    l.id AS livraison_id, l.date_livraison, l.livreur, l.vehicule, l.etat AS livraison_etat,
                    l.commentaire AS livraison_commentaire
             FROM commande c
             JOIN boulanger b ON b.id = c.boulanger_id
             JOIN utilisateur u ON u.id = b.utilisateur_id
             LEFT JOIN utilisateur g ON g.id = c.gestionnaire_id
             LEFT JOIN livraison l ON l.commande_id = c.id
             WHERE c.id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    /** Lignes de commande avec le produit associe. */
    public static function lignes(int $commandeId): array
    {
        return Database::fetchAll(
            'SELECT lc.*, p.libelle, p.reference, p.unite, p.stock_disponible, p.actif
             FROM ligne_commande lc
             JOIN produit p ON p.id = lc.produit_id
             WHERE lc.commande_id = :id
             ORDER BY p.libelle',
            ['id' => $commandeId]
        );
    }

    /** Historique horodate des statuts (F07 - frese de suivi). */
    public static function historiques(int $commandeId): array
    {
        return Database::fetchAll(
            "SELECT h.*, u.nom AS auteur, u.role AS auteur_role
             FROM historique_statut h
             LEFT JOIN utilisateur u ON u.id = h.utilisateur_id
             WHERE h.commande_id = :id
             ORDER BY h.date_heure ASC, h.id ASC",
            ['id' => $commandeId]
        );
    }

    /**
     * Liste filtrable des commandes.
     * @param array $filtres [statut, boulanger_id, depuis, jusqu, q]
     */
    public static function lister(array $filtres = [], int $limite = 200): array
    {
        $sql = "SELECT c.*, b.nom_boulangerie, u.nom AS boulanger_nom, u.telephone AS boulanger_telephone,
                       l.date_livraison, l.livreur, l.etat AS livraison_etat,
                       (SELECT COALESCE(SUM(quantite), 0) FROM ligne_commande lc WHERE lc.commande_id = c.id) AS total_sacs,
                       (SELECT COUNT(*) FROM ligne_commande lc WHERE lc.commande_id = c.id) AS nb_lignes
                FROM commande c
                JOIN boulanger b ON b.id = c.boulanger_id
                JOIN utilisateur u ON u.id = b.utilisateur_id
                LEFT JOIN livraison l ON l.commande_id = c.id
                WHERE 1 = 1";
        $params = [];

        if (!empty($filtres['statut']) && in_array($filtres['statut'], self::STATUTS, true)) {
            $sql .= ' AND c.statut = :statut';
            $params['statut'] = $filtres['statut'];
        }
        if (!empty($filtres['boulanger_id'])) {
            $sql .= ' AND c.boulanger_id = :bid';
            $params['bid'] = (int) $filtres['boulanger_id'];
        }
        if (!empty($filtres['depuis'])) {
            $sql .= ' AND c.date_commande >= :depuis';
            $params['depuis'] = $filtres['depuis'] . ' 00:00:00';
        }
        if (!empty($filtres['jusqu'])) {
            $sql .= ' AND c.date_commande <= :jusqu';
            $params['jusqu'] = $filtres['jusqu'] . ' 23:59:59';
        }
        if (!empty($filtres['q'])) {
            $sql .= ' AND (c.reference LIKE :q OR b.nom_boulangerie LIKE :q OR u.nom LIKE :q)';
            $params['q'] = '%' . $filtres['q'] . '%';
        }
        if (!empty($filtres['statuts']) && is_array($filtres['statuts'])) {
            $pairs = [];
            foreach ($filtres['statuts'] as $i => $s) {
                if (in_array($s, self::STATUTS, true)) {
                    $pairs[] = ':st' . $i;
                    $params['st' . $i] = $s;
                }
            }
            if ($pairs !== []) {
                $sql .= ' AND c.statut IN (' . implode(',', $pairs) . ')';
            }
        }

        $ordre = Database::dialecte() === 'sqlite'
            ? "CASE c.statut WHEN 'recue' THEN 1 WHEN 'validee' THEN 2 WHEN 'en_preparation' THEN 3 WHEN 'en_livraison' THEN 4 WHEN 'livree' THEN 5 WHEN 'refusee' THEN 6 ELSE 7 END"
            : 'FIELD(c.statut, "recue", "validee", "en_preparation", "en_livraison", "livree", "refusee", "annulee")';

        $sql .= ' ORDER BY ' . $ordre . ', c.date_commande DESC LIMIT ' . (int) $limite;

        return Database::fetchAll($sql, $params);
    }

    public static function compterParStatut(?int $boulangerId = null): array
    {
        $sql    = 'SELECT statut, COUNT(*) AS total FROM commande';
        $params = [];
        if ($boulangerId !== null) {
            $sql .= ' WHERE boulanger_id = :bid';
            $params['bid'] = $boulangerId;
        }
        $sql .= ' GROUP BY statut';

        $resultat = array_fill_keys(self::STATUTS, 0);
        foreach (Database::fetchAll($sql, $params) as $ligne) {
            $resultat[$ligne['statut']] = (int) $ligne['total'];
        }

        return $resultat;
    }

    /** Commandes recentes d'un boulanger (F08 - reapprovisionnement). */
    public static function dernieresDuBoulanger(int $boulangerId, int $limite = 5): array
    {
        return Database::fetchAll(
            'SELECT c.*, (SELECT COALESCE(SUM(quantite), 0) FROM ligne_commande lc WHERE lc.commande_id = c.id) AS total_sacs
             FROM commande c
             WHERE c.boulanger_id = :bid
             ORDER BY c.date_commande DESC LIMIT ' . (int) $limite,
            ['bid' => $boulangerId]
        );
    }

    /** Commandes actives (non terminees) d'un boulanger. */
    public static function enCoursDuBoulanger(int $boulangerId): array
    {
        return Database::fetchAll(
            "SELECT c.*, l.date_livraison, l.livreur, l.etat AS livraison_etat,
                    (SELECT COALESCE(SUM(quantite), 0) FROM ligne_commande lc WHERE lc.commande_id = c.id) AS total_sacs
             FROM commande c
             LEFT JOIN livraison l ON l.commande_id = c.id
             WHERE c.boulanger_id = :bid AND c.statut IN ('recue','validee','en_preparation','en_livraison')
             ORDER BY c.date_livraison_souhaitee ASC, c.date_commande DESC",
            ['bid' => $boulangerId]
        );
    }

    /** Dernier produit commande, pour faciliter le reapprovisionnement. */
    public static function dernieresLignes(int $boulangerId, int $limite = 6): array
    {
        return Database::fetchAll(
            'SELECT p.id, p.libelle, p.reference, p.unite, p.prix_unitaire, MAX(c.date_commande) AS derniere_commande,
                    SUM(lc.quantite) AS quantite_cumulee
             FROM ligne_commande lc
             JOIN commande c ON c.id = lc.commande_id
             JOIN produit p ON p.id = lc.produit_id
             WHERE c.boulanger_id = :bid AND p.actif = 1
             GROUP BY p.id, p.libelle, p.reference, p.unite, p.prix_unitaire
             ORDER BY derniere_commande DESC LIMIT ' . (int) $limite,
            ['bid' => $boulangerId]
        );
    }

    // -----------------------------------------------------------------
    // Cycle de vie : validation, refus, changement de statut
    // -----------------------------------------------------------------
    /** F11 : validation par le gestionnaire (reserve le stock). */
    public static function valider(int $commandeId, int $gestionnaireId): array
    {
        $commande = self::trouver($commandeId);
        if ($commande === null) {
            return ['ok' => false, 'message' => 'Commande introuvable.'];
        }
        if ($commande['statut'] !== 'recue') {
            return ['ok' => false, 'message' => 'Seule une commande « Reçue » peut être validée.'];
        }

        $lignes = self::lignes($commandeId);
        if ($lignes === []) {
            return ['ok' => false, 'message' => 'La commande ne contient aucune ligne.'];
        }

        // Verification de la disponibilite du produit (regle de gestion).
        $manquants = [];
        foreach ($lignes as $ligne) {
            if ((int) $ligne['actif'] !== 1 || (int) $ligne['stock_disponible'] < (int) $ligne['quantite']) {
                $manquants[] = $ligne['libelle'] . ' (' . $ligne['quantite'] . ' demandé(s), ' . (int) $ligne['stock_disponible'] . ' disponible(s))';
            }
        }
        if ($manquants !== []) {
            return ['ok' => false, 'message' => 'Disponibilité insuffisante : ' . implode(' ; ', $manquants) . '.'];
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            foreach ($lignes as $ligne) {
                Produit::decrementer((int) $ligne['produit_id'], (int) $ligne['quantite']);
            }
            Database::run(
                'UPDATE commande SET statut = :statut, gestionnaire_id = :gid, motif_refus = NULL WHERE id = :id',
                ['statut' => 'validee', 'gid' => $gestionnaireId, 'id' => $commandeId]
            );
            HistoriqueStatut::tracer($commandeId, 'validee', $gestionnaireId, 'Disponibilité vérifiée et commande validée');
            Journal::enregistrer('commande_validee', 'commande', $commandeId, 'Validation du gestionnaire');
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        self::prevenirBoulanger($commande, 'validee', 'Votre commande est validée. Elle sera préparée puis livrée.');

        return ['ok' => true, 'message' => 'Commande validée, le stock a été réservé.'];
    }

    /** F11 : refus motive (restaure le stock si la commande etait validee). */
    public static function refuser(int $commandeId, int $gestionnaireId, string $motif): array
    {
        $commande = self::trouver($commandeId);
        if ($commande === null) {
            return ['ok' => false, 'message' => 'Commande introuvable.'];
        }
        if (!in_array($commande['statut'], ['recue', 'validee'], true)) {
            return ['ok' => false, 'message' => 'Cette commande ne peut plus être refusée.'];
        }
        if (trim($motif) === '') {
            return ['ok' => false, 'message' => 'Le motif de refus est obligatoire.'];
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            if ($commande['statut'] === 'validee') {
                foreach (self::lignes($commandeId) as $ligne) {
                    Produit::restaurer((int) $ligne['produit_id'], (int) $ligne['quantite']);
                }
            }
            Database::run(
                'UPDATE commande SET statut = :statut, gestionnaire_id = :gid, motif_refus = :motif WHERE id = :id',
                ['statut' => 'refusee', 'gid' => $gestionnaireId, 'motif' => mb_substr(trim($motif), 0, 255), 'id' => $commandeId]
            );
            HistoriqueStatut::tracer($commandeId, 'refusee', $gestionnaireId, trim($motif));
            Journal::enregistrer('commande_refusee', 'commande', $commandeId, 'Motif : ' . trim($motif));
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        self::prevenirBoulanger($commande, 'refusee', 'Motif du refus : ' . trim($motif));

        return ['ok' => true, 'message' => 'Commande refusée. Le boulanger a été informé du motif.'];
    }

    /** F12 : passage au statut suivant de la sequence. */
    public static function changerStatut(int $commandeId, string $nouveauStatut, int $utilisateurId, ?string $commentaire = null): array
    {
        $commande = self::trouver($commandeId);
        if ($commande === null) {
            return ['ok' => false, 'message' => 'Commande introuvable.'];
        }
        if (!array_key_exists($commande['statut'], self::SEQUENCE)) {
            return ['ok' => false, 'message' => 'La commande est terminée ou interrompue : son statut ne peut plus évoluer.'];
        }
        if (self::SEQUENCE[$commande['statut']] !== $nouveauStatut) {
            return ['ok' => false, 'message' => 'Passage de statut non autorisé : la séquence doit être respectée.'];
        }

        Database::run(
            'UPDATE commande SET statut = :statut, gestionnaire_id = COALESCE(gestionnaire_id, :gid) WHERE id = :id',
            ['statut' => $nouveauStatut, 'gid' => $utilisateurId, 'id' => $commandeId]
        );
        HistoriqueStatut::tracer($commandeId, $nouveauStatut, $utilisateurId, $commentaire);
        Journal::enregistrer('statut_modifie', 'commande', $commandeId, 'Nouveau statut : ' . $nouveauStatut);

        [, $libelle] = statut_meta($nouveauStatut);
        self::prevenirBoulanger($commande, $nouveauStatut, 'Nouveau statut de votre commande : ' . $libelle . '.');

        return ['ok' => true, 'message' => 'Statut mis à jour : ' . $libelle . '.'];
    }

    /** F06 : annulation par le boulanger tant que la commande est « recue ». */
    public static function annuler(int $commandeId, int $boulangerId, int $utilisateurId): array
    {
        $commande = self::trouver($commandeId);
        if ($commande === null || (int) $commande['boulanger_id'] !== $boulangerId) {
            return ['ok' => false, 'message' => 'Commande introuvable.'];
        }
        if (!in_array($commande['statut'], self::STATUTS_MODIFIABLES, true)) {
            return ['ok' => false, 'message' => 'Une commande déjà validée ne peut plus être annulée. Contactez votre commercial.'];
        }

        Database::run('UPDATE commande SET statut = :statut WHERE id = :id', ['statut' => 'annulee', 'id' => $commandeId]);
        HistoriqueStatut::tracer($commandeId, 'annulee', $utilisateurId, 'Annulation par le boulanger');
        Journal::enregistrer('commande_annulee', 'commande', $commandeId, 'Annulation boulanger');

        Notifier::gestionnaires(
            'Commande annulée',
            'La commande ' . $commande['reference'] . ' a été annulée par le boulanger.',
            url('gestionnaire/commande', ['id' => $commandeId]),
            'info'
        );

        return ['ok' => true, 'message' => 'Commande annulée. Le stock n\'a pas été engagé.'];
    }

    /** F14 : notification du boulanger a chaque changement de statut. */
    private static function prevenirBoulanger(array $commande, string $statut, string $message): void
    {
        $userId = (int) Database::scalar('SELECT utilisateur_id FROM boulanger WHERE id = :id', ['id' => (int) $commande['boulanger_id']]);
        if ($userId <= 0) {
            return;
        }

        [, $libelle, $icone] = statut_meta($statut);
        Notifier::a(
            $userId,
            'Commande ' . $commande['reference'] . ' – ' . $libelle,
            $message,
            url('boulanger/commande', ['id' => (int) $commande['id']]),
            in_array($statut, ['refusee', 'annulee'], true) ? 'danger' : ($statut === 'livree' ? 'succes' : 'info')
        );

        // Canal SMS prevu en phase ulterieure (F19) : Notifier::sms($telephone, $message);
    }

    // -----------------------------------------------------------------
    // Statistiques et export (F15, F16)
    // -----------------------------------------------------------------
    public static function statistiques(?string $depuis = null, ?string $jusqu = null): array
    {
        $filtre = '';
        $params = [];
        if ($depuis !== null) {
            $filtre .= ' AND date_commande >= :depuis';
            $params['depuis'] = $depuis . ' 00:00:00';
        }
        if ($jusqu !== null) {
            $filtre .= ' AND date_commande <= :jusqu';
            $params['jusqu'] = $jusqu . ' 23:59:59';
        }

        $total = (int) Database::scalar('SELECT COUNT(*) FROM commande WHERE 1 = 1' . $filtre, $params);
        $sacs  = (int) Database::scalar(
            'SELECT COALESCE(SUM(lc.quantite), 0)
             FROM ligne_commande lc JOIN commande c ON c.id = lc.commande_id
             WHERE 1 = 1' . str_replace('date_commande', 'c.date_commande', $filtre),
            $params
        );

        $delaiSql = Database::dialecte() === 'sqlite'
            ? "SELECT AVG(TIMESTAMPDIFF('HOUR', c.date_commande, h.date_heure)) AS moyenne_heures"
            : 'SELECT AVG(TIMESTAMPDIFF(HOUR, c.date_commande, h.date_heure)) AS moyenne_heures';

        $delai = Database::fetch(
            $delaiSql . '
             FROM commande c
             JOIN historique_statut h ON h.commande_id = c.id AND h.statut = "livree"
             WHERE 1 = 1' . str_replace('date_commande', 'c.date_commande', $filtre),
            $params
        );

        return [
            'total'          => $total,
            'sacs'           => $sacs,
            'par_statut'     => self::compterParStatut(),
            'delai_moyen'    => $delai !== null && $delai['moyenne_heures'] !== null ? round((float) $delai['moyenne_heures'], 1) : null,
            'par_mois'       => self::volumesParMois(6),
            'top_produits'   => self::topProduits(5, $depuis, $jusqu),
            'top_boulangers' => self::topBoulangers(5, $depuis, $jusqu),
            'taux_livraison' => $total > 0
                ? round(((int) Database::scalar('SELECT COUNT(*) FROM commande WHERE statut = "livree"' . $filtre, $params)) * 100 / $total, 1)
                : 0.0,
        ];
    }

    public static function volumesParMois(int $mois = 6): array
    {
        $depuis = Database::dialecte() === 'sqlite'
            ? "c.date_commande >= DATE(CURDATE(), '-' || :m || ' months')"
            : 'c.date_commande >= DATE_SUB(CURDATE(), INTERVAL :m MONTH)';

        $rows = Database::fetchAll(
            'SELECT DATE_FORMAT(c.date_commande, "%Y-%m") AS periode,
                    COUNT(DISTINCT c.id) AS nb_commandes,
                    COALESCE(SUM(lc.quantite), 0) AS total_sacs
             FROM commande c
             LEFT JOIN ligne_commande lc ON lc.commande_id = c.id
             WHERE ' . $depuis . '
             GROUP BY periode ORDER BY periode',
            ['m' => $mois]
        );

        return $rows;
    }

    public static function topProduits(int $limite = 5, ?string $depuis = null, ?string $jusqu = null): array
    {
        $filtre = '';
        $params = [];
        if ($depuis !== null) {
            $filtre .= ' AND c.date_commande >= :depuis';
            $params['depuis'] = $depuis . ' 00:00:00';
        }
        if ($jusqu !== null) {
            $filtre .= ' AND c.date_commande <= :jusqu';
            $params['jusqu'] = $jusqu . ' 23:59:59';
        }

        return Database::fetchAll(
            'SELECT p.libelle, p.reference, SUM(lc.quantite) AS total_sacs, COUNT(DISTINCT c.id) AS nb_commandes
             FROM ligne_commande lc
             JOIN produit p ON p.id = lc.produit_id
             JOIN commande c ON c.id = lc.commande_id
             WHERE 1 = 1' . $filtre . '
             GROUP BY p.id, p.libelle, p.reference
             ORDER BY total_sacs DESC LIMIT ' . (int) $limite,
            $params
        );
    }

    public static function topBoulangers(int $limite = 5, ?string $depuis = null, ?string $jusqu = null): array
    {
        $filtre = '';
        $params = [];
        if ($depuis !== null) {
            $filtre .= ' AND c.date_commande >= :depuis';
            $params['depuis'] = $depuis . ' 00:00:00';
        }
        if ($jusqu !== null) {
            $filtre .= ' AND c.date_commande <= :jusqu';
            $params['jusqu'] = $jusqu . ' 23:59:59';
        }

        return Database::fetchAll(
            'SELECT b.nom_boulangerie, b.ville, COUNT(DISTINCT c.id) AS nb_commandes,
                    COALESCE(SUM(lc.quantite), 0) AS total_sacs
             FROM commande c
             JOIN boulanger b ON b.id = c.boulanger_id
             LEFT JOIN ligne_commande lc ON lc.commande_id = c.id
             WHERE 1 = 1' . $filtre . '
             GROUP BY b.id, b.nom_boulangerie, b.ville
             ORDER BY total_sacs DESC LIMIT ' . (int) $limite,
            $params
        );
    }

    /** F16 : jeu de donnees pret pour l'export CSV (compatible CRM / SAP). */
    public static function donneesExport(array $filtres = []): array
    {
        return self::lister($filtres, 5000);
    }

    private static function referenceUnique(): string
    {
        do {
            $reference = 'CMD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            $existe    = (int) Database::scalar('SELECT COUNT(*) FROM commande WHERE reference = :r', ['r' => $reference]);
        } while ($existe > 0);

        return $reference;
    }

    /** Le boulanger ne peut consulter que ses propres commandes (regle de gestion). */
    public static function appartientAuBoulanger(int $commandeId, int $boulangerId): bool
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM commande WHERE id = :id AND boulanger_id = :bid',
            ['id' => $commandeId, 'bid' => $boulangerId]
        ) > 0;
    }

    public static function delaiMinimumHeures(): int
    {
        return Parametre::entier('delai_minimum_heures', DELAI_MINIMUM_HEURES);
    }
}

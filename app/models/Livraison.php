<?php
/**
 * Modele LIVRAISON : planification et suivi des tournees (F13).
 */

declare(strict_types=1);

namespace Model;

use Core\Database;

final class Livraison
{
    public const ETATS = ['planifiee', 'en_cours', 'livree', 'echouee'];

    public static function trouver(int $commandeId): ?array
    {
        return Database::fetch('SELECT * FROM livraison WHERE commande_id = :id LIMIT 1', ['id' => $commandeId]);
    }

    /**
     * Planifie ou replanifie la livraison d'une commande.
     * Retourne false si la commande n'est pas dans un etat compatible.
     */
    public static function planifier(int $commandeId, array $d): bool
    {
        $commande = Commande::trouver($commandeId);
        if ($commande === null || !in_array($commande['statut'], ['validee', 'en_preparation', 'en_livraison'], true)) {
            return false;
        }

        $existante = self::trouver($commandeId);
        if ($existante === null) {
            Database::run(
                'INSERT INTO livraison (commande_id, date_livraison, livreur, vehicule, etat, commentaire)
                 VALUES (:commande, :date_livraison, :livreur, :vehicule, :etat, :commentaire)',
                [
                    'commande'       => $commandeId,
                    'date_livraison' => $d['date_livraison'],
                    'livreur'        => $d['livreur'] ?? null,
                    'vehicule'       => $d['vehicule'] ?? null,
                    'etat'           => 'planifiee',
                    'commentaire'    => $d['commentaire'] ?? null,
                ]
            );
        } else {
            Database::run(
                'UPDATE livraison SET date_livraison = :date_livraison, livreur = :livreur,
                        vehicule = :vehicule, commentaire = :commentaire
                 WHERE commande_id = :commande',
                [
                    'date_livraison' => $d['date_livraison'],
                    'livreur'        => $d['livreur'] ?? null,
                    'vehicule'       => $d['vehicule'] ?? null,
                    'commentaire'    => $d['commentaire'] ?? null,
                    'commande'       => $commandeId,
                ]
            );
        }

        return true;
    }

    public static function changerEtat(int $commandeId, string $etat, ?string $commentaire = null): bool
    {
        if (!in_array($etat, self::ETATS, true)) {
            return false;
        }

        Database::run(
            'UPDATE livraison SET etat = :etat, commentaire = :commentaire WHERE commande_id = :commande',
            ['etat' => $etat, 'commentaire' => $commentaire, 'commande' => $commandeId]
        );

        return true;
    }

    /**
     * Planning des livraisons (F13).
     * @param string|null $date  filtre sur une journee precise
     */
    public static function planning(?string $date = null, bool $aVenir = false): array
    {
        $sql = "SELECT l.*, c.reference, c.statut, c.adresse_livraison, c.date_livraison_souhaitee,
                       c.id AS commande_id, b.nom_boulangerie, b.ville, b.zone_livraison,
                       u.nom AS contact, u.telephone,
                       (SELECT COALESCE(SUM(quantite), 0) FROM ligne_commande lc WHERE lc.commande_id = c.id) AS total_sacs
                FROM livraison l
                JOIN commande c ON c.id = l.commande_id
                JOIN boulanger b ON b.id = c.boulanger_id
                JOIN utilisateur u ON u.id = b.utilisateur_id
                WHERE 1 = 1";
        $params = [];

        if ($date !== null && $date !== '') {
            $sql .= ' AND l.date_livraison = :date';
            $params['date'] = $date;
        } elseif ($aVenir) {
            $sql .= ' AND l.date_livraison >= CURDATE()';
        }

        $ordre = Database::dialecte() === 'sqlite'
            ? "CASE l.etat WHEN 'en_cours' THEN 1 WHEN 'planifiee' THEN 2 WHEN 'echouee' THEN 3 ELSE 4 END"
            : "FIELD(l.etat, 'en_cours', 'planifiee', 'echouee', 'livree')";

        $sql .= ' ORDER BY ' . $ordre . ', l.date_livraison, c.reference';

        return Database::fetchAll($sql, $params);
    }

    /** Commandes validees/preparees sans livraison planifiee. */
    public static function aPlanifier(): array
    {
        return Database::fetchAll(
            "SELECT c.*, b.nom_boulangerie, b.zone_livraison, u.telephone,
                    (SELECT COALESCE(SUM(quantite), 0) FROM ligne_commande lc WHERE lc.commande_id = c.id) AS total_sacs
             FROM commande c
             JOIN boulanger b ON b.id = c.boulanger_id
             JOIN utilisateur u ON u.id = b.utilisateur_id
             LEFT JOIN livraison l ON l.commande_id = c.id
             WHERE l.id IS NULL AND c.statut IN ('validee','en_preparation')
             ORDER BY c.date_livraison_souhaitee"
        );
    }

    public static function statistiques(?string $depuis = null): array
    {
        $filtre = '';
        $params = [];
        if ($depuis !== null) {
            $filtre = ' AND date_livraison >= :depuis';
            $params['depuis'] = $depuis;
        }

        return [
            'planifiees' => (int) Database::scalar("SELECT COUNT(*) FROM livraison WHERE etat = 'planifiee'" . $filtre, $params),
            'en_cours'   => (int) Database::scalar("SELECT COUNT(*) FROM livraison WHERE etat = 'en_cours'" . $filtre, $params),
            'livrees'    => (int) Database::scalar("SELECT COUNT(*) FROM livraison WHERE etat = 'livree'" . $filtre, $params),
            'echouees'   => (int) Database::scalar("SELECT COUNT(*) FROM livraison WHERE etat = 'echouee'" . $filtre, $params),
            'a_planifier' => count(self::aPlanifier()),
        ];
    }
}

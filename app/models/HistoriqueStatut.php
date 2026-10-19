<?php
/**
 * Modele HISTORIQUE_STATUT : tracabilite horodatee et attribuee
 * de chaque changement de statut (regle de gestion).
 */

declare(strict_types=1);

namespace Model;

use Core\Database;

final class HistoriqueStatut
{
    public static function tracer(int $commandeId, string $statut, ?int $utilisateurId, ?string $motif = null): void
    {
        Database::run(
            'INSERT INTO historique_statut (commande_id, statut, utilisateur_id, motif)
             VALUES (:commande, :statut, :uid, :motif)',
            [
                'commande' => $commandeId,
                'statut'   => $statut,
                'uid'      => $utilisateurId,
                'motif'    => $motif === null ? null : mb_substr($motif, 0, 255),
            ]
        );
    }

    public static function parCommande(int $commandeId): array
    {
        return Database::fetchAll(
            'SELECT h.*, u.nom AS auteur, u.role AS auteur_role
             FROM historique_statut h
             LEFT JOIN utilisateur u ON u.id = h.utilisateur_id
             WHERE h.commande_id = :id
             ORDER BY h.date_heure ASC, h.id ASC',
            ['id' => $commandeId]
        );
    }

    /** Derniere mise a jour d'une commande. */
    public static function derniereEtape(int $commandeId): ?array
    {
        return Database::fetch(
            'SELECT * FROM historique_statut WHERE commande_id = :id ORDER BY date_heure DESC, id DESC LIMIT 1',
            ['id' => $commandeId]
        );
    }
}

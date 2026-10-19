<?php
/**
 * Modele BOULANGER : fiche client (boulangerie) rattachee a un compte.
 */

declare(strict_types=1);

namespace Model;

use Core\Database;

final class Boulanger
{
    public static function creer(int $utilisateurId, array $donnees): void
    {
        Database::run(
            'INSERT INTO boulanger (utilisateur_id, nom_boulangerie, adresse, ville, zone_livraison)
             VALUES (:uid, :nom, :adresse, :ville, :zone)',
            [
                'uid'     => $utilisateurId,
                'nom'     => $donnees['nom_boulangerie'] ?? $donnees['nom'],
                'adresse' => $donnees['adresse'] ?? 'À préciser',
                'ville'   => $donnees['ville'] ?? 'Dakar',
                'zone'    => $donnees['zone_livraison'] ?? null,
            ]
        );
    }

    public static function mettreAJour(int $utilisateurId, array $donnees): void
    {
        Database::run(
            'UPDATE boulanger SET nom_boulangerie = :nom, adresse = :adresse, ville = :ville, zone_livraison = :zone
             WHERE utilisateur_id = :uid',
            [
                'nom'     => $donnees['nom_boulangerie'],
                'adresse' => $donnees['adresse'],
                'ville'   => $donnees['ville'] ?? 'Dakar',
                'zone'    => $donnees['zone_livraison'] ?? null,
                'uid'     => $utilisateurId,
            ]
        );
    }

    public static function trouverParUtilisateur(int $utilisateurId): ?array
    {
        return Database::fetch('SELECT * FROM boulanger WHERE utilisateur_id = :uid LIMIT 1', ['uid' => $utilisateurId]);
    }

    /** Liste des boulangeries avec leur volume de commandes (suivi commercial). */
    public static function listerAvecActivite(int $jours = 90): array
    {
        $depuis = Database::dialecte() === 'sqlite'
            ? "c.date_commande >= DATETIME(NOW(), '-' || :jours || ' days')"
            : 'c.date_commande >= DATE_SUB(NOW(), INTERVAL :jours DAY)';

        return Database::fetchAll(
            "SELECT b.id, b.nom_boulangerie, b.adresse, b.ville, b.zone_livraison,
                    u.nom AS contact, u.email, u.telephone, u.actif,
                    COUNT(c.id) AS nb_commandes,
                    COALESCE(SUM(ca.total_sacs), 0) AS total_sacs,
                    MAX(c.date_commande) AS derniere_commande
             FROM boulanger b
             JOIN utilisateur u ON u.id = b.utilisateur_id
             LEFT JOIN commande c ON c.boulanger_id = b.id AND " . $depuis . "
             LEFT JOIN (
                 SELECT commande_id, SUM(quantite) AS total_sacs FROM ligne_commande GROUP BY commande_id
             ) ca ON ca.commande_id = c.id
             GROUP BY b.id, b.nom_boulangerie, b.adresse, b.ville, b.zone_livraison, u.nom, u.email, u.telephone, u.actif
             ORDER BY total_sacs DESC",
            ['jours' => $jours]
        );
    }
}

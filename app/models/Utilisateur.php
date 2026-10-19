<?php
/**
 * Modele UTILISATEUR : comptes et droits d'acces.
 */

declare(strict_types=1);

namespace Model;

use Core\Database;

final class Utilisateur
{
    public const ROLES = ['boulanger', 'gestionnaire', 'administrateur'];

    public static function lister(?string $role = null, ?string $recherche = null): array
    {
        $sql    = "SELECT u.*, b.nom_boulangerie, b.ville
                   FROM utilisateur u
                   LEFT JOIN boulanger b ON b.utilisateur_id = u.id
                   WHERE 1 = 1";
        $params = [];

        if ($role !== null && in_array($role, self::ROLES, true)) {
            $sql .= ' AND u.role = :role';
            $params['role'] = $role;
        }
        if ($recherche !== null && $recherche !== '') {
            $sql .= ' AND (u.nom LIKE :q OR u.email LIKE :q OR u.telephone LIKE :q OR b.nom_boulangerie LIKE :q)';
            $params['q'] = '%' . $recherche . '%';
        }

        $sql .= ' ORDER BY u.role, u.nom';

        return Database::fetchAll($sql, $params);
    }

    public static function trouver(int $id): ?array
    {
        return Database::fetch(
            "SELECT u.*, b.id AS boulanger_id, b.nom_boulangerie, b.adresse AS boulanger_adresse,
                    b.ville AS boulanger_ville, b.zone_livraison
             FROM utilisateur u
             LEFT JOIN boulanger b ON b.utilisateur_id = u.id
             WHERE u.id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    public static function parEmail(string $email): ?array
    {
        return Database::fetch('SELECT * FROM utilisateur WHERE email = :email LIMIT 1', ['email' => mb_strtolower($email)]);
    }

    public static function emailExiste(string $email, ?int $saufId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM utilisateur WHERE email = :email';
        $params = ['email' => mb_strtolower($email)];
        if ($saufId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $saufId;
        }

        return (int) Database::scalar($sql, $params) > 0;
    }

    /** Cree un compte (+ fiche boulanger si besoin). Retourne l'identifiant cree. */
    public static function creer(array $donnees): int
    {
        $id = Database::insert(
            'INSERT INTO utilisateur (nom, email, telephone, mot_de_passe, role, actif, double_verification)
             VALUES (:nom, :email, :telephone, :mdp, :role, :actif, :dv)',
            [
                'nom'       => $donnees['nom'],
                'email'     => mb_strtolower($donnees['email']),
                'telephone' => $donnees['telephone'] ?? null,
                'mdp'       => password_hash($donnees['mot_de_passe'], PASSWORD_BCRYPT),
                'role'      => $donnees['role'],
                'actif'     => (int) ($donnees['actif'] ?? 0),
                'dv'        => (int) ($donnees['double_verification'] ?? 0),
            ]
        );

        if ($donnees['role'] === 'boulanger') {
            Boulanger::creer($id, $donnees);
        }

        return $id;
    }

    public static function mettreAJour(int $id, array $donnees): void
    {
        Database::run(
            'UPDATE utilisateur SET nom = :nom, email = :email, telephone = :telephone,
                    role = :role, double_verification = :dv
             WHERE id = :id',
            [
                'nom'       => $donnees['nom'],
                'email'     => mb_strtolower($donnees['email']),
                'telephone' => $donnees['telephone'] ?? null,
                'role'      => $donnees['role'],
                'dv'        => (int) ($donnees['double_verification'] ?? 0),
                'id'        => $id,
            ]
        );
    }

    public static function changerMotDePasse(int $id, string $clair): void
    {
        Database::run(
            'UPDATE utilisateur SET mot_de_passe = :mdp, reset_token = NULL, reset_expire_le = NULL,
                    tentatives_connexion = 0, bloque_jusqua = NULL
             WHERE id = :id',
            ['mdp' => password_hash($clair, PASSWORD_BCRYPT), 'id' => $id]
        );
    }

    public static function basculerActivation(int $id): int
    {
        Database::run('UPDATE utilisateur SET actif = 1 - actif WHERE id = :id AND id <> :self', [
            'id'   => $id,
            'self' => (int) (\Core\Auth::id() ?? 0),
        ]);

        return (int) Database::scalar('SELECT actif FROM utilisateur WHERE id = :id', ['id' => $id]);
    }

    /** Comptes actifs ayant un role donne (destinataires de notifications). */
    public static function actifsParRole(string ...$roles): array
    {
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $sql = "SELECT id, nom FROM utilisateur WHERE actif = 1 AND role IN ($placeholders) ORDER BY nom";

        return Database::fetchAll($sql, $roles);
    }

    public static function statistiques(): array
    {
        return [
            'total'          => (int) Database::scalar('SELECT COUNT(*) FROM utilisateur'),
            'actifs'         => (int) Database::scalar('SELECT COUNT(*) FROM utilisateur WHERE actif = 1'),
            'boulangers'     => (int) Database::scalar("SELECT COUNT(*) FROM utilisateur WHERE role = 'boulanger' AND actif = 1"),
            'gestionnaires'  => (int) Database::scalar("SELECT COUNT(*) FROM utilisateur WHERE role = 'gestionnaire' AND actif = 1"),
            'admins'         => (int) Database::scalar("SELECT COUNT(*) FROM utilisateur WHERE role = 'administrateur' AND actif = 1"),
            'inactifs'       => (int) Database::scalar('SELECT COUNT(*) FROM utilisateur WHERE actif = 0'),
        ];
    }
}

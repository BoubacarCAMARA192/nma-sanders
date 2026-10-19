<?php
/**
 * Notifications in-app (F09, F14).
 * Le canal SMS (F19) est prevu en phase ulterieure : le point d'entree
 * `Notifier::sms()` centralise l'appel au fournisseur et reste inactif tant
 * qu'aucune passerelle n'est configuree.
 */

declare(strict_types=1);

namespace Core;

final class Notifier
{
    /** Envoie une notification a un utilisateur precis. */
    public static function a(int $utilisateurId, string $titre, string $message, ?string $lien = null, string $type = 'info'): void
    {
        if ($utilisateurId <= 0) {
            return;
        }

        Database::run(
            'INSERT INTO notification (utilisateur_id, titre, message, lien, type)
             VALUES (:uid, :titre, :message, :lien, :type)',
            [
                'uid'     => $utilisateurId,
                'titre'   => mb_substr($titre, 0, 150),
                'message' => mb_substr($message, 0, 500),
                'lien'    => $lien,
                'type'    => in_array($type, ['info', 'succes', 'alerte', 'danger'], true) ? $type : 'info',
            ]
        );
    }

    /** Notifie tous les gestionnaires actifs (F09). */
    public static function gestionnaires(string $titre, string $message, ?string $lien = null, string $type = 'info'): void
    {
        $ids = Database::fetchAll(
            "SELECT id FROM utilisateur WHERE role IN ('gestionnaire','administrateur') AND actif = 1"
        );
        foreach ($ids as $row) {
            self::a((int) $row['id'], $titre, $message, $lien, $type);
        }
    }

    public static function nonLues(int $utilisateurId): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM notification WHERE utilisateur_id = :uid AND lu = 0',
            ['uid' => $utilisateurId]
        );
    }

    public static function dernieres(int $utilisateurId, int $limite = 8): array
    {
        return Database::fetchAll(
            'SELECT * FROM notification WHERE utilisateur_id = :uid ORDER BY lu ASC, cree_le DESC LIMIT ' . (int) $limite,
            ['uid' => $utilisateurId]
        );
    }

    public static function marquerToutesLues(int $utilisateurId): void
    {
        Database::run('UPDATE notification SET lu = 1 WHERE utilisateur_id = :uid AND lu = 0', ['uid' => $utilisateurId]);
    }

    /**
     * Envoi SMS (F19 - phase ulterieure).
     * Brancher ici l'API du fournisseur retenu par NMA Sanders.
     */
    public static function sms(string $telephone, string $message): bool
    {
        if (!defined('SMS_API_KEY') || SMS_API_KEY === '') {
            return false; // aucun fournisseur configure : notification in-app uniquement
        }

        return false;
    }
}

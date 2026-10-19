<?php
/**
 * Journal des operations : chaque action structurante est horodatee,
 * attribuee a son auteur et conservee avec l'adresse IP.
 */

declare(strict_types=1);

namespace Core;

final class Journal
{
    public static function enregistrer(string $action, ?string $entite = null, ?int $entiteId = null, ?string $details = null, ?int $utilisateurId = null): void
    {
        try {
            Database::run(
                'INSERT INTO journal_operation (utilisateur_id, action, entite, entite_id, details, adresse_ip)
                 VALUES (:uid, :action, :entite, :eid, :details, :ip)',
                [
                    'uid'     => $utilisateurId ?? Auth::id(),
                    'action'  => mb_substr($action, 0, 80),
                    'entite'  => $entite,
                    'eid'     => $entiteId,
                    'details' => $details === null ? null : mb_substr($details, 0, 500),
                    'ip'      => self::ip(),
                ]
            );
        } catch (\Throwable $e) {
            error_log('Journal: ' . $e->getMessage());
        }
    }

    public static function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /** Auteur lisible d'une operation. */
    public static function auteur(?string $nom, ?string $role): string
    {
        if ($nom === null) {
            return 'Système';
        }
        $libelleRole = match ($role) {
            'boulanger'      => 'Boulanger',
            'gestionnaire'   => 'Gestionnaire',
            'administrateur' => 'Administrateur',
            default          => 'Utilisateur',
        };

        return $nom . ' (' . $libelleRole . ')';
    }
}

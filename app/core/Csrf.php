<?php
/**
 * Protection CSRF : jeton unique par session, verifie sur toute requete mutante.
 */

declare(strict_types=1);

namespace Core;

use Core\Journal;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function verifier(?string $token): bool
    {
        $reference = $_SESSION[self::SESSION_KEY] ?? null;

        return is_string($reference)
            && is_string($token)
            && $token !== ''
            && hash_equals($reference, $token);
    }

    /** Interrompt la requete si le jeton est invalide (a appeler en debut de POST). */
    public static function proteger(): void
    {
        if (self::verifier($_POST['_token'] ?? null)) {
            return;
        }

        // Journalisation de la tentative (protection contre le CSRF).
        Journal::enregistrer('csrf_rejete', 'securite', null, 'Requête POST sans jeton valide : ' . (get_param('r') ?? ''));

        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="fr"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Session expirée</title>'
            . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
            . '</head><body class="bg-light"><div class="container py-5">'
            . '<div class="card shadow-sm mx-auto" style="max-width:480px"><div class="card-body p-4 text-center">'
            . '<h1 class="h5 fw-bold mb-2">Session expirée</h1>'
            . '<p class="text-muted small">Votre session a expiré ou le jeton de sécurité est invalide. '
            . 'Merci de vous reconnecter pour continuer.</p>'
            . '<a class="btn btn-primary" href="' . e(url('connexion')) . '">Se reconnecter</a>'
            . '</div></div></div></body></html>';
        exit;
    }
}

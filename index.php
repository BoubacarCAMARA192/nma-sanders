<?php
/**
 * Point d'entree unique de la plateforme NMA Sanders.
 * Toutes les requetes sont traitees par le routeur (app/core/Router.php).
 */

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use Core\Router;
use Core\View;

// En-tetes de securite appliques a toutes les reponses.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 1; mode=block');

// En production (HTTPS), decommenter la ligne suivante :
// header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

try {
    (new Router())->dispatcher();
} catch (Throwable $e) {
    error_log('Fatal: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (APP_DEBUG) {
        throw $e;
    }
    View::erreur(500, 'Erreur interne', 'Une erreur est survenue. Merci de réessayer.');
} finally {
    if (class_exists(\Core\Sqlite::class)) {
        \Core\Sqlite::synchroniser();
    }
}

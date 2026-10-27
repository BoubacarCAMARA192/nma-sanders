<?php
/**
 * Configuration generale de la plateforme Sanders Connect.
 * Trois couches : presentation (app/views), traitement (app/controllers + app/core),
 * donnees (app/models + MySQL).
 */

declare(strict_types=1);

// --- Detection du protocole (local, mutualise, proxy type Vercel) ------
$enHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

// --- Session securisee -------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $enHttps,
    ]);
    session_name('SANDERS_CONNECT_SID');
    session_start();
}

// --- Base de donnees ---------------------------------------------------
define('DB_HOST', getenv('SC_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('SC_DB_PORT') ?: '3306');
define('DB_NAME', getenv('SC_DB_NAME') ?: 'sanders_connect');
define('DB_USER', getenv('SC_DB_USER') ?: 'root');
define('DB_PASS', getenv('SC_DB_PASS') !== false ? getenv('SC_DB_PASS') : '');

// --- Application -------------------------------------------------------
define('APP_NAME', 'Sanders Connect');
define('APP_SLOGAN', 'Commandez votre farine en toute autonomie');
define('APP_VERSION', '1.0');
define('APP_DEBUG', filter_var(getenv('SC_DEBUG') !== false ? getenv('SC_DEBUG') : '1', FILTER_VALIDATE_BOOLEAN));
// Comptes de demonstration affiches sur la page de connexion (SC_DEMO=1).
define('APP_DEMO', filter_var(getenv('SC_DEMO') !== false ? getenv('SC_DEMO') : (APP_DEBUG ? '1' : '0'), FILTER_VALIDATE_BOOLEAN));
define('APP_TIMEZONE', 'Africa/Dakar');
define('APP_LOCALE', 'fr_FR');

date_default_timezone_set(APP_TIMEZONE);
setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'French');

// --- Chemins -----------------------------------------------------------
define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'app');
define('VIEW_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'views');
define('STORAGE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'storage');

/**
 * Detection automatique de l'URL de base (fonctionne en local, sous dossier
 * htdocs ou a la racine d'un hebergement mutualise).
 */
$baseForcee = getenv('SC_BASE_URL');
if ($baseForcee !== false) {
    // Deploiements particuliers (ex. Vercel) : URL de base imposee.
    $scriptDir = rtrim(str_replace('\\', '/', $baseForcee), '/');
} else {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $scriptDir = rtrim($scriptDir, '/');
}
define('BASE_URL', $scriptDir === '' || $scriptDir === '.' ? '' : $scriptDir);
define('APP_URL', ($enHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL);

// --- Reglages metier par defaut (surcharges par la table parametre) ----
define('DELAI_MINIMUM_HEURES', 24);
define('MAX_TENTATIVES_CONNEXION', 5);
define('DUREE_BLOCAGE_MINUTES', 15);
define('DUREE_SESSION_MINUTES', 45);
define('CAPACITE_SACS_PAR_JOUR', 200);

// --- Affichage des erreurs --------------------------------------------
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    $dossierLogs = STORAGE_PATH . DIRECTORY_SEPARATOR . 'logs';
    if (is_dir($dossierLogs) && is_writable($dossierLogs)) {
        ini_set('log_errors', '1');
        ini_set('error_log', $dossierLogs . DIRECTORY_SEPARATOR . 'php-errors.log');
    }
}

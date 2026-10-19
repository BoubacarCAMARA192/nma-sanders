<?php
/**
 * Amorçage de l'application : autoload PSR-4 simplifie, helpers, demarrage de session.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'Core\\'       => APP_PATH . '/core/',
        'Model\\'      => APP_PATH . '/models/',
        'Controller\\' => APP_PATH . '/controllers/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (strncmp($class, $prefix, strlen($prefix)) === 0) {
            $relative = substr($class, strlen($prefix));
            $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
            return;
        }
    }
});

require_once APP_PATH . '/core/helpers.php';

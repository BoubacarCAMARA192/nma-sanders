<?php
/**
 * Point d'entree pour les plateformes serverless (Vercel).
 * Toutes les requetes non statiques sont reecrites vers ce fichier
 * (voir vercel.json) puis traitees par le front controller de l'application.
 */

declare(strict_types=1);

if (!isset($_SERVER['SCRIPT_NAME']) || $_SERVER['SCRIPT_NAME'] === '/api/index.php') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

require __DIR__ . '/../index.php';

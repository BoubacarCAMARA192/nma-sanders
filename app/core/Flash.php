<?php
/**
 * Messages flash (notifications d'interface) stockes en session.
 */

declare(strict_types=1);

namespace Core;

final class Flash
{
    private const KEY = '_flash';

    public static function ajouter(string $type, string $message): void
    {
        $_SESSION[self::KEY][] = ['type' => $type, 'message' => $message];
    }

    public static function succes(string $message): void
    {
        self::ajouter('success', $message);
    }

    public static function erreur(string $message): void
    {
        self::ajouter('danger', $message);
    }

    public static function info(string $message): void
    {
        self::ajouter('info', $message);
    }

    public static function avertir(string $message): void
    {
        self::ajouter('warning', $message);
    }

    /** Consomme et renvoie les messages en attente. */
    public static function recuperer(): array
    {
        $messages = $_SESSION[self::KEY] ?? [];
        unset($_SESSION[self::KEY]);

        return $messages;
    }
}

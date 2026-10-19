<?php
/**
 * Parametres applicatifs charges depuis la table `parametre` (avec cache memoire).
 */

declare(strict_types=1);

namespace Core;

final class Parametre
{
    private static ?array $cache = null;

    private static function charger(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (Database::fetchAll('SELECT cle, valeur FROM parametre') as $ligne) {
                self::$cache[$ligne['cle']] = $ligne['valeur'];
            }
        }

        return self::$cache;
    }

    public static function tout(): array
    {
        return self::charger();
    }

    public static function valeur(string $cle, string $defaut = ''): string
    {
        return self::charger()[$cle] ?? $defaut;
    }

    public static function entier(string $cle, int $defaut = 0): int
    {
        $valeur = self::charger()[$cle] ?? null;

        return $valeur === null || !is_numeric($valeur) ? $defaut : (int) $valeur;
    }

    public static function enregistrer(string $cle, string $valeur): void
    {
        Database::run(
            'INSERT INTO parametre (cle, valeur, libelle) VALUES (:cle, :valeur, :libelle)
             ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)',
            ['cle' => $cle, 'valeur' => $valeur, 'libelle' => $cle]
        );
        self::$cache = null;
    }
}

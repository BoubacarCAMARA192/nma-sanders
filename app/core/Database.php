<?php
/**
 * Couche donnees : connexion PDO unique (singleton).
 * Toutes les requetes de l'application sont preparees (protection injection SQL).
 */

declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function pdo(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_STRINGIFY_FETCHES  => false,
                ]);
            } catch (PDOException $e) {
                if (APP_DEBUG) {
                    http_response_code(500);
                    exit('<h1>Connexion a la base impossible</h1><p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>');
                }
                error_log('DB: ' . $e->getMessage());
                http_response_code(500);
                exit('Service momentanement indisponible. Merci de reessayer.');
            }
        }

        return self::$instance;
    }

    /** Execute une requete preparee et renvoie le statement. */
    public static function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function scalar(string $sql, array $params = [])
    {
        $value = self::run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);

        return (int) self::pdo()->lastInsertId();
    }

    public static function lastInsertId(): int
    {
        return (int) self::pdo()->lastInsertId();
    }
}

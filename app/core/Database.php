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

    /**
     * Pilote actif : MySQL en local (XAMPP), SQLite automatiquement sur les
     * plateformes serverless (Vercel) sans base MySQL configuree.
     */
    public static function dialecte(): string
    {
        $pilote = getenv('NMA_DB_DRIVER');

        if ($pilote !== false && $pilote !== '') {
            return strtolower($pilote) === 'sqlite' ? 'sqlite' : 'mysql';
        }

        if (getenv('VERCEL') !== false && getenv('NMA_DB_HOST') === false) {
            return 'sqlite';
        }

        return 'mysql';
    }

    public static function pdo(): PDO
    {
        if (self::$instance === null) {
            try {
                if (self::dialecte() === 'sqlite') {
                    require_once __DIR__ . '/Sqlite.php';
                    self::$instance = Sqlite::ouvrir();
                } else {
                    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

                    self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                        PDO::ATTR_STRINGIFY_FETCHES  => false,
                    ]);
                }
            } catch (PDOException $e) {
                if (APP_DEBUG) {
                    http_response_code(500);
                    exit('<h1>Connexion a la base impossible</h1><p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>');
                }
                error_log('DB: ' . $e->getMessage());
                http_response_code(503);
                $app = htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8');
                $retour = htmlspecialchars((defined('BASE_URL') ? BASE_URL : '/') . '?r=', ENT_QUOTES, 'UTF-8');
                echo <<<HTML
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Base de donnees indisponible - {$app}</title>
<style>
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(160deg,#7c4a03,#4a2c02 60%,#2b1a01);font-family:system-ui,'Segoe UI',sans-serif;color:#2b2116;padding:1.5rem}
.carte{background:#fff;border-radius:20px;max-width:470px;padding:2rem 2.2rem;box-shadow:0 30px 60px -20px rgba(0,0,0,.5);text-align:center}
.svg{width:58px;height:58px;margin:0 auto .5rem}
h1{font-size:1.15rem;margin:0 0 .6rem;color:#2b2116}
p{font-size:.92rem;line-height:1.5;color:#5b4a35;margin:0 0 .8rem}
ul{text-align:left;font-size:.88rem;color:#5b4a35;line-height:1.55;margin:0 0 1.2rem;padding-left:1.2rem}
a{display:inline-block;background:#8b5a06;color:#fff;text-decoration:none;font-weight:600;font-size:.9rem;padding:.55rem 1.4rem;border-radius:999px}
a:hover{background:#6d4504}
</style>
</head>
<body>
<div class="carte">
<svg class="svg" viewBox="0 0 60 60" aria-hidden="true"><path d="M30 58 C 28 40 28 30 30 14" stroke="#8b5a06" stroke-width="3" fill="none"/><ellipse cx="20" cy="26" rx="7" ry="11" fill="#e8a33d" transform="rotate(-24 20 26)"/><ellipse cx="40" cy="26" rx="7" ry="11" fill="#f2b455" transform="rotate(24 40 26)"/><ellipse cx="20" cy="42" rx="7" ry="11" fill="#e8a33d" transform="rotate(-24 20 42)"/><ellipse cx="40" cy="42" rx="7" ry="11" fill="#f2b455" transform="rotate(24 40 42)"/><ellipse cx="30" cy="12" rx="6" ry="10" fill="#f7e3b6"/></svg>
<h1>Base de donnees injoignable</h1>
<p>Le serveur MySQL ne repond pas pour le moment.</p>
<ul>
<li><strong>En local :</strong> demarrez MySQL dans le panneau XAMPP, puis rechargez la page.</li>
<li><strong>En ligne :</strong> ce deploiement presente le site ; les commandes et tableaux de bord s'utilisent sur la version locale.</li>
</ul>
<a href="{$retour}">Reessayer</a>
</div>
</body>
</html>
HTML;
                exit;
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

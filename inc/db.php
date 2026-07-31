<?php
declare(strict_types=1);

/**
 * WCC CMMS — Database connection (single source of truth).
 *
 * Defaults are the standard XAMPP / LAN setup:
 *   host: localhost   database: workshop_db   user: root   password: (empty)
 *
 * Those defaults can be overridden by environment variables (WCC_DB_HOST/PORT/
 * NAME/USER/PASS) so the same code runs unchanged inside a Docker container, on a
 * managed host, or anywhere the database is not local. When none are set — which
 * is the case under plain XAMPP — behaviour is identical to the fixed credentials.
 *
 * No runtime credential UI — keep the kitchen free of nukes.
 */

final class WccDatabase
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $host   = getenv('WCC_DB_HOST') ?: 'localhost';
            $port   = getenv('WCC_DB_PORT') ?: '3306';
            $dbname = getenv('WCC_DB_NAME') ?: 'workshop_db';
            $user   = getenv('WCC_DB_USER') ?: 'root';
            // An empty password is valid, so only fall back when truly unset.
            $password = getenv('WCC_DB_PASS');
            if ($password === false) $password = '';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            try {
                self::$connection = new PDO($dsn, $user, $password, $options);
            } catch (PDOException $e) {
                error_log('[WCC DB] Connection failed: ' . $e->getMessage());
                throw new RuntimeException('Critical database connection failure. Check server logs.', 0, $e);
            }
        }

        return self::$connection;
    }
}

function get_wcc_db_connection(): PDO
{
    return WccDatabase::getConnection();
}

// Backward compatibility: many pages expect $pdo after require.
$pdo = get_wcc_db_connection();

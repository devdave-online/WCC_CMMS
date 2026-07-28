<?php
declare(strict_types=1);

/**
 * WCC CMMS — Database connection (single source of truth).
 *
 * Local / LAN only: XAMPP MariaDB with fixed credentials.
 *   host: localhost
 *   database: workshop_db
 *   user: root
 *   password: (empty)
 *
 * No runtime credential UI — keep the kitchen free of nukes.
 */

final class WccDatabase
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $host     = 'localhost';
            $dbname   = 'workshop_db';
            $user     = 'root';
            $password = '';

            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

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

<?php

namespace LightLogger\Database;

use LightLogger\Tools\Env;
use LightLogger\Tools\Log\Log;
use PDO;
use PDOException;
use Exception;

class Database
{
    private static ?PDO $connection = null;

    /**
     * Get database connection (singleton pattern).
     */
    public static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        try {
            $driver = Env::get('db_driver', 'mariadb');
            $host = Env::get('db_host', 'localhost');
            $port = Env::get('db_port', '3306');
            $database = Env::get('db_database', 'light_logger');
            $username = Env::get('db_username', 'root');
            $password = Env::get('db_password', '');

            if ($driver === 'postgres') {
                $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
            } else {
                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            }

            self::$connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            Log::success("Database connected ({$driver})");

            return self::$connection;
        } catch (PDOException $e) {
            Log::error("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Run migrations from migrations directory.
     */
    public static function runMigrations(): void
    {
        $migrationsDir = dirname(__DIR__, 2) . '/migrations';

        if (!is_dir($migrationsDir)) {
            Log::warning("Migrations directory not found: {$migrationsDir}");
            return;
        }

        $files = glob($migrationsDir . '/*.sql');
        sort($files);

        if (empty($files)) {
            Log::info("No migration files found");
            return;
        }

        $pdo = self::getConnection();

        foreach ($files as $file) {
            $filename = basename($file);
            Log::info("Running migration: {$filename}");

            try {
                $sql = file_get_contents($file);
                $pdo->exec($sql);
                Log::success("Migration completed: {$filename}");
            } catch (PDOException $e) {
                Log::error("Migration failed: {$filename} - " . $e->getMessage());
                throw $e;
            }
        }
    }
}

<?php

namespace LightLogger\Install;

use LightLogger\Tools\Env;
use LightLogger\Tools\Log\Log;
use Exception;

class Install
{
    private string $envPath;
    private string $envExamplePath;
    private string $lockFilePath;

    public function __construct()
    {
        // Use server-data volume for writable files (not mounted Windows directory)
        $dataDir = '/app/server-data';

        // Create data directory if it doesn't exist
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0777, true);
        }

        $this->envPath = $dataDir . '/.env';
        $this->envExamplePath = dirname(__DIR__, 2) . '/.env.example';
        $this->lockFilePath = $dataDir . '/.installed';
    }

    /**
     * Check if the application is installed.
     * Uses lock file approach (like WordPress) - checks on every request.
     */
    public function isInstalled(): bool
    {
        $exists = file_exists($this->lockFilePath);
        Log::debug("Installation check: lock file {$this->lockFilePath} " . ($exists ? 'EXISTS' : 'NOT FOUND'));
        return $exists;
    }

    /**
     * Get current installation status with details.
     */
    public function getStatus(): array
    {
        return [
            'installed' => $this->isInstalled(),
            'env_exists' => file_exists($this->envPath),
            'env_example_exists' => file_exists($this->envExamplePath),
        ];
    }

    /**
     * Test database connection.
     */
    public function testDatabaseConnection(array $config): array
    {
        $driver = $config['driver'] ?? 'mariadb';
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? ($driver === 'postgres' ? 5432 : 3306);
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        try {
            if ($driver === 'postgres') {
                $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
            } else {
                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            }

            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
            ]);

            // Test query
            $pdo->query('SELECT 1');

            return [
                'success' => true,
                'message' => 'Database connection successful',
            ];
        } catch (\PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test Redis connection.
     */
    public function testRedisConnection(array $config): array
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 6379;
        $password = $config['password'] ?? '';

        try {
            $redis = new \Redis();
            $connected = $redis->connect($host, (int)$port, 5.0);

            if (!$connected) {
                return [
                    'success' => false,
                    'message' => 'Could not connect to Redis server',
                ];
            }

            if (!empty($password)) {
                $redis->auth($password);
            }

            $redis->ping();

            return [
                'success' => true,
                'message' => 'Redis connection successful',
            ];
        } catch (\RedisException $e) {
            return [
                'success' => false,
                'message' => 'Redis connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Save configuration and complete installation.
     */
    public function complete(array $config): array
    {
        try {
            Log::info("Starting installation...");
            Log::info("ENV path: {$this->envPath}");
            Log::info("Lock file path: {$this->lockFilePath}");

            // Check if directory is writable
            $dir = dirname($this->envPath);
            Log::info("Checking directory: {$dir}");

            if (!is_dir($dir)) {
                throw new Exception("Directory does not exist: {$dir}");
            }

            if (!is_writable($dir)) {
                $perms = substr(sprintf('%o', fileperms($dir)), -4);
                throw new Exception("Directory not writable: {$dir} (permissions: {$perms})");
            }

            Log::success("Directory is writable");

            // Build env content
            $envContent = $this->buildEnvContent($config);
            Log::info("Built .env content (" . strlen($envContent) . " bytes)");

            // Write .env file
            Log::info("Writing .env file...");
            $envWritten = file_put_contents($this->envPath, $envContent);
            if ($envWritten === false) {
                $error = error_get_last();
                throw new Exception('Failed to write .env file: ' . ($error['message'] ?? 'Unknown error'));
            }
            Log::success(".env file written ({$envWritten} bytes)");

            // Create lock file to mark installation as complete
            $lockContent = "Installation completed: " . date('Y-m-d H:i:s') . "\n";
            Log::info("Creating lock file: {$this->lockFilePath}");
            $lockWritten = file_put_contents($this->lockFilePath, $lockContent);
            if ($lockWritten === false) {
                $error = error_get_last();
                throw new Exception('Failed to create lock file at ' . $this->lockFilePath . ': ' . ($error['message'] ?? 'Unknown error'));
            }
            Log::success("Lock file created ({$lockWritten} bytes)");

            // Verify lock file was created
            if (!file_exists($this->lockFilePath)) {
                throw new Exception('Lock file was not created successfully at ' . $this->lockFilePath);
            }
            Log::success("Lock file verified to exist");

            // Run database migrations to create tables
            Log::info("Running database migrations...");
            try {
                \LightLogger\Database\Database::runMigrations();
                Log::success("Database migrations completed");
            } catch (\Exception $e) {
                throw new Exception('Failed to run migrations: ' . $e->getMessage());
            }

            // Create admin user if provided
            if (isset($config['user'])) {
                Log::info("Creating admin user...");
                $userResult = $this->createAdminUser(
                    $config['user']['username'],
                    $config['user']['email'],
                    $config['user']['password']
                );

                if (!$userResult['success']) {
                    throw new Exception('Failed to create admin user: ' . $userResult['message']);
                }
                Log::success("Admin user created successfully");
            }

            Log::success("Installation completed successfully!");

            return [
                'success' => true,
                'message' => 'Installation completed successfully',
                'lock_file' => $this->lockFilePath,
            ];
        } catch (Exception $e) {
            Log::error("Installation failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build .env file content from configuration.
     */
    private function buildEnvContent(array $config): string
    {
        $app = $config['app'] ?? [];
        $database = $config['database'] ?? [];
        $redis = $config['redis'] ?? [];
        $elasticsearch = $config['elasticsearch'] ?? [];

        $lines = [
            '# Application Configuration',
            'APP_DEBUG=' . ($app['debug'] ?? 'false'),
            'APP_HOST=' . ($app['host'] ?? '0.0.0.0'),
            'APP_PORT=' . ($app['port'] ?? '9501'),
            'APP_ENV=' . ($app['env'] ?? 'production'),
            '',
            '# Database Configuration',
            'DB_DRIVER=' . ($database['driver'] ?? 'mariadb'),
            'DB_HOST=' . ($database['host'] ?? 'localhost'),
            'DB_PORT=' . ($database['port'] ?? '3306'),
            'DB_DATABASE=' . ($database['database'] ?? 'light_logger'),
            'DB_USERNAME=' . ($database['username'] ?? 'root'),
            'DB_PASSWORD=' . ($database['password'] ?? ''),
            '',
            '# Redis Configuration',
            'REDIS_HOST=' . ($redis['host'] ?? 'localhost'),
            'REDIS_PORT=' . ($redis['port'] ?? '6379'),
            'REDIS_PASSWORD=' . ($redis['password'] ?? ''),
            '',
            '# Elasticsearch Configuration',
            'ELASTICSEARCH_HOST=' . ($elasticsearch['host'] ?? 'localhost'),
            'ELASTICSEARCH_PORT=' . ($elasticsearch['port'] ?? '9200'),
        ];

        return implode("\n", $lines) . "\n";
    }

    /**
     * Get default configuration values (for Docker environment).
     */
    public function getDefaultConfig(): array
    {
        return [
            'app' => [
                'debug' => Env::get('app_debug', 'true'),
                'host' => Env::get('app_host', '0.0.0.0'),
                'port' => Env::get('app_port', '9501'),
                'env' => Env::get('app_env', 'local'),
            ],
            'database' => [
                'driver' => Env::get('db_driver', 'mariadb'),
                'host' => Env::get('db_host', 'mariadb'),
                'port' => Env::get('db_port', '3306'),
                'database' => Env::get('db_database', 'light_logger'),
                'username' => Env::get('db_username', 'light_logger'),
                'password' => Env::get('db_password', ''),
            ],
            'redis' => [
                'host' => Env::get('redis_host', 'redis'),
                'port' => Env::get('redis_port', '6379'),
                'password' => Env::get('redis_password', ''),
            ],
            'elasticsearch' => [
                'host' => Env::get('elasticsearch_host', 'elasticsearch'),
                'port' => Env::get('elasticsearch_port', '9200'),
            ],
        ];
    }

    /**
     * Create admin user during installation.
     */
    public function createAdminUser(string $username, string $email, string $password): array
    {
        try {
            // Import User model
            require_once dirname(__DIR__) . '/Model/User.php';

            // Check if user already exists
            $existingUser = \LightLogger\Model\User::findByUsername($username);
            if ($existingUser) {
                return [
                    'success' => false,
                    'message' => 'Username already exists'
                ];
            }

            $existingEmail = \LightLogger\Model\User::findByEmail($email);
            if ($existingEmail) {
                return [
                    'success' => false,
                    'message' => 'Email already exists'
                ];
            }

            // Create the user
            $user = \LightLogger\Model\User::create($username, $email, $password);

            return [
                'success' => true,
                'message' => 'Admin user created successfully',
                'user_id' => $user['id']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}

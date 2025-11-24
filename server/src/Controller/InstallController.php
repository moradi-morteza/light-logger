<?php

namespace LightLogger\Controller;

use LightLogger\Http\Request;
use LightLogger\Http\Response;
use LightLogger\Install\Install;

class InstallController extends Controller
{
    private Install $install;

    public function __construct()
    {
        $this->install = new Install();
    }

    /**
     * Get installation status.
     * GET /api/install/status
     */
    public function status(Request $request, Response $response): void
    {
        $status = $this->install->getStatus();
        $defaults = $this->install->getDefaultConfig();

        $this->success($response, [
            'status' => $status,
            'defaults' => $defaults,
        ]);
    }

    /**
     * Test database connection.
     * POST /api/install/test-database
     */
    public function testDatabase(Request $request, Response $response): void
    {
        $data = $request->json() ?? [];

        $missing = $this->validateRequired($data, ['driver', 'host', 'port', 'database', 'username']);
        if (!empty($missing)) {
            $this->error($response, 'Missing required fields', 400, $missing);
            return;
        }

        $result = $this->install->testDatabaseConnection($data);

        if ($result['success']) {
            $this->success($response, [], $result['message']);
        } else {
            $this->error($response, $result['message']);
        }
    }

    /**
     * Test Redis connection.
     * POST /api/install/test-redis
     */
    public function testRedis(Request $request, Response $response): void
    {
        $data = $request->json() ?? [];

        $missing = $this->validateRequired($data, ['host', 'port']);
        if (!empty($missing)) {
            $this->error($response, 'Missing required fields', 400, $missing);
            return;
        }

        $result = $this->install->testRedisConnection($data);

        if ($result['success']) {
            $this->success($response, [], $result['message']);
        } else {
            $this->error($response, $result['message']);
        }
    }

    /**
     * Complete installation.
     * POST /api/install/complete
     */
    public function complete(Request $request, Response $response): void
    {
        // Check if already installed
        if ($this->install->isInstalled()) {
            $this->error($response, 'Application is already installed', 400);
            return;
        }

        $data = $request->json() ?? [];

        // Validate required configuration sections
        if (!isset($data['database'])) {
            $this->error($response, 'Database configuration is required', 400);
            return;
        }

        // Validate user data
        if (!isset($data['user'])) {
            $this->error($response, 'User information is required', 400);
            return;
        }

        $userErrors = $this->validateRequired($data['user'], ['username', 'email', 'password']);
        if (!empty($userErrors)) {
            $this->error($response, $userErrors, 400);
            return;
        }

        // Test database connection before completing
        $dbTest = $this->install->testDatabaseConnection($data['database']);
        if (!$dbTest['success']) {
            $this->error($response, 'Database connection failed: ' . $dbTest['message'], 400);
            return;
        }

        // Test Redis connection if provided
        if (isset($data['redis']) && !empty($data['redis']['host'])) {
            $redisTest = $this->install->testRedisConnection($data['redis']);
            if (!$redisTest['success']) {
                $this->error($response, 'Redis connection failed: ' . $redisTest['message'], 400);
                return;
            }
        }

        // Complete installation
        $result = $this->install->complete($data);

        if ($result['success']) {
            $this->success($response, [
                'message' => $result['message'],
                'restart_required' => true,
            ], 'Installation completed! Please restart the server.');
        } else {
            $this->error($response, $result['message'], 500);
        }
    }

    /**
     * Check if application is installed (simple check).
     * GET /api/install/check
     */
    public function check(Request $request, Response $response): void
    {
        $this->success($response, [
            'installed' => $this->install->isInstalled(),
        ]);
    }
}

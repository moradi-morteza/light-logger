<?php

namespace LightLogger\Controller;

use LightLogger\Http\Request;
use LightLogger\Http\Response;

/**
 * Handles health check and system info endpoints.
 */
class HealthController extends Controller
{
    private string $host;
    private int $port;

    public function __construct(
        string $host = '0.0.0.0',
        int $port = 9501
    ) {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * GET /health
     * Health check endpoint for load balancers and Kubernetes.
     */
    public function health(Request $request, Response $response): void
    {
        $response->json([
            'status' => 'ok',
            'time' => time(),
        ]);
    }

    /**
     * GET /
     * Root endpoint with server information.
     */
    public function index(Request $request, Response $response): void
    {
        $response->json([
            'name' => 'Light Logger',
            'version' => '0.1.0',
            'status' => 'running',
            'endpoints' => [
                'health' => '/health',
                'logs' => '/api/v1/logs',
                'projects' => '/api/v1/projects',
                'websocket' => "ws://{$this->host}:{$this->port}",
            ],
        ]);
    }
}

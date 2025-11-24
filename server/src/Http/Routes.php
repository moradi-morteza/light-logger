<?php

namespace LightLogger\Http;

use LightLogger\Controller\HealthController;
use LightLogger\Controller\LogController;

/**
 * Registers all HTTP routes for the application.
 */
class Routes
{
    private Router $router;
    private HealthController $healthController;
    private LogController $logController;

    public function __construct(
        Router $router,
        HealthController $healthController,
        LogController $logController
    ){
        $this->router = $router;
        $this->healthController = $healthController;
        $this->logController = $logController;
    }

    /**
     * Register all routes.
     */
    public function register(): void
    {
        $this->registerSystemRoutes();
        $this->registerLogRoutes();
    }

    /**
     * Register system/health routes.
     */
    private function registerSystemRoutes(): void
    {
        $this->router->get('/', [$this->healthController, 'index']);
        $this->router->get('/health', [$this->healthController, 'health']);
    }

    /**
     * Register log-related routes (client API).
     */
    private function registerLogRoutes(): void
    {
        $this->router->post('/api/v1/logs', [$this->logController, 'ingest']);
        $this->router->get('/api/v1/logs', [$this->logController, 'index']);
    }
}

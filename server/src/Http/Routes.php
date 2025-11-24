<?php

namespace LightLogger\Http;

use LightLogger\Controller\HealthController;
use LightLogger\Controller\InstallController;
use LightLogger\Controller\LogController;
use LightLogger\Controller\ProjectController;

/**
 * Registers all HTTP routes for the application.
 */
class Routes
{
    private Router $router;
    private HealthController $healthController;
    private LogController $logController;
    private InstallController $installController;
    private ProjectController $projectController;

    public function __construct(
        Router $router,
        HealthController $healthController,
        LogController $logController,
        InstallController $installController,
        ProjectController $projectController
    ){
        $this->router = $router;
        $this->healthController = $healthController;
        $this->logController = $logController;
        $this->installController = $installController;
        $this->projectController = $projectController;
    }

    /**
     * Register all routes.
     */
    public function register(): void
    {
        $this->registerSystemRoutes();
        $this->registerInstallRoutes();
        $this->registerProjectRoutes();
        $this->registerLogRoutes();
    }

    /**
     * Register system/health routes.
     */
    private function registerSystemRoutes(): void
    {
        $this->router->get('/health', [$this->healthController, 'health']);
    }

    /**
     * Register installation routes.
     */
    private function registerInstallRoutes(): void
    {
        $this->router->get('/api/install/status', [$this->installController, 'status']);
        $this->router->get('/api/install/check', [$this->installController, 'check']);
        $this->router->post('/api/install/test-database', [$this->installController, 'testDatabase']);
        $this->router->post('/api/install/test-redis', [$this->installController, 'testRedis']);
        $this->router->post('/api/install/complete', [$this->installController, 'complete']);
    }

    /**
     * Register project management routes.
     */
    private function registerProjectRoutes(): void
    {
        $this->router->get('/api/projects', [$this->projectController, 'index']);
        $this->router->post('/api/projects', [$this->projectController, 'store']);
        $this->router->get('/api/projects/{id}', [$this->projectController, 'show']);
        $this->router->delete('/api/projects/{id}', [$this->projectController, 'destroy']);
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

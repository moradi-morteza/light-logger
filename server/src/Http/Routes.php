<?php

namespace LightLogger\Http;

use LightLogger\Controller\HealthController;
use LightLogger\Controller\InstallController;
use LightLogger\Controller\LogController;
use LightLogger\Controller\ProjectController;
use LightLogger\Controller\AuthController;
use LightLogger\Middleware\AuthMiddleware;

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
    private AuthController $authController;
    private AuthMiddleware $authMiddleware;

    public function __construct(
        Router $router,
        HealthController $healthController,
        LogController $logController,
        InstallController $installController,
        ProjectController $projectController,
        AuthController $authController,
        AuthMiddleware $authMiddleware
    ){
        $this->router = $router;
        $this->healthController = $healthController;
        $this->logController = $logController;
        $this->installController = $installController;
        $this->projectController = $projectController;
        $this->authController = $authController;
        $this->authMiddleware = $authMiddleware;
    }

    /**
     * Register all routes.
     */
    public function register(): void
    {
        $this->registerSystemRoutes();
        $this->registerInstallRoutes();
        $this->registerAuthRoutes();
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
     * Register authentication routes.
     */
    private function registerAuthRoutes(): void
    {
        $this->router->post('/api/auth/login', [$this->authController, 'login']);
        $this->router->post('/api/auth/logout', [$this->authController, 'logout'])
            ->middleware($this->authMiddleware);
        $this->router->get('/api/auth/me', [$this->authController, 'me'])
            ->middleware($this->authMiddleware);
        $this->router->post('/api/auth/check', [$this->authController, 'check']);
    }

    /**
     * Register project management routes (protected).
     */
    private function registerProjectRoutes(): void
    {
        $this->router->get('/api/projects', [$this->projectController, 'index'])
            ->middleware($this->authMiddleware);
        $this->router->post('/api/projects', [$this->projectController, 'store'])
            ->middleware($this->authMiddleware);
        $this->router->get('/api/projects/{id}', [$this->projectController, 'show'])
            ->middleware($this->authMiddleware);
        $this->router->delete('/api/projects/{id}', [$this->projectController, 'destroy'])
            ->middleware($this->authMiddleware);
        $this->router->get('/api/projects/{id}/schema', [$this->projectController, 'getSchema'])
            ->middleware($this->authMiddleware);
        $this->router->put('/api/projects/{id}/schema', [$this->projectController, 'updateSchema'])
            ->middleware($this->authMiddleware);
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

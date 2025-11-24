<?php

namespace LightLogger\Server;

use LightLogger\Controller\HealthController;
use LightLogger\Controller\InstallController;
use LightLogger\Controller\LogController;
use LightLogger\Controller\ProjectController;
use LightLogger\Controller\AuthController;
use LightLogger\Middleware\AuthMiddleware;
use LightLogger\Database\Database;
use LightLogger\Http\Router;
use LightLogger\Http\Routes;
use LightLogger\Install\Install;
use LightLogger\Tools\Env;
use LightLogger\Tools\Log\Log;
use Swoole\WebSocket\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;

class LoggerServer
{
    private Server $server;
    private string $host;
    private int $port;
    private string $panelPath;

    // HTTP handling
    private Router $router;
    private Install $install;

    public function __construct(string $host, int $port)
    {
        $this->host = $host;
        $this->port = $port;
        $this->panelPath = dirname(__DIR__, 3) . '/panel/dist';
        $this->install = new Install();

        /**
         * Create a WebSocket server.
         * Swoole WebSocket server automatically supports HTTP
         * so we don't need two separate servers.
         */
        $this->server = new Server($host, $port);

        $this->initializeRoutes();
        $this->configureServer();
        $this->registerEvents();
    }

    /**
     * Initialize HTTP routes and controllers.
     */
    private function initializeRoutes(): void
    {
        $this->router = new Router();

        // Create controllers
        $healthController = new HealthController(
            $this->host,
            $this->port
        );

        $logController = new LogController();
        $installController = new InstallController();
        $projectController = new ProjectController();
        $authController = new AuthController();
        $authMiddleware = new AuthMiddleware();

        // Register routes
        $routes = new Routes(
            $this->router,
            $healthController,
            $logController,
            $installController,
            $projectController,
            $authController,
            $authMiddleware
        );

        $routes->register();
    }

    /**
     * Register all Swoole events: lifecycle, HTTP and WebSocket handlers.
     */
    private function registerEvents(): void
    {
        $this->server->on('start', [$this, 'onStart']);
        $this->server->on('workerStart', [$this, 'onWorkerStart']);

        // HTTP handler
        $this->server->on('request', [$this, 'onHttpRequest']);

        // WebSocket events
        $this->server->on('open', [$this, 'onOpen']);
        $this->server->on('message', [$this, 'onMessage']);
        $this->server->on('close', [$this, 'onClose']);
    }

    /**
     * Start the server (this will block until shutdown).
     */
    public function start(): void
    {
        $this->server->start();
    }

    /* ---------------------------------------------------------
     *  SERVER EVENTS
     * --------------------------------------------------------- */

    /**
     * Fired when the master process starts.
     * Great place to print startup logs.
     */
    public function onStart(Server $server): void
    {
        Log::success(" LIGHT LOGGER SERVER IS READY ");
        Log::info("  ➜  WebSocket: ws://{$this->host}:{$this->port}");
        Log::info("  ➜  HTTP:      http://{$this->host}:{$this->port}");
    }

    /**
     * Fired when each worker process starts.
     * Useful for setting worker-specific config or prefixes.
     */
    public function onWorkerStart(Server $server, int $workerId): void
    {
        Log::$prefix = "W{$workerId}";
        Log::success("Worker #{$workerId} started");

        // Run database migrations (only in first worker to avoid race conditions)
        if ($workerId === 0 && $this->install->isInstalled()) {
            try {
                Log::info("Running database migrations...");
                Database::runMigrations();
            } catch (\Exception $e) {
                Log::error("Migration failed: " . $e->getMessage());
            }
        }
    }

    /* ---------------------------------------------------------
     *  HTTP HANDLER
     * --------------------------------------------------------- */

    /**
     * Handle incoming HTTP requests.
     */
    public function onHttpRequest(Request $request, Response $response): void
    {
        // Handle CORS preflight
        $method = strtoupper($request->server['request_method'] ?? 'GET');
        if ($method === 'OPTIONS') {
            $res = new \LightLogger\Http\Response($response);
            $res->handleOptions();
            return;
        }

        $uri = $request->server['request_uri'] ?? '/';

        // Always allow API routes
        if (str_starts_with($uri, '/api/')) {
            // Check installation status for non-install API routes
            if (!str_starts_with($uri, '/api/install/') && !$this->install->isInstalled()) {
                $res = new \LightLogger\Http\Response($response);
                $res->cors();
                $res->error('Application not installed', 503, ['redirect' => '/setup']);
                return;
            }

            $this->router->dispatch($request, $response);
            return;
        }

        // Allow health check route
        if ($uri === '/health') {
            $this->router->dispatch($request, $response);
            return;
        }

        // Serve static files from panel/dist
        $this->serveStaticOrSpa($request, $response);
    }

    /**
     * Serve static files or SPA fallback.
     */
    private function serveStaticOrSpa(Request $request, Response $response): void
    {
        $uri = $request->server['request_uri'] ?? '/';

        // Clean path to prevent directory traversal
        $path = parse_url($uri, PHP_URL_PATH);
        $path = str_replace(['..', '//'], '', $path);

        // Check if panel dist exists
        if (!is_dir($this->panelPath)) {
            $this->serveInstallPlaceholder($response);
            return;
        }

        // Try to serve static file (CSS, JS, images, etc)
        $filePath = $this->panelPath . $path;

        if ($path !== '/' && is_file($filePath)) {
            $this->serveFile($response, $filePath);
            return;
        }

        // SPA fallback - always serve index.html for routes
        // The Vue router will handle checking installation status
        $indexPath = $this->panelPath . '/index.html';
        if (is_file($indexPath)) {
            $this->serveFile($response, $indexPath);
            return;
        }

        // No panel built yet - serve placeholder
        $this->serveInstallPlaceholder($response);
    }

    /**
     * Serve a static file.
     */
    private function serveFile(Response $response, string $filePath): void
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeTypes = [
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
        ];

        $contentType = $mimeTypes[$extension] ?? 'application/octet-stream';

        $response->header('Content-Type', $contentType);

        // HTML files should not be cached (always get latest)
        // Assets (JS/CSS) have hashed names and can be cached forever
        if ($extension === 'html') {
            $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', '0');
        } else {
            $response->header('Cache-Control', 'public, max-age=31536000, immutable');
        }

        $response->sendfile($filePath);
    }

    /**
     * Serve installation placeholder when panel is not built.
     */
    private function serveInstallPlaceholder(Response $response): void
    {
        $installed = $this->install->isInstalled();
        $statusText = $installed ? 'Installed' : 'Not Installed';
        $statusColor = $installed ? '#10b981' : '#f59e0b';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Light Logger - Setup</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e4e4e7;
        }
        .container {
            text-align: center;
            padding: 2rem;
            max-width: 600px;
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, #818cf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            margin: 1rem 0;
            background: {$statusColor}20;
            color: {$statusColor};
            border: 1px solid {$statusColor};
        }
        .message {
            color: #a1a1aa;
            line-height: 1.6;
            margin: 1.5rem 0;
        }
        .api-info {
            background: #27272a;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-top: 2rem;
            text-align: left;
        }
        .api-info h3 {
            color: #818cf8;
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        .endpoint {
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.875rem;
            color: #a1a1aa;
            padding: 0.5rem 0;
            border-bottom: 1px solid #3f3f46;
        }
        .endpoint:last-child { border-bottom: none; }
        .endpoint .method {
            display: inline-block;
            width: 60px;
            color: #10b981;
            font-weight: 600;
        }
        .endpoint .method.post { color: #f59e0b; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Light Logger</h1>
        <div class="status">{$statusText}</div>
        <p class="message">
            The Vue.js panel has not been built yet.<br>
            Please build the panel or use the API directly.
        </p>
        <div class="api-info">
            <h3>Setup API Endpoints</h3>
            <div class="endpoint"><span class="method">GET</span> /api/install/status</div>
            <div class="endpoint"><span class="method">GET</span> /api/install/check</div>
            <div class="endpoint"><span class="method post">POST</span> /api/install/test-database</div>
            <div class="endpoint"><span class="method post">POST</span> /api/install/test-redis</div>
            <div class="endpoint"><span class="method post">POST</span> /api/install/complete</div>
        </div>
    </div>
</body>
</html>
HTML;

        $response->header('Content-Type', 'text/html');
        $response->end($html);
    }

    /* ---------------------------------------------------------
     *  WEBSOCKET EVENTS
     * --------------------------------------------------------- */

    /**
     * Fired when a client establishes a WebSocket connection.
     */
    public function onOpen(Server $server, Request $req): void
    {
        Log::success("Client #{$req->fd} connected");

        $server->push($req->fd, json_encode([
            'event' => 'connected',
            'fd'    => $req->fd,
            'msg'   => 'Welcome to LightLogger WebSocket server!',
        ]));
    }

    /**
     * Handle incoming WebSocket messages.
     */
    public function onMessage(Server $server, Frame $frame): void
    {
        Log::info("Message from #{$frame->fd}: {$frame->data}");

        // Simple echo response
        $server->push($frame->fd, json_encode([
            'echo' => $frame->data,
            'ts'   => time(),
        ]));
    }

    /**
     * Fired when a WebSocket connection closes.
     */
    public function onClose(Server $server, int $fd): void
    {
        Log::warning("Client #{$fd} disconnected");
    }

    /* ---------------------------------------------------------
     *  SERVER CONFIGURATION
     * --------------------------------------------------------- */

    /**
     * Configure Swoole server settings.
     * Each configuration is documented for clarity.
     */
    private function configureServer(): void
    {
        $this->server->set([

            /**
             * Number of worker processes.
             * For I/O-heavy services like this logger, 2 workers are ideal for production.
             * If the workload becomes CPU-intensive in the future (e.g., heavy log processing,
             * compression, encryption, or analytics), you may increase the worker count.
             */
            'worker_num' => Env::isProduction() ? 2 : 1,

            /**
             * Enable HTTP/2 protocol.
             * Required for microservices or gRPC-like communication.
             */
            'open_http2_protocol' => true,

            /**
             * Enable coroutine support.
             * Makes all I/O (fs, sockets, curl, redis, etc.) non-blocking.
             */
            'enable_coroutine' => true,

            /**
             * Hook all PHP blocking I/O functions and convert them into async coroutine calls.
             */
            'hook_flags' => SWOOLE_HOOK_ALL,
        ]);
    }
}

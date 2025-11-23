<?php

namespace LightLogger\Server;

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

    public function __construct(string $host, int $port)
    {
        $this->host = $host;
        $this->port = $port;

        /**
         * Create a WebSocket server.
         * Swoole WebSocket server automatically supports HTTP
         * so we don't need two separate servers.
         */
        $this->server = new Server($host, $port);

        $this->configureServer();
        $this->registerEvents();
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
    }

    /* ---------------------------------------------------------
     *  HTTP HANDLER
     * --------------------------------------------------------- */

    /**
     * Handle incoming HTTP requests.
     */
    public function onHttpRequest(Request $req, Response $res): void
    {
        $uri = $req->server['request_uri'] ?? '/';

        // Basic health-check endpoint for Kubernetes or load balancers
        if ($uri === '/health') {
            $res->header('Content-Type', 'application/json');
            $res->end(json_encode(['status' => 'ok', 'time' => time()]));
            return;
        }

        // Root endpoint
        if ($uri === '/') {
            $res->end("LoggerSystem: WebSocket + HTTP server running");
            return;
        }

        // All other routes
        $res->status(404);
        $res->end("Not Found");
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

<?php

namespace LightLogger\Http;

use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

/**
 * Simple HTTP router for handling API endpoints.
 */
class Router
{
    private array $routes = [];

    /**
     * Register a GET route.
     */
    public function get(string $path, callable $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, callable $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register a PUT route.
     */
    public function put(string $path, callable $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $path, callable $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Add a route with method and path.
     */
    private function addRoute(string $method, string $path, callable $handler): self
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'pattern' => $this->pathToPattern($path),
            'middleware' => [],
        ];

        return $this;
    }

    /**
     * Add middleware to the last registered route.
     */
    public function middleware($middleware): self
    {
        if (empty($this->routes)) {
            return $this;
        }

        $lastIndex = count($this->routes) - 1;

        if (is_array($middleware)) {
            $this->routes[$lastIndex]['middleware'] = array_merge(
                $this->routes[$lastIndex]['middleware'],
                $middleware
            );
        } else {
            $this->routes[$lastIndex]['middleware'][] = $middleware;
        }

        return $this;
    }

    /**
     * Convert path with parameters to regex pattern.
     * Example: /api/projects/{id} -> /api/projects/(?P<id>[^/]+)
     * Supports {path} for catch-all routes (matches anything including slashes)
     */
    private function pathToPattern(string $path): string
    {
        // Special handling for {path} - catch-all pattern
        $pattern = preg_replace('/\{path\}/', '(?P<path>.+)', $path);
        // Regular parameters (don't match slashes)
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $pattern . '$#';
    }

    /**
     * Dispatch a request to the appropriate handler.
     */
    public function dispatch(SwooleRequest $swooleRequest, SwooleResponse $swooleResponse): void
    {
        $request = new Request($swooleRequest);
        $response = new Response($swooleResponse);

        // Set CORS headers for all API requests
        $response->cors();

        $method = $request->getMethod();
        $uri = $request->getUri();

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $request->setParams($params);

                try {
                    // Execute middleware chain
                    $this->executeMiddleware($route, $request, $response);
                } catch (\Throwable $e) {
                    $response->error($e->getMessage(), 500);
                }

                return;
            }
        }

        // No route found
        $response->error('Not Found', 404);
    }

    /**
     * Execute middleware chain and final handler
     */
    private function executeMiddleware(array $route, Request $request, Response $response): void
    {
        $middleware = $route['middleware'];
        $handler = $route['handler'];

        // If no middleware, execute handler directly
        if (empty($middleware)) {
            $handler($request, $response);
            return;
        }

        // Build middleware chain
        $next = function ($request, $response) use ($handler) {
            $handler($request, $response);
            return null;
        };

        // Wrap each middleware
        for ($i = count($middleware) - 1; $i >= 0; $i--) {
            $currentMiddleware = $middleware[$i];
            $next = function ($request, $response) use ($currentMiddleware, $next) {
                return $currentMiddleware->handle($request, $response, $next);
            };
        }

        // Execute the chain
        $result = $next($request, $response);

        // If middleware returned a response, it already handled it
        if ($result instanceof Response) {
            return;
        }
    }

}

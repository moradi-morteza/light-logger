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
        ];

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
                    $route['handler']($request, $response);
                } catch (\Throwable $e) {
                    $response->error($e->getMessage(), 500);
                }

                return;
            }
        }

        // No route found
        $response->error('Not Found', 404);
    }

}

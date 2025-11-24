<?php

namespace LightLogger\Http;

use Swoole\Http\Request as SwooleRequest;

/**
 * HTTP Request wrapper with helper methods.
 */
class Request
{
    private SwooleRequest $swooleRequest;
    private array $params = [];
    private ?array $jsonBody = null;
    private ?array $user = null;
    private ?string $authToken = null;

    public function __construct(SwooleRequest $swooleRequest)
    {
        $this->swooleRequest = $swooleRequest;
    }

    /**
     * Get the HTTP method.
     */
    public function getMethod(): string
    {
        return strtoupper($this->swooleRequest->server['request_method'] ?? 'GET');
    }

    /**
     * Get the request URI (path only, no query string).
     */
    public function getUri(): string
    {
        return $this->swooleRequest->server['request_uri'] ?? '/';
    }

    /**
     * Get the full request URI including query string.
     */
    public function getFullUri(): string
    {
        $uri = $this->getUri();
        $query = $this->swooleRequest->server['query_string'] ?? '';

        return $query ? "{$uri}?{$query}" : $uri;
    }

    /**
     * Get a header value.
     */
    public function getHeader(string $name): ?string
    {
        $name = strtolower($name);
        return $this->swooleRequest->header[$name] ?? null;
    }

    /**
     * Get all headers.
     */
    public function getHeaders(): array
    {
        return $this->swooleRequest->header ?? [];
    }

    /**
     * Get the project token from header.
     */
    public function getProjectToken(): ?string
    {
        return $this->getHeader('x-project-token');
    }

    /**
     * Get query parameter.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->swooleRequest->get[$key] ?? $default;
    }

    /**
     * Get all query parameters.
     */
    public function queryAll(): array
    {
        return $this->swooleRequest->get ?? [];
    }

    /**
     * Get POST parameter.
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->swooleRequest->post[$key] ?? $default;
    }

    /**
     * Get all POST parameters.
     */
    public function postAll(): array
    {
        return $this->swooleRequest->post ?? [];
    }

    /**
     * Get raw request body.
     */
    public function getRawBody(): string
    {
        return $this->swooleRequest->rawContent() ?? '';
    }

    /**
     * Get JSON body as array.
     */
    public function json(): ?array
    {
        if ($this->jsonBody !== null) {
            return $this->jsonBody;
        }

        $body = $this->getRawBody();
        if (empty($body)) {
            return null;
        }

        $this->jsonBody = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $this->jsonBody;
    }

    /**
     * Get a value from JSON body.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        $json = $this->json();
        return $json[$key] ?? $default;
    }

    /**
     * Set route parameters.
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Get a route parameter.
     */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Get all route parameters.
     */
    public function params(): array
    {
        return $this->params;
    }

    /**
     * Get client IP address.
     */
    public function getClientIp(): string
    {
        return $this->getHeader('x-forwarded-for')
            ?? $this->getHeader('x-real-ip')
            ?? $this->swooleRequest->server['remote_addr']
            ?? '0.0.0.0';
    }

    /**
     * Get the underlying Swoole request.
     */
    public function getSwooleRequest(): SwooleRequest
    {
        return $this->swooleRequest;
    }

    /**
     * Check if the request expects JSON response.
     */
    public function expectsJson(): bool
    {
        $accept = $this->getHeader('accept') ?? '';
        return str_contains($accept, 'application/json') || str_contains($accept, '*/*');
    }

    /**
     * Check if the request has JSON content type.
     */
    public function isJson(): bool
    {
        $contentType = $this->getHeader('content-type') ?? '';
        return str_contains($contentType, 'application/json');
    }

    /**
     * Get the auth token from Authorization header.
     */
    public function getAuthToken(): ?string
    {
        if ($this->authToken) {
            return $this->authToken;
        }

        $authHeader = $this->getHeader('authorization');
        if (!$authHeader) {
            return null;
        }

        // Extract Bearer token
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Set the auth token (used by middleware).
     */
    public function setAuthToken(string $token): void
    {
        $this->authToken = $token;
    }

    /**
     * Set the authenticated user (used by middleware).
     */
    public function setUser(array $user): void
    {
        $this->user = $user;
    }

    /**
     * Get the authenticated user.
     */
    public function getUser(): ?array
    {
        return $this->user;
    }

    /**
     * Check if the request is authenticated.
     */
    public function isAuthenticated(): bool
    {
        return $this->user !== null;
    }
}

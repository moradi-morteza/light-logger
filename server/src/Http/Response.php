<?php

namespace LightLogger\Http;

use Swoole\Http\Response as SwooleResponse;

/**
 * HTTP Response wrapper with helper methods.
 */
class Response
{
    private SwooleResponse $swooleResponse;
    private bool $sent = false;

    public function __construct(SwooleResponse $swooleResponse)
    {
        $this->swooleResponse = $swooleResponse;
    }

    /**
     * Set a response header.
     */
    public function header(string $name, string $value): self
    {
        $this->swooleResponse->header($name, $value);
        return $this;
    }

    /**
     * Set the HTTP status code.
     */
    public function status(int $code): self
    {
        $this->swooleResponse->status($code);
        return $this;
    }

    /**
     * Send a JSON response.
     */
    public function json(array $data, int $status = 200): void
    {
        if ($this->sent) {
            return;
        }

        $this->sent = true;
        $this->swooleResponse->status($status);
        $this->swooleResponse->header('Content-Type', 'application/json');
        $this->swooleResponse->end(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Send a success response.
     */
    public function success(array $data = [], string $message = 'Success', int $status = 200): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Send an error response.
     */
    public function error(string $message, int $status = 400, array $errors = []): void
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        $this->json($response, $status);
    }

    /**
     * Send an unauthorized response (401).
     */
    public function unauthorized(array $data = []): Response
    {
        $response = array_merge([
            'success' => false,
            'message' => 'Unauthorized'
        ], $data);

        $this->json($response, 401);
        return $this;
    }

    /**
     * Send a created response (201).
     */
    public function created(array $data = [], string $message = 'Created'): void
    {
        $this->success($data, $message, 201);
    }

    /**
     * Send a no content response (204).
     */
    public function noContent(): void
    {
        if ($this->sent) {
            return;
        }

        $this->sent = true;
        $this->swooleResponse->status(204);
        $this->swooleResponse->end();
    }

    /**
     * Send a plain text response.
     */
    public function text(string $content, int $status = 200): void
    {
        if ($this->sent) {
            return;
        }

        $this->sent = true;
        $this->swooleResponse->status($status);
        $this->swooleResponse->header('Content-Type', 'text/plain');
        $this->swooleResponse->end($content);
    }

    /**
     * Send an HTML response.
     */
    public function html(string $content, int $status = 200): void
    {
        if ($this->sent) {
            return;
        }

        $this->sent = true;
        $this->swooleResponse->status($status);
        $this->swooleResponse->header('Content-Type', 'text/html');
        $this->swooleResponse->end($content);
    }

    /**
     * Send a redirect response.
     */
    public function redirect(string $url, int $status = 302): void
    {
        if ($this->sent) {
            return;
        }

        $this->sent = true;
        $this->swooleResponse->status($status);
        $this->swooleResponse->header('Location', $url);
        $this->swooleResponse->end();
    }

    /**
     * Check if response has been sent.
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * Get the underlying Swoole response.
     */
    public function getSwooleResponse(): SwooleResponse
    {
        return $this->swooleResponse;
    }

    /**
     * Set CORS headers for API responses.
     */
    public function cors(string $origin = '*', array $methods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']): self
    {
        $this->swooleResponse->header('Access-Control-Allow-Origin', $origin);
        $this->swooleResponse->header('Access-Control-Allow-Methods', implode(', ', $methods));
        $this->swooleResponse->header('Access-Control-Allow-Headers', 'Content-Type, X-Project-Token, Authorization');
        $this->swooleResponse->header('Access-Control-Max-Age', '86400');

        return $this;
    }

    /**
     * Handle OPTIONS preflight request.
     */
    public function handleOptions(): void
    {
        $this->cors();
        $this->noContent();
    }
}

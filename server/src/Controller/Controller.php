<?php

namespace LightLogger\Controller;

use LightLogger\Http\Response;

/**
 * Base controller class with common functionality.
 */
abstract class Controller
{
    /**
     * Send a success response.
     */
    protected function success(Response $response, array $data = [], string $message = 'Success', int $status = 200): void
    {
        $response->cors();
        $response->success($data, $message, $status);
    }

    /**
     * Send an error response.
     */
    protected function error(Response $response, string $message, int $status = 400, array $errors = []): void
    {
        $response->cors();
        $response->error($message, $status, $errors);
    }

    /**
     * Send a created response.
     */
    protected function created(Response $response, array $data = [], string $message = 'Created'): void
    {
        $response->cors();
        $response->created($data, $message);
    }

    /**
     * Send a not found response.
     */
    protected function notFound(Response $response, string $message = 'Not found'): void
    {
        $response->cors();
        $response->error($message, 404);
    }

    /**
     * Send an unauthorized response.
     */
    protected function unauthorized(Response $response, string $message = 'Unauthorized'): void
    {
        $response->cors();
        $response->error($message, 401);
    }

    /**
     * Validate required fields in request data.
     *
     * @return array Array of missing fields (empty if all present)
     */
    protected function validateRequired(array $data, array $requiredFields): array
    {
        $missing = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }
}

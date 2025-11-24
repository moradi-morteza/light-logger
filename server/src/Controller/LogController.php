<?php

namespace LightLogger\Controller;

use LightLogger\Http\Request;
use LightLogger\Http\Response;
use LightLogger\Tools\Log\Log;

/**
 * Handles log ingestion and retrieval endpoints.
 */
class LogController extends Controller
{

    public function __construct()
    {

    }


    /**
     * POST /api/v1/logs
     * Ingest logs from clients.
     */
    public function ingest(Request $request, Response $response): void
    {
        $response->cors();

        // Parse log data
        $data = $request->json();
        if ($data === null) {
            $this->error($response, 'Invalid JSON body', 400);
            return;
        }

        // Support single log or batch
        $logs = isset($data['logs']) ? $data['logs'] : [$data];

        $entries = [];
        $errors = [];

        foreach ($logs as $index => $logData) {
            // handle
        }

        if (!empty($errors)) {
            $response->cors();
            $response->json([
                'success' => false,
                'message' => 'Some logs failed validation',
                'accepted' => count($entries),
                'rejected' => count($errors),
                'errors' => $errors,
            ], 422);
            return;
        }

        Log::info("Received " . count($entries));

        $this->success($response, [
            'accepted' => count($entries),
        ], 'Logs received');
    }

    /**
     * GET /api/v1/logs
     * Retrieve logs for a project.
     */
    public function index(Request $request, Response $response): void
    {
        $response->cors();

        $limit = (int) ($request->query('limit', 100));
        $offset = (int) ($request->query('offset', 0));
        $level = $request->query('level');

        $limit = min($limit, 1000); // Cap at 1000
        
        if ($level) {

        } else {

        }

        $this->success($response, []);
    }
}

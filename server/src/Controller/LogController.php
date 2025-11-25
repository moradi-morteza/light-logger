<?php

namespace LightLogger\Controller;

use LightLogger\Http\Request;
use LightLogger\Http\Response;
use LightLogger\Model\Project;
use LightLogger\Validator\LogValidator;
use LightLogger\Tools\Log\Log;

/**
 * Handles log ingestion and retrieval endpoints.
 */
class LogController extends Controller
{
    private LogValidator $validator;

    public function __construct()
    {
        $this->validator = new LogValidator();
    }


    /**
     * POST /api/v1/logs
     * Ingest logs from clients.
     */
    public function ingest(Request $request, Response $response): void
    {
        $response->cors();

        // Get project token from Authorization header
        $authHeader = $request->getHeader('Authorization');
        if (!$authHeader || !preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $this->error($response, 'Missing or invalid Authorization header. Use: Bearer YOUR_PROJECT_TOKEN', 401);
            return;
        }

        $token = $matches[1];

        // Verify project exists
        $project = Project::getByToken($token);
        if (!$project) {
            $this->error($response, 'Invalid project token', 401);
            return;
        }

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
            // Add project_id to log data
            $logData['project_id'] = $project['id'];

            // Validate log against schema
            if (!$this->validator->validate($logData, $project['schema'])) {
                $errors[] = [
                    'index' => $index,
                    'errors' => $this->validator->getErrors(),
                ];
                continue;
            }

            // Log is valid
            $entries[] = $logData;
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

        // TODO: Send validated logs to Elasticsearch
        Log::info("Received " . count($entries) . " valid log(s) for project: {$project['name']}");

        $this->success($response, [
            'accepted' => count($entries),
            'project' => $project['name'],
        ], 'Logs received successfully');
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

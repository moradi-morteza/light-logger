<?php

namespace LightLogger\Controller;

use LightLogger\Http\Request;
use LightLogger\Http\Response;
use LightLogger\Model\Project;
use Exception;

class ProjectController extends Controller
{
    /**
     * Get all projects.
     * GET /api/projects
     */
    public function index(Request $request, Response $response): void
    {
        try {
            $projects = Project::getAll();
            $this->success($response, $projects);
        } catch (Exception $e) {
            $this->error($response, 'Failed to fetch projects: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get single project by ID.
     * GET /api/projects/{id}
     */
    public function show(Request $request, Response $response): void
    {
        $id = (int) $request->param('id');

        if ($id <= 0) {
            $this->error($response, 'Invalid project ID', 400);
            return;
        }

        try {
            $project = Project::getById($id);

            if (!$project) {
                $this->notFound($response, 'Project not found');
                return;
            }

            $this->success($response, $project);
        } catch (Exception $e) {
            $this->error($response, 'Failed to fetch project: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create new project.
     * POST /api/projects
     */
    public function store(Request $request, Response $response): void
    {
        $data = $request->json() ?? [];

        // Validate
        $missing = $this->validateRequired($data, ['name']);
        if (!empty($missing)) {
            $this->error($response, 'Missing required fields', 400, $missing);
            return;
        }

        $name = trim($data['name']);

        if (empty($name)) {
            $this->error($response, 'Project name cannot be empty', 400);
            return;
        }

        if (strlen($name) > 255) {
            $this->error($response, 'Project name is too long (max 255 characters)', 400);
            return;
        }

        try {
            $project = Project::create($name);
            $this->created($response, $project, 'Project created successfully');
        } catch (Exception $e) {
            $this->error($response, 'Failed to create project: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete project.
     * DELETE /api/projects/{id}
     */
    public function destroy(Request $request, Response $response): void
    {
        $id = (int) $request->param('id');

        if ($id <= 0) {
            $this->error($response, 'Invalid project ID', 400);
            return;
        }

        try {
            $deleted = Project::delete($id);

            if (!$deleted) {
                $this->notFound($response, 'Project not found');
                return;
            }

            $this->success($response, [], 'Project deleted successfully');
        } catch (Exception $e) {
            $this->error($response, 'Failed to delete project: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get project schema.
     * GET /api/projects/{id}/schema
     */
    public function getSchema(Request $request, Response $response): void
    {
        $id = (int) $request->param('id');

        if ($id <= 0) {
            $this->error($response, 'Invalid project ID', 400);
            return;
        }

        try {
            $project = Project::getById($id);

            if (!$project) {
                $this->notFound($response, 'Project not found');
                return;
            }

            $this->success($response, [
                'schema' => $project['schema'],
            ]);
        } catch (Exception $e) {
            $this->error($response, 'Failed to fetch schema: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update project schema.
     * PUT /api/projects/{id}/schema
     */
    public function updateSchema(Request $request, Response $response): void
    {
        $id = (int) $request->param('id');

        if ($id <= 0) {
            $this->error($response, 'Invalid project ID', 400);
            return;
        }

        $data = $request->json() ?? [];

        // Validate schema structure
        if (!isset($data['schema'])) {
            $this->error($response, 'Schema is required', 400);
            return;
        }

        $schema = $data['schema'];

        if (!is_array($schema)) {
            $this->error($response, 'Schema must be an object', 400);
            return;
        }

        // Validate fields array
        if (!isset($schema['fields']) || !is_array($schema['fields'])) {
            $this->error($response, 'Schema must contain a fields array', 400);
            return;
        }

        // Validate each field definition
        $validationErrors = $this->validateSchemaFields($schema['fields']);
        if (!empty($validationErrors)) {
            $this->error($response, 'Invalid schema definition', 400, $validationErrors);
            return;
        }

        try {
            $updated = Project::updateSchema($id, $schema);

            if (!$updated) {
                $this->notFound($response, 'Project not found');
                return;
            }

            $this->success($response, [
                'schema' => $schema,
            ], 'Schema updated successfully');
        } catch (Exception $e) {
            $this->error($response, 'Failed to update schema: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Validate schema fields structure.
     */
    private function validateSchemaFields(array $fields): array
    {
        $errors = [];
        $allowedTypes = ['string', 'number', 'boolean', 'array', 'object', 'datetime'];

        foreach ($fields as $index => $field) {
            $fieldPath = "fields[{$index}]";

            // Check required properties
            if (!isset($field['name']) || !is_string($field['name']) || trim($field['name']) === '') {
                $errors[] = "{$fieldPath}: 'name' is required and must be a non-empty string";
            }

            if (!isset($field['type']) || !in_array($field['type'], $allowedTypes, true)) {
                $allowedTypesStr = implode(', ', $allowedTypes);
                $errors[] = "{$fieldPath}: 'type' must be one of: {$allowedTypesStr}";
            }

            if (!isset($field['indexed']) || !is_bool($field['indexed'])) {
                $errors[] = "{$fieldPath}: 'indexed' is required and must be boolean";
            }

            if (!isset($field['required']) || !is_bool($field['required'])) {
                $errors[] = "{$fieldPath}: 'required' is required and must be boolean";
            }

            // Validate field name format
            if (isset($field['name']) && !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field['name'])) {
                $errors[] = "{$fieldPath}: Field name must start with letter or underscore, contain only letters, numbers, and underscores";
            }

            // Validate validation rules if present
            if (isset($field['validation']) && !is_array($field['validation'])) {
                $errors[] = "{$fieldPath}: 'validation' must be an object";
            }
        }

        return $errors;
    }
}

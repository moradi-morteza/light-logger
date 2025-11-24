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
}

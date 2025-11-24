<?php

namespace LightLogger\Controller;

use LightLogger\Auth\Auth;
use LightLogger\Http\Request;
use LightLogger\Http\Response;

class AuthController extends Controller
{
    /**
     * Login user and return token
     * POST /api/auth/login
     */
    public function login(Request $request, Response $response): void
    {
        $data = $request->json();

        // Validate required fields
        $errors = $this->validateRequired($data, ['username', 'password']);
        if (!empty($errors)) {
            $response->error($errors, 400);
            return;
        }

        // Attempt login
        $result = Auth::login($data['username'], $data['password'], $request);

        if (!$result) {
            $response->unauthorized([
                'success' => false,
                'message' => 'Invalid credentials'
            ]);
            return;
        }

        $response->success([
            'success' => true,
            'token' => $result['token'],
            'user' => $result['user'],
            'expires_at' => $result['expires_at']
        ]);
    }

    /**
     * Logout user (destroy session)
     * POST /api/auth/logout
     */
    public function logout(Request $request, Response $response): void
    {
        $token = $request->getAuthToken();

        if (!$token) {
            $response->error('No token provided', 400);
            return;
        }

        Auth::logout($token);

        $response->success([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get current authenticated user
     * GET /api/auth/me
     */
    public function me(Request $request, Response $response): void
    {
        $user = $request->getUser();

        if (!$user) {
            $response->unauthorized([
                'success' => false,
                'message' => 'Not authenticated'
            ]);
            return;
        }

        $response->success([
            'success' => true,
            'user' => $user
        ]);
    }

    /**
     * Check if token is valid
     * POST /api/auth/check
     */
    public function check(Request $request, Response $response): void
    {
        $data = $request->json();
        $token = $data['token'] ?? null;

        if (!$token) {
            $response->error('Token required', 400);
            return;
        }

        $user = Auth::verify($token);

        if (!$user) {
            $response->json([
                'success' => false,
                'valid' => false
            ]);
            return;
        }

        $response->success([
            'success' => true,
            'valid' => true,
            'user' => $user
        ]);
    }
}

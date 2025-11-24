<?php

namespace LightLogger\Middleware;

use LightLogger\Auth\Auth;
use LightLogger\Http\Request;
use LightLogger\Http\Response;
use LightLogger\Tools\Log\Log;

class AuthMiddleware implements Middleware
{
    /**
     * Handle the request
     */
    public function handle(Request $request, Response $response, callable $next): ?Response
    {
        // Get Authorization header
        $authHeader = $request->getHeader('Authorization');

        if (!$authHeader) {
            Log::warning("Auth failed: No Authorization header");
            return $response->unauthorized([
                'success' => false,
                'message' => 'Authentication required'
            ]);
        }

        // Extract bearer token
        $token = Auth::extractBearerToken($authHeader);

        if (!$token) {
            Log::warning("Auth failed: Invalid authorization header format");
            return $response->unauthorized([
                'success' => false,
                'message' => 'Invalid authorization header'
            ]);
        }

        Log::debug("Verifying token: " . substr($token, 0, 16) . "...");

        // Verify token and get user
        $user = Auth::verify($token);

        if (!$user) {
            Log::warning("Auth failed: Invalid or expired token");
            return $response->unauthorized([
                'success' => false,
                'message' => 'Invalid or expired token'
            ]);
        }

        Log::success("Auth successful for user: " . $user['username']);

        // Attach user to request
        $request->setUser($user);
        $request->setAuthToken($token);

        // Continue to next middleware or handler
        return $next($request, $response);
    }
}

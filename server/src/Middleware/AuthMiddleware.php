<?php

namespace LightLogger\Middleware;

use LightLogger\Auth\Auth;
use LightLogger\Http\Request;
use LightLogger\Http\Response;

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
            return $response->unauthorized([
                'success' => false,
                'message' => 'Authentication required'
            ]);
        }

        // Extract bearer token
        $token = Auth::extractBearerToken($authHeader);

        if (!$token) {
            return $response->unauthorized([
                'success' => false,
                'message' => 'Invalid authorization header'
            ]);
        }

        // Verify token and get user
        $user = Auth::verify($token);

        if (!$user) {
            return $response->unauthorized([
                'success' => false,
                'message' => 'Invalid or expired token'
            ]);
        }

        // Attach user to request
        $request->setUser($user);
        $request->setAuthToken($token);

        // Continue to next middleware or handler
        return $next($request, $response);
    }
}

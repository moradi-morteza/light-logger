<?php

namespace LightLogger\Auth;

use LightLogger\Model\User;
use LightLogger\Model\Session;
use LightLogger\Http\Request;

class Auth
{
    /**
     * Authenticate user and create session
     */
    public static function login(string $username, string $password, Request $request): ?array
    {
        // Verify credentials
        $user = User::verifyPassword($username, $password);

        if (!$user) {
            return null;
        }

        // Update last login
        User::updateLastLogin($user['id']);

        // Create session
        $session = Session::create(
            $user['id'],
            $request->getClientIp(),
            $request->getHeader('User-Agent')
        );

        return [
            'user' => $user,
            'token' => $session['token'],
            'expires_at' => $session['expires_at']
        ];
    }

    /**
     * Logout user (destroy session)
     */
    public static function logout(string $token): bool
    {
        return Session::delete($token);
    }

    /**
     * Verify token and get user
     */
    public static function verify(string $token): ?array
    {
        $user = Session::getUserByToken($token);

        if ($user) {
            // Extend session on valid request
            Session::extend($token);
        }

        return $user;
    }

    /**
     * Get user from token
     */
    public static function getUserFromToken(string $token): ?array
    {
        return Session::getUserByToken($token);
    }

    /**
     * Generate a secure random token
     */
    public static function generateToken(int $bytes = 64): string
    {
        return bin2hex(random_bytes($bytes));
    }

    /**
     * Extract bearer token from Authorization header
     */
    public static function extractBearerToken(string $authHeader): ?string
    {
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return null;
        }

        return $matches[1];
    }
}

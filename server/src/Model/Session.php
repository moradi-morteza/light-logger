<?php

namespace LightLogger\Model;

use LightLogger\Database\Database;
use LightLogger\Tools\Log\Log;
use PDO;

class Session
{
    /**
     * Session lifetime in seconds (24 hours)
     */
    private const SESSION_LIFETIME = 86400;

    /**
     * Create a new session
     */
    public static function create(int $userId, string $ipAddress = null, string $userAgent = null): array
    {
        $db = Database::getConnection();

        $sessionId = bin2hex(random_bytes(32));
        $token = bin2hex(random_bytes(64));
        $expiresAt = date('Y-m-d H:i:s', time() + self::SESSION_LIFETIME);

        $stmt = $db->prepare("
            INSERT INTO sessions (id, user_id, token, ip_address, user_agent, expires_at, created_at)
            VALUES (:id, :user_id, :token, :ip_address, :user_agent, :expires_at, NOW())
        ");

        $stmt->execute([
            'id' => $sessionId,
            'user_id' => $userId,
            'token' => $token,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => $expiresAt
        ]);

        return [
            'id' => $sessionId,
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * Find session by token
     */
    public static function findByToken(string $token): ?array
    {
        $db = Database::getConnection();

        // Delete expired sessions first
        self::deleteExpired();

        $stmt = $db->prepare("
            SELECT id, user_id, token, ip_address, user_agent, expires_at, created_at
            FROM sessions
            WHERE token = :token AND expires_at > NOW()
        ");

        $stmt->execute(['token' => $token]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            Log::info("Session not found for token: " . substr($token, 0, 16) . "...");
        } else {
            Log::info("Session found for user_id: " . $session['user_id']);
        }

        return $session ?: null;
    }

    /**
     * Get user by session token
     */
    public static function getUserByToken(string $token): ?array
    {
        $session = self::findByToken($token);

        if (!$session) {
            return null;
        }

        return User::findById($session['user_id']);
    }

    /**
     * Delete a session (logout)
     */
    public static function delete(string $token): bool
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            DELETE FROM sessions
            WHERE token = :token
        ");

        $stmt->execute(['token' => $token]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete all sessions for a user
     */
    public static function deleteByUserId(int $userId): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            DELETE FROM sessions
            WHERE user_id = :user_id
        ");

        $stmt->execute(['user_id' => $userId]);
    }

    /**
     * Delete expired sessions
     */
    public static function deleteExpired(): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            DELETE FROM sessions
            WHERE expires_at <= NOW()
        ");

        $stmt->execute();
    }

    /**
     * Extend session expiration
     */
    public static function extend(string $token): bool
    {
        $db = Database::getConnection();

        $expiresAt = date('Y-m-d H:i:s', time() + self::SESSION_LIFETIME);

        $stmt = $db->prepare("
            UPDATE sessions
            SET expires_at = :expires_at
            WHERE token = :token
        ");

        $stmt->execute([
            'expires_at' => $expiresAt,
            'token' => $token
        ]);

        return $stmt->rowCount() > 0;
    }
}

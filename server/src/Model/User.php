<?php

namespace LightLogger\Model;

use LightLogger\Database\Database;
use PDO;

class User
{
    /**
     * Create a new user
     */
    public static function create(string $username, string $email, string $password): array
    {
        $db = Database::getConnection();

        $hashedPassword = self::hashPassword($password);

        $stmt = $db->prepare("
            INSERT INTO users (username, email, password, created_at, updated_at)
            VALUES (:username, :email, :password, NOW(), NOW())
        ");

        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword
        ]);

        $userId = $db->lastInsertId();

        return self::findById($userId);
    }

    /**
     * Find user by ID
     */
    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT id, username, email, created_at, updated_at, last_login_at
            FROM users
            WHERE id = :id
        ");

        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Find user by username
     */
    public static function findByUsername(string $username): ?array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT id, username, email, password, created_at, updated_at, last_login_at
            FROM users
            WHERE username = :username
        ");

        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Find user by email
     */
    public static function findByEmail(string $email): ?array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT id, username, email, password, created_at, updated_at, last_login_at
            FROM users
            WHERE email = :email
        ");

        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Verify user password
     */
    public static function verifyPassword(string $username, string $password): ?array
    {
        $user = self::findByUsername($username);

        if (!$user) {
            return null;
        }

        if (!password_verify($password, $user['password'])) {
            return null;
        }

        // Remove password from returned data
        unset($user['password']);

        return $user;
    }

    /**
     * Update last login timestamp
     */
    public static function updateLastLogin(int $userId): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            UPDATE users
            SET last_login_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute(['id' => $userId]);
    }

    /**
     * Hash password using bcrypt
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    /**
     * Get user without sensitive data
     */
    public static function getSafeUser(array $user): array
    {
        unset($user['password']);
        return $user;
    }
}

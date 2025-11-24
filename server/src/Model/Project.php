<?php

namespace LightLogger\Model;

use LightLogger\Database\Database;
use LightLogger\Tools\Log\Log;
use PDO;
use Exception;

class Project
{
    /**
     * Create a new project.
     */
    public static function create(string $name): array
    {
        try {
            $pdo = Database::getConnection();

            // Generate secure token
            $token = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare(
                "INSERT INTO projects (name, token) VALUES (:name, :token)"
            );

            $stmt->execute([
                'name' => $name,
                'token' => $token,
            ]);

            $id = $pdo->lastInsertId();

            Log::success("Project created: {$name} (ID: {$id})");

            return [
                'id' => (int)$id,
                'name' => $name,
                'token' => $token,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        } catch (Exception $e) {
            Log::error("Failed to create project: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all projects.
     */
    public static function getAll(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, name, token, created_at, updated_at FROM projects ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    /**
     * Get project by ID.
     */
    public static function getById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, name, token, created_at, updated_at FROM projects WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get project by token.
     */
    public static function getByToken(string $token): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, name, token, created_at, updated_at FROM projects WHERE token = :token");
        $stmt->execute(['token' => $token]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Delete project by ID.
     */
    public static function delete(int $id): bool
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $deleted = $stmt->rowCount() > 0;

            if ($deleted) {
                Log::success("Project deleted: ID {$id}");
            } else {
                Log::warning("Project not found: ID {$id}");
            }

            return $deleted;
        } catch (Exception $e) {
            Log::error("Failed to delete project: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if token exists and is valid.
     */
    public static function tokenExists(string $token): bool
    {
        return self::getByToken($token) !== null;
    }
}

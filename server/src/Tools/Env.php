<?php

namespace LightLogger\Tools;

use Exception;

class Env
{
    private static array $data = [];

    /**
     * @throws Exception
     */
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            throw new Exception("Env file not found: $path");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            $key = strtolower(trim($key));
            $value = trim($value);

            // store only once (immutable)
            if (!isset(self::$data[$key])) {
                self::$data[$key] = $value;
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $key = strtolower($key);
        return self::$data[$key] ?? $default;
    }

    public static function isProduction(): bool
    {
        return self::get('APP_ENV') === 'production';
    }

    public static function isDebug(): bool
    {
        return in_array(
            strtolower(self::get('APP_DEBUG', 'false')),
            ['true', '1', 'yes', 'on'],
            true
        );
    }
}
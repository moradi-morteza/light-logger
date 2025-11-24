<?php

require_once __DIR__ . '/vendor/autoload.php';

use LightLogger\Tools\Env;
use LightLogger\Tools\Log\Log;

// Try to load .env from server-data volume first, fallback to old location
$envPath = '/app/server-data/.env';
if (!file_exists($envPath)) {
    $envPath = __DIR__ . '/.env';
}

try {
    Env::load($envPath);
} catch (Exception $e) {
    // .env doesn't exist yet (not installed), continue with environment variables
    Log::info('.env file not found, using environment variables');
}

<?php

require_once __DIR__ . '/vendor/autoload.php';

use LightLogger\Tools\Env;
use LightLogger\Tools\Log\Log;

try {
    Env::load(__DIR__ . '/.env');
} catch (Exception $e) {
    Log::error($e->getMessage());
    exit(1);
}

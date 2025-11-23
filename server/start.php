<?php

use LightLogger\Server\LoggerServer;
use LightLogger\Tools\Env;

require_once __DIR__ . '/bootstrap.php';

$server = new LoggerServer(Env::get("app_host"), Env::get("app_port"));
$server->start();

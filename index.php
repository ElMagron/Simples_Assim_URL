<?php

require __DIR__ . '/vendor/autoload.php';

use App\Router;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$router = new Router();

require __DIR__ . '/routes.php';

$router->run();

exit;
<?php

// Carrega o Autoload do Composer
require __DIR__ . '/vendor/autoload.php';

use App\Router;

// Carrega as Variáveis de Ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configurações de Erro (Essencial para DEBUG)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Instancia o Router e Define as Rotas
$router = new Router();

$router->run();

// Finaliza o Script
exit;
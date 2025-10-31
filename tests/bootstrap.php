<?php
// tests/bootstrap.php

// 1. Carrega o Autoload do Composer
require dirname(__DIR__) . '/vendor/autoload.php';

// 2. Inicializa o Dotenv
// Isso garante que $_ENV e getenv() tenham acesso às variáveis do .env
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad(); 

// Nota: O método safeLoad() não lança exceção se o .env não for encontrado.
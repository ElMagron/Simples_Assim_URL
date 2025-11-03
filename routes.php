<?php

/** @var \App\Router $router */ // Para ajudar o seu editor/IDE

$router->get('api/status', 'handleHealthCheck');

$router->post('api/link', 'handlePostCreate');

$router->get('api/stats/(\w+)', 'handleGetStats');

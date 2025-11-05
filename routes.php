<?php

/** @var \App\Router $router */

$router->get('api/status', 'handleHealthCheck');

$router->post('api/link', 'handlePostCreate');

$router->get('api/stats/(\w+)', 'handleGetStats');

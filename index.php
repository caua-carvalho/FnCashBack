<?php

require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/Controllers/HomeController.php';

$router = new Router();

// Definição das rotas
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [HomeController::class, 'about']);

// Executa o roteamento
$router->dispatch();

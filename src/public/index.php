<?php

declare(strict_types=1);

// Erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json');

// Raiz do projeto
$root = dirname(__DIR__);

// Core
require_once $root . '/Router.php';
require_once $root . '/middleware_helpers.php';

// Controllers
require_once $root . '/Controllers/HomeController.php';
require_once $root . '/Controllers/TransactionController.php';
require_once $root . '/Controllers/CreateTokenController.php';

// Router
$router = new Router();

// Rotas públicas
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [HomeController::class, 'about']);
$router->get('/createToken', [CreateToken::class, 'index']);

// Rotas protegidas
$router->get('/transactions', withAuth([TransactionController::class, 'index']));
$router->get('/transactions/show', withAuth([TransactionController::class, 'show']));
$router->post('/transactions', withAuth([TransactionController::class, 'create']));
$router->post('/transactions/update', withAuth([TransactionController::class, 'update']));
$router->post('/transactions/delete', withAuth([TransactionController::class, 'destroy']));
$router->post('/transactions/audio', withAuth([TransactionController::class, 'storeAudio']));

// Dispatch
$router->dispatch();

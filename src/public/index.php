<?php

declare(strict_types=1);

/**
 * ======================================================
 * BOOTSTRAP DA APLICAÇÃO
 * ======================================================
 */

// --------------------
// Debug (ajuste por ambiente depois)
// --------------------
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// --------------------
// CORS
// --------------------
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// --------------------
// Root da aplicação
// --------------------
define('APP_ROOT', dirname(__DIR__));

// --------------------
// ENV
// --------------------
require_once APP_ROOT . '/vendor/autoload.php';

// Carrega env
$dotenv = Dotenv\Dotenv::createImmutable(APP_ROOT);
$dotenv->load();

// --------------------
// Core
// --------------------
require_once APP_ROOT . '/Router.php';
require_once APP_ROOT . '/middleware_helpers.php';

// --------------------
// Controllers
// --------------------
require_once APP_ROOT . '/Controllers/HomeController.php';
require_once APP_ROOT . '/Controllers/TransactionController.php';
require_once APP_ROOT . '/Controllers/CreateTokenController.php';
require_once APP_ROOT . '/Controllers/UserController.php';

// --------------------
// Router
// --------------------
$router = new Router();

// Rotas públicas
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [HomeController::class, 'about']);
$router->get('/createToken', [CreateTokenController::class, 'index']);
$router->post('/login', [UserController::class, 'login']);

// Rotas protegidas
$router->get('/transactions', withAuth([TransactionController::class, 'index']));
$router->get('/transactions/show', withAuth([TransactionController::class, 'show']));
$router->post('/transactions', withAuth([TransactionController::class, 'create']));
$router->post('/transactions/update', withAuth([TransactionController::class, 'update']));
$router->post('/transactions/delete', withAuth([TransactionController::class, 'destroy']));
$router->post('/transactions/audio', withAuth([TransactionController::class, 'storeAudio']));

// --------------------
// Dispatch
// --------------------
$router->dispatch();

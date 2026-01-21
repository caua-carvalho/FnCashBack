
<?php
// Ativa exibição de erros para ambiente de desenvolvimento
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Carrega o roteador principal
require_once __DIR__ . '/Router.php';

// Carrega controllers principais
require_once __DIR__ . '/Controllers/HomeController.php';
require_once __DIR__ . '/Controllers/TransactionController.php';

// Carrega helpers de middleware (JWT)
require_once __DIR__ . '/middleware_helpers.php';

// Instancia o roteador
$router = new Router();

// Rotas públicas
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [HomeController::class, 'about']);

// Rotas protegidas por autenticação JWT
$router->get('/transactions', withAuth([TransactionController::class, 'index']));
$router->get('/transactions/show', withAuth([TransactionController::class, 'show']));
$router->post('/transactions', withAuth([TransactionController::class, 'store']));
$router->post('/transactions/update', withAuth([TransactionController::class, 'update']));
$router->post('/transactions/delete', withAuth([TransactionController::class, 'destroy']));

// Inicia o roteamento (resolve a rota e executa o handler)
$router->dispatch();

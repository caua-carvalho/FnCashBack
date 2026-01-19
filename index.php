
<?php
// Ponto de entrada do sistema MVC com rotas e middleware JWT
require_once 'Controllers/HomeController.php';
require_once 'jwt_utils.php';

// Configurar CORS para app mobile (Expo)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

// Definir rotas
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Exemplo de rota pública
if ($uri === '/' && $method === 'GET') {
	$controller = new HomeController();
	$controller->index();
	exit;
}

// Exemplo de rota protegida
if ($uri === '/api/protected' && $method === 'GET') {
	$jwt = getBearerToken();
	$secret = 'SUA_CHAVE_SECRETA_AQUI'; // Troque por uma chave segura
	$payload = validateJWT($jwt, $secret);
	if (!$payload) {
		http_response_code(401);
		echo json_encode(['error' => 'Token inválido ou expirado']);
		exit;
	}
	// Resposta protegida
	header('Content-Type: application/json');
	echo json_encode(['message' => 'Acesso autorizado', 'user' => $payload]);
	exit;
}

// Rota não encontrada
http_response_code(404);
echo json_encode(['error' => 'Rota não encontrada']);

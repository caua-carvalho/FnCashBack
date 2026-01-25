
<?php
// Middleware para validar JWT e fornecer informações do usuário autenticado
// Inclua este arquivo antes de rotas que precisam de autenticação
require_once __DIR__ . '/jwt_utils.php';
require_once __DIR__ . '/config/EnvLoader.php';

/**
 * Middleware que valida o JWT do header Authorization.
 * Se válido, armazena as informações do usuário em $GLOBALS['auth_user'].
 * Caso contrário, retorna erro 401 e encerra a execução.
 *
 * @param callable $next Função handler da rota protegida
 * @return mixed
 */
function jwtMiddleware($next) {
    $secret = $_ENV['JWT_SECRET'] ?? 'changeme';
    $token = getBearerToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Token não fornecido']);
        exit;
    }
    $payload = validateJWT($token, $secret);
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido ou expirado']);
        exit;
    }
    // Disponibiliza as infos do usuário globalmente para uso nos controllers
    $GLOBALS['auth_user'] = $payload;
    // Executa o próximo handler (controller/action)
    return $next();
}

/**
 * Recupera o usuário autenticado do contexto global
 * @return array|null
 */
function getAuthenticatedUser() {
    return $GLOBALS['auth_user'] ?? null;
}

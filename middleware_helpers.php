
<?php
// Helper para aplicar o middleware JWT em rotas protegidas
require_once __DIR__ . '/jwt_middleware.php';

/**
 * Envolve um handler de rota com o middleware de autenticação JWT.
 * Use em rotas que exigem usuário autenticado.
 *
 * Exemplo:
 * $router->get('/rota', withAuth([Controller::class, 'metodo']));
 *
 * @param callable|array $handler
 * @return callable
 */
function withAuth($handler) {
    return function() use ($handler) {
        return jwtMiddleware($handler);
    };
}

<?php
// Helper para aplicar o middleware JWT em rotas protegidas
require_once APP_ROOT . '/jwt_middleware.php';

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
function withAuth(array $handler)
{
    return function () use ($handler) {
        return jwtMiddleware(function () use ($handler) {

            [$controllerClass, $method] = $handler;

            $controller = new $controllerClass();

            return $controller->$method();
        });
    };
}


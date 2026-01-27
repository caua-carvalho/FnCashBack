<?php
/**
 * Classe Router
 * Responsável por registrar rotas e despachar requisições para os handlers corretos.
 * Suporta rotas GET e POST, handlers como controllers ou funções anônimas.
 */
class Router
{
    // Armazena as rotas registradas por método HTTP
    private array $routes = [];

    /**
     * Registra uma rota GET
     * @param string $path Caminho da rota
     * @param callable|array $handler Handler (controller ou função)
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Registra uma rota POST
     * @param string $path Caminho da rota
     * @param callable|array $handler Handler (controller ou função)
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Adiciona uma rota ao array de rotas
     * @param string $method Método HTTP
     * @param string $path Caminho da rota
     * @param callable|array $handler Handler
     */
    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }

    /**
     * Despacha a requisição para o handler correto
     * Resolve o controller e método, ou executa função anônima
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/');

        if ($uri === '') {
            $uri = '/';
        }

        $handler = $this->routes[$method][$uri] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo json_encode([
                'error' => 'Rota não encontrada',
                'method' => $method,
                'uri' => $uri
            ]);
            return;
        }

        if (is_array($handler)) {
            [$controller, $methodName] = $handler;
            (new $controller())->$methodName();
            return;
        }

        call_user_func($handler);
    }

}

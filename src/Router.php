<?php

declare(strict_types=1);

class Router
{
    private array $routes = [];
    private string $basePath;

    public function __construct()
    {
        // Router NÃO carrega env, apenas consome
        $basePath = $_ENV['APP_BASE_PATH'] ?? '';
        $this->basePath = rtrim($basePath, '/');
    }

    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $path = '/' . trim($path, '/');
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Remove basePath se existir
        if ($this->basePath !== '' && str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath));
        }

        // Normaliza URI
        $uri = '/' . trim($uri, '/');
        if ($uri === '//') {
            $uri = '/';
        }

        $handler = $this->routes[$method][$uri] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo json_encode([
                'error'    => 'Rota não encontrada',
                'method'   => $method,
                'uri'      => $uri,
                'basePath' => $this->basePath
            ]);
            return;
        }

        if (is_array($handler)) {
            [$controller, $methodName] = $handler;

            if (!class_exists($controller) || !method_exists($controller, $methodName)) {
                http_response_code(500);
                echo json_encode(['error' => 'Handler inválido']);
                return;
            }

            (new $controller())->$methodName();
            return;
        }

        call_user_func($handler);
    }
}

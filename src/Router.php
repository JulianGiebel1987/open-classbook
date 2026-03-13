<?php

namespace OpenClassbook;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, array $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchRoute($route['path'], $uri);
            if ($params === false) {
                continue;
            }

            // Middleware ausfuehren
            foreach ($route['middleware'] as $middlewareClass) {
                $middleware = new $middlewareClass();
                $result = $middleware->handle();
                if ($result === false) {
                    return;
                }
            }

            // Controller aufrufen
            [$controllerClass, $action] = $route['handler'];
            $controller = new $controllerClass();
            $controller->$action(...$params);
            return;
        }

        // 404 - Route nicht gefunden
        http_response_code(404);
        View::render('errors/404');
    }

    private function matchRoute(string $routePath, string $uri): array|false
    {
        // Einfache Parameter wie {id} ersetzen
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return false;
    }
}

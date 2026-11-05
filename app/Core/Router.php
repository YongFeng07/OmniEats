<?php

namespace App\Core;

/**
 * Minimal front-controller router.
 *
 * Routes are declared in routes/web.php:
 *     $router->get('/path/{id}', 'App\Controllers\X@method', ['auth', 'roles:admin']);
 *
 * The third argument is an optional list of route guards handled by
 * App\Middleware\AuthMiddleware (RBAC) before the controller runs.
 */
class Router
{
    /** @var array<string, array<string, array{handler: string, middleware: string[]}>> */
    private array $routes = [];

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, string $handler, array $middleware): void
    {
        $this->routes[$method][$path] = ['handler' => $handler, 'middleware' => $middleware];
    }

    /**
     * Match the request against the registered routes and run the
     * handler, or send a 404 page when nothing matches.
     */
    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri = $this->normalise($uri);

        foreach ($this->routes[$method] ?? [] as $pattern => $route) {
            $params = $this->match($pattern, $uri);
            if ($params === null) {
                continue;
            }

            foreach ($route['middleware'] as $guard) {
                \App\Middleware\AuthMiddleware::apply($guard);
            }

            [$class, $action] = explode('@', $route['handler'], 2);
            (new $class())->{$action}(...$params);
            return;
        }

        abort(404);
    }

    /**
     * Normalise a URI: strip the query string, the directory the front
     * controller lives in (handles http://localhost/omnieats/public/...
     * under Apache/XAMPP), and any trailing slash.
     */
    private function normalise(string $uri): string
    {
        $uri = parse_url($uri, PHP_URL_PATH) ?? '/';

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        if (strlen($uri) > 1) {
            $uri = rtrim($uri, '/');
        }

        return $uri === '' ? '/' : $uri;
    }

    /**
     * Match a route pattern like /orders/{id} against a URI.
     * Returns the captured parameters, or null when there is no match.
     */
    private function match(string $pattern, string $uri): ?array
    {
        if ($pattern === $uri) {
            return [];
        }

        if (!str_contains($pattern, '{')) {
            return null;
        }

        $regex = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $pattern);
        if (preg_match('#^' . $regex . '$#', $uri, $matches)) {
            array_shift($matches);
            return array_values($matches);
        }

        return null;
    }
}

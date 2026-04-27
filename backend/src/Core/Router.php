<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function patch(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, callable|array $handler, array $middleware): void
    {
        $this->routes[$method][] = compact('path', 'handler', 'middleware');
    }

    public function dispatch(Request $request): array
    {
        $routes = $this->routes[$request->method] ?? [];

        foreach ($routes as $route) {
            $params = $this->match($route['path'], $request->path);

            if ($params === null) {
                continue;
            }

            $request->attributes = [...$request->attributes, ...$params];

            foreach ($route['middleware'] as $middleware) {
                $middleware->handle($request);
            }

            $body = call_user_func($route['handler'], $request);

            return [
                'status' => $body['status'] ?? 200,
                'body' => $body,
            ];
        }

        throw new RuntimeException('Route not found.', 404);
    }

    private function match(string $routePath, string $requestPath): ?array
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (!preg_match($pattern, $requestPath, $matches)) {
            return null;
        }

        return array_filter(
            $matches,
            static fn ($key): bool => !is_int($key),
            ARRAY_FILTER_USE_KEY
        );
    }
}

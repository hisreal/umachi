<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\AdminController;
use App\Controllers\AttendanceController;

class Router
{
    /** @var array<string, array<string, callable|array{0: class-string, 1: string}>> */
    private array $routes = [];

    private mixed $fallback = null;

    public function __construct(private Request $request, private Response $response)
    {
    }

    public function get(string|array $routes, callable|array $handler): void
    {
        foreach ((array) $routes as $route) {
            $this->routes['GET'][trim((string) $route, '/')] = $handler;
        }
    }

    public function fallback(callable|array $handler): void
    {
        $this->fallback = $handler;
    }

    public function dispatch(): void
    {
        $route = $this->request->route();
        $method = $this->request->method();
        $handler = $this->routes[$method][$route] ?? null;

        if ($handler === null && str_starts_with($route, 'admin/')) {
            (new AdminController())->placeholderPage($route);
            return;
        }

        if ($handler === null) {
            $this->response->setStatusCode(404);
            $handler = $this->fallback ?? [AttendanceController::class, 'notFound'];
        }

        $this->call($handler);
    }

    private function call(callable|array $handler): void
    {
        if (is_array($handler) && is_string($handler[0])) {
            $controller = new $handler[0]();
            $method = $handler[1];
            $controller->{$method}();
            return;
        }

        $handler();
    }
}

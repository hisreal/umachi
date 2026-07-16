<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\AdminController;
use App\Controllers\AttendanceController;
use App\Middleware\MiddlewareInterface;

class Router
{
    /** @var array<string, array<string, callable|array{0: class-string, 1: string}>> */
    private array $routes = [];

    /** @var MiddlewareInterface[] */
    private array $middleware = [];

    private mixed $fallback = null;

    public function __construct(private Request $request, private Response $response)
    {
    }

    public function get(string|array $routes, callable|array $handler): void
    {
        $this->add('GET', $routes, $handler);
    }

    public function post(string|array $routes, callable|array $handler): void
    {
        $this->add('POST', $routes, $handler);
    }

    public function middleware(MiddlewareInterface $middleware): void
    {
        $this->middleware[] = $middleware;
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

        if ($handler === null && $method === 'GET' && str_starts_with($route, 'admin/')) {
            $handler = static function () use ($route): void {
                (new AdminController())->placeholderPage($route);
            };
        }

        if ($handler === null) {
            $this->response->setStatusCode(404);
            $handler = $this->fallback ?? [AttendanceController::class, 'notFound'];
        }

        $this->runMiddlewareStack(function () use ($handler): void {
            $this->call($handler);
        });
    }

    private function add(string $method, string|array $routes, callable|array $handler): void
    {
        foreach ((array) $routes as $route) {
            $this->routes[$method][trim((string) $route, '/')] = $handler;
        }
    }

    private function runMiddlewareStack(callable $destination): void
    {
        $next = array_reduce(
            array_reverse($this->middleware),
            fn (callable $next, MiddlewareInterface $middleware): callable => function () use ($middleware, $next): void {
                $middleware->handle($this->request, $this->response, static function () use ($next): void {
                    $next();
                });
            },
            $destination
        );

        $next();
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


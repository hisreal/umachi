<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

class AuthMiddleware implements MiddlewareInterface
{
    private AuthService $auth;

    public function __construct(?AuthService $auth = null)
    {
        $this->auth = $auth ?? new AuthService();
    }

    public function handle(Request $request, Response $response, callable $next): void
    {
        if (!$this->auth->check()) {
            $response->redirect(route_url('auth/login'));
        }

        $next($request, $response);
    }
}

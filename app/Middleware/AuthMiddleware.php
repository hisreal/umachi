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
        // Authentication checks will be enabled when login persistence is implemented.
        $next($request, $response);
    }
}

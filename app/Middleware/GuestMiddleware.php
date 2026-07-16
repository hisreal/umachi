<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

class GuestMiddleware implements MiddlewareInterface
{
    private AuthService $auth;
    private Config $config;

    public function __construct(?AuthService $auth = null, ?Config $config = null)
    {
        $this->auth = $auth ?? new AuthService();
        $this->config = $config ?? new Config(CONFIG_PATH);
    }

    public function handle(Request $request, Response $response, callable $next): void
    {
        if ($this->auth->check()) {
            $response->redirect(route_url((string) $this->config->get('auth.default_redirect', 'dashboard')));
        }

        $next($request, $response);
    }
}

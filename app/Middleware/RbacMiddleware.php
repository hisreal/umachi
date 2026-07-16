<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Database;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\RbacService;

class RbacMiddleware implements MiddlewareInterface
{
    private AuthService $auth;
    private RbacService $rbac;

    public function __construct(?AuthService $auth = null, ?RbacService $rbac = null)
    {
        $this->auth = $auth ?? new AuthService();
        $this->rbac = $rbac ?? new RbacService();
    }

    public function handle(Request $request, Response $response, callable $next): void
    {
        $route = $request->route();

        if ($this->rbac->isPublicRoute($route)) {
            $next($request, $response);
            return;
        }

        if (!$this->auth->check()) {
            Session::flash('auth_error', 'Please sign in to continue.');
            $response->redirect(route_url('auth/login'));
        }

        $roles = $this->auth->roles();
        $normalizedRoles = array_map(static fn (string $role): string => strtolower(trim($role)), $roles);
        $isAccountant = in_array('accountant', $normalizedRoles, true);
        $profileRoutes = $isAccountant
            ? ['admin/edit-profile', 'admin/change-password', 'auth/logout', 'logout']
            : ['profile/complete', 'auth/logout', 'logout'];
        if (!$this->auth->profileCompleted() && !in_array($route, $profileRoutes, true)) {
            $response->redirect(route_url($isAccountant ? 'admin/edit-profile' : 'profile/complete'));
        }

        $passwordRoutes = ['admin/change-password', 'profile/change-password', 'change-password', 'profile/complete', 'auth/logout', 'logout'];
        if ($this->auth->mustChangePassword() && !in_array($route, $passwordRoutes, true)) {
            $response->redirect(route_url($this->auth->passwordChangeRoute()));
        }

        $selfServiceRoutes = [
            'dashboard',
            'profile',
            'profile/edit',
            'profile/complete',
            'profile/change-password',
            'change-password',
            'settings',
            'announcements',
        ];

        if (!$isAccountant && in_array($route, $selfServiceRoutes, true)) {
            $next($request, $response);
            return;
        }

        if (!$this->rbac->canAccess($route, $roles)) {
            $this->logDeniedAccess($request, $roles);
            $response->setStatusCode(403);
            echo '403 Forbidden - You do not have permission to access this page.';
            return;
        }

        $next($request, $response);
    }

    private function logDeniedAccess(Request $request, array $roles): void
    {
        try {
            Database::getInstance()->insert('activity_logs', [
                'log_code' => 'ACT-' . date('YmdHis') . '-' . random_int(100, 999),
                'user_id' => Session::get('auth.user_id') !== null ? (int) Session::get('auth.user_id') : null,
                'employee_id' => ($employeeId = (int) Session::get('auth.employee_id', 0)) > 0 ? $employeeId : null,
                'activity_type' => 'Unauthorized Access Attempt',
                'module' => 'RBAC',
                'activity' => 'Access Denied: ' . $request->route(),
                'entity_type' => 'route',
                'entity_id' => null,
                'old_value' => null,
                'new_value' => json_encode([
                    'route' => $request->route(),
                    'roles' => array_values(array_map('strval', $roles)),
                ], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'browser' => $this->browserFromUserAgent($request->userAgent()),
                'status' => 'warning',
                'notes' => 'Server-side RBAC denied this request.',
            ]);
        } catch (\Throwable $exception) {
            error_log('[RBAC Audit] ' . $exception->getMessage());
        }
    }

    private function browserFromUserAgent(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Edg') => 'Edge',
            str_contains($userAgent, 'Firefox') => 'Firefox',
            str_contains($userAgent, 'Chrome') => 'Chrome',
            str_contains($userAgent, 'Safari') => 'Safari',
            default => 'Unknown',
        };
    }
}



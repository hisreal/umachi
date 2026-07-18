<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\RbacService;
use App\Services\ActivityLogService;

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
        $activeRole = strtolower(trim((string) Session::get('auth.role', '')));
        $isAdminOnlyRole = in_array($activeRole, ['manager', 'supervisor', 'accountant'], true);

        // Admin-only roles are authorized using the role selected at login. This
        // prevents a multi-role account from inheriting attendant-module access.
        $authorizationRoles = $isAdminOnlyRole
            ? [ucfirst($activeRole)]
            : $roles;

        $profileRoutes = $isAdminOnlyRole
            ? ['admin/edit-profile', 'admin/change-password', 'auth/logout', 'logout']
            : ['profile/complete', 'auth/logout', 'logout'];
        if (!$this->auth->profileCompleted() && !in_array($route, $profileRoutes, true)) {
            $response->redirect(route_url($isAdminOnlyRole ? 'admin/edit-profile' : 'profile/complete'));
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
        if ($route === 'admin/activity-log' && !in_array($activeRole, ['admin', 'administrator'], true)) {
            $this->logDeniedAccess($request, $authorizationRoles);
            $response->setStatusCode(403);
            echo '403 Forbidden - You do not have permission to access this page.';
            return;
        }


        if (!$isAdminOnlyRole && in_array($route, $selfServiceRoutes, true)) {
            $next($request, $response);
            return;
        }

        if (!$this->rbac->canAccess($route, $authorizationRoles)) {
            $this->logDeniedAccess($request, $authorizationRoles);
            $response->setStatusCode(403);
            echo '403 Forbidden - You do not have permission to access this page.';
            return;
        }

        $next($request, $response);
    }

    private function logDeniedAccess(Request $request, array $roles): void
    {
        (new ActivityLogService())->record(
            'Permission Denied',
            'Security',
            'Unauthorized page access was blocked for route: ' . $request->route(),
            ['entity_type' => 'route', 'notes' => 'Server-side RBAC denied this request.'],
            'warning',
            null,
            ['route' => $request->route(), 'roles' => array_values(array_map('strval', $roles))],
            $request
        );
    }
}



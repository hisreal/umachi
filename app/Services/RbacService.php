<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

class RbacService
{
    private static ?Config $sharedConfig = null;
    private Config $config;
    /** @var array<string, string[]> */
    private array $rolePermissionCache = [];

    public function __construct(?Config $config = null)
    {
        if ($config !== null) {
            $this->config = $config;
            return;
        }
        $this->config = self::$sharedConfig ??= new Config(CONFIG_PATH);
    }

    public function isPublicRoute(string $route): bool
    {
        return $this->matchesAny($this->normalizeRoute($route), $this->config->get('rbac.public_routes', []));
    }

    public function canAccess(string $route, array $roles): bool
    {
        $route = $this->normalizeRoute($route);

        foreach ($this->normalizeRoles($roles) as $role) {
            $patterns = $this->permissionsForRole($role);

            if (is_array($patterns) && $this->matchesAny($route, $patterns)) {
                return true;
            }
        }

        return false;
    }

    public function defaultRouteFor(array $roles): string
    {
        $redirects = $this->config->get('rbac.role_redirects', []);

        foreach ($this->normalizeRoles($roles) as $role) {
            if (is_array($redirects) && isset($redirects[$role])) {
                return (string) $redirects[$role];
            }
        }

        return 'dashboard';
    }

    /** @return string[] */
    private function permissionsForRole(string $role): array
    {
        if (isset($this->rolePermissionCache[$role])) {
            return $this->rolePermissionCache[$role];
        }
        $patterns = $this->config->get('rbac.permissions.' . $role, []);
        if (!is_array($patterns)) {
            $patterns = [];
        }

        return $this->rolePermissionCache[$role] = array_values(array_map('strval', $patterns));
    }

    private function matchesAny(string $route, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($this->matches($route, (string) $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function matches(string $route, string $pattern): bool
    {
        $pattern = $this->normalizeRoute($pattern);

        if ($pattern === '*') {
            return true;
        }

        if ($pattern === $route) {
            return true;
        }

        $regex = '#^' . str_replace('\\*', '.*', preg_quote($pattern, '#')) . '$#';

        return preg_match($regex, $route) === 1;
    }

    private function normalizeRoute(string $route): string
    {
        $route = trim($route, '/');

        return $route === '' ? 'dashboard' : $route;
    }

    private function normalizeRoles(array $roles): array
    {
        return array_values(array_unique(array_filter(array_map(static fn (mixed $role): string => trim((string) $role), $roles))));
    }
}

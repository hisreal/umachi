<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;

final class AdminNavigationService
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private static array $filteredCache = [];

    public function __construct(private ?RbacService $rbac = null)
    {
        $this->rbac ??= new RbacService();
    }

    /** @param array<int, array<string, mixed>> $items */
    public function forCurrentUser(array $items): array
    {
        $roles = Session::get('auth.roles', []);
        $roles = is_array($roles) ? array_map('strval', $roles) : [];
        $displayRole = trim((string) Session::get('auth.role', ''));
        if ($displayRole !== '' && !in_array($displayRole, $roles, true)) {
            $roles[] = $displayRole;
        }

        return $this->forRoles($items, $roles);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param string[] $roles
     * @return array<int, array<string, mixed>>
     */
    public function forRoles(array $items, array $roles): array
    {
        $roles = array_values(array_unique(array_filter(array_map('trim', $roles))));
        sort($roles);
        $cacheKey = hash('sha256', serialize([$roles, $items]));
        if (isset(self::$filteredCache[$cacheKey])) {
            return self::$filteredCache[$cacheKey];
        }

        $filtered = [];
        foreach ($items as $item) {
            $authorized = $this->filterItem($item, $roles);
            if ($authorized !== null) {
                $filtered[] = $authorized;
            }
        }

        return self::$filteredCache[$cacheKey] = $filtered;
    }

    /**
     * @param array<string, mixed> $item
     * @param string[] $roles
     * @return array<string, mixed>|null
     */
    private function filterItem(array $item, array $roles): ?array
    {
        $children = is_array($item['children'] ?? null) ? $item['children'] : [];
        if ($children !== []) {
            $authorizedChildren = [];
            foreach ($children as $child) {
                if (!is_array($child)) {
                    continue;
                }
                $authorized = $this->filterItem($child, $roles);
                if ($authorized !== null) {
                    $authorizedChildren[] = $authorized;
                }
            }
            if ($authorizedChildren === []) {
                return null;
            }
            $item['children'] = $authorizedChildren;
            $item['active_routes'] = $this->authorizedRoutes((array) ($item['active_routes'] ?? []), $roles);

            return $item;
        }

        $route = trim((string) ($item['route'] ?? ''), '/');
        if ($route === '' || (!$this->rbac->isPublicRoute($route) && !$this->rbac->canAccess($route, $roles))) {
            return null;
        }
        $item['active_routes'] = $this->authorizedRoutes((array) ($item['active_routes'] ?? []), $roles);

        return $item;
    }

    /** @param mixed[] $routes @param string[] $roles @return string[] */
    private function authorizedRoutes(array $routes, array $roles): array
    {
        return array_values(array_filter(array_map('strval', $routes), function (string $route) use ($roles): bool {
            return $this->rbac->isPublicRoute($route) || $this->rbac->canAccess($route, $roles);
        }));
    }
}

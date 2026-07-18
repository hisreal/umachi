<?php declare(strict_types=1);

$currentRoute = trim((string) ($currentRoute ?? 'attendance/clock-in'), '/');
$attendantName = $attendantName ?? ($employee['name'] ?? 'Station Staff');
$attendantRole = $attendantRole ?? ($employee['role'] ?? 'Pump Attendant');
$sidebarVariant = trim((string) ($sidebarVariant ?? ''));
$sidebarHomeRoute = trim((string) ($sidebarHomeRoute ?? 'dashboard'), '/');
$sidebarBrandTitle = $sidebarBrandTitle ?? 'FuelOps';
$sidebarBrandSubtitle = $sidebarBrandSubtitle ?? 'Staff Panel';
if (!isset($navItems)) {
    $dashboardMenuLabel = \App\Services\DashboardLabelService::forCurrentUser();
    $roles = \App\Core\Session::get('auth.roles', []);
    $roles = is_array($roles) ? array_map('strval', $roles) : [];
    $roles[] = (string) \App\Core\Session::get('auth.role', '');
    $dutyPolicy = new \App\Services\AttendanceDutyPolicyService();
    $isPumpAttendant = array_filter($roles, [$dutyPolicy, 'requiresManualDuty']) !== [];
    $navItems = [
    ['label' => $dashboardMenuLabel, 'route' => 'dashboard', 'icon' => 'fa-solid fa-gauge-high'],
    ['label' => 'Clock In', 'route' => 'attendance/clock-in', 'icon' => 'fa-solid fa-fingerprint'],
    ['label' => 'Clock Out', 'route' => 'attendance/clock-out', 'icon' => 'fa-solid fa-arrow-right-from-bracket'],
    ...($isPumpAttendant ? [['label' => 'Fuel Sales History', 'route' => 'fuel-sales/history', 'icon' => 'fa-solid fa-gas-pump']] : []),
    ['label' => 'Attendance History', 'route' => 'attendance/history', 'icon' => 'fa-solid fa-clock-rotate-left'],
    ...($isPumpAttendant ? [['label' => 'Duty Roster', 'route' => 'duty-roster', 'icon' => 'fa-solid fa-calendar-days']] : []),
    ['label' => 'Leave Requests', 'route' => 'leave-requests', 'icon' => 'fa-solid fa-person-walking-arrow-right'],
    ['label' => 'Profile', 'route' => 'profile', 'icon' => 'fa-solid fa-user-gear'],
        ['label' => 'Change Password', 'route' => 'profile/change-password', 'icon' => 'fa-solid fa-key'],
        ['label' => 'Announcements', 'route' => 'announcements', 'icon' => 'fa-solid fa-bullhorn'],
        ['label' => 'Logout', 'route' => 'auth/logout', 'icon' => 'fa-solid fa-right-from-bracket', 'logout' => true],
    ];
}
$hasGroupedNavItems = array_reduce(
    $navItems,
    static fn (bool $hasChildren, array $item): bool => $hasChildren || !empty($item['children']),
    false
);
?>
<aside class="attendant-sidebar <?php echo e($sidebarVariant); ?>" id="attendantSidebar" aria-label="Staff navigation">
    <div class="attendant-sidebar__brand">
        <a href="<?php echo e(route_url($sidebarHomeRoute)); ?>" aria-label="Dashboard home">
            <span class="attendant-brand-mark"><i class="fa-solid fa-gas-pump"></i></span>
            <span>
                <strong><?php echo e($sidebarBrandTitle); ?></strong>
                <small><?php echo e($sidebarBrandSubtitle); ?></small>
            </span>
        </a>

        <label class="attendant-sidebar__close" for="attendantSidebarControl" role="button" tabindex="0" aria-label="Close navigation">
            <i class="fa-solid fa-xmark"></i>
        </label>
    </div>

    <div class="attendant-sidebar__profile">
        <div>
            <strong><?php echo e($attendantName); ?></strong>
            <span><?php echo e($attendantRole); ?></span>
        </div>
    </div>

    <nav class="attendant-sidebar__nav <?php echo $hasGroupedNavItems ? 'attendant-sidebar__nav--tree' : ''; ?>" aria-label="Main menu">
        <ul>
            <?php foreach ($navItems as $index => $item): ?>
                <?php
                $route = trim((string) ($item['route'] ?? ''), '/');
                $children = $item['children'] ?? [];
                $activeRoutes = array_map(
                    static fn (string $activeRoute): string => trim($activeRoute, '/'),
                    $item['active_routes'] ?? []
                );
                $childRoutes = [];
                foreach ($children as $child) {
                    $childRoute = trim((string) ($child['route'] ?? ''), '/');
                    if ($childRoute !== '') {
                        $childRoutes[] = $childRoute;
                    }
                    foreach (($child['active_routes'] ?? []) as $childActiveRoute) {
                        $childRoutes[] = trim((string) $childActiveRoute, '/');
                    }
                }
                $isActive = ($route !== '' && $route === $currentRoute)
                    || in_array($currentRoute, $activeRoutes, true)
                    || in_array($currentRoute, $childRoutes, true);
                $href = $route === '' || $route === '#' ? '#' : route_url($route);
                $submenuId = 'attendantSidebarSubmenu' . $index;
                ?>
                <li>
                    <?php if (!empty($children)): ?>
                        <button
                            class="attendant-sidebar__toggle <?php echo $isActive ? 'active' : ''; ?>"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#<?php echo e($submenuId); ?>"
                            aria-expanded="<?php echo $isActive ? 'true' : 'false'; ?>"
                            aria-controls="<?php echo e($submenuId); ?>"
                        >
                            <i class="<?php echo e($item['icon'] ?? 'fa-solid fa-circle'); ?>"></i>
                            <span><?php echo e($item['label'] ?? 'Menu'); ?></span>
                            <i class="fa-solid fa-chevron-down attendant-sidebar__chevron"></i>
                        </button>
                        <ul class="attendant-sidebar__submenu collapse <?php echo $isActive ? 'show' : ''; ?>" id="<?php echo e($submenuId); ?>">
                            <?php foreach ($children as $child): ?>
                                <?php
                                $childRoute = trim((string) ($child['route'] ?? ''), '/');
                                $childHref = $childRoute === '' || $childRoute === '#' ? '#' : route_url($childRoute);
                                $childActiveRoutes = array_map(
                                    static fn (string $activeRoute): string => trim($activeRoute, '/'),
                                    $child['active_routes'] ?? []
                                );
                                $isChildActive = ($childRoute !== '' && $childRoute === $currentRoute)
                                    || in_array($currentRoute, $childActiveRoutes, true);
                                ?>
                                <li>
                                    <a href="<?php echo e($childHref); ?>" class="<?php echo $isChildActive ? 'active' : ''; ?>">
                                        <i class="<?php echo e($child['icon'] ?? 'fa-solid fa-circle'); ?>"></i>
                                        <span><?php echo e($child['label'] ?? 'Menu item'); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <a
                            href="<?php echo e($href); ?>"
                            class="<?php echo $isActive ? 'active' : ''; ?>"
                            <?php echo !empty($item['logout']) ? 'data-admin-logout="true"' : ''; ?>
                        >
                            <i class="<?php echo e($item['icon'] ?? 'fa-solid fa-circle'); ?>"></i>
                            <span><?php echo e($item['label'] ?? 'Menu'); ?></span>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>

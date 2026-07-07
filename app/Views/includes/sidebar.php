<?php declare(strict_types=1);

$currentRoute = $currentRoute ?? 'attendance/clock-in';
$attendantName = $attendantName ?? ($employee['name'] ?? 'Station Staff');
$attendantRole = $attendantRole ?? ($employee['role'] ?? 'Pump Attendant');
$navItems = $navItems ?? [
    ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-solid fa-gauge-high'],
    ['label' => 'Clock In', 'route' => 'attendance/clock-in', 'icon' => 'fa-solid fa-fingerprint'],
    ['label' => 'Clock Out', 'route' => 'attendance/clock-out', 'icon' => 'fa-solid fa-arrow-right-from-bracket'],
     ['label' => 'Fuel Sales History', 'route' => 'fuel-sales/history', 'icon' => 'fa-solid fa-gas-pump'],
    ['label' => 'Attendance History', 'route' => 'attendance/history', 'icon' => 'fa-solid fa-clock-rotate-left'],
 ['label' => 'Duty Roster', 'route' => 'duty-roster', 'icon' => 'fa-solid fa-calendar-days'],
    // ['label' => 'Leave Requests', 'route' => 'leave-requests', 'icon' => 'fa-solid fa-person-walking-arrow-right'],
    // ['label' => 'Profile', 'route' => 'profile', 'icon' => 'fa-solid fa-user-gear'],
    ['label' => 'logout', 'route' => 'auth/login', 'icon' => 'fa-solid fa-right-from-bracket'],
];
?>
<aside class="attendant-sidebar" id="attendantSidebar" aria-label="Staff navigation">
    <div class="attendant-sidebar__brand">
        <a href="<?php echo e(route_url('dashboard')); ?>" aria-label="Staff dashboard home">
            <span class="attendant-brand-mark"><i class="fa-solid fa-gas-pump"></i></span>
            <span>
                <strong>FuelOps</strong>
                <small>Staff Panel</small>
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

    <nav class="attendant-sidebar__nav" aria-label="Main menu">
        <ul>
            <?php foreach ($navItems as $item): ?>
                <?php
                $route = trim((string) $item['route'], '/');
                $href = $route === '#' ? '#' : route_url($route);
                $isActive = $route === trim($currentRoute, '/');
                ?>
                <li>
                    <a href="<?php echo e($href); ?>" class="<?php echo $isActive ? 'active' : ''; ?>">
                        <i class="<?php echo e($item['icon']); ?>"></i>
                        <span><?php echo e($item['label']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>

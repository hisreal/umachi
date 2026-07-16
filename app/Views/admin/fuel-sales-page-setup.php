<?php

declare(strict_types=1);

$isSupervisorFuelRoute = str_starts_with((string) ($currentRoute ?? ''), 'supervisor/');
$isManagerFuelRoute = str_starts_with((string) ($currentRoute ?? ''), 'manager/');

$topbarSubtitle = $isManagerFuelRoute ? 'Manager Dashboard' : ($isSupervisorFuelRoute ? 'Supervisor Dashboard' : 'Admin Dashboard');
$extraStyles = ['css/clock-in.css', 'css/admin-dashboard.css', 'css/fuel-sales-management.css'];
$extraScripts = ['https://cdn.jsdelivr.net/npm/chart.js', 'js/admin-dashboard.js', 'js/fuel-sales-management.js'];
$sidebarVariant = 'admin-sidebar';
$sidebarHomeRoute = ($isManagerFuelRoute || $isSupervisorFuelRoute) ? 'dashboard' : 'admin/dashboard';
$sidebarBrandTitle = 'FuelOps';
$sidebarBrandSubtitle = $isManagerFuelRoute ? 'Manager Panel' : ($isSupervisorFuelRoute ? 'Supervisor Panel' : 'Admin Panel');
$navItems = require __DIR__ . '/../includes/admin-nav.php';
$adminUser = [
    'name' => $isManagerFuelRoute ? 'Manager' : ($isSupervisorFuelRoute ? 'Supervisor' : 'Administrator'),
    'role' => $topbarSubtitle,
];
$employee = ['name' => $adminUser['name'], 'role' => $adminUser['role']];
$attendantName = $adminUser['name'];
$attendantRole = $adminUser['role'];

require __DIR__ . '/fuel-sales-data.php';

<?php

declare(strict_types=1);

$topbarSubtitle = 'Admin Dashboard';
$extraStyles = ['css/clock-in.css', 'css/admin-dashboard.css', 'css/fuel-sales-management.css'];
$extraScripts = ['https://cdn.jsdelivr.net/npm/chart.js', 'js/admin-dashboard.js', 'js/fuel-sales-management.js'];
$sidebarVariant = 'admin-sidebar';
$sidebarHomeRoute = 'admin/dashboard';
$sidebarBrandTitle = 'FuelOps';
$sidebarBrandSubtitle = 'Admin Panel';
$navItems = require __DIR__ . '/../includes/admin-nav.php';
$adminUser = ['name' => 'Administrator', 'role' => 'System Administrator'];
$employee = ['name' => $adminUser['name'], 'role' => $adminUser['role']];
$attendantName = $adminUser['name'];
$attendantRole = $adminUser['role'];

require __DIR__ . '/fuel-sales-data.php';

<?php

declare(strict_types=1);

$pageTitle = 'Attendance Dashboard | FuelOps Admin Dashboard';
$pageHeading = 'Attendance Dashboard';
$topbarSubtitle = 'Admin Dashboard';
$currentRoute = 'admin/attendance-dashboard';
$extraStyles = ['css/clock-in.css', 'css/admin-dashboard.css', 'css/attendance-management.css'];
$extraScripts = ['js/admin-dashboard.js', 'js/attendance-management.js'];
$sidebarVariant = 'admin-sidebar';
$sidebarHomeRoute = 'admin/dashboard';
$sidebarBrandTitle = 'FuelOps';
$sidebarBrandSubtitle = 'Admin Panel';
$navItems = require __DIR__ . '/../includes/admin-nav.php';
$adminUser = ['name' => 'Administrator', 'role' => 'System Administrator'];
$employee = ['name' => $adminUser['name'], 'role' => $adminUser['role']];
$attendantName = $adminUser['name'];
$attendantRole = $adminUser['role'];
require __DIR__ . '/attendance-data.php';

$summaryCards = [
    ['label' => 'Total Employees', 'value' => $attendanceSummary['total_employees'] . ' Employees', 'icon' => 'fa-solid fa-users', 'tone' => 'primary'],
    ['label' => 'Present Today', 'value' => $attendanceSummary['present_today'] . ' Present', 'icon' => 'fa-solid fa-user-check', 'tone' => 'success'],
    ['label' => 'Absent Today', 'value' => $attendanceSummary['absent_today'] . ' Absent', 'icon' => 'fa-solid fa-user-xmark', 'tone' => 'danger'],
    ['label' => 'Late Today', 'value' => $attendanceSummary['late_today'] . ' Late', 'icon' => 'fa-solid fa-clock', 'tone' => 'warning'],
    ['label' => 'Employees on Leave', 'value' => $attendanceSummary['on_leave'] . ' Employee', 'icon' => 'fa-solid fa-calendar-days', 'tone' => 'info'],
    ['label' => 'Attendance Rate', 'value' => $attendanceSummary['attendance_rate'], 'icon' => 'fa-solid fa-chart-line', 'tone' => 'purple'],
];

$quickActions = [
    ['title' => 'View Attendance History', 'route' => 'admin/attendance-history', 'icon' => 'fa-solid fa-clock-rotate-left'],
    ['title' => 'Attendance Settings', 'route' => 'admin/attendance-settings', 'icon' => 'fa-solid fa-sliders'],
    ['title' => 'Export Attendance', 'route' => '#', 'icon' => 'fa-solid fa-download'],
    ['title' => 'Attendance Reports', 'route' => 'admin/reports-attendance', 'icon' => 'fa-solid fa-chart-bar'],
];
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page attendance-module-page" data-attendance-chart-data="<?php echo e(json_encode($attendanceChartData, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>">
    <section class="clock-hero attendance-hero"><div class="container-fluid"><nav class="attendance-breadcrumb" aria-label="Breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Attendance</span><i class="fa-solid fa-chevron-right"></i><span>Dashboard</span></nav><div class="clock-hero__content attendance-hero-card"><div><span class="eyebrow">Attendance Overview</span><h1>Attendance Dashboard</h1><p>Monitor today's staff attendance, trends, and recent clock activities using sample operational data.</p></div><span class="attendance-hero-icon"><i class="fa-solid fa-calendar-check"></i></span></div></div></section>
    <section class="container-fluid clock-workspace">
        <div class="attendance-summary-grid"><?php foreach ($summaryCards as $card): ?><article class="attendance-summary-card attendance-summary-card--<?php echo e($card['tone']); ?>"><span><i class="<?php echo e($card['icon']); ?>"></i></span><div><small><?php echo e($card['label']); ?></small><strong><?php echo e($card['value']); ?></strong></div></article><?php endforeach; ?></div>
        <div class="row g-4 mt-1"><div class="col-12 col-xl-4"><article class="app-card card attendance-chart-card"><div class="app-card__header"><div><span class="eyebrow">Trend</span><h2>Monthly Attendance Trend</h2></div></div><canvas id="attendanceMonthlyChart" height="230"></canvas></article></div><div class="col-12 col-xl-4"><article class="app-card card attendance-chart-card"><div class="app-card__header"><div><span class="eyebrow">This Week</span><h2>Daily Attendance</h2></div></div><canvas id="attendanceDailyChart" height="230"></canvas></article></div><div class="col-12 col-xl-4"><article class="app-card card attendance-chart-card"><div class="app-card__header"><div><span class="eyebrow">Status</span><h2>Attendance Status Distribution</h2></div></div><canvas id="attendanceStatusChart" height="230"></canvas></article></div></div>
        <section class="attendance-section mt-4"><div class="attendance-section-heading"><div><span class="eyebrow">Fast Access</span><h2>Quick Actions</h2></div></div><div class="row g-4"><?php foreach ($quickActions as $action): ?><div class="col-12 col-md-6 col-xl-3"><a class="attendance-action-card app-card card" href="<?php echo e($action['route'] === '#' ? '#' : route_url($action['route'])); ?>" <?php echo $action['route'] === '#' ? 'data-attendance-export="true"' : ''; ?>><span><i class="<?php echo e($action['icon']); ?>"></i></span><strong><?php echo e($action['title']); ?></strong><i class="fa-solid fa-arrow-right"></i></a></div><?php endforeach; ?></div></section>
        <div class="row g-4 mt-1 align-items-start"><div class="col-12 col-xl-8"><article class="app-card card attendance-table-card"><div class="app-card__header"><div><span class="eyebrow">Today</span><h2>Today's Attendance</h2></div></div><div class="table-responsive"><table class="table attendance-table admin-attendance-table align-middle"><thead><tr><th>Employee ID</th><th>Employee Name</th><th>Department</th><th>Role</th><th>Clock In</th><th>Clock Out</th><th>Shift</th><th>Status</th></tr></thead><tbody><?php foreach (array_slice($attendanceRecords, 0, 5) as $record): ?><tr><td><strong><?php echo e($record['employee_id']); ?></strong></td><td><?php echo e($record['name']); ?></td><td><?php echo e($record['department']); ?></td><td><?php echo e($record['role']); ?></td><td><?php echo e($record['clock_in']); ?></td><td><?php echo e($record['clock_out']); ?></td><td><?php echo e($record['shift']); ?></td><td><span class="table-badge <?php echo e($attendanceStatusClasses[$record['status']]); ?>"><?php echo e($record['status']); ?></span></td></tr><?php endforeach; ?></tbody></table></div></article></div><div class="col-12 col-xl-4"><article class="app-card card attendance-activity-card"><div class="app-card__header"><div><span class="eyebrow">Live Feed</span><h2>Recent Attendance Activity</h2></div></div><div class="attendance-activity-list"><?php foreach ($recentActivities as $activity): ?><div><span><i class="<?php echo e($activity['icon']); ?>"></i></span><div><p><?php echo e($activity['message']); ?></p><small><?php echo e($activity['time']); ?></small></div></div><?php endforeach; ?></div></article></div></div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

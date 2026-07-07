<?php

declare(strict_types=1);

$pageTitle = 'Admin Dashboard | FuelOps Management System';
$pageHeading = 'Dashboard';
$topbarSubtitle = 'Admin Dashboard';
$currentRoute = $currentRoute ?? 'admin/dashboard';
$extraStyles = ['css/clock-in.css', 'css/admin-dashboard.css'];
$extraScripts = ['js/admin-dashboard.js'];

// =========================================
// DATABASE PLACEHOLDER
// Replace this sample administrator record
// with authenticated user data from MySQL.
// =========================================
$adminUser = [
    'name' => 'Administrator',
    'role' => 'System Administrator',
];

$employee = [
    'name' => $adminUser['name'],
    'role' => $adminUser['role'],
];
$attendantName = $adminUser['name'];
$attendantRole = $adminUser['role'];

$sidebarVariant = 'admin-sidebar';
$sidebarHomeRoute = 'admin/dashboard';
$sidebarBrandTitle = 'FuelOps';
$sidebarBrandSubtitle = 'Admin Panel';
$navItems = $navItems ?? require __DIR__ . '/../includes/admin-nav.php';

// =========================================
// DATABASE PLACEHOLDER
// Load dashboard statistics from MySQL
// =========================================
$dashboard = [
    'employees' => 16,
    'present' => 14,
    'absent' => 2,
    'leave' => 1,
    'sales' => 4850000,
    'liters' => 8750,
    'pending_leave' => 4,
    'active_pumps' => 4,
    'total_pumps' => 4,
    'current_shift' => 'Morning Shift',
    'announcements' => 3,
];

$kpiCards = [
    ['label' => 'Total Employees', 'value' => $dashboard['employees'] . ' Employees', 'icon' => 'fa-solid fa-users', 'tone' => 'primary'],
    ['label' => 'Employees Present Today', 'value' => $dashboard['present'] . ' Present', 'icon' => 'fa-solid fa-user-check', 'tone' => 'success'],
    ['label' => 'Employees Absent', 'value' => $dashboard['absent'] . ' Absent', 'icon' => 'fa-solid fa-user-xmark', 'tone' => 'danger'],
    ['label' => 'Employees on Leave', 'value' => $dashboard['leave'] . ' Employee', 'icon' => 'fa-solid fa-calendar-days', 'tone' => 'warning'],
    ['label' => 'Today\'s Fuel Sales', 'value' => 'NGN ' . number_format($dashboard['sales']), 'icon' => 'fa-solid fa-money-bill-trend-up', 'tone' => 'info'],
    ['label' => 'Total Liters Sold Today', 'value' => number_format($dashboard['liters']) . ' Liters', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'secondary'],
    ['label' => 'Pending Leave Requests', 'value' => $dashboard['pending_leave'] . ' Requests', 'icon' => 'fa-solid fa-clipboard-list', 'tone' => 'orange'],
    ['label' => 'Active Pumps', 'value' => $dashboard['active_pumps'] . ' / ' . $dashboard['total_pumps'] . ' Active', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'success'],
    ['label' => 'Current Shift', 'value' => $dashboard['current_shift'], 'icon' => 'fa-solid fa-clock', 'tone' => 'dark'],
    ['label' => 'System Announcements', 'value' => $dashboard['announcements'] . ' New Announcements', 'icon' => 'fa-solid fa-bullhorn', 'tone' => 'purple'],
];

// =========================================
// DATABASE PLACEHOLDER
// Retrieve attendance, fuel sales, leave
// requests, announcements, and notifications.
// =========================================
$attendanceRecords = [
    ['employee' => 'John Doe', 'role' => 'Pump Attendant', 'clock_in' => '06:02 AM', 'shift' => 'Morning', 'status' => 'Present'],
    ['employee' => 'Mary Johnson', 'role' => 'Cashier', 'clock_in' => '06:08 AM', 'shift' => 'Morning', 'status' => 'Present'],
    ['employee' => 'Daniel James', 'role' => 'Pump Attendant', 'clock_in' => '06:14 AM', 'shift' => 'Morning', 'status' => 'Late'],
    ['employee' => 'Esther Grace', 'role' => 'Supervisor', 'clock_in' => '05:54 AM', 'shift' => 'Morning', 'status' => 'Present'],
];

$fuelSales = [
    ['pump' => 'Pump 1', 'fuel_type' => 'Petrol (PMS)', 'attendant' => 'John Doe', 'liters' => 2250, 'amount' => 1250000, 'shift' => 'Morning'],
    ['pump' => 'Pump 2', 'fuel_type' => 'Diesel (AGO)', 'attendant' => 'Mary Johnson', 'liters' => 1800, 'amount' => 1180000, 'shift' => 'Morning'],
    ['pump' => 'Pump 3', 'fuel_type' => 'Petrol (PMS)', 'attendant' => 'Daniel James', 'liters' => 2950, 'amount' => 1640000, 'shift' => 'Morning'],
    ['pump' => 'Pump 4', 'fuel_type' => 'Gas (LPG)', 'attendant' => 'Esther Grace', 'liters' => 1750, 'amount' => 780000, 'shift' => 'Morning'],
];

$leaveRequests = [
    ['employee' => 'Aisha Bello', 'type' => 'Annual Leave', 'duration' => '5 Days', 'date_applied' => '2026-07-05', 'status' => 'Pending'],
    ['employee' => 'Samuel Peters', 'type' => 'Sick Leave', 'duration' => '2 Days', 'date_applied' => '2026-07-06', 'status' => 'Pending'],
    ['employee' => 'Ngozi Williams', 'type' => 'Emergency Leave', 'duration' => '1 Day', 'date_applied' => '2026-07-06', 'status' => 'Pending'],
];

$announcements = [
    ['title' => 'Safety Reminder', 'message' => 'Always verify opening and closing meter readings before submitting fuel sales.', 'date' => '2026-07-07', 'icon' => 'fa-solid fa-shield-halved'],
    ['title' => 'Staff Meeting', 'message' => 'Monthly operations meeting on Monday at 9:00 AM.', 'date' => '2026-07-08', 'icon' => 'fa-solid fa-users'],
    ['title' => 'System Update', 'message' => 'Attendance verification has been improved for faster daily check-ins.', 'date' => '2026-07-09', 'icon' => 'fa-solid fa-screwdriver-wrench'],
];

$notifications = [
    ['message' => 'New leave request submitted.', 'time' => '10 mins ago', 'unread' => true],
    ['message' => 'Duty roster updated.', 'time' => '28 mins ago', 'unread' => true],
    ['message' => 'Fuel sales verified.', 'time' => '1 hour ago', 'unread' => false],
    ['message' => 'New employee added.', 'time' => '2 hours ago', 'unread' => false],
];

$quickActions = [
    ['title' => 'Add Employee', 'description' => 'Register a new staff member for station operations.', 'route' => 'admin/add-employee', 'icon' => 'fa-solid fa-user-plus'],
    ['title' => 'Assign Duty', 'description' => 'Create shift and pump assignments for attendants.', 'route' => 'supervisor/manage-duty-roster', 'icon' => 'fa-solid fa-calendar-check'],
    ['title' => 'Approve Leave', 'description' => 'Review and process pending staff leave requests.', 'route' => 'admin/leave-requests', 'icon' => 'fa-solid fa-clipboard-check'],
    ['title' => 'View Reports', 'description' => 'Open operational reports and performance summaries.', 'route' => 'admin/reports', 'icon' => 'fa-solid fa-chart-bar'],
    ['title' => 'Manage Pumps', 'description' => 'Review pump activity, allocation, and fuel lanes.', 'route' => 'admin/pumps', 'icon' => 'fa-solid fa-gas-pump'],
    ['title' => 'Employee List', 'description' => 'View employee records and staff directory details.', 'route' => 'admin/employees', 'icon' => 'fa-solid fa-users'],
];

$chartData = [
    'attendance' => [
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        'values' => [95, 92, 96, 94, 97, 95],
    ],
    'sales' => [
        'labels' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
        'values' => [3850000, 4120000, 4380000, 4590000, 4850000, 5100000, 3970000],
    ],
    'leave' => [
        'labels' => ['Annual Leave', 'Sick Leave', 'Casual Leave', 'Emergency Leave', 'Study Leave'],
        'values' => [35, 20, 18, 17, 10],
    ],
];

$statusClasses = [
    'Present' => 'admin-status--success',
    'Late' => 'admin-status--warning',
    'Pending' => 'admin-status--warning',
];

require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page admin-dashboard-page" data-admin-chart-data="<?php echo e(json_encode($chartData, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>">
    <section class="clock-hero admin-dashboard-hero">
        <div class="container-fluid">
            <div class="clock-hero__content admin-hero-card">
                <div>
                    <span class="eyebrow">Enterprise Overview</span>
                    <h1><?php echo e($pageHeading); ?></h1>
                    <p>Welcome back, <?php echo e($adminUser['name']); ?>. Here's an overview of today's filling station operations.</p>
                    <div class="admin-live-time" aria-live="polite">
                        <i class="fa-solid fa-calendar-days"></i>
                        <span id="adminLiveDateTime">Loading current date and time...</span>
                    </div>
                </div>
                <span class="employee-avatar" aria-hidden="true">
                    <i class="fa-solid fa-gauge-high"></i>
                </span>
            </div>
        </div>
    </section>

    <section class="container-fluid clock-workspace">
        <div class="admin-kpi-grid">
            <?php foreach ($kpiCards as $card): ?>
                <article class="admin-kpi-card admin-kpi-card--<?php echo e($card['tone']); ?>">
                    <span class="admin-kpi-icon"><i class="<?php echo e($card['icon']); ?>"></i></span>
                    <div>
                        <span><?php echo e($card['label']); ?></span>
                        <strong><?php echo e($card['value']); ?></strong>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-12 col-xl-4">
                <article class="app-card card admin-chart-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Attendance</span>
                            <h2>Monthly Attendance</h2>
                        </div>
                        <span class="admin-section-icon"><i class="fa-solid fa-chart-line"></i></span>
                    </div>
                    <canvas id="attendanceChart" height="230" aria-label="Monthly attendance line chart"></canvas>
                </article>
            </div>
            <div class="col-12 col-xl-4">
                <article class="app-card card admin-chart-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Sales</span>
                            <h2>Fuel Sales Trend</h2>
                        </div>
                        <span class="admin-section-icon"><i class="fa-solid fa-chart-column"></i></span>
                    </div>
                    <canvas id="salesChart" height="230" aria-label="Fuel sales bar chart"></canvas>
                </article>
            </div>
            <div class="col-12 col-xl-4">
                <article class="app-card card admin-chart-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Leave</span>
                            <h2>Leave Statistics</h2>
                        </div>
                        <span class="admin-section-icon"><i class="fa-solid fa-chart-pie"></i></span>
                    </div>
                    <canvas id="leaveChart" height="230" aria-label="Leave statistics doughnut chart"></canvas>
                </article>
            </div>
        </div>

        <section class="admin-section mt-4" aria-labelledby="quickActionsTitle">
            <div class="admin-section-header">
                <div>
                    <span class="eyebrow">Fast Access</span>
                    <h2 id="quickActionsTitle">Quick Actions</h2>
                </div>
            </div>
            <div class="row g-4">
                <?php foreach ($quickActions as $action): ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <a class="admin-action-card app-card card" href="<?php echo e(route_url($action['route'])); ?>">
                            <span class="admin-action-icon"><i class="<?php echo e($action['icon']); ?>"></i></span>
                            <div>
                                <h3><?php echo e($action['title']); ?></h3>
                                <p><?php echo e($action['description']); ?></p>
                            </div>
                            <span class="admin-action-link">Open <i class="fa-solid fa-arrow-right"></i></span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="row g-4 mt-1 align-items-start">
            <div class="col-12 col-xl-8">
                <article class="app-card card admin-table-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Today</span>
                            <h2>Today's Attendance</h2>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table attendance-table admin-table align-middle">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Role</th>
                                    <th>Clock In</th>
                                    <th>Shift</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendanceRecords as $record): ?>
                                    <tr>
                                        <td><strong><?php echo e($record['employee']); ?></strong></td>
                                        <td><?php echo e($record['role']); ?></td>
                                        <td><?php echo e($record['clock_in']); ?></td>
                                        <td><?php echo e($record['shift']); ?></td>
                                        <td><span class="table-badge <?php echo e($statusClasses[$record['status']] ?? 'admin-status--success'); ?>"><?php echo e($record['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="app-card card admin-table-card mt-4">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Sales</span>
                            <h2>Recent Fuel Sales</h2>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table attendance-table admin-table align-middle">
                            <thead>
                                <tr>
                                    <th>Pump</th>
                                    <th>Fuel Type</th>
                                    <th>Attendant</th>
                                    <th>Liters Sold</th>
                                    <th>Amount</th>
                                    <th>Shift</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fuelSales as $sale): ?>
                                    <tr>
                                        <td><strong><?php echo e($sale['pump']); ?></strong></td>
                                        <td><?php echo e($sale['fuel_type']); ?></td>
                                        <td><?php echo e($sale['attendant']); ?></td>
                                        <td><?php echo e(number_format($sale['liters'])); ?> L</td>
                                        <td>NGN <?php echo e(number_format($sale['amount'])); ?></td>
                                        <td><?php echo e($sale['shift']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>

            <div class="col-12 col-xl-4">
                <article class="app-card card admin-notification-panel">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Alerts</span>
                            <h2>Notifications</h2>
                        </div>
                        <button class="btn btn-outline-brand btn-sm" type="button" id="markNotificationsRead">Mark read</button>
                    </div>
                    <div class="admin-notification-list">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="admin-notification-item <?php echo $notification['unread'] ? 'is-unread' : ''; ?>">
                                <span class="admin-notification-dot"></span>
                                <div>
                                    <p><?php echo e($notification['message']); ?></p>
                                    <small><?php echo e($notification['time']); ?></small>
                                </div>
                                <?php if ($notification['unread']): ?>
                                    <span class="badge text-bg-danger">Unread</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="app-card card admin-announcement-panel mt-4">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Company Notices</span>
                            <h2>System Announcements</h2>
                        </div>
                    </div>
                    <div class="admin-announcement-list">
                        <?php foreach ($announcements as $announcement): ?>
                            <article class="admin-announcement-card">
                                <span><i class="<?php echo e($announcement['icon']); ?>"></i></span>
                                <div>
                                    <h3><?php echo e($announcement['title']); ?></h3>
                                    <p><?php echo e($announcement['message']); ?></p>
                                    <time datetime="<?php echo e($announcement['date']); ?>"><?php echo e(date('d M Y', strtotime($announcement['date']))); ?></time>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>
            </div>
        </div>

        <article class="app-card card admin-table-card mt-4">
            <div class="app-card__header">
                <div>
                    <span class="eyebrow">Approvals</span>
                    <h2>Pending Leave Requests</h2>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table attendance-table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>Duration</th>
                            <th>Date Applied</th>
                            <th>Approval Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaveRequests as $request): ?>
                            <tr>
                                <td><strong><?php echo e($request['employee']); ?></strong></td>
                                <td><?php echo e($request['type']); ?></td>
                                <td><?php echo e($request['duration']); ?></td>
                                <td><?php echo e(date('d M Y', strtotime($request['date_applied']))); ?></td>
                                <td><span class="table-badge admin-status--warning"><?php echo e($request['status']); ?></span></td>
                                <td>
                                    <div class="admin-table-actions">
                                        <button class="btn btn-sm btn-success" type="button" data-leave-action="approve" data-employee="<?php echo e($request['employee']); ?>">Approve</button>
                                        <button class="btn btn-sm btn-outline-danger" type="button" data-leave-action="reject" data-employee="<?php echo e($request['employee']); ?>">Reject</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

<?php

declare(strict_types=1);

use App\Models\Announcement;

$dashboardLabel = \App\Services\DashboardLabelService::forCurrentUser();
$pageTitle = $dashboardLabel . ' | FuelOps Management System';
$pageHeading = $dashboardLabel;
$topbarSubtitle = $dashboardLabel;
$currentRoute = $currentRoute ?? 'admin/dashboard';
$extraStyles = ['css/clock-in.css', 'css/admin-dashboard.css'];
$extraScripts = ['js/admin-dashboard.js'];

$adminUser = $adminUser ?? ['name' => 'Administrator', 'role' => 'Administrator'];
$employee = ['name' => $adminUser['name'], 'role' => $adminUser['role']];
$attendantName = $adminUser['name'];
$attendantRole = $adminUser['role'];

$sidebarVariant = 'admin-sidebar';
$sidebarHomeRoute = 'admin/dashboard';
$sidebarBrandTitle = 'FuelOps';
$sidebarBrandSubtitle = 'Admin Panel';
$navItems = $navItems ?? require __DIR__ . '/../includes/admin-nav.php';

$dashboard = $dashboard ?? [];
$dashboardSections = $dashboardSections ?? [
    'employees' => true,
    'attendance' => true,
    'sales' => true,
    'inventory' => true,
    'pumps' => true,
    'duty' => true,
    'leave' => true,
    'announcements' => true,
    'notifications' => true,
    'reports' => true,
];
$kpiCards = [
    ['label' => 'Total Employees', 'value' => number_format((int) ($dashboard['employees'] ?? 0)), 'icon' => 'fa-solid fa-users', 'tone' => 'primary'],
    ['label' => 'Active Employees', 'value' => number_format((int) ($dashboard['active_employees'] ?? 0)), 'icon' => 'fa-solid fa-user-check', 'tone' => 'success'],
    ['label' => 'Employees Present Today', 'value' => number_format((int) ($dashboard['present'] ?? 0)), 'icon' => 'fa-solid fa-user-clock', 'tone' => 'success'],
    ['label' => 'Employees Absent Today', 'value' => number_format((int) ($dashboard['absent'] ?? 0)), 'icon' => 'fa-solid fa-user-xmark', 'tone' => 'danger'],
    ['label' => 'Employees Currently on Leave', 'value' => number_format((int) ($dashboard['leave'] ?? 0)), 'icon' => 'fa-solid fa-calendar-days', 'tone' => 'warning'],
    ['label' => "Today's Fuel Sales", 'value' => 'NGN ' . number_format((float) ($dashboard['sales'] ?? 0), 2), 'icon' => 'fa-solid fa-money-bill-trend-up', 'tone' => 'info'],
    ['label' => "Today's Litres Sold", 'value' => number_format((float) ($dashboard['litres'] ?? 0), 2) . ' L', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'secondary'],
    ['label' => 'Available Petrol', 'value' => number_format((float) ($dashboard['petrol'] ?? 0), 2) . ' L', 'icon' => 'fa-solid fa-droplet', 'tone' => 'primary'],
    ['label' => 'Available Diesel', 'value' => number_format((float) ($dashboard['diesel'] ?? 0), 2) . ' L', 'icon' => 'fa-solid fa-oil-can', 'tone' => 'warning'],
    ['label' => 'Available Gas', 'value' => number_format((float) ($dashboard['gas'] ?? 0), 2) . ' L', 'icon' => 'fa-solid fa-fire-flame-simple', 'tone' => 'info'],
    ['label' => 'Active Pumps', 'value' => (int) ($dashboard['active_pumps'] ?? 0) . ' / ' . (int) ($dashboard['total_pumps'] ?? 0), 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'success'],
    ['label' => 'Pumps Under Maintenance', 'value' => number_format((int) ($dashboard['maintenance_pumps'] ?? 0)), 'icon' => 'fa-solid fa-screwdriver-wrench', 'tone' => 'danger'],
    ['label' => 'Pending Leave Requests', 'value' => number_format((int) ($dashboard['pending_leave'] ?? 0)), 'icon' => 'fa-solid fa-clipboard-list', 'tone' => 'orange'],
    ['label' => 'Pending Sales Verification', 'value' => number_format((int) ($dashboard['pending_sales'] ?? 0)), 'icon' => 'fa-solid fa-circle-check', 'tone' => 'warning'],
    ['label' => "Today's Duty Assignments", 'value' => number_format((int) ($dashboard['duty_assignments'] ?? 0)), 'icon' => 'fa-solid fa-calendar-check', 'tone' => 'purple'],
    ['label' => 'Current Shift', 'value' => (string) ($dashboard['current_shift'] ?? 'No active shift'), 'icon' => 'fa-solid fa-clock', 'tone' => 'dark'],
];

$kpiSections = [
    'Total Employees' => 'employees',
    'Active Employees' => 'employees',
    'Employees Present Today' => 'attendance',
    'Employees Absent Today' => 'attendance',
    'Employees Currently on Leave' => 'leave',
    "Today's Fuel Sales" => 'sales',
    "Today's Litres Sold" => 'sales',
    'Available Petrol' => 'inventory',
    'Available Diesel' => 'inventory',
    'Available Gas' => 'inventory',
    'Active Pumps' => 'pumps',
    'Pumps Under Maintenance' => 'pumps',
    'Pending Leave Requests' => 'leave',
    'Pending Sales Verification' => 'sales',
    "Today's Duty Assignments" => 'duty',
    'Current Shift' => 'duty',
];
$kpiCards = array_values(array_filter(
    $kpiCards,
    static fn (array $card): bool => !empty($dashboardSections[$kpiSections[$card['label']] ?? ''])
));
$attendanceRecords = $attendanceRecords ?? [];
$fuelSales = $fuelSales ?? [];
$leaveRequests = $leaveRequests ?? [];
$announcements = $announcements ?? [];
$notifications = $notifications ?? [];
$chartData = $chartData ?? [
    'attendance' => ['labels' => [], 'values' => []],
    'sales' => ['labels' => [], 'values' => [], 'litres' => []],
    'leave' => ['labels' => [], 'values' => []],
];

$quickActions = [
    ['title' => 'Add Employee', 'description' => 'Register a new staff member for station operations.', 'route' => 'admin/add-employee', 'icon' => 'fa-solid fa-user-plus'],
    ['title' => 'Assign Duty', 'description' => 'Create shift and pump assignments for attendants.', 'route' => 'admin/duty-roster', 'icon' => 'fa-solid fa-calendar-check'],
    ['title' => 'Verify Fuel Sales', 'description' => 'Review pending attendant sales submissions.', 'route' => 'admin/verify-sales', 'icon' => 'fa-solid fa-receipt'],
    ['title' => 'Approve Leave', 'description' => 'Review and process pending staff leave requests.', 'route' => 'admin/leave-requests', 'icon' => 'fa-solid fa-clipboard-check'],
    ['title' => 'Record Fuel Delivery', 'description' => 'Update station fuel inventory deliveries.', 'route' => 'admin/fuel-inventory', 'icon' => 'fa-solid fa-truck-droplet'],
    ['title' => 'View Reports', 'description' => 'Open fuel sales reports and performance summaries.', 'route' => 'admin/fuel-sales-report', 'icon' => 'fa-solid fa-chart-bar'],
];
$dashboardQuickActionRoutes = is_array($dashboardQuickActionRoutes ?? null) ? $dashboardQuickActionRoutes : array_column($quickActions, 'route');
$quickActions = array_values(array_filter(
    $quickActions,
    static fn (array $action): bool => in_array($action['route'], $dashboardQuickActionRoutes, true)
));
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

        <div class="admin-kpi-grid mt-4" aria-label="Operational summaries">
            <article class="admin-kpi-card admin-kpi-card--warning" <?php echo empty($dashboardSections['attendance']) ? 'hidden' : ''; ?>><span class="admin-kpi-icon"><i class="fa-solid fa-clock"></i></span><div><span>Late Today</span><strong><?php echo e((string) ($attendanceSummary['late'] ?? 0)); ?></strong></div></article>
            <article class="admin-kpi-card admin-kpi-card--info" <?php echo empty($dashboardSections['attendance']) ? 'hidden' : ''; ?>><span class="admin-kpi-icon"><i class="fa-solid fa-business-time"></i></span><div><span>Overtime Employees</span><strong><?php echo e((string) ($attendanceSummary['overtime'] ?? 0)); ?></strong></div></article>
            <article class="admin-kpi-card admin-kpi-card--success" <?php echo empty($dashboardSections['leave']) ? 'hidden' : ''; ?>><span class="admin-kpi-icon"><i class="fa-solid fa-check-double"></i></span><div><span>Leave Approved Today</span><strong><?php echo e((string) ($leaveSummary['approved_today'] ?? 0)); ?></strong></div></article>
            <article class="admin-kpi-card admin-kpi-card--danger" <?php echo empty($dashboardSections['inventory']) ? 'hidden' : ''; ?>><span class="admin-kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></span><div><span>Low Stock Alerts</span><strong><?php echo e((string) ($dashboard['low_stock'] ?? 0)); ?></strong></div></article>
            <article class="admin-kpi-card admin-kpi-card--primary" <?php echo empty($dashboardSections['duty']) ? 'hidden' : ''; ?>><span class="admin-kpi-icon"><i class="fa-solid fa-sun"></i></span><div><span>Morning Shift Employees</span><strong><?php echo e((string) ($dutySummary['morning'] ?? 0)); ?></strong></div></article>
            <article class="admin-kpi-card admin-kpi-card--purple" <?php echo empty($dashboardSections['duty']) ? 'hidden' : ''; ?>><span class="admin-kpi-icon"><i class="fa-solid fa-moon"></i></span><div><span>Evening Shift Employees</span><strong><?php echo e((string) ($dutySummary['evening'] ?? 0)); ?></strong></div></article>
        </div>
        <div class="row g-4 mt-1">
            <div class="col-12 col-xl-4" <?php echo empty($dashboardSections['attendance']) ? 'hidden' : ''; ?>>
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
            <div class="col-12 col-xl-4" <?php echo empty($dashboardSections['sales']) ? 'hidden' : ''; ?>>
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
            <div class="col-12 col-xl-4" <?php echo empty($dashboardSections['leave']) ? 'hidden' : ''; ?>>
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
            <div class="col-12 col-xl-4" <?php echo empty($dashboardSections['inventory']) ? 'hidden' : ''; ?>>
                <article class="app-card card admin-chart-card">
                    <div class="app-card__header"><div><span class="eyebrow">Inventory</span><h2>Fuel Inventory Trend</h2></div><span class="admin-section-icon"><i class="fa-solid fa-chart-area"></i></span></div>
                    <canvas id="inventoryChart" height="230" aria-label="Fuel inventory trend line chart"></canvas>
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
                <article class="app-card card admin-table-card" <?php echo empty($dashboardSections['attendance']) ? 'hidden' : ''; ?>>
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
                                    <th>Clock Out</th>
                                    <th>Shift</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($attendanceRecords === []): ?><tr><td colspan="6" class="text-center text-muted py-4">No attendance records found.</td></tr><?php else: ?><?php foreach ($attendanceRecords as $record): ?>
                                    <tr>
                                        <td><strong><?php echo e($record['employee']); ?></strong></td>
                                        <td><?php echo e($record['role']); ?></td>
                                        <td><?php echo e(!empty($record['clock_in']) ? date('h:i A', strtotime((string) $record['clock_in'])) : 'Not Clocked In'); ?></td>
                                        <td><?php echo e(!empty($record['clock_out']) ? date('h:i A', strtotime((string) $record['clock_out'])) : 'Not Clocked Out'); ?></td>
                                        <td><?php echo e($record['shift']); ?></td>
                                        <td><span class="table-badge <?php echo e($statusClasses[$record['status']] ?? 'admin-status--success'); ?>"><?php echo e($record['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="app-card card admin-table-card mt-4" <?php echo empty($dashboardSections['sales']) ? 'hidden' : ''; ?>>
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
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($fuelSales === []): ?><tr><td colspan="7" class="text-center text-muted py-4">No verified fuel sales found.</td></tr><?php else: ?><?php foreach ($fuelSales as $sale): ?>
                                    <tr>
                                        <td><strong><?php echo e($sale['pump']); ?></strong></td>
                                        <td><?php echo e($sale['fuel_type']); ?></td>
                                        <td><?php echo e($sale['attendant']); ?></td>
                                        <td><?php echo e(number_format((float) $sale['litres'], 2)); ?> L</td>
                                        <td>NGN <?php echo e(number_format((float) ($sale['amount'] ?? 0), 2)); ?></td>
                                        <td><?php echo e($sale['shift']); ?></td><td><?php echo e(!empty($sale['submitted_at']) ? date('h:i A', strtotime((string) $sale['submitted_at'])) : 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>

            <div class="col-12 col-xl-4">
                <article class="app-card card admin-notification-panel" <?php echo empty($dashboardSections['notifications']) ? 'hidden' : ''; ?>>
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
                                    <h3><a href="<?php echo e(route_url('admin/announcement-details') . '&id=' . urlencode((string) $announcement['id'])); ?>"><?php echo e($announcement['title']); ?></a></h3><span class="table-badge"><?php echo e((string) ($announcement['priority'] ?? 'Normal')); ?></span>
                                    <p><?php echo e($announcement['message']); ?></p>
                                    <time datetime="<?php echo e($announcement['date']); ?>"><?php echo e(format_date($announcement['date'])); ?></time>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>
            </div>
        </div>

        <article class="app-card card admin-table-card mt-4" <?php echo empty($dashboardSections['leave']) ? 'hidden' : ''; ?>>
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
                        <?php if ($leaveRequests === []): ?><tr><td colspan="6" class="text-center text-muted py-4">No pending leave requests found.</td></tr><?php else: ?><?php foreach ($leaveRequests as $request): ?>
                            <tr>
                                <td><strong><?php echo e($request['employee']); ?></strong></td>
                                <td><?php echo e($request['type']); ?></td>
                                <td><?php echo e($request['duration']); ?></td>
                                <td><?php echo e(format_date($request['date_applied'])); ?></td>
                                <td><span class="table-badge admin-status--warning"><?php echo e($request['status']); ?></span></td>
                                <td>
                                    <div class="admin-table-actions"><a class="btn btn-sm btn-primary" href="<?php echo e(route_url('admin/leave-requests')); ?>">Review Request</a></div>
                                </td>
                            </tr>
                        <?php endforeach; ?><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>




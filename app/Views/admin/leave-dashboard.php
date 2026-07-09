<?php

declare(strict_types=1);

$pageTitle = 'Leave Management | FuelOps Admin Dashboard';
$pageHeading = 'Leave Management';
$currentRoute = 'admin/leave-dashboard';
$extraScripts = ['js/admin-dashboard.js', 'https://cdn.jsdelivr.net/npm/chart.js', 'js/leave-management.js'];
require __DIR__ . '/leave-management-setup.php';
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page leave-module-page">
    <section class="clock-hero leave-hero"><div class="container-fluid"><nav class="leave-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Leave Management</span></nav><div class="clock-hero__content leave-hero-card"><div><span class="eyebrow">Leave Administration</span><h1>Leave Management</h1><p>Monitor employee leave requests, approvals, history, and policy configuration.</p></div><span class="leave-hero-icon"><i class="fa-solid fa-person-walking-arrow-right"></i></span></div></div></section>
    <section class="container-fluid clock-workspace">
        <div class="leave-summary-grid"><?php foreach ($leaveStats as $card): ?><article class="leave-summary-card leave-summary-card--<?php echo e($card['tone']); ?>"><span><i class="<?php echo e($card['icon']); ?>"></i></span><div><small><?php echo e($card['label']); ?></small><strong><?php echo e($card['value']); ?></strong></div></article><?php endforeach; ?></div>
        <div class="row g-4 mt-1">
            <div class="col-xl-6"><article class="app-card card leave-chart-card"><div class="leave-card-heading"><span class="eyebrow">Monthly Trend</span><h2>Monthly Leave Requests</h2></div><canvas id="monthlyLeaveChart" height="210"></canvas></article></div>
            <div class="col-xl-3 col-md-6"><article class="app-card card leave-chart-card"><div class="leave-card-heading"><span class="eyebrow">Types</span><h2>Leave Type Distribution</h2></div><canvas id="leaveTypeChart" height="235"></canvas></article></div>
            <div class="col-xl-3 col-md-6"><article class="app-card card leave-chart-card"><div class="leave-card-heading"><span class="eyebrow">Approvals</span><h2>Approval Status</h2></div><canvas id="approvalStatusChart" height="235"></canvas></article></div>
        </div>
        <div class="row g-4 mt-1">
            <div class="col-xl-8"><article class="app-card card leave-table-card"><div class="leave-toolbar"><div><span class="eyebrow">Recent Activity</span><h2>Recent Leave Requests</h2></div><a class="btn btn-primary" href="<?php echo e(route_url('admin/leave-requests')); ?>"><i class="fa-solid fa-eye"></i>Review Requests</a></div><div class="table-responsive"><table class="table attendance-table leave-table align-middle"><thead><tr><th>Employee</th><th>Department</th><th>Leave Type</th><th>Start Date</th><th>End Date</th><th>Duration</th><th>Status</th></tr></thead><tbody><?php foreach (array_slice($leaveRequests, 0, 5) as $request): ?><tr><td><strong><?php echo e($request['employee']); ?></strong><small><?php echo e($request['employee_id']); ?></small></td><td><?php echo e($request['department']); ?></td><td><?php echo e($request['type']); ?></td><td><?php echo e(date('d M Y', strtotime($request['start']))); ?></td><td><?php echo e(date('d M Y', strtotime($request['end']))); ?></td><td><?php echo e((string) $request['days']); ?> days</td><td><span class="table-badge <?php echo e($leaveStatusClasses[$request['status']]); ?>"><?php echo e($request['status']); ?></span></td></tr><?php endforeach; ?></tbody></table></div></article></div>
            <div class="col-xl-4"><div class="leave-action-grid"><a class="app-card card leave-action-card" href="<?php echo e(route_url('admin/leave-requests')); ?>"><span><i class="fa-solid fa-envelope-open-text"></i></span><div><h3>Review Leave Requests</h3><p>Approve, reject, forward, or inspect pending leave records.</p></div><i class="fa-solid fa-arrow-right"></i></a><a class="app-card card leave-action-card" href="<?php echo e(route_url('admin/leave-history')); ?>"><span><i class="fa-solid fa-folder-open"></i></span><div><h3>Leave History</h3><p>Search historical leave records and export summaries.</p></div><i class="fa-solid fa-arrow-right"></i></a><a class="app-card card leave-action-card" href="<?php echo e(route_url('admin/leave-types')); ?>"><span><i class="fa-solid fa-list-check"></i></span><div><h3>Leave Types</h3><p>Manage annual, sick, casual, emergency, and other leave categories.</p></div><i class="fa-solid fa-arrow-right"></i></a><a class="app-card card leave-action-card" href="<?php echo e(route_url('admin/leave-approval-settings')); ?>"><span><i class="fa-solid fa-user-shield"></i></span><div><h3>Approval Settings</h3><p>Configure the active leave approval workflow.</p></div><i class="fa-solid fa-arrow-right"></i></a></div></div>
        </div>
    </section>
</main>
<script>
window.leaveChartData = <?php echo json_encode(['monthly' => $monthlyLeaveRequests, 'types' => $leaveTypeDistribution, 'statuses' => $approvalStatusDistribution, 'typeLabels' => $leaveTypeNames], JSON_THROW_ON_ERROR); ?>;
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
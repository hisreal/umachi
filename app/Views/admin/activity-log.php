<?php

declare(strict_types=1);

$pageTitle = 'Activity Log | FuelOps Admin Dashboard';
$pageHeading = 'Activity Log';
$currentRoute = 'admin/activity-log';
$extraScripts = ['js/admin-dashboard.js', 'js/activity-log.js'];
$activityLogs = $activityLogs ?? [];
$activityPage = $activityPage ?? ['total' => 0, 'page' => 1, 'pages' => 1, 'from' => 0, 'to' => 0];
$activityStats = $activityStats ?? [];
$activityOptions = $activityOptions ?? ['employees' => [], 'roles' => [], 'modules' => [], 'actions' => [], 'statuses' => []];
$activityFilters = $activityFilters ?? [];
$activityStatusClasses = [
    'Success' => 'table-badge--success',
    'Failed' => 'table-badge--danger',
    'Warning' => 'table-badge--warning',
    'Information' => 'table-badge--info',
];
$queryForPage = static function (int $page) use ($activityFilters): string {
    $query = $activityFilters;
    $query['page'] = $page;
    return route_url('admin/activity-log') . '&' . http_build_query(array_filter(
        $query,
        static fn (mixed $value): bool => $value !== '' && $value !== null
    ));
};
$optionValue = static fn (array $row): string => (string) ($row['value'] ?? '');

require __DIR__ . '/company-settings-setup.php';
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page settings-module-page">
    <section class="clock-hero settings-hero">
        <div class="container-fluid">
            <nav class="settings-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Settings</span><i class="fa-solid fa-chevron-right"></i><span>Activity Log</span></nav>
            <div class="clock-hero__content settings-hero-card">
                <div><span class="eyebrow">System Audit</span><h1>Activity Log</h1><p>Monitor important user actions, changes, logins, approvals, and system events.</p></div>
                <span class="settings-hero-icon"><i class="fa-solid fa-clipboard-list"></i></span>
            </div>
        </div>
    </section>
    <section class="container-fluid clock-workspace">
        <?php if (!empty($activityLogError)): ?><div class="alert alert-danger"><?php echo e($activityLogError); ?></div><?php endif; ?>
        <div class="settings-summary-grid">
            <?php foreach ($activityStats as $card): ?>
                <article class="settings-summary-card settings-summary-card--<?php echo e($card['tone']); ?>"><span><i class="<?php echo e($card['icon']); ?>"></i></span><div><small><?php echo e($card['label']); ?></small><strong><?php echo e($card['value']); ?></strong></div></article>
            <?php endforeach; ?>
        </div>
        <article class="app-card card settings-table-card mt-4">
            <div class="settings-toolbar">
                <div><span class="eyebrow">Audit Monitor</span><h2>System Activity Records</h2></div>
            </div>
            <form class="settings-filter-grid" method="get" action="<?php echo e(route_url('admin/activity-log')); ?>">
                <input type="hidden" name="route" value="admin/activity-log">
                <div class="filter-control filter-control--wide"><i class="fa-solid fa-magnifying-glass"></i><input id="activitySearch" name="search" value="<?php echo e((string) ($activityFilters['search'] ?? '')); ?>" type="search" placeholder="Search employee, module, action, description, or IP address"></div>
                <input class="form-control" name="date_from" value="<?php echo e((string) ($activityFilters['date_from'] ?? '')); ?>" type="date" aria-label="From date">
                <input class="form-control" name="date_to" value="<?php echo e((string) ($activityFilters['date_to'] ?? '')); ?>" type="date" aria-label="To date">
                <select class="form-select" name="employee" aria-label="Employee"><option value="">All employees</option><?php foreach ($activityOptions['employees'] as $row): $value = $optionValue($row); ?><option value="<?php echo e($value); ?>" <?php echo ($activityFilters['employee'] ?? '') === $value ? 'selected' : ''; ?>><?php echo e($value); ?></option><?php endforeach; ?></select>
                <select class="form-select" name="role" aria-label="Role"><option value="">All roles</option><?php foreach ($activityOptions['roles'] as $row): $value = $optionValue($row); ?><option value="<?php echo e($value); ?>" <?php echo ($activityFilters['role'] ?? '') === $value ? 'selected' : ''; ?>><?php echo e($value); ?></option><?php endforeach; ?></select>
                <select class="form-select" name="module" aria-label="Module"><option value="">All modules</option><?php foreach ($activityOptions['modules'] as $row): $value = $optionValue($row); ?><option value="<?php echo e($value); ?>" <?php echo ($activityFilters['module'] ?? '') === $value ? 'selected' : ''; ?>><?php echo e($value); ?></option><?php endforeach; ?></select>
                <select class="form-select" name="action" aria-label="Action"><option value="">All actions</option><?php foreach ($activityOptions['actions'] as $row): $value = $optionValue($row); ?><option value="<?php echo e($value); ?>" <?php echo ($activityFilters['action'] ?? '') === $value ? 'selected' : ''; ?>><?php echo e($value); ?></option><?php endforeach; ?></select>
                <select class="form-select" name="status" aria-label="Status"><option value="">All statuses</option><?php foreach ($activityOptions['statuses'] as $row): $value = ucfirst(strtolower($optionValue($row))); ?><option value="<?php echo e($value); ?>" <?php echo strcasecmp((string) ($activityFilters['status'] ?? ''), $value) === 0 ? 'selected' : ''; ?>><?php echo e($value); ?></option><?php endforeach; ?></select>
                <select class="form-select" name="sort" aria-label="Sort by"><option value="date" <?php echo ($activityFilters['sort'] ?? 'date') === 'date' ? 'selected' : ''; ?>>Sort: Date</option><option value="employee" <?php echo ($activityFilters['sort'] ?? '') === 'employee' ? 'selected' : ''; ?>>Sort: Employee</option><option value="role" <?php echo ($activityFilters['sort'] ?? '') === 'role' ? 'selected' : ''; ?>>Sort: Role</option><option value="module" <?php echo ($activityFilters['sort'] ?? '') === 'module' ? 'selected' : ''; ?>>Sort: Module</option><option value="action" <?php echo ($activityFilters['sort'] ?? '') === 'action' ? 'selected' : ''; ?>>Sort: Action</option></select>
                <select class="form-select" name="direction" aria-label="Sort direction"><option value="desc" <?php echo ($activityFilters['direction'] ?? 'desc') === 'desc' ? 'selected' : ''; ?>>Newest / Z-A</option><option value="asc" <?php echo ($activityFilters['direction'] ?? '') === 'asc' ? 'selected' : ''; ?>>Oldest / A-Z</option></select>
                <button class="btn btn-brand" type="submit"><i class="fa-solid fa-filter"></i> Apply Filters</button>
                <a class="btn btn-light" href="<?php echo e(route_url('admin/activity-log')); ?>">Reset</a>
            </form>
            <div class="table-responsive">
                <table class="table attendance-table settings-table align-middle">
                    <thead><tr><th>Date & Time</th><th>Employee</th><th>Role</th><th>Module</th><th>Action</th><th>Description</th><th>IP Address</th><th>Browser</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody id="activityLogBody">
                    <?php if ($activityLogs === []): ?>
                        <tr><td colspan="10" class="text-center py-5">No activity logs found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($activityLogs as $log): ?>
                            <tr>
                                <td><?php echo e($log['datetime']); ?></td>
                                <td><strong><?php echo e($log['user']); ?></strong><small><?php echo e($log['employee_id']); ?></small></td>
                                <td><?php echo e($log['role']); ?></td>
                                <td><?php echo e($log['module']); ?></td>
                                <td><?php echo e($log['action']); ?></td>
                                <td><?php echo e($log['activity']); ?></td>
                                <td><?php echo e($log['ip']); ?></td>
                                <td><?php echo e($log['browser']); ?></td>
                                <td><span class="table-badge <?php echo e($activityStatusClasses[$log['status']] ?? 'table-badge--info'); ?>"><?php echo e($log['status']); ?></span></td>
                                <td><button class="btn btn-sm btn-light" data-activity-view="<?php echo e($log['id']); ?>" type="button" aria-label="View activity details"><i class="fa-solid fa-eye"></i></button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="settings-pagination">
                <span>Showing <?php echo e((string) $activityPage['from']); ?>-<?php echo e((string) $activityPage['to']); ?> of <?php echo e((string) $activityPage['total']); ?> activity records</span>
                <div>
                    <a class="btn btn-outline-brand btn-sm <?php echo $activityPage['page'] <= 1 ? 'disabled' : ''; ?>" href="<?php echo e($queryForPage(max(1, (int) $activityPage['page'] - 1))); ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></a>
                    <span class="px-2">Page <?php echo e((string) $activityPage['page']); ?> of <?php echo e((string) $activityPage['pages']); ?></span>
                    <a class="btn btn-outline-brand btn-sm <?php echo $activityPage['page'] >= $activityPage['pages'] ? 'disabled' : ''; ?>" href="<?php echo e($queryForPage(min((int) $activityPage['pages'], (int) $activityPage['page'] + 1))); ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></a>
                </div>
            </div>
        </article>
    </section>
</main>
<div class="modal fade" id="activityDetailsModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content settings-modal"><div class="modal-header"><h5 class="modal-title">Activity Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body" id="activityDetailsContent"></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal" type="button">Close</button></div></div></div></div>
<script>window.activityLogs = <?php echo json_encode($activityLogs, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>

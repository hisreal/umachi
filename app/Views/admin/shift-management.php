<?php

declare(strict_types=1);

$pageTitle = 'Shift Management | FuelOps Admin Dashboard';
$pageHeading = 'Shift Management';
$currentRoute = 'admin/shift-management';
require __DIR__ . '/duty-roster-setup.php';
require __DIR__ . '/../includes/header.php';

$filterUrl = app_base_url() . '/index.php';
$queryWithoutPage = $shiftFilters;
unset($queryWithoutPage['page'], $queryWithoutPage['per_page']);
$sortUrl = static function (string $sort) use ($queryWithoutPage): string {
    $query = $queryWithoutPage;
    $query['sort'] = $sort;
    $query['direction'] = ($queryWithoutPage['sort'] ?? '') === $sort && ($queryWithoutPage['direction'] ?? 'asc') === 'asc' ? 'desc' : 'asc';

    return route_url('admin/shift-management') . '&' . http_build_query(array_filter($query, static fn (mixed $value): bool => $value !== '' && $value !== null));
};
$pageUrl = static function (int $page) use ($shiftFilters): string {
    $query = $shiftFilters;
    $query['page'] = $page;
    unset($query['per_page']);

    return route_url('admin/shift-management') . '&' . http_build_query(array_filter($query, static fn (mixed $value): bool => $value !== '' && $value !== null));
};
?>
<main class="clock-in-page duty-module-page">
    <section class="clock-hero duty-hero"><div class="container-fluid"><nav class="duty-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Duty Roster</span><i class="fa-solid fa-chevron-right"></i><span>Shift Management</span></nav><div class="clock-hero__content duty-hero-card"><div><span class="eyebrow">Shift Configuration</span><h1>Shift Management</h1><p>Configure working hours and review current employee shift assignments.</p></div><?php if ($canManageShifts): ?><a class="btn btn-light" href="<?php echo e(route_url('admin/add-shift')); ?>"><i class="fa-solid fa-plus"></i>Add Shift</a><?php else: ?><span class="duty-hero-icon"><i class="fa-solid fa-business-time"></i></span><?php endif; ?></div></div></section>
    <section class="container-fluid clock-workspace">
        <?php if (!empty($shiftError)): ?><div class="alert alert-danger" role="alert"><?php echo e($shiftError); ?></div><?php endif; ?>
        <?php if (!empty($shiftSuccess)): ?><div class="alert alert-success" role="alert"><?php echo e($shiftSuccess); ?></div><?php endif; ?>
        <div class="duty-summary-grid duty-summary-grid--four"><?php foreach ($shiftStats as $card): ?><article class="duty-summary-card duty-summary-card--<?php echo e($card['tone']); ?>"><span><i class="<?php echo e($card['icon']); ?>"></i></span><div><small><?php echo e($card['label']); ?></small><strong><?php echo e($card['value']); ?></strong></div></article><?php endforeach; ?></div>

        <article class="app-card card duty-table-card mt-4">
            <div class="duty-toolbar"><div><span class="eyebrow">Shift Registry</span><h2>Configured Shifts</h2></div><?php if ($canManageShifts): ?><a class="btn btn-primary" href="<?php echo e(route_url('admin/add-shift')); ?>"><i class="fa-solid fa-plus"></i>Add Shift</a><?php endif; ?></div>
            <form class="fuel-filter-grid mt-3" method="get" action="<?php echo e($filterUrl); ?>">
                <input type="hidden" name="route" value="admin/shift-management">
                <div class="filter-control filter-control--wide"><i class="fa-solid fa-magnifying-glass"></i><input name="search" type="search" value="<?php echo e($shiftFilters['search']); ?>" placeholder="Search shift code or name"></div>
                <select class="form-select" name="status" onchange="this.form.submit()"><option value="">All statuses</option><?php foreach ($shiftStatuses as $status): ?><option value="<?php echo e($status); ?>" <?php echo $shiftFilters['status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option><?php endforeach; ?></select>
                <input class="form-control" type="time" name="reporting_time" value="<?php echo e($shiftFilters['reporting_time']); ?>" title="Reporting time" onchange="this.form.submit()">
                <input class="form-control" type="time" name="closing_time" value="<?php echo e($shiftFilters['closing_time']); ?>" title="Closing time" onchange="this.form.submit()">
                <input type="hidden" name="sort" value="<?php echo e($shiftFilters['sort']); ?>">
                <input type="hidden" name="direction" value="<?php echo e($shiftFilters['direction']); ?>">
            </form>
            <div class="table-responsive mt-3"><table class="table attendance-table duty-table align-middle"><thead><tr><th>Code</th><th><a href="<?php echo e($sortUrl('shift_name')); ?>">Shift Name</a></th><th><a href="<?php echo e($sortUrl('reporting_time')); ?>">Reporting Time</a></th><th><a href="<?php echo e($sortUrl('closing_time')); ?>">Closing Time</a></th><th>Max Employees</th><th>Grace Period</th><th>Assigned</th><th><a href="<?php echo e($sortUrl('status')); ?>">Status</a></th><th>Actions</th></tr></thead><tbody><?php if ($shiftConfigurations === []): ?><tr><td colspan="9" class="text-center py-4">No shifts found.</td></tr><?php endif; ?><?php foreach ($shiftConfigurations as $shift): ?><tr><td><strong><?php echo e($shift['shift_code']); ?></strong></td><td><?php echo e($shift['shift_name']); ?></td><td><?php echo e($shift['reporting']); ?></td><td><?php echo e($shift['closing']); ?></td><td><?php echo e((string) $shift['maximum_employees']); ?></td><td><?php echo e((string) $shift['grace_period']); ?> mins</td><td><?php echo e((string) $shift['assigned']); ?></td><td><span class="table-badge <?php echo e($shift['status'] === 'Active' ? 'duty-status--active' : 'duty-status--off'); ?>"><?php echo e($shift['status']); ?></span></td><td><div class="duty-actions"><button class="btn btn-sm btn-light" data-shift-action="view" data-name="<?php echo e($shift['shift_name']); ?>" data-code="<?php echo e($shift['shift_code']); ?>" data-time="<?php echo e($shift['reporting'] . ' - ' . $shift['closing']); ?>" data-status="<?php echo e($shift['status']); ?>" type="button" title="View"><i class="fa-solid fa-eye"></i></button><?php if ($canManageShifts): ?><a class="btn btn-sm btn-light" href="<?php echo e(route_url('admin/edit-shift')); ?>&shift=<?php echo e((string) $shift['id']); ?>" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a><form method="post" action="<?php echo e(route_url('admin/shifts/toggle')); ?>" class="d-inline"><?php echo csrf_field(); ?><input type="hidden" name="shift_id" value="<?php echo e((string) $shift['id']); ?>"><button class="btn btn-sm btn-light" data-shift-action="toggle" data-name="<?php echo e($shift['shift_name']); ?>" type="submit" title="Activate or deactivate"><i class="fa-solid fa-power-off"></i></button></form><form method="post" action="<?php echo e(route_url('admin/shifts/delete')); ?>" class="d-inline"><?php echo csrf_field(); ?><input type="hidden" name="shift_id" value="<?php echo e((string) $shift['id']); ?>"><button class="btn btn-sm btn-light duty-action-danger" data-shift-action="delete" data-name="<?php echo e($shift['shift_name']); ?>" type="submit" title="Delete"><i class="fa-solid fa-trash"></i></button></form><?php endif; ?></div></td></tr><?php endforeach; ?></tbody></table></div>
            <div class="fuel-pagination"><span>Showing <?php echo e((string) $shiftPagination['from']); ?>-<?php echo e((string) $shiftPagination['to']); ?> of <?php echo e((string) $shiftPagination['total']); ?> shifts</span><div><?php if ($shiftPagination['page'] > 1): ?><a class="btn btn-outline-brand btn-sm" href="<?php echo e($pageUrl($shiftPagination['page'] - 1)); ?>"><i class="fa-solid fa-chevron-left"></i></a><?php else: ?><button class="btn btn-outline-brand btn-sm" disabled type="button"><i class="fa-solid fa-chevron-left"></i></button><?php endif; ?><?php if ($shiftPagination['page'] < $shiftPagination['pages']): ?><a class="btn btn-outline-brand btn-sm" href="<?php echo e($pageUrl($shiftPagination['page'] + 1)); ?>"><i class="fa-solid fa-chevron-right"></i></a><?php else: ?><button class="btn btn-outline-brand btn-sm" disabled type="button"><i class="fa-solid fa-chevron-right"></i></button><?php endif; ?></div></div>
        </article>

        <article class="app-card card duty-table-card mt-4">
            <div class="duty-toolbar"><div><span class="eyebrow">Shift Assignment</span><h2>Current Employees by Shift</h2></div></div>
            <div class="table-responsive"><table class="table attendance-table duty-table align-middle"><thead><tr><th>Employee</th><th>Department</th><th>Shift</th><th>Reporting Time</th><th>Closing Time</th><th>Status</th></tr></thead><tbody><?php foreach ($shiftAssignments as $assignment): ?><tr><td><strong><?php echo e($assignment['employee']); ?></strong></td><td><?php echo e($assignment['department']); ?></td><td><?php echo e($assignment['shift']); ?></td><td><?php echo e($assignment['reporting']); ?></td><td><?php echo e($assignment['closing']); ?></td><td><span class="table-badge <?php echo e($dutyStatusClasses[$assignment['status']] ?? 'duty-status--active'); ?>"><?php echo e($assignment['status']); ?></span></td></tr><?php endforeach; ?></tbody></table></div>
        </article>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
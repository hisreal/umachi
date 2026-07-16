<?php

declare(strict_types=1);

$pageTitle = 'Pump Management | FuelOps Admin Dashboard';
$pageHeading = 'Pump Management';
$currentRoute = 'admin/pumps';
require __DIR__ . '/pump-page-setup.php';
require __DIR__ . '/../includes/header.php';

$filterUrl = app_base_url() . '/index.php';
$queryWithoutPage = $pumpFilters;
unset($queryWithoutPage['page'], $queryWithoutPage['per_page']);
$sortUrl = static function (string $sort) use ($queryWithoutPage): string {
    $query = $queryWithoutPage;
    $query['sort'] = $sort;
    $query['direction'] = ($queryWithoutPage['sort'] ?? '') === $sort && ($queryWithoutPage['direction'] ?? 'asc') === 'asc' ? 'desc' : 'asc';

    return route_url('admin/pumps') . '&' . http_build_query(array_filter($query, static fn (mixed $value): bool => $value !== '' && $value !== null));
};
$pageUrl = static function (int $page) use ($pumpFilters): string {
    $query = $pumpFilters;
    $query['page'] = $page;
    unset($query['per_page']);

    return route_url('admin/pumps') . '&' . http_build_query(array_filter($query, static fn (mixed $value): bool => $value !== '' && $value !== null));
};
$exportUrl = static function (string $type) use ($exportQuery): string {
    return route_url('admin/pumps/export') . '&type=' . rawurlencode($type) . ($exportQuery === '' ? '' : '&' . $exportQuery);
};
?>
<main class="clock-in-page pump-module-page">
    <section class="clock-hero pump-hero"><div class="container-fluid"><nav class="pump-breadcrumb" aria-label="Breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Pump Management</span><i class="fa-solid fa-chevron-right"></i><span>Pumps</span></nav><div class="clock-hero__content pump-hero-card"><div><span class="eyebrow">Pump Registry</span><h1>Pump Management</h1><p>Manage fuel pump records, fuel type assignments, current meter readings, and pump status.</p></div><?php if ($canManagePumps): ?><a class="btn btn-light" href="<?php echo e(route_url('admin/add-pump')); ?>"><i class="fa-solid fa-plus"></i>Add New Pump</a><?php endif; ?></div></div></section>
    <section class="container-fluid clock-workspace">
        <?php if (!empty($pumpError)): ?><div class="alert alert-danger" role="alert"><?php echo e($pumpError); ?></div><?php endif; ?>
        <?php if (!empty($pumpSuccess)): ?><div class="alert alert-success" role="alert"><?php echo e($pumpSuccess); ?></div><?php endif; ?>
        <div class="pump-summary-grid"><?php foreach ($pumpStats as $card): ?><article class="pump-summary-card pump-summary-card--<?php echo e($card['tone']); ?>"><span><i class="<?php echo e($card['icon']); ?>"></i></span><div><small><?php echo e($card['label']); ?></small><strong><?php echo e($card['value']); ?></strong></div></article><?php endforeach; ?></div>
        <article class="app-card card pump-table-card mt-4"><div class="pump-toolbar"><div><span class="eyebrow">Pump Inventory</span><h2>All Pumps</h2></div><div class="pump-toolbar-actions"><div class="dropdown"><button class="btn btn-outline-brand dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fa-solid fa-download"></i>Export</button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="<?php echo e($exportUrl('pdf')); ?>">Export PDF</a></li><li><a class="dropdown-item" href="<?php echo e($exportUrl('excel')); ?>">Export Excel</a></li><li><a class="dropdown-item" href="<?php echo e($exportUrl('csv')); ?>">Export CSV</a></li></ul></div><?php if ($canManagePumps): ?><a class="btn btn-primary" href="<?php echo e(route_url('admin/add-pump')); ?>"><i class="fa-solid fa-plus"></i>Add New Pump</a><?php endif; ?></div></div>
            <form class="pump-filter-grid" method="get" action="<?php echo e($filterUrl); ?>">
                <input type="hidden" name="route" value="admin/pumps">
                <div class="filter-control filter-control--wide"><i class="fa-solid fa-magnifying-glass"></i><input id="pumpSearch" name="search" type="search" value="<?php echo e($pumpFilters['search']); ?>" placeholder="Search pump number, name, manufacturer, serial"></div>
                <select class="form-select" id="pumpFuelFilter" name="fuel_type" onchange="this.form.submit()"><option value="">All fuel types</option><?php foreach ($fuelTypes as $fuelType): ?><option value="<?php echo e($fuelType); ?>" <?php echo $pumpFilters['fuel_type'] === $fuelType ? 'selected' : ''; ?>><?php echo e($fuelType); ?></option><?php endforeach; ?></select>
                <select class="form-select" id="pumpStatusFilter" name="status" onchange="this.form.submit()"><option value="">All statuses</option><?php foreach ($pumpStatuses as $status): ?><option value="<?php echo e($status); ?>" <?php echo $pumpFilters['status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option><?php endforeach; ?></select>
                <select class="form-select" name="manufacturer" onchange="this.form.submit()"><option value="">All manufacturers</option><?php foreach ($pumpManufacturers as $manufacturer): ?><option value="<?php echo e($manufacturer); ?>" <?php echo $pumpFilters['manufacturer'] === $manufacturer ? 'selected' : ''; ?>><?php echo e($manufacturer); ?></option><?php endforeach; ?></select>
                <select class="form-select" name="year" onchange="this.form.submit()"><option value="">All years</option><?php foreach ($installationYears as $year): ?><option value="<?php echo e((string) $year); ?>" <?php echo (string) $pumpFilters['year'] === (string) $year ? 'selected' : ''; ?>><?php echo e((string) $year); ?></option><?php endforeach; ?></select>
                <input type="hidden" name="sort" value="<?php echo e($pumpFilters['sort']); ?>">
                <input type="hidden" name="direction" value="<?php echo e($pumpFilters['direction']); ?>">
            </form>
            <div class="table-responsive"><table class="table attendance-table pump-table align-middle"><thead><tr><th><a href="<?php echo e($sortUrl('pump_number')); ?>">Pump Number</a></th><th>Pump Name</th><th><a href="<?php echo e($sortUrl('fuel_type')); ?>">Fuel Type</a></th><th><a href="<?php echo e($sortUrl('status')); ?>">Pump Status</a></th><th>Current Meter Reading</th><th><a href="<?php echo e($sortUrl('manufacturer')); ?>">Manufacturer</a></th><th>Last Updated</th><th>Actions</th></tr></thead><tbody id="pumpTableBody"><?php if ($pumps === []): ?><tr><td colspan="8" class="text-center py-4">No pumps found.</td></tr><?php endif; ?><?php foreach ($pumps as $pump): ?><tr data-pump-row data-search="<?php echo e(strtolower($pump['pump_number'] . ' ' . $pump['pump_name'] . ' ' . $pump['manufacturer'] . ' ' . $pump['serial_number'])); ?>" data-fuel="<?php echo e($pump['fuel_type']); ?>" data-status="<?php echo e($pump['status']); ?>"><td><strong><?php echo e($pump['pump_number']); ?></strong></td><td><?php echo e($pump['pump_name']); ?></td><td><?php echo e($pump['fuel_type']); ?></td><td><span class="table-badge <?php echo e($pumpStatusClasses[$pump['status']] ?? ''); ?>"><?php echo e($pump['status']); ?></span></td><td><?php echo e(number_format((float) $pump['meter'], 2)); ?></td><td><?php echo e($pump['manufacturer']); ?></td><td><?php echo e($pump['last_updated']); ?></td><td><div class="pump-actions"><button class="btn btn-sm btn-light" data-pump-action="view" data-pump="<?php echo e($pump['pump_number']); ?>" data-name="<?php echo e($pump['pump_name']); ?>" data-fuel="<?php echo e($pump['fuel_type']); ?>" data-status="<?php echo e($pump['status']); ?>" data-meter="<?php echo e(number_format((float) $pump['meter'], 2)); ?>" data-manufacturer="<?php echo e($pump['manufacturer']); ?>" data-serial="<?php echo e($pump['serial_number']); ?>" title="View" type="button"><i class="fa-solid fa-eye"></i></button><?php if ($canManagePumps): ?><a class="btn btn-sm btn-light" href="<?php echo e(route_url('admin/edit-pump')); ?>&pump=<?php echo e((string) $pump['id']); ?>" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a><form method="post" action="<?php echo e(route_url('admin/pumps/toggle')); ?>" class="d-inline"><?php echo csrf_field(); ?><input type="hidden" name="pump_id" value="<?php echo e((string) $pump['id']); ?>"><button class="btn btn-sm btn-light" data-pump-action="toggle" data-pump="<?php echo e($pump['pump_number']); ?>" title="Activate or deactivate" type="submit"><i class="fa-solid fa-power-off"></i></button></form><form method="post" action="<?php echo e(route_url('admin/pumps/delete')); ?>" class="d-inline"><?php echo csrf_field(); ?><input type="hidden" name="pump_id" value="<?php echo e((string) $pump['id']); ?>"><button class="btn btn-sm btn-light pump-action-danger" data-pump-action="delete" data-pump="<?php echo e($pump['pump_number']); ?>" title="Delete" type="submit"><i class="fa-solid fa-trash"></i></button></form><?php endif; ?></div></td></tr><?php endforeach; ?></tbody></table></div><div class="pump-pagination"><span id="pumpPageSummary">Showing <?php echo e((string) $pagination['from']); ?>-<?php echo e((string) $pagination['to']); ?> of <?php echo e((string) $pagination['total']); ?> pumps</span><div><?php if ($pagination['page'] > 1): ?><a class="btn btn-outline-brand btn-sm" href="<?php echo e($pageUrl($pagination['page'] - 1)); ?>"><i class="fa-solid fa-chevron-left"></i></a><?php else: ?><button class="btn btn-outline-brand btn-sm" disabled type="button"><i class="fa-solid fa-chevron-left"></i></button><?php endif; ?><?php if ($pagination['page'] < $pagination['pages']): ?><a class="btn btn-outline-brand btn-sm" href="<?php echo e($pageUrl($pagination['page'] + 1)); ?>"><i class="fa-solid fa-chevron-right"></i></a><?php else: ?><button class="btn btn-outline-brand btn-sm" disabled type="button"><i class="fa-solid fa-chevron-right"></i></button><?php endif; ?></div></div></article>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
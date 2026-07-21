<?php

declare(strict_types=1);

$pageTitle = 'Pump Maintenance | FuelOps Admin Dashboard';
$pageHeading = 'Pump Maintenance';
$currentRoute = 'admin/maintenance';
require __DIR__ . '/pump-page-setup.php';

$pumpFilters['status'] = 'Under Maintenance';
$pumpFilters['page'] = max(1, (int) $request->query('page', 1));
$pumpResult = $pumpModel->paginated($pumpFilters);
$pumps = $pumpResult['records'];
$pagination = $pumpResult['pagination'];
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page pump-module-page">
    <section class="clock-hero pump-hero"><div class="container-fluid"><nav class="pump-breadcrumb" aria-label="Breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><a href="<?php echo e(route_url('admin/pumps')); ?>">Pump Management</a><i class="fa-solid fa-chevron-right"></i><span>Maintenance</span></nav><div class="clock-hero__content pump-hero-card"><div><span class="eyebrow">Maintenance Registry</span><h1>Pump Maintenance</h1><p>Review pumps currently under maintenance and return them to active service when ready.</p></div><a class="btn btn-light" href="<?php echo e(route_url('admin/pumps')); ?>"><i class="fa-solid fa-gas-pump"></i>All Pumps</a></div></div></section>
    <section class="container-fluid clock-workspace">
        <div class="pump-summary-grid"><?php foreach ($pumpStats as $card): ?><article class="pump-summary-card pump-summary-card--<?php echo e($card['tone']); ?>"><span><i class="<?php echo e($card['icon']); ?>"></i></span><div><small><?php echo e($card['label']); ?></small><strong><?php echo e($card['value']); ?></strong></div></article><?php endforeach; ?></div>
        <article class="app-card card pump-table-card mt-4">
            <div class="pump-toolbar"><div><span class="eyebrow">Maintenance Queue</span><h2>Pumps Under Maintenance</h2></div></div>
            <div class="table-responsive"><table class="table attendance-table pump-table align-middle"><thead><tr><th>Pump Number</th><th>Pump Name</th><th>Fuel Type</th><th>Status</th><th>Meter Reading</th><th>Manufacturer</th><th>Last Updated</th><th>Actions</th></tr></thead><tbody id="pumpTableBody">
                <?php if ($pumps === []): ?><tr><td colspan="8" class="text-center py-4">No pumps are currently under maintenance.</td></tr><?php endif; ?>
                <?php foreach ($pumps as $pump): ?><tr data-pump-row><td><strong><?php echo e($pump['pump_number']); ?></strong></td><td><?php echo e($pump['pump_name']); ?></td><td><?php echo e($pump['fuel_type']); ?></td><td><span class="table-badge pump-status--maintenance"><?php echo e($pump['status']); ?></span></td><td><?php echo e(number_format((float) $pump['meter'], 2)); ?></td><td><?php echo e($pump['manufacturer']); ?></td><td><?php echo e($pump['last_updated']); ?></td><td><div class="pump-actions"><a class="btn btn-sm btn-light" href="<?php echo e(route_url('admin/edit-pump')); ?>&pump=<?php echo e((string) $pump['id']); ?>" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a><?php if ($canManagePumps): ?><form method="post" action="<?php echo e(route_url('admin/pumps/toggle')); ?>" class="d-inline"><?php echo csrf_field(); ?><input type="hidden" name="pump_id" value="<?php echo e((string) $pump['id']); ?>"><button class="btn btn-sm btn-light" data-pump-action="toggle" data-pump="<?php echo e($pump['pump_number']); ?>" title="Return to active service" type="submit"><i class="fa-solid fa-power-off"></i></button></form><form method="post" action="<?php echo e(route_url('admin/pumps/delete')); ?>" class="d-inline"><?php echo csrf_field(); ?><input type="hidden" name="pump_id" value="<?php echo e((string) $pump['id']); ?>"><button class="btn btn-sm btn-light pump-action-danger" data-pump-action="delete" data-pump="<?php echo e($pump['pump_number']); ?>" title="Delete" type="submit"><i class="fa-solid fa-trash"></i></button></form><?php endif; ?></div></td></tr><?php endforeach; ?>
            </tbody></table></div>
        </article>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

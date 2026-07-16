<?php

declare(strict_types=1);

$pageTitle = 'Pump Meter History | FuelOps Admin Dashboard';
$pageHeading = 'Pump Meter History';
$currentRoute = 'admin/pump-meter-history';
require __DIR__ . '/fuel-sales-page-setup.php';
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page fuel-module-page">
    <section class="clock-hero fuel-hero"><div class="container-fluid"><nav class="fuel-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Fuel Sales</span><i class="fa-solid fa-chevron-right"></i><span>Pump Meter History</span></nav><div class="clock-hero__content fuel-hero-card"><div><span class="eyebrow">Meter Audit</span><h1>Pump Meter History</h1><p>Track opening and closing meter readings recorded during clock-out fuel sales submissions.</p></div><span class="fuel-hero-icon"><i class="fa-solid fa-gauge-high"></i></span></div></div></section>
    <section class="container-fluid clock-workspace"><div class="fuel-summary-grid"><?php foreach ($pumpMeterSummary as $card): ?><article class="fuel-summary-card fuel-summary-card--<?php echo e($card['tone']); ?>"><span><i class="<?php echo e($card['icon']); ?>"></i></span><div><small><?php echo e($card['label']); ?></small><strong><?php echo e($card['value']); ?></strong></div></article><?php endforeach; ?></div><article class="app-card card fuel-table-card mt-4"><div class="fuel-toolbar"><div><span class="eyebrow">Meter Records</span><h2>Opening & Closing Readings</h2></div></div><div class="table-responsive"><table class="table attendance-table fuel-table align-middle"><thead><tr><th>Date</th><th>Pump</th><th>Fuel Type</th><th>Opening Meter</th><th>Closing Meter</th><th>Difference</th><th>Employee</th><th>Shift</th><th>Status</th></tr></thead><tbody><?php foreach ($pumpMeterReadings as $reading): ?><tr><td><?php echo e(format_date($reading['date'])); ?></td><td><?php echo e($reading['pump']); ?></td><td><?php echo e($reading['fuel_type']); ?></td><td><?php echo e(number_format($reading['opening_meter'], 2)); ?></td><td><?php echo e(number_format($reading['closing_meter'], 2)); ?></td><td><?php echo e(number_format($reading['difference'], 2)); ?> L</td><td><?php echo e($reading['attendant']); ?></td><td><?php echo e($reading['shift']); ?></td><td><?php echo e($reading['status']); ?></td></tr><?php endforeach; ?></tbody></table></div></article></section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

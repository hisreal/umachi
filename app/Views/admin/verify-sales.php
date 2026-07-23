<?php

declare(strict_types=1);

$pageTitle = 'Verify Fuel Sales | FuelOps Admin Dashboard';
$pageHeading = 'Verify Fuel Sales';
$currentRoute = 'admin/verify-sales';
require __DIR__ . '/fuel-sales-page-setup.php';
$fuelSuccess = \App\Core\Session::pullFlash('fuel_success');
$fuelError = \App\Core\Session::pullFlash('fuel_error');
$expectedLiters = $selectedSale['closing_meter'] - $selectedSale['opening_meter'];
$variance = (float) ($selectedSale['variance'] ?? 0);
$attendanceId = (int) ($selectedSale['attendance_id'] ?? 0);
$meterPhotoUrl = static fn (string $type): string => route_url('admin/attendance-history/selfie') . '&id=' . rawurlencode((string) $attendanceId) . '&type=' . rawurlencode($type);
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page fuel-module-page">
    <section class="clock-hero fuel-hero"><div class="container-fluid"><nav class="fuel-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Fuel Sales</span><i class="fa-solid fa-chevron-right"></i><span>Verify Sales</span></nav><div class="clock-hero__content fuel-hero-card"><div><span class="eyebrow">Verification Workflow</span><h1>Verify Fuel Sales</h1><p>Review submitted meter readings and validate sales records before approval.</p></div><span class="fuel-hero-icon"><i class="fa-solid fa-circle-check"></i></span></div></div></section>
    <section class="container-fluid clock-workspace">
        <article class="app-card card fuel-verification-card">
            <div class="app-card__header"><div><span class="eyebrow">Payment Summary</span><h2>Shift Reconciliation</h2></div><span class="badge <?php echo ($selectedSale['balance_status'] ?? 'balanced') === 'balanced' ? 'bg-success' : (($selectedSale['balance_status'] ?? '') === 'shortage' ? 'bg-danger' : 'bg-warning text-dark'); ?>"><?php echo e(ucfirst((string) ($selectedSale['balance_status'] ?? 'balanced'))); ?></span></div>
            <div class="fuel-detail-grid"><?php foreach ([['Expected Amount', 'NGN ' . number_format((float) ($selectedSale['expected_amount'] ?? 0), 2)], ['Cash Received', 'NGN ' . number_format((float) ($selectedSale['cash_received'] ?? 0), 2)], ['POS / Card', 'NGN ' . number_format((float) ($selectedSale['pos_received'] ?? 0), 2)], ['Bank Transfer', 'NGN ' . number_format((float) ($selectedSale['bank_transfer_received'] ?? 0), 2)], ['Total Received', 'NGN ' . number_format((float) ($selectedSale['total_received'] ?? 0), 2)], ['Difference', 'NGN ' . number_format((float) ($selectedSale['difference_amount'] ?? 0), 2)], ['Payment Remark', (string) ($selectedSale['payment_remark'] ?: 'None')]] as $item): ?><div><span><?php echo e($item[0]); ?></span><strong><?php echo e($item[1]); ?></strong></div><?php endforeach; ?></div>
        </article>
    </section>
    <section class="container-fluid pb-4">
        <article class="app-card card fuel-verification-card">
            <div class="app-card__header"><div><span class="eyebrow">Meter Evidence</span><h2>Opening &amp; Closing Meter Photos</h2></div><i class="fa-solid fa-camera"></i></div>
            <div class="d-flex flex-wrap gap-3">
                <?php if ($attendanceId > 0 && !empty($selectedSale['opening_meter_image'])): ?><a class="btn btn-outline-brand" href="<?php echo e($meterPhotoUrl('opening-meter')); ?>" data-image-view data-image-title="Opening Meter Photo"><i class="fa-solid fa-eye me-2"></i>View Opening Meter Photo</a><?php else: ?><span class="text-muted">Opening meter photo not available.</span><?php endif; ?>
                <?php if ($attendanceId > 0 && !empty($selectedSale['closing_meter_image'])): ?><a class="btn btn-outline-brand" href="<?php echo e($meterPhotoUrl('closing-meter')); ?>" data-image-view data-image-title="Closing Meter Photo"><i class="fa-solid fa-eye me-2"></i>View Closing Meter Photo</a><?php else: ?><span class="text-muted">Closing meter photo not available.</span><?php endif; ?>
            </div>
        </article>
    </section>
    <section class="container-fluid clock-workspace"><div class="row g-4 align-items-start"><div class="col-12 col-xl-7"><article class="app-card card fuel-verification-card"><div class="app-card__header"><div><span class="eyebrow">Transaction Details</span><h2><?php echo e($selectedSale['transaction_id']); ?></h2></div><span class="table-badge <?php echo e($salesStatusClasses[$selectedSale['status']]); ?>"><?php echo e($selectedSale['status']); ?></span></div><div class="fuel-detail-grid"><?php foreach ([['Date', format_date($selectedSale['date'])], ['Pump', $selectedSale['pump']], ['Fuel Type', $selectedSale['fuel_type']], ['Shift', $selectedSale['shift']], ['Attendant', $selectedSale['attendant']], ['Opening Meter Reading', number_format($selectedSale['opening_meter'])], ['Closing Meter Reading', number_format($selectedSale['closing_meter'])], ['Meter Difference', number_format($expectedLiters) . ' L'], ['Liters Sold', number_format($selectedSale['liters_sold']) . ' L'], ['Amount Collected', 'NGN ' . number_format($selectedSale['amount'])], ['Submitted Time', $selectedSale['submitted_time']]] as $item): ?><div><span><?php echo e($item[0]); ?></span><strong><?php echo e($item[1]); ?></strong></div><?php endforeach; ?></div></article></div><div class="col-12 col-xl-5"><article class="app-card card fuel-validation-card"><div class="app-card__header"><div><span class="eyebrow">Validation Summary</span><h2>Meter Validation</h2></div></div><div class="fuel-validation-stack"><div><span>Expected Liters Sold</span><strong><?php echo e(number_format($expectedLiters)); ?> L</strong></div><i class="fa-solid fa-arrow-down"></i><div><span>Actual Liters Sold</span><strong><?php echo e(number_format($selectedSale['liters_sold'])); ?> L</strong></div><i class="fa-solid fa-arrow-down"></i><div><span>Amount Variance</span><strong>NGN <?php echo e(number_format($variance, 2)); ?></strong></div></div><?php if (!empty($fuelSuccess)): ?><div class="alert alert-success mt-3"><?php echo e((string) $fuelSuccess); ?></div><?php endif; ?><?php if (!empty($fuelError)): ?><div class="alert alert-danger mt-3"><?php echo e((string) $fuelError); ?></div><?php endif; ?><form method="post" action="<?php echo e(route_url('admin/fuel-sales/verify')); ?>"><?php echo csrf_field(); ?><input type="hidden" name="sale_code" value="<?php echo e($selectedSale['transaction_id']); ?>"><label class="form-label mt-3" for="verificationNotes">Verification Notes</label><textarea class="form-control" id="verificationNotes" name="verification_notes" rows="4" placeholder="Optional verification notes"></textarea><div class="fuel-form-actions"><button class="btn btn-success" name="action" value="verify" type="submit">Verify Sales</button><button class="btn btn-outline-danger" name="action" value="reject" type="submit">Reject Sales</button><button class="btn btn-outline-brand" name="action" value="correction" type="submit">Request Correction</button></div></form></article></div></div></section>


    
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

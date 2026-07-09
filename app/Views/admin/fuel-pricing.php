<?php

declare(strict_types=1);

$pageTitle = 'Fuel Pricing | FuelOps Admin Dashboard';
$pageHeading = 'Fuel Pricing';
$currentRoute = 'admin/fuel-pricing';
require __DIR__ . '/company-settings-setup.php';
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page settings-module-page">
    <section class="clock-hero settings-hero"><div class="container-fluid"><nav class="settings-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Settings</span><i class="fa-solid fa-chevron-right"></i><span>Fuel Pricing</span></nav><div class="clock-hero__content settings-hero-card"><div><span class="eyebrow">Company Settings</span><h1>Fuel Pricing</h1><p>Manage selling prices, effective dates, and price history for station fuel products.</p></div><span class="settings-hero-icon"><i class="fa-solid fa-tags"></i></span></div></div></section>
    <section class="container-fluid clock-workspace">
        <div class="settings-summary-grid settings-summary-grid--four"><?php foreach ($priceCards as $card): ?><article class="settings-summary-card settings-summary-card--<?php echo e($card['tone']); ?>"><span><i class="<?php echo e($card['icon']); ?>"></i></span><div><small><?php echo e($card['label']); ?></small><strong><?php echo e($card['value']); ?></strong></div></article><?php endforeach; ?></div>
        <form class="settings-form needs-validation mt-4" id="fuelPricingForm" novalidate>
            <article class="app-card card settings-form-card">
                <div class="settings-section-heading"><span><i class="fa-solid fa-pen-to-square"></i></span><div><small>Current Price Update</small><h2>Fuel Pricing Form</h2></div></div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label" for="petrolPrice">Petrol (PMS)</label><div class="input-group"><span class="input-group-text">₦</span><input class="form-control settings-price-input" id="petrolPrice" inputmode="decimal" value="945.00" required></div></div>
                    <div class="col-md-4"><label class="form-label" for="dieselPrice">Diesel (AGO)</label><div class="input-group"><span class="input-group-text">₦</span><input class="form-control settings-price-input" id="dieselPrice" inputmode="decimal" value="1150.00" required></div></div>
                    <div class="col-md-4"><label class="form-label" for="gasPrice">Gas (LPG)</label><div class="input-group"><span class="input-group-text">₦</span><input class="form-control settings-price-input" id="gasPrice" inputmode="decimal" value="980.00" required></div></div>
                    <div class="col-md-3"><label class="form-label" for="effectiveDate">Effective Date</label><input class="form-control" id="effectiveDate" type="date" value="2026-07-10" required></div>
                    <div class="col-md-3"><label class="form-label" for="effectiveTime">Effective Time</label><input class="form-control" id="effectiveTime" type="time" value="06:00" required></div>
                    <div class="col-md-6"><label class="form-label" for="updatedBy">Updated By</label><input class="form-control" id="updatedBy" value="Administrator" readonly></div>
                    <div class="col-12"><label class="form-label" for="pricingRemarks">Remarks</label><textarea class="form-control" id="pricingRemarks" rows="3" placeholder="Optional pricing remarks">Scheduled fuel price update for next business day.</textarea></div>
                </div>
                <div class="settings-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i>Save Prices</button><button class="btn btn-outline-brand" type="reset" data-settings-reset><i class="fa-solid fa-rotate-left"></i>Reset</button><a class="btn btn-light" href="<?php echo e(route_url('admin/dashboard')); ?>">Cancel</a></div>
            </article>
        </form>
        <article class="app-card card settings-table-card mt-4"><div class="settings-toolbar"><div><span class="eyebrow">Audit Trail</span><h2>Price History</h2></div></div><div class="table-responsive"><table class="table attendance-table settings-table align-middle"><thead><tr><th>Date</th><th>Fuel Type</th><th>Old Price</th><th>New Price</th><th>Difference</th><th>Updated By</th><th>Effective Date</th><th>Actions</th></tr></thead><tbody><?php foreach ($priceHistory as $history): ?><?php $difference = $history['new_price'] - $history['old_price']; ?><tr><td><?php echo e($history['date']); ?></td><td><strong><?php echo e($history['fuel_type']); ?></strong></td><td>₦<?php echo e(number_format($history['old_price'], 2)); ?></td><td>₦<?php echo e(number_format($history['new_price'], 2)); ?></td><td><span class="settings-difference <?php echo $difference >= 0 ? 'is-positive' : 'is-negative'; ?>"><?php echo $difference >= 0 ? '+' : ''; ?>₦<?php echo e(number_format($difference, 2)); ?></span></td><td><?php echo e($history['updated_by']); ?></td><td><?php echo e(date('d M Y', strtotime($history['effective_date']))); ?></td><td><div class="settings-actions"><button class="btn btn-sm btn-light" data-price-action="view" data-price-id="<?php echo e($history['id']); ?>" type="button"><i class="fa-solid fa-eye"></i></button><button class="btn btn-sm btn-light" data-price-action="edit" data-price-id="<?php echo e($history['id']); ?>" type="button"><i class="fa-solid fa-pen-to-square"></i></button><button class="btn btn-sm btn-light settings-action-danger" data-price-action="delete" data-price-id="<?php echo e($history['id']); ?>" type="button"><i class="fa-solid fa-trash"></i></button></div></td></tr><?php endforeach; ?></tbody></table></div></article>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
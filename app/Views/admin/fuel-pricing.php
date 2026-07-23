<?php

declare(strict_types=1);

$pageTitle = 'Fuel Pricing | FuelOps Admin Dashboard';
$pageHeading = 'Fuel Pricing';
$currentRoute = 'admin/fuel-pricing';
require __DIR__ . '/company-settings-setup.php';
$settingsSuccess = \App\Core\Session::pullFlash('settings_success');
$settingsError = \App\Core\Session::pullFlash('settings_error');
$settingsCsrf = (new \App\Services\AuthService())->csrfToken();
$PetrolPrice = $fuelPrices['petrol'] ?? ['price' => 0, 'effective_date' => date('Y-m-d'), 'effective_time' => date('H:i'), 'updated_by' => 'System'];
$agoPrice = $fuelPrices['ago'] ?? ['price' => 0];
$lpgPrice = $fuelPrices['lpg'] ?? ['price' => 0];
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page settings-module-page">
    <section class="clock-hero settings-hero"><div class="container-fluid"><nav class="settings-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Settings</span><i class="fa-solid fa-chevron-right"></i><span>Fuel Pricing</span></nav><div class="clock-hero__content settings-hero-card"><div><span class="eyebrow">Company Settings</span><h1>Fuel Pricing</h1><p>Manage selling prices, effective dates, and price history for station fuel products.</p></div><span class="settings-hero-icon"><i class="fa-solid fa-tags"></i></span></div></div></section>
    <section class="container-fluid clock-workspace">
        <?php if (is_string($settingsSuccess) && $settingsSuccess !== ''): ?><div class="alert alert-success"><?php echo e($settingsSuccess); ?></div><?php endif; ?>
        <?php if (is_string($settingsError) && $settingsError !== ''): ?><div class="alert alert-danger"><?php echo e($settingsError); ?></div><?php endif; ?>
        <div class="settings-summary-grid settings-summary-grid--four"><?php foreach ($priceCards as $card): ?><article class="settings-summary-card settings-summary-card--<?php echo e($card['tone']); ?>"><span><i class="<?php echo e($card['icon']); ?>"></i></span><div><small><?php echo e($card['label']); ?></small><strong><?php echo e($card['value']); ?></strong></div></article><?php endforeach; ?></div>
        <form class="settings-form needs-validation mt-4" id="fuelPricingForm" method="post" action="<?php echo e(route_url('admin/settings/fuel-pricing/save')); ?>" novalidate>
            <input type="hidden" name="_csrf_token" value="<?php echo e($settingsCsrf); ?>">
            <article class="app-card card settings-form-card">
                <div class="settings-section-heading"><span><i class="fa-solid fa-pen-to-square"></i></span><div><small>Current Price Update</small><h2>Fuel Pricing Form</h2></div></div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label" for="petrolPrice">Petrol (Petrol)</label><div class="input-group"><span class="input-group-text">NGN</span><input class="form-control settings-price-input" id="petrolPrice" name="Petrol_price" inputmode="decimal" value="<?php echo e(number_format((float) $PetrolPrice['price'], 2, '.', '')); ?>" required></div></div>
                    <div class="col-md-4"><label class="form-label" for="dieselPrice">Diesel (AGO)</label><div class="input-group"><span class="input-group-text">NGN</span><input class="form-control settings-price-input" id="dieselPrice" name="ago_price" inputmode="decimal" value="<?php echo e(number_format((float) $agoPrice['price'], 2, '.', '')); ?>" required></div></div>
                    <div class="col-md-4"><label class="form-label" for="gasPrice">Gas (LPG)</label><div class="input-group"><span class="input-group-text">NGN</span><input class="form-control settings-price-input" id="gasPrice" name="lpg_price" inputmode="decimal" value="<?php echo e(number_format((float) $lpgPrice['price'], 2, '.', '')); ?>" required></div></div>
                    <div class="col-md-3"><label class="form-label" for="effectiveDate">Effective Date</label><input class="form-control" id="effectiveDate" name="effective_date" type="date" value="<?php echo e((string) $PetrolPrice['effective_date']); ?>" required></div>
                    <div class="col-md-3"><label class="form-label" for="effectiveTime">Effective Time</label><input class="form-control" id="effectiveTime" name="effective_time" type="time" value="<?php echo e((string) $PetrolPrice['effective_time']); ?>" required></div>
                    <div class="col-md-6"><label class="form-label" for="updatedBy">Updated By</label><input class="form-control" id="updatedBy" value="<?php echo e((string) $PetrolPrice['updated_by']); ?>" readonly></div>
                    <div class="col-12"><label class="form-label" for="pricingRemarks">Remarks</label><textarea class="form-control" id="pricingRemarks" name="remarks" rows="3" placeholder="Optional pricing remarks"></textarea></div>
                </div>
                <div class="settings-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i>Save Prices</button><button class="btn btn-outline-brand" type="reset" data-settings-reset><i class="fa-solid fa-rotate-left"></i>Reset</button><a class="btn btn-light" href="<?php echo e(route_url('admin/dashboard')); ?>">Cancel</a></div>
            </article>
        </form>
        <article class="app-card card settings-table-card mt-4"><div class="settings-toolbar"><div><span class="eyebrow">Audit Trail</span><h2>Price History</h2></div></div><div class="table-responsive"><table class="table attendance-table settings-table align-middle"><thead><tr><th>Date</th><th>Fuel Type</th><th>Old Price</th><th>New Price</th><th>Difference</th><th>Updated By</th><th>Effective Date</th><th>Actions</th></tr></thead><tbody><?php foreach ($priceHistory as $history): ?><?php $difference = $history['new_price'] - $history['old_price']; ?><tr><td><?php echo e($history['date']); ?></td><td><strong><?php echo e($history['fuel_type']); ?></strong></td><td>NGN <?php echo e(number_format($history['old_price'], 2)); ?></td><td>NGN <?php echo e(number_format($history['new_price'], 2)); ?></td><td><span class="settings-difference <?php echo $difference >= 0 ? 'is-positive' : 'is-negative'; ?>"><?php echo $difference >= 0 ? '+' : ''; ?>NGN <?php echo e(number_format($difference, 2)); ?></span></td><td><?php echo e($history['updated_by']); ?></td><td><?php echo e(format_date($history['effective_date'])); ?></td><td><div class="settings-actions"><button class="btn btn-sm btn-light" data-price-action="view" data-price-id="<?php echo e($history['id']); ?>" type="button"><i class="fa-solid fa-eye"></i></button><button class="btn btn-sm btn-light" data-price-action="edit" data-price-id="<?php echo e($history['id']); ?>" type="button"><i class="fa-solid fa-pen-to-square"></i></button><button class="btn btn-sm btn-light settings-action-danger" data-price-action="delete" data-price-id="<?php echo e($history['id']); ?>" type="button"><i class="fa-solid fa-trash"></i></button></div></td></tr><?php endforeach; ?></tbody></table></div></article>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

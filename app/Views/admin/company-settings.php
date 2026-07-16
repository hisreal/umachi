<?php

declare(strict_types=1);

$pageTitle = 'Company Settings | FuelOps Admin Dashboard';
$pageHeading = 'Company Settings';
$currentRoute = 'admin/company-settings';
require __DIR__ . '/company-settings-setup.php';
$settingsModel = new \App\Models\SettingsModel();
$companySettings = $settingsModel->settings('company');
$systemSettings = $settingsModel->settings('system');
$settingsSuccess = \App\Core\Session::pullFlash('settings_success');
$settingsError = \App\Core\Session::pullFlash('settings_error');
$settingsCsrf = (new \App\Services\AuthService())->csrfToken();
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page settings-module-page">
    <section class="clock-hero settings-hero"><div class="container-fluid"><nav class="settings-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Settings</span><i class="fa-solid fa-chevron-right"></i><span>Company Settings</span></nav><div class="clock-hero__content settings-hero-card"><div><span class="eyebrow">System Configuration</span><h1>Company Settings</h1><p>Manage station identity, public company details, and core system preferences.</p></div><span class="settings-hero-icon"><i class="fa-solid fa-building"></i></span></div></div></section>
    <section class="container-fluid clock-workspace">
        <?php if (is_string($settingsSuccess) && $settingsSuccess !== ''): ?><div class="alert alert-success"><?php echo e($settingsSuccess); ?></div><?php endif; ?>
        <?php if (is_string($settingsError) && $settingsError !== ''): ?><div class="alert alert-danger"><?php echo e($settingsError); ?></div><?php endif; ?>
        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <form class="settings-form needs-validation" method="post" action="<?php echo e(route_url('admin/settings/company/save')); ?>" novalidate>
                    <input type="hidden" name="_csrf_token" value="<?php echo e($settingsCsrf); ?>">
                    <article class="app-card card settings-form-card h-100">
                        <div class="settings-section-heading"><span><i class="fa-solid fa-building"></i></span><div><small>Company Information</small><h2>Station Profile</h2></div></div>
                        <div class="row g-3">
                            <div class="col-12"><label class="form-label" for="companyName">Company Name</label><input class="form-control" id="companyName" name="company_name" value="<?php echo e((string) ($companySettings['company_name'] ?? 'Umachi Oil and Gas')); ?>" required></div>
                            <div class="col-md-6"><label class="form-label" for="companyEmail">Email</label><input class="form-control" id="companyEmail" name="email" type="email" value="<?php echo e((string) ($companySettings['email'] ?? 'info@umachioil.test')); ?>" required></div>
                            <div class="col-md-6"><label class="form-label" for="companyPhone">Phone</label><input class="form-control" id="companyPhone" name="phone" value="<?php echo e((string) ($companySettings['phone'] ?? '+234 800 000 0000')); ?>" required></div>
                            <div class="col-12"><label class="form-label" for="companyWebsite">Website</label><input class="form-control" id="companyWebsite" name="website" value="<?php echo e((string) ($companySettings['website'] ?? '')); ?>"></div>
                            <div class="col-12"><label class="form-label" for="companyAddress">Address</label><textarea class="form-control" id="companyAddress" name="address" rows="4" required><?php echo e((string) ($companySettings['address'] ?? 'Main Station, Lagos, Nigeria')); ?></textarea></div>
                        </div>
                        <div class="settings-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i>Save Company Info</button></div>
                    </article>
                </form>
            </div>
            <div class="col-12 col-xl-6">
                <form class="settings-form needs-validation" method="post" action="<?php echo e(route_url('admin/settings/system/save')); ?>" novalidate>
                    <input type="hidden" name="_csrf_token" value="<?php echo e($settingsCsrf); ?>">
                    <article class="app-card card settings-form-card h-100">
                        <div class="settings-section-heading"><span><i class="fa-solid fa-sliders"></i></span><div><small>System Settings</small><h2>Application Preferences</h2></div></div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label" for="timezone">Timezone</label><input class="form-control" id="timezone" name="timezone" value="<?php echo e((string) ($systemSettings['timezone'] ?? 'Africa/Lagos')); ?>" required></div>
                            <div class="col-md-6"><label class="form-label" for="currency">Currency</label><input class="form-control" id="currency" name="currency" value="<?php echo e((string) ($systemSettings['currency'] ?? 'NGN')); ?>" required></div>
                            <div class="col-md-6"><label class="form-label" for="dateFormat">Date Format</label><input class="form-control" id="dateFormat" name="date_format" value="<?php echo e((string) ($systemSettings['date_format'] ?? 'd M Y')); ?>" required></div>
                            <div class="col-md-6 d-flex align-items-end"><div class="form-check form-switch mb-2"><input class="form-check-input" id="maintenanceMode" name="maintenance_mode" type="checkbox" <?php echo !empty($systemSettings['maintenance_mode']) ? 'checked' : ''; ?>><label class="form-check-label" for="maintenanceMode">Maintenance Mode</label></div></div>
                        </div>
                        <div class="settings-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i>Save System Settings</button></div>
                    </article>
                </form>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

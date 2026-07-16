<?php

declare(strict_types=1);

use App\Core\Session;

$pageTitle = 'Change Password | FuelOps Staff Dashboard';
$pageHeading = 'Change Password';
$topbarSubtitle = 'Pump Attendant Dashboard';
$currentRoute = $currentRoute ?? 'profile/change-password';
$extraStyles = ['css/clock-in.css', 'css/profile.css', 'css/profile-management.css'];
$extraScripts = ['js/profile-management.js'];

$passwordError = Session::pullFlash('password_error');
$passwordSuccess = Session::pullFlash('password_success');
$forcePasswordChange = Session::get('auth.must_change_password', false);

$authUser = Session::get('auth.user', []);
$attendantName = is_array($authUser) ? (string) ($authUser['name'] ?? 'Station Staff') : 'Station Staff';
$attendantRole = is_array($authUser) ? (string) ($authUser['role'] ?? 'Pump Attendant') : 'Pump Attendant';

require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page profile-page profile-module-page">
    <section class="clock-hero profile-hero">
        <div class="container-fluid">
            <nav class="profile-breadcrumb">
                <a href="<?php echo e(route_url('dashboard')); ?>">Dashboard</a>
                <i class="fa-solid fa-chevron-right"></i>
                <a href="<?php echo e(route_url('profile')); ?>">Profile</a>
                <i class="fa-solid fa-chevron-right"></i>
                <span>Change Password</span>
            </nav>
            <div class="clock-hero__content profile-hero-card">
                <div>
                    <span class="eyebrow">Account Security</span>
                    <h1><?php echo e($pageHeading); ?></h1>
                    <p><?php echo $forcePasswordChange ? 'You must change your default password before continuing.' : 'Update your password to keep your staff account secure.'; ?></p>
                </div>
                <span class="profile-hero-icon" aria-hidden="true"><i class="fa-solid fa-key"></i></span>
            </div>
        </div>
    </section>

    <section class="container-fluid clock-workspace">
        <div class="row g-4">
            <div class="col-xl-4">
                <article class="app-card card profile-info-card h-100">
                    <div class="profile-section-heading">
                        <span><i class="fa-solid fa-shield-halved"></i></span>
                        <div>
                            <small>Security Information</small>
                            <h2>Account Status</h2>
                        </div>
                    </div>
                    <div class="profile-info-grid profile-info-grid--single">
                        <div><small>Employee</small><strong><?php echo e($attendantName); ?></strong></div>
                        <div><small>Role</small><strong><?php echo e($attendantRole); ?></strong></div>
                        <div><small>Password Status</small><strong><?php echo $forcePasswordChange ? 'Password change required' : 'Active'; ?></strong></div>
                        <div><small>Security Rule</small><strong>Minimum 8 characters</strong></div>
                    </div>
                </article>
            </div>

            <div class="col-xl-8">
                <form class="app-card card profile-form-card needs-validation" id="passwordForm" method="POST" action="<?php echo e(route_url('profile/change-password')); ?>" novalidate>
                    <?php echo csrf_field(); ?>
                    <?php if (!empty($passwordError)): ?>
                        <div class="alert alert-danger"><?php echo e((string) $passwordError); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($passwordSuccess)): ?>
                        <div class="alert alert-success"><?php echo e((string) $passwordSuccess); ?></div>
                    <?php endif; ?>

                    <div class="profile-section-heading">
                        <span><i class="fa-solid fa-lock"></i></span>
                        <div>
                            <small>Password Change Form</small>
                            <h2>Update Password</h2>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="currentPassword">Current Password</label>
                            <div class="password-field">
                                <input class="form-control" id="currentPassword" name="current_password" type="password" autocomplete="current-password" required>
                                <button type="button" data-toggle-password="currentPassword" aria-label="Show current password"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="newPassword">New Password</label>
                            <div class="password-field">
                                <input class="form-control" id="newPassword" name="new_password" type="password" autocomplete="new-password" required>
                                <button type="button" data-toggle-password="newPassword" aria-label="Show new password"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="confirmPassword">Confirm New Password</label>
                            <div class="password-field">
                                <input class="form-control" id="confirmPassword" name="confirm_password" type="password" autocomplete="new-password" required>
                                <button type="button" data-toggle-password="confirmPassword" aria-label="Show confirmed password"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="password-strength">
                        <div class="password-strength-bar"><span id="passwordStrengthBar"></span></div>
                        <strong id="passwordStrengthLabel">Very Weak</strong>
                    </div>
                    <div class="password-requirements">
                        <span data-password-rule="length"><i class="fa-regular fa-circle"></i>Minimum 8 characters</span>
                        <span data-password-rule="upper"><i class="fa-regular fa-circle"></i>At least one uppercase letter</span>
                        <span data-password-rule="lower"><i class="fa-regular fa-circle"></i>At least one lowercase letter</span>
                        <span data-password-rule="number"><i class="fa-regular fa-circle"></i>At least one number</span>
                        <span data-password-rule="special"><i class="fa-regular fa-circle"></i>At least one special character</span>
                    </div>
                    <div class="profile-form-actions">
                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i>Update Password</button>
                        <button class="btn btn-outline-brand" type="reset" data-profile-reset><i class="fa-solid fa-rotate-left"></i>Reset</button>
                        <a class="btn btn-light" href="<?php echo e(route_url('profile')); ?>" data-profile-cancel>Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

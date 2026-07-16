<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/view-helpers.php';

$pageTitle = 'Login | Umachi Oil and Gas Filling Station Staff & Activity Management System';
$companyName = 'Umachi Oil and Gas ';
$systemName = 'Staff & Activity Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Secure staff login for the Filling Station Staff and Activity Management System.">
    <title><?php echo e($pageTitle); ?></title>

    <link rel="shortcut icon" href="<?php echo e(asset_url('images/favicon.png')); ?>">
    <link rel="apple-touch-icon" href="<?php echo e(asset_url('images/apple-icon.png')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset_url('vendor/bootstrap/css/bootstrap.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset_url('vendor/fontawesome/css/all.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset_url('css/login.css')); ?>">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <a href="<?php echo e(route_url('dashboard')); ?>" class="auth-back-button" id="authBackButton" aria-label="Go back">
            <i class="fa-solid fa-arrow-left"></i>
        </a>

        <section class="auth-brand" aria-labelledby="loginSystemTitle">
            <img src="<?php echo e(asset_url('images/logo.png')); ?>" alt="<?php echo e($companyName); ?> logo" class="auth-logo">
            <div>
                <p><?php echo e($systemName); ?></p>
            </div>
        </section>

        <section class="auth-card" aria-labelledby="loginTitle">
            <div class="auth-card__header">
                <span class="auth-kicker">Secure Access</span>
                <h2 id="loginTitle">Welcome Back</h2>
                <p>Sign in to access your account.</p>
            </div>

            <form method="POST" action="<?php echo e(route_url('auth/login')); ?>" class="auth-form needs-validation" id="loginForm" autocomplete="off" novalidate>
                <?php echo csrf_field(); ?>
                <div class="auth-field">
                    <label class="form-label" for="username">Company Email or Employee ID</label>
                    <div class="auth-input-group">
                        <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                        <input type="text" id="username" name="username" class="form-control" placeholder="e.g. UMACHI-0001 or name@company.com" autocomplete="username" value="<?php echo e((string) ($oldUsername ?? '')); ?>" required>
                    </div>
                    <div class="invalid-feedback">Company email or Employee ID is required.</div>
                </div>

                <div class="auth-field">
                    <label class="form-label" for="role">Role Selection</label>
                    <div class="auth-input-group">
                        <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-id-badge"></i></span>
                        <select id="role" name="role" class="form-select" required>
                            <option value="">Select your role</option>
                            <option value="Admin" <?php echo ($oldRole ?? '') === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="Manager" <?php echo ($oldRole ?? '') === 'Manager' ? 'selected' : ''; ?>>Manager</option>
                            <option value="Supervisor" <?php echo ($oldRole ?? '') === 'Supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                            <option value="Pump Attendant" <?php echo ($oldRole ?? '') === 'Pump Attendant' ? 'selected' : ''; ?>>Pump Attendant</option>
                            <option value="Cashier" <?php echo ($oldRole ?? '') === 'Cashier' ? 'selected' : ''; ?>>Cashier</option>
                            <option value="Security" <?php echo ($oldRole ?? '') === 'Security' ? 'selected' : ''; ?>>Security</option>
                            <option value="Accountant" <?php echo ($oldRole ?? '') === 'Accountant' ? 'selected' : ''; ?>>Accountant</option>
                            <option value="Driver" <?php echo ($oldRole ?? '') === 'Driver' ? 'selected' : ''; ?>>Driver</option>

                        </select>
                    </div>
                    <div class="invalid-feedback">Please select your role.</div>
                </div>

                <div class="auth-field">
                    <label class="form-label" for="password">Password</label>
                    <div class="auth-input-group">
                        <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="auth-password-toggle" id="togglePassword" aria-label="Show password">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">Password is required.</div>
                </div>

                <button type="submit" class="btn auth-submit" id="loginButton">
                    <span class="auth-submit__text">Sign In</span>
                    <span class="auth-submit__loader" aria-hidden="true"></span>
                </button>

                <!--<button type="submit" class="btn auth-submit" id="loginButton">
                    <span class="auth-submit__text">Sign In</span>
                    <span class="auth-submit__loader" aria-hidden="true"></span>
                </button>-->

                <a href="#" class="auth-forgot-link" id="forgotPasswordLink">Forgot Password?</a>
            </form>
        </section>

        <footer class="auth-footer">
            <span>&copy; 2026 Umachi Oil and Gas.</span>
            <span>All Rights Reserved.</span>
            <span>Version 1.0.0</span>
        </footer>
    </main>

    <script src="<?php echo e(asset_url('vendor/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if (!empty($authError)): ?>
        <script>
            window.authLoginMessage = <?php echo json_encode((string) $authError, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        </script>
    <?php endif; ?>
    <script src="<?php echo e(asset_url('js/login.js')); ?>"></script>
</body>
</html>


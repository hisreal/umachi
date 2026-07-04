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

            <form method="POST" action="<?php echo e(route_url('clock-in')); ?>" class="auth-form needs-validation" autocomplete="off" novalidate>
                <div class="auth-field">
                    <label class="form-label" for="username">Username</label>
                    <div class="auth-input-group">
                        <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                        <input type="text" autocomplete="off" id="username" class="form-control" placeholder="Enter your username" autocomplete="username" required>
                    </div>
                    <div class="invalid-feedback">Username is required.</div>
                </div>

                <div class="auth-field">
                    <label class="form-label" for="role">Role Selection</label>
                    <div class="auth-input-group">
                        <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-id-badge"></i></span>
                        <select id="role" class="form-select" required>
                            <option value="">Select your role</option>
                            <option value="Admin">Admin</option>
                            <option value="Manager">Manager</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Pump Attendant">Pump Attendant</option>
                            <option value="Cashier">Cashier</option>
                            <option value="Security">Security</option>
                            <option value="Accountant">Accountant</option>
                        </select>
                    </div>
                    <div class="invalid-feedback">Please select your role.</div>
                </div>

                <div class="auth-field">
                    <label class="form-label" for="password">Password</label>
                    <div class="auth-input-group">
                        <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" id="password" class="form-control" placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="auth-password-toggle" id="togglePassword" aria-label="Show password">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">Password is required.</div>
                </div>

                <button type="submit" class="btn auth-submit">
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
            <span>&copy; 2026 ABC Filling Station.</span>
            <span>All Rights Reserved.</span>
            <span>Version 1.0.0</span>
        </footer>
    </main>

    <script src="<?php echo e(asset_url('vendor/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo e(asset_url('js/login.js')); ?>"></script>
</body>
</html>

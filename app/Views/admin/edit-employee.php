<?php

declare(strict_types=1);

$pageTitle = 'Edit Employee | FuelOps Admin Dashboard';
$pageHeading = 'Edit Employee';
$topbarSubtitle = 'Admin Dashboard';
$currentRoute = 'admin/edit-employee';
$extraStyles = ['css/clock-in.css', 'css/admin-dashboard.css', 'css/employee-management.css'];
$extraScripts = ['js/admin-dashboard.js', 'js/employee-management.js'];
$sidebarVariant = 'admin-sidebar';
$sidebarHomeRoute = 'admin/dashboard';
$sidebarBrandTitle = 'FuelOps';
$sidebarBrandSubtitle = 'Admin Panel';
$navItems = require __DIR__ . '/../includes/admin-nav.php';
$adminUser = ['name' => 'Administrator', 'role' => 'System Administrator'];
$employee = ['name' => $adminUser['name'], 'role' => $adminUser['role']];
$attendantName = $adminUser['name'];
$attendantRole = $adminUser['role'];

require __DIR__ . '/employee-data.php';
$formMode = 'edit';
$formEmployee = $selectedEmployee;
$employeeSuccess = \App\Core\Session::pullFlash('employee_success');
$employeeError = \App\Core\Session::pullFlash('employee_error');
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page employee-module-page">
    <section class="clock-hero employee-hero"><div class="container-fluid"><nav class="employee-breadcrumb" aria-label="Breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><a href="<?php echo e(route_url('admin/employees')); ?>">Employee Management</a><i class="fa-solid fa-chevron-right"></i><span>Edit Employee</span></nav><div class="clock-hero__content employee-hero-card"><div><span class="eyebrow">Employee Record</span><h1>Edit <?php echo e($selectedEmployee['name']); ?></h1><p>Update employee information in demo mode. Backend persistence will be connected later.</p></div><span class="employee-hero-icon"><i class="fa-solid fa-user-pen"></i></span></div></div></section>
    <section class="container-fluid clock-workspace">
        <?php if (is_string($employeeSuccess) && $employeeSuccess !== ''): ?><div class="alert alert-success"><?php echo e($employeeSuccess); ?></div><?php endif; ?>
        <?php if (is_string($employeeError) && $employeeError !== ''): ?><div class="alert alert-danger"><?php echo e($employeeError); ?></div><?php endif; ?>
        <?php require __DIR__ . '/employee-form.php'; ?>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

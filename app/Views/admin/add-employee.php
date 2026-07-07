<?php

declare(strict_types=1);

$pageTitle = 'Add Employee | FuelOps Admin Dashboard';
$pageHeading = 'Add Employee';
$topbarSubtitle = 'Admin Dashboard';
$currentRoute = 'admin/add-employee';
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
$formMode = 'add';
$formEmployee = [
    'id' => 'EMP007', 'first_name' => '', 'last_name' => '', 'gender' => '', 'dob' => '', 'marital_status' => '', 'phone' => '', 'email' => '', 'address' => '', 'emergency_contact_name' => '', 'emergency_contact_phone' => '', 'department' => '', 'role' => '', 'employment_type' => '', 'status' => 'Active', 'date_joined' => date('Y-m-d'), 'supervisor' => '', 'shift' => '', 'salary' => '', 'allowance' => '', 'bank_name' => '', 'account_name' => '', 'account_number' => '', 'photo' => 'images/sample-passport.svg',
];
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page employee-module-page">
    <section class="clock-hero employee-hero"><div class="container-fluid"><nav class="employee-breadcrumb" aria-label="Breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><a href="<?php echo e(route_url('admin/employees')); ?>">Employee Management</a><i class="fa-solid fa-chevron-right"></i><span>Add Employee</span></nav><div class="clock-hero__content employee-hero-card"><div><span class="eyebrow">Employee Registration</span><h1>Add Employee</h1><p>Create a new employee record using frontend-only sample form handling.</p></div><span class="employee-hero-icon"><i class="fa-solid fa-user-plus"></i></span></div></div></section>
    <section class="container-fluid clock-workspace">
        <?php require __DIR__ . '/employee-form.php'; ?>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

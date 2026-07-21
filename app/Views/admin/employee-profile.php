<?php

declare(strict_types=1);

$pageTitle = 'Employee Profile | FuelOps Admin Dashboard';
$pageHeading = 'Employee Profile';
$topbarSubtitle = 'Admin Dashboard';
$currentRoute = 'admin/employee-profile';
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

// ============================================
// DATABASE PLACEHOLDER
// Retrieve employee attendance, leave, and duty
// records from MySQL.
// ============================================

require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page employee-module-page">
    <section class="clock-hero employee-hero"><div class="container-fluid"><nav class="employee-breadcrumb" aria-label="Breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><a href="<?php echo e(route_url('admin/employees')); ?>">Employee Management</a><i class="fa-solid fa-chevron-right"></i><span>Employee Profile</span></nav><div class="clock-hero__content employee-hero-card"><div><span class="eyebrow">Employee Record</span><h1><?php echo e($selectedEmployee['name']); ?></h1><p>Complete profile overview using static sample data for future database integration.</p></div><div class="employee-profile-actions"><a class="btn btn-light" href="<?php echo e(route_url('admin/edit-employee')); ?>&employee=<?php echo e($selectedEmployee['id']); ?>"><i class="fa-solid fa-pen"></i>Edit Profile</a><button class="btn btn-light" type="button" data-profile-action="print"><i class="fa-solid fa-print"></i>Print Profile</button><a class="btn btn-outline-light" href="<?php echo e(route_url('admin/employees')); ?>">Back to Employee List</a></div></div></div></section>
    <section class="container-fluid clock-workspace">
        <div class="row g-4 align-items-start">
            <div class="col-12 col-xl-4">
                <article class="app-card card employee-profile-card">
                    <img src="<?php echo e(asset_url($selectedEmployee['photo'])); ?>" alt="Passport photograph of <?php echo e($selectedEmployee['name']); ?>">
                    <h2><?php echo e($selectedEmployee['name']); ?></h2>
                    <p><?php echo e($selectedEmployee['id']); ?> · <?php echo e($selectedEmployee['role']); ?></p>
                    <span class="table-badge <?php echo e($statusClasses[$selectedEmployee['status']] ?? 'employee-status--active'); ?>"><?php echo e($selectedEmployee['status']); ?></span>
                    <div class="employee-profile-meta"><div><span>Department</span><strong><?php echo e($selectedEmployee['department']); ?></strong></div><div><span>Date Joined</span><strong><?php echo e(format_date($selectedEmployee['date_joined'] ?? null)); ?></strong></div><div><span>Duration of Service</span><strong><?php echo e($selectedEmployee['service_duration'] ?? \App\Services\ServiceDurationFormatter::format((string) ($selectedEmployee['date_joined'] ?? ''))); ?></strong></div></div>
                    <a class="btn btn-outline-brand w-100" href="<?php echo e(route_url('admin/employee-documents')); ?>&employee=<?php echo e($selectedEmployee['id']); ?>"><i class="fa-solid fa-folder-open"></i>Manage Documents</a>
                </article>
            </div>
            <div class="col-12 col-xl-8">
                <div class="row g-4">
                    <div class="col-12 col-lg-6"><article class="app-card card employee-info-card"><h2>Personal Information</h2><div class="employee-info-list"><div><span>Gender</span><strong><?php echo e($selectedEmployee['gender']); ?></strong></div><div><span>Date of Birth</span><strong><?php echo e(format_date($selectedEmployee['dob'] ?? null)); ?></strong></div><div><span>Phone Number</span><strong><?php echo e($selectedEmployee['phone']); ?></strong></div><div><span>Email</span><strong><?php echo e($selectedEmployee['email']); ?></strong></div><div><span>Address</span><strong><?php echo e($selectedEmployee['address']); ?></strong></div><div><span>Emergency Contact</span><strong><?php echo e($selectedEmployee['emergency_contact']); ?></strong></div></div></article></div>
                    <div class="col-12 col-lg-6"><article class="app-card card employee-info-card"><h2>Employment Information</h2><div class="employee-info-list"><div><span>Department</span><strong><?php echo e($selectedEmployee['department']); ?></strong></div><div><span>Role</span><strong><?php echo e($selectedEmployee['role']); ?></strong></div><div><span>Salary</span><strong>NGN <?php echo e(number_format($selectedEmployee['salary'])); ?> / Month</strong></div><div><span>Supervisor</span><strong><?php echo e($selectedEmployee['supervisor']); ?></strong></div><div><span>Shift</span><strong><?php echo e($selectedEmployee['shift']); ?></strong></div><div><span>Employment Type</span><strong><?php echo e($selectedEmployee['employment_type']); ?></strong></div></div></article></div>
                </div>
            </div>
        </div>

       
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>


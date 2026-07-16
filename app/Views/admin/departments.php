<?php

declare(strict_types=1);

$pageTitle = 'Departments | FuelOps Admin Dashboard';
$pageHeading = 'Departments';
$topbarSubtitle = 'Admin Dashboard';
$currentRoute = 'admin/departments';
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

try {
    $departmentRows = (new \App\Models\Employee())->departmentsWithCounts();
} catch (Throwable) {
    $departmentRows = [
        ['id' => 1, 'name' => 'Administration', 'description' => 'Administrative office and management.', 'status' => 'active', 'employee_count' => 0],
        ['id' => 2, 'name' => 'Operations', 'description' => 'Forecourt and pump operations.', 'status' => 'active', 'employee_count' => 0],
    ];
}

$employeeCsrf = (new \App\Services\AuthService())->csrfToken();
$employeeSuccess = \App\Core\Session::pullFlash('employee_success');
$employeeError = \App\Core\Session::pullFlash('employee_error');
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page employee-module-page">
    <section class="clock-hero employee-hero"><div class="container-fluid"><nav class="employee-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><a href="<?php echo e(route_url('admin/employees')); ?>">Employee Management</a><i class="fa-solid fa-chevron-right"></i><span>Departments</span></nav><div class="clock-hero__content employee-hero-card"><div><span class="eyebrow">Organization Setup</span><h1>Departments</h1><p>Create and manage departments used by employee records and role assignments.</p></div><span class="employee-hero-icon"><i class="fa-solid fa-building"></i></span></div></div></section>
    <section class="container-fluid clock-workspace">
        <?php if (is_string($employeeSuccess) && $employeeSuccess !== ''): ?><div class="alert alert-success"><?php echo e($employeeSuccess); ?></div><?php endif; ?>
        <?php if (is_string($employeeError) && $employeeError !== ''): ?><div class="alert alert-danger"><?php echo e($employeeError); ?></div><?php endif; ?>
        <div class="row g-4">
            <div class="col-xl-4"><form class="app-card card employee-form-section" method="post" action="<?php echo e(route_url('admin/departments/save')); ?>"><input type="hidden" name="_csrf_token" value="<?php echo e($employeeCsrf); ?>"><div class="employee-section-heading"><span><i class="fa-solid fa-plus"></i></span><div><small>Department Form</small><h2>Add Department</h2></div></div><label class="form-label" for="departmentName">Department Name</label><input class="form-control mb-3" id="departmentName" name="name" required><label class="form-label" for="departmentDescription">Description</label><textarea class="form-control mb-3" id="departmentDescription" name="description" rows="4"></textarea><label class="form-label" for="departmentStatus">Status</label><select class="form-select mb-3" id="departmentStatus" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select><button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-check"></i>Save Department</button></form></div>
            <div class="col-xl-8"><article class="app-card card employee-list-card"><div class="employee-toolbar"><div><span class="eyebrow">Department Records</span><h2>All Departments</h2></div></div><div class="table-responsive"><table class="table attendance-table employee-table align-middle"><thead><tr><th>Department</th><th>Description</th><th>Employees</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach ($departmentRows as $department): ?><tr><td><strong><?php echo e($department['name']); ?></strong></td><td><?php echo e((string) ($department['description'] ?? '')); ?></td><td><?php echo e((string) $department['employee_count']); ?></td><td><span class="table-badge <?php echo $department['status'] === 'active' ? 'employee-status--active' : 'employee-status--inactive'; ?>"><?php echo e(ucfirst((string) $department['status'])); ?></span></td><td><form method="post" action="<?php echo e(route_url('admin/departments/deactivate')); ?>" class="d-inline"><input type="hidden" name="_csrf_token" value="<?php echo e($employeeCsrf); ?>"><input type="hidden" name="department_id" value="<?php echo e((string) $department['id']); ?>"><button class="btn btn-sm btn-light" type="submit"><i class="fa-solid fa-toggle-off"></i></button></form></td></tr><?php endforeach; ?></tbody></table></div></article></div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
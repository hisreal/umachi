<?php

declare(strict_types=1);

$pageTitle = 'Employee Management | FuelOps Admin Dashboard';
$pageHeading = 'Employee Management';
$topbarSubtitle = 'Admin Dashboard';
$currentRoute = $currentRoute ?? 'admin/employees';
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
$employeeCsrf = (new \App\Services\AuthService())->csrfToken();
$employeeSuccess = \App\Core\Session::pullFlash('employee_success');
$employeeError = \App\Core\Session::pullFlash('employee_error');
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page employee-module-page">
    <section class="clock-hero employee-hero">
        <div class="container-fluid">
            <nav class="employee-breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a>
                <i class="fa-solid fa-chevron-right"></i>
                <span>Employee Management</span>
            </nav>
            <div class="clock-hero__content employee-hero-card">
                <div>
                    <span class="eyebrow">Admin Module</span>
                    <h1><?php echo e($pageHeading); ?></h1>
                    <p>Manage employee records, roles, status, and account actions from the database.</p>
                </div>
                <a class="btn btn-light employee-hero-action" href="<?php echo e(route_url('admin/add-employee')); ?>">
                    <i class="fa-solid fa-user-plus"></i>
                    Add Employee
                </a>
            </div>
        </div>
    </section>

    <section class="container-fluid clock-workspace">
        <?php if (is_string($employeeSuccess) && $employeeSuccess !== ''): ?><div class="alert alert-success"><?php echo e($employeeSuccess); ?></div><?php endif; ?>
        <?php if (is_string($employeeError) && $employeeError !== ''): ?><div class="alert alert-danger"><?php echo e($employeeError); ?></div><?php endif; ?>

        <div class="employee-summary-grid">
            <?php foreach ($employeeStats as $stat): ?>
                <article class="employee-summary-card employee-summary-card--<?php echo e($stat['tone']); ?>">
                    <span><i class="<?php echo e($stat['icon']); ?>"></i></span>
                    <div>
                        <small><?php echo e($stat['label']); ?></small>
                        <strong><?php echo e((string) $stat['value']); ?></strong>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <article class="app-card card employee-list-card mt-4">
            <div class="employee-toolbar">
                <div>
                    <span class="eyebrow">Employee Directory</span>
                    <h2>All Employees</h2>
                </div>
                <div class="employee-toolbar-actions">
                    <div class="dropdown">
                        <button class="btn btn-outline-brand dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-download"></i>
                            Export
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><button class="dropdown-item" type="button" data-export-type="PDF">Export PDF</button></li>
                            <li><button class="dropdown-item" type="button" data-export-type="Excel">Export Excel</button></li>
                            <li><button class="dropdown-item" type="button" data-export-type="CSV">Export CSV</button></li>
                        </ul>
                    </div>
                    <a class="btn btn-primary" href="<?php echo e(route_url('admin/add-employee')); ?>">
                        <i class="fa-solid fa-plus"></i>
                        Add Employee
                    </a>
                </div>
            </div>

            <div class="employee-filter-grid" aria-label="Employee search and filters">
                <div class="filter-control filter-control--wide">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" id="employeeSearch" placeholder="Search by name, ID, phone, or email">
                </div>
                <select class="form-select" id="departmentFilter" aria-label="Filter by department">
                    <option value="">All departments</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo e($department); ?>"><?php echo e($department); ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="form-select" id="roleFilter" aria-label="Filter by role">
                    <option value="">All roles</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo e($role); ?>"><?php echo e($role); ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="form-select" id="statusFilter" aria-label="Filter by status">
                    <option value="">All statuses</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?php echo e($status); ?>"><?php echo e($status); ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="form-select" id="genderFilter" aria-label="Filter by gender">
                    <option value="">All genders</option>
                    <?php foreach ($genders as $gender): ?>
                        <option value="<?php echo e($gender); ?>"><?php echo e($gender); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="table-responsive employee-table-wrap">
                <table class="table attendance-table employee-table align-middle">
                    <thead>
                        <tr>
                            <th>Passport</th>
                            <th>Employee ID</th>
                            <th>Full Name</th>
                            <th>Gender</th>
                            <th>Phone Number</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Employment Status</th>
                            <th>Date Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTableBody">
                        <?php foreach ($employees as $staff): ?>
                            <?php $isDeletedEmployee = ($staff['is_deleted'] ?? false) === true; ?>
                            <tr data-employee-row
                                data-search="<?php echo e(strtolower($staff['name'] . ' ' . $staff['id'] . ' ' . $staff['phone'] . ' ' . $staff['email'])); ?>"
                                data-department="<?php echo e($staff['department']); ?>"
                                data-role="<?php echo e($staff['role']); ?>"
                                data-status="<?php echo e($staff['status']); ?>"
                                data-gender="<?php echo e($staff['gender']); ?>">
                                <td><img class="employee-avatar-sm" src="<?php echo e(asset_url($staff['photo'])); ?>" alt="Passport photograph of <?php echo e($staff['name']); ?>"></td>
                                <td><strong><?php echo e($staff['id']); ?></strong></td>
                                <td><?php echo e($staff['name']); ?></td>
                                <td><?php echo e($staff['gender']); ?></td>
                                <td><?php echo e($staff['phone']); ?></td>
                                <td><?php echo e($staff['department']); ?></td>
                                <td><?php echo e($staff['role']); ?></td>
                                <td><span class="table-badge <?php echo e($statusClasses[$staff['status']] ?? 'employee-status--active'); ?>"><?php echo e($staff['status']); ?></span></td>
                                <td><?php echo e(format_date($staff['date_joined'] ?? null)); ?></td>
                                <td>
                                    <div class="employee-actions">
                                        <?php if (!$isDeletedEmployee): ?>
                                            <a class="btn btn-sm btn-light" href="<?php echo e(route_url('admin/employee-profile')); ?>&employee=<?php echo e($staff['id']); ?>" title="View Profile"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-sm btn-light" href="<?php echo e(route_url('admin/edit-employee')); ?>&employee=<?php echo e($staff['id']); ?>" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                            <form method="post" action="<?php echo e(route_url('admin/employees/toggle-account')); ?>" class="d-inline" data-confirm-submit="Change this employee account status?"><input type="hidden" name="_csrf_token" value="<?php echo e($employeeCsrf); ?>"><input type="hidden" name="employee" value="<?php echo e($staff['id']); ?>"><button class="btn btn-sm btn-light" type="submit" title="Activate or deactivate"><i class="fa-solid fa-power-off"></i></button></form>
                                            <form method="post" action="<?php echo e(route_url('admin/employees/reset-password')); ?>" class="d-inline" data-confirm-submit="Reset this employee password?"><input type="hidden" name="_csrf_token" value="<?php echo e($employeeCsrf); ?>"><input type="hidden" name="employee" value="<?php echo e($staff['id']); ?>"><button class="btn btn-sm btn-light" type="submit" title="Reset password"><i class="fa-solid fa-key"></i></button></form>
                                            <form method="post" action="<?php echo e(route_url('admin/employees/delete')); ?>" class="d-inline" data-confirm-submit="Delete this employee record?"><input type="hidden" name="_csrf_token" value="<?php echo e($employeeCsrf); ?>"><input type="hidden" name="employee" value="<?php echo e($staff['id']); ?>"><button class="btn btn-sm btn-light employee-action-danger" type="submit" title="Delete"><i class="fa-solid fa-trash"></i></button></form>
                                        <?php else: ?>
                                            <span class="btn btn-sm btn-light disabled" title="Deleted record"><i class="fa-solid fa-lock"></i></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="employee-pagination">
                <span id="employeePageSummary">Showing database records</span>
                <div>
                    <button class="btn btn-outline-brand btn-sm" type="button" id="prevEmployeePage"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="btn btn-outline-brand btn-sm" type="button" id="nextEmployeePage"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
        </article>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>


<?php

declare(strict_types=1);

$pageTitle = 'Duty Roster Management | FuelOps Admin Dashboard';
$pageHeading = 'Duty Roster Management';
$currentRoute = 'admin/duty-roster';
require __DIR__ . '/duty-roster-setup.php';
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page duty-module-page">
    <section class="clock-hero duty-hero">
        <div class="container-fluid">
            <nav class="duty-breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a>
                <i class="fa-solid fa-chevron-right"></i>
                <span>Duty Roster</span>
            </nav>
            <div class="clock-hero__content duty-hero-card">
                <div>
                    <span class="eyebrow">Workforce Scheduling</span>
                    <h1>Duty Roster Management</h1>
                    <p>Create, assign, and monitor staff schedules, shifts, and pump duty allocations.</p>
                </div>
                <a class="btn btn-light" href="<?php echo e(route_url('admin/pump-allocation')); ?>">
                    <i class="fa-solid fa-plus"></i>Assign Duty
                </a>
            </div>
        </div>
    </section>

    <section class="container-fluid clock-workspace">
        <div class="duty-summary-grid">
            <?php foreach ($dutyStats as $card): ?>
                <article class="duty-summary-card duty-summary-card--<?php echo e($card['tone']); ?>">
                    <span><i class="<?php echo e($card['icon']); ?>"></i></span>
                    <div>
                        <small><?php echo e($card['label']); ?></small>
                        <strong><?php echo e($card['value']); ?></strong>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <article class="app-card card duty-table-card mt-4">
            <div class="duty-toolbar">
                <div>
                    <span class="eyebrow">Daily Roster</span>
                    <h2>Assigned Duties</h2>
                </div>
                <a class="btn btn-primary" href="<?php echo e(route_url('admin/pump-allocation')); ?>">
                    <i class="fa-solid fa-user-check"></i>Assign Duty
                </a>
            </div>

            <div class="duty-filter-grid duty-filter-grid--roster">
                <div class="filter-control filter-control--wide">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input id="dutyRosterSearch" type="search" placeholder="Search employee name or ID">
                </div>
                <input class="form-control" id="dutyDateFilter" type="date" value="2026-07-08">
                <select class="form-select" id="dutyShiftFilter"><option value="">All shifts</option><?php foreach ($shiftNames as $shift): ?><option value="<?php echo e($shift); ?>"><?php echo e($shift); ?></option><?php endforeach; ?></select>
                <select class="form-select" id="dutyDepartmentFilter"><option value="">All departments</option><?php foreach ($departments as $department): ?><option value="<?php echo e($department); ?>"><?php echo e($department); ?></option><?php endforeach; ?></select>
                <select class="form-select" id="dutyRoleFilter"><option value="">All roles</option><?php foreach ($roles as $role): ?><option value="<?php echo e($role); ?>"><?php echo e($role); ?></option><?php endforeach; ?></select>
                <select class="form-select" id="dutyPumpFilter"><option value="">All pumps</option><?php foreach ($pumps as $pump): ?><option value="<?php echo e($pump); ?>"><?php echo e($pump); ?></option><?php endforeach; ?></select>
                <select class="form-select" id="dutyFuelFilter"><option value="">All fuel types</option><?php foreach ($fuelTypes as $fuelType): ?><option value="<?php echo e($fuelType); ?>"><?php echo e($fuelType); ?></option><?php endforeach; ?></select>
            </div>

            <div class="table-responsive">
                <table class="table attendance-table duty-table align-middle">
                    <thead>
                        <tr>
                            <th>Date</th><th>Employee</th><th>Department</th><th>Role</th><th>Shift</th><th>Pump</th><th>Fuel Type</th><th>Reporting Time</th><th>Closing Time</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="dutyRosterBody">
                        <?php foreach ($rosterAssignments as $assignment): ?>
                            <tr data-duty-row data-search="<?php echo e(strtolower($assignment['employee'] . ' ' . $assignment['employee_id'])); ?>" data-date="<?php echo e($assignment['date']); ?>" data-shift="<?php echo e($assignment['shift']); ?>" data-department="<?php echo e($assignment['department']); ?>" data-role="<?php echo e($assignment['role']); ?>" data-pump="<?php echo e($assignment['pump']); ?>" data-fuel="<?php echo e($assignment['fuel_type']); ?>">
                                <td><?php echo e(date('d M Y', strtotime($assignment['date']))); ?></td>
                                <td><strong><?php echo e($assignment['employee']); ?></strong><small><?php echo e($assignment['employee_id']); ?></small></td>
                                <td><?php echo e($assignment['department']); ?></td>
                                <td><?php echo e($assignment['role']); ?></td>
                                <td><?php echo e($assignment['shift']); ?></td>
                                <td><?php echo e($assignment['pump']); ?></td>
                                <td><?php echo e($assignment['fuel_type']); ?></td>
                                <td><?php echo e($assignment['reporting']); ?></td>
                                <td><?php echo e($assignment['closing']); ?></td>
                                <td><span class="table-badge <?php echo e($dutyStatusClasses[$assignment['status']] ?? 'duty-status--scheduled'); ?>"><?php echo e($assignment['status']); ?></span></td>
                                <td>
                                    <div class="duty-actions">
                                        <button class="btn btn-sm btn-light" data-duty-action="view" data-duty-name="<?php echo e($assignment['employee']); ?>" type="button" title="View"><i class="fa-solid fa-eye"></i></button>
                                        <button class="btn btn-sm btn-light" data-duty-action="edit" data-duty-name="<?php echo e($assignment['employee']); ?>" type="button" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                        <button class="btn btn-sm btn-light duty-action-danger" data-duty-action="delete" data-duty-name="<?php echo e($assignment['employee']); ?>" type="button" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="duty-pagination"><span id="dutyRosterSummary">Showing sample roster records</span><div><button class="btn btn-outline-brand btn-sm" id="prevDutyPage" type="button"><i class="fa-solid fa-chevron-left"></i></button><button class="btn btn-outline-brand btn-sm" id="nextDutyPage" type="button"><i class="fa-solid fa-chevron-right"></i></button></div></div>
        </article>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
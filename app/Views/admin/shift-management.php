<?php

declare(strict_types=1);

$pageTitle = 'Shift Management | FuelOps Admin Dashboard';
$pageHeading = 'Shift Management';
$currentRoute = 'admin/shift-management';
require __DIR__ . '/duty-roster-setup.php';
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page duty-module-page">
    <section class="clock-hero duty-hero"><div class="container-fluid"><nav class="duty-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Duty Roster</span><i class="fa-solid fa-chevron-right"></i><span>Shift Management</span></nav><div class="clock-hero__content duty-hero-card"><div><span class="eyebrow">Shift Configuration</span><h1>Shift Management</h1><p>Configure working hours and review current employee shift assignments.</p></div><span class="duty-hero-icon"><i class="fa-solid fa-business-time"></i></span></div></div></section>
    <section class="container-fluid clock-workspace">
        <div class="duty-summary-grid duty-summary-grid--four"><?php foreach ($shiftStats as $card): ?><article class="duty-summary-card duty-summary-card--<?php echo e($card['tone']); ?>"><span><i class="<?php echo e($card['icon']); ?>"></i></span><div><small><?php echo e($card['label']); ?></small><strong><?php echo e($card['value']); ?></strong></div></article><?php endforeach; ?></div>

        <form class="duty-form needs-validation mt-4" id="shiftConfigForm" novalidate>
            <div class="row g-4">
                <?php foreach ($shiftConfigurations as $index => $shift): ?>
                    <div class="col-lg-6">
                        <article class="app-card card duty-form-card h-100">
                            <div class="duty-section-heading"><span><i class="<?php echo $shift['name'] === 'Morning Shift' ? 'fa-solid fa-sun' : 'fa-solid fa-moon'; ?>"></i></span><div><small>Working Shift</small><h2><?php echo e($shift['name']); ?></h2></div></div>
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label" for="shiftName<?php echo $index; ?>">Shift Name</label><input class="form-control" id="shiftName<?php echo $index; ?>" value="<?php echo e($shift['name']); ?>" required></div>
                                <div class="col-md-6"><label class="form-label" for="shiftStatus<?php echo $index; ?>">Status</label><select class="form-select" id="shiftStatus<?php echo $index; ?>" required><option selected><?php echo e($shift['status']); ?></option><option>Inactive</option></select></div>
                                <div class="col-md-4"><label class="form-label" for="shiftStart<?php echo $index; ?>">Start Time</label><input class="form-control" type="time" id="shiftStart<?php echo $index; ?>" value="<?php echo e($shift['start']); ?>" required></div>
                                <div class="col-md-4"><label class="form-label" for="shiftEnd<?php echo $index; ?>">End Time</label><input class="form-control" type="time" id="shiftEnd<?php echo $index; ?>" value="<?php echo e($shift['end']); ?>" required></div>
                                <div class="col-md-4"><label class="form-label" for="shiftMax<?php echo $index; ?>">Maximum Employees</label><input class="form-control" type="number" id="shiftMax<?php echo $index; ?>" min="1" value="<?php echo e((string) $shift['max_employees']); ?>" required></div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="duty-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i>Save Changes</button><button class="btn btn-outline-brand" type="reset"><i class="fa-solid fa-rotate-left"></i>Reset</button></div>
        </form>

        <article class="app-card card duty-table-card mt-4">
            <div class="duty-toolbar"><div><span class="eyebrow">Shift Assignment</span><h2>Current Employees by Shift</h2></div></div>
            <div class="table-responsive"><table class="table attendance-table duty-table align-middle"><thead><tr><th>Employee</th><th>Department</th><th>Shift</th><th>Reporting Time</th><th>Closing Time</th><th>Status</th></tr></thead><tbody><?php foreach ($shiftAssignments as $assignment): ?><tr><td><strong><?php echo e($assignment['employee']); ?></strong></td><td><?php echo e($assignment['department']); ?></td><td><?php echo e($assignment['shift']); ?></td><td><?php echo e($assignment['reporting']); ?></td><td><?php echo e($assignment['closing']); ?></td><td><span class="table-badge <?php echo e($dutyStatusClasses[$assignment['status']] ?? 'duty-status--active'); ?>"><?php echo e($assignment['status']); ?></span></td></tr><?php endforeach; ?></tbody></table></div>
        </article>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
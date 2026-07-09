<?php

declare(strict_types=1);

$pageTitle = 'Pump Allocation | FuelOps Admin Dashboard';
$pageHeading = 'Pump Allocation';
$currentRoute = 'admin/pump-allocation';
require __DIR__ . '/duty-roster-setup.php';
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page duty-module-page">
    <section class="clock-hero duty-hero"><div class="container-fluid"><nav class="duty-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Duty Roster</span><i class="fa-solid fa-chevron-right"></i><span>Pump Allocation</span></nav><div class="clock-hero__content duty-hero-card"><div><span class="eyebrow">Pump Duty Assignment</span><h1>Pump Allocation</h1><p>Assign available employees to pumps, shifts, supervisors, and fuel types.</p></div><span class="duty-hero-icon"><i class="fa-solid fa-map-location-dot"></i></span></div></div></section>
    <section class="container-fluid clock-workspace">
        <div class="row g-4">
            <div class="col-xl-4">
                <article class="app-card card duty-workflow-card h-100">
                    <div class="duty-section-heading"><span><i class="fa-solid fa-route"></i></span><div><small>Assignment Workflow</small><h2>Assign Duty Flow</h2></div></div>
                    <ol class="assignment-flow"><li><span>1</span>Employee</li><li><span>2</span>Pump</li><li><span>3</span>Fuel Type</li><li><span>4</span>Date</li><li><span>5</span>Shift</li></ol>
                </article>
            </div>
            <div class="col-xl-8">
                <form class="app-card card duty-form-card duty-form needs-validation" id="pumpAllocationForm" novalidate>
                    <div class="duty-section-heading"><span><i class="fa-solid fa-user-plus"></i></span><div><small>New Allocation</small><h2>Assign Employee to Pump</h2></div></div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="allocationEmployee">Employee</label><select class="form-select" id="allocationEmployee" required><option value="">Select employee</option><?php foreach ($employees as $staff): ?><option value="<?php echo e($staff['id']); ?>" data-name="<?php echo e($staff['name']); ?>" data-department="<?php echo e($staff['department']); ?>" data-role="<?php echo e($staff['role']); ?>"><?php echo e($staff['name']); ?> (<?php echo e($staff['id']); ?>)</option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label class="form-label" for="allocationDepartment">Department</label><input class="form-control" id="allocationDepartment" readonly placeholder="Auto display"></div>
                        <div class="col-md-3"><label class="form-label" for="allocationRole">Role</label><input class="form-control" id="allocationRole" readonly placeholder="Auto display"></div>
                        <div class="col-md-4"><label class="form-label" for="allocationPump">Pump</label><select class="form-select" id="allocationPump" required><option value="">Select pump</option><?php foreach ($pumps as $pump): ?><option value="<?php echo e($pump); ?>"><?php echo e($pump); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4"><label class="form-label" for="allocationFuel">Fuel Type</label><select class="form-select" id="allocationFuel" required><option value="">Select fuel type</option><?php foreach ($fuelTypes as $fuelType): ?><option value="<?php echo e($fuelType); ?>"><?php echo e($fuelType); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4"><label class="form-label" for="allocationDate">Assignment Date</label><input class="form-control" type="date" id="allocationDate" value="2026-07-08" required></div>
                        <div class="col-md-4"><label class="form-label" for="allocationShift">Shift</label><select class="form-select" id="allocationShift" required><option value="">Select shift</option><?php foreach ($shiftNames as $shift): ?><option value="<?php echo e($shift); ?>"><?php echo e($shift); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4"><label class="form-label" for="allocationReporting">Reporting Time</label><input class="form-control" type="time" id="allocationReporting" value="06:00" required></div>
                        <div class="col-md-4"><label class="form-label" for="allocationClosing">Closing Time</label><input class="form-control" type="time" id="allocationClosing" value="14:00" required></div>
                        <div class="col-md-6"><label class="form-label" for="allocationSupervisor">Supervisor</label><select class="form-select" id="allocationSupervisor" required><option value="">Select supervisor</option><option>Chinedu Okafor</option><option>Fatima Yusuf</option><option>Emeka Nwosu</option></select></div>
                        <div class="col-md-6"><label class="form-label" for="allocationRemarks">Remarks</label><textarea class="form-control" id="allocationRemarks" rows="1" placeholder="Optional remarks"></textarea></div>
                    </div>
                    <div class="duty-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i>Assign Duty</button><button class="btn btn-outline-brand" type="reset"><i class="fa-solid fa-rotate-left"></i>Reset</button></div>
                </form>
            </div>
        </div>

        <article class="app-card card duty-table-card mt-4">
            <div class="duty-toolbar"><div><span class="eyebrow">Current Allocations</span><h2>Current Pump Allocation</h2></div></div>
            <div class="table-responsive"><table class="table attendance-table duty-table align-middle"><thead><tr><th>Date</th><th>Employee</th><th>Department</th><th>Pump</th><th>Fuel Type</th><th>Shift</th><th>Reporting Time</th><th>Closing Time</th><th>Supervisor</th><th>Status</th><th>Actions</th></tr></thead><tbody id="pumpAllocationBody"><?php foreach ($pumpAllocations as $allocation): ?><tr data-allocation-row data-employee-id="<?php echo e($allocation['employee_id']); ?>" data-employee="<?php echo e($allocation['employee']); ?>" data-date="<?php echo e($allocation['date']); ?>" data-shift="<?php echo e($allocation['shift']); ?>" data-pump="<?php echo e($allocation['pump']); ?>"><td><?php echo e(date('d M Y', strtotime($allocation['date']))); ?></td><td><strong><?php echo e($allocation['employee']); ?></strong><small><?php echo e($allocation['employee_id']); ?></small></td><td><?php echo e($allocation['department']); ?></td><td><?php echo e($allocation['pump']); ?></td><td><?php echo e($allocation['fuel_type']); ?></td><td><?php echo e($allocation['shift']); ?></td><td><?php echo e($allocation['reporting']); ?></td><td><?php echo e($allocation['closing']); ?></td><td><?php echo e($allocation['supervisor']); ?></td><td><span class="table-badge <?php echo e($dutyStatusClasses[$allocation['status']] ?? 'duty-status--scheduled'); ?>"><?php echo e($allocation['status']); ?></span></td><td><div class="duty-actions"><button class="btn btn-sm btn-light" data-duty-action="view" data-duty-name="<?php echo e($allocation['employee']); ?>" type="button" title="View"><i class="fa-solid fa-eye"></i></button><button class="btn btn-sm btn-light" data-duty-action="edit" data-duty-name="<?php echo e($allocation['employee']); ?>" type="button" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button><button class="btn btn-sm btn-light duty-action-danger" data-duty-action="delete" data-duty-name="<?php echo e($allocation['employee']); ?>" type="button" title="Delete"><i class="fa-solid fa-trash"></i></button></div></td></tr><?php endforeach; ?></tbody></table></div>
        </article>
    </section>
</main>
<script>
    window.existingPumpAllocations = <?php echo json_encode($pumpAllocations, JSON_THROW_ON_ERROR); ?>;
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
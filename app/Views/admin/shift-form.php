<?php

declare(strict_types=1);

$isEditMode = ($formMode ?? 'add') === 'edit';
$formShift = $formShift ?? $selectedShift;
$formAction = $isEditMode ? route_url('admin/shifts/update') : route_url('admin/shifts/store');
?>
<?php if (!empty($shiftError)): ?><div class="alert alert-danger" role="alert"><?php echo e($shiftError); ?></div><?php endif; ?>
<?php if (!empty($shiftSuccess)): ?><div class="alert alert-success" role="alert"><?php echo e($shiftSuccess); ?></div><?php endif; ?>
<form class="duty-form needs-validation" id="shiftConfigForm" method="post" action="<?php echo e($formAction); ?>" novalidate>
    <?php echo csrf_field(); ?>
    <?php if ($isEditMode): ?><input type="hidden" name="shift_id" value="<?php echo e((string) $formShift['id']); ?>"><?php endif; ?>
    <article class="app-card card duty-form-card">
        <div class="duty-section-heading"><span><i class="fa-solid fa-business-time"></i></span><div><small>Shift Information</small><h2><?php echo $isEditMode ? 'Edit Shift Details' : 'Create New Shift'; ?></h2></div></div>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="shiftCode">Shift Code</label><input class="form-control" id="shiftCode" name="shift_code" value="<?php echo e($formShift['shift_code']); ?>" placeholder="MORNING" required><div class="invalid-feedback">Shift code is required.</div></div>
            <div class="col-md-8"><label class="form-label" for="shiftName">Shift Name</label><input class="form-control" id="shiftName" name="shift_name" value="<?php echo e($formShift['shift_name']); ?>" placeholder="Morning Shift" required><div class="invalid-feedback">Shift name is required.</div></div>
            <div class="col-md-4"><label class="form-label" for="reportingTime">Reporting Time</label><input class="form-control" type="time" id="reportingTime" name="reporting_time" value="<?php echo e($formShift['reporting_time']); ?>" required><div class="invalid-feedback">Reporting time is required.</div></div>
            <div class="col-md-4"><label class="form-label" for="closingTime">Closing Time</label><input class="form-control" type="time" id="closingTime" name="closing_time" value="<?php echo e($formShift['closing_time']); ?>" required><div class="invalid-feedback">Closing time is required.</div></div>
            <div class="col-md-4"><label class="form-label" for="maximumEmployees">Maximum Employees</label><input class="form-control" type="number" id="maximumEmployees" name="maximum_employees" min="1" value="<?php echo e((string) $formShift['maximum_employees']); ?>" required><div class="invalid-feedback">Enter a valid maximum employee count.</div></div>
            <div class="col-md-6"><label class="form-label" for="gracePeriod">Grace Period (Minutes)</label><input class="form-control" type="number" id="gracePeriod" name="grace_period" min="0" max="120" value="<?php echo e((string) $formShift['grace_period']); ?>" required><div class="invalid-feedback">Grace period must be between 0 and 120.</div></div>
            <div class="col-md-6"><label class="form-label" for="shiftStatus">Status</label><select class="form-select" id="shiftStatus" name="status" required><?php foreach ($shiftStatuses as $status): ?><option value="<?php echo e($status); ?>" <?php echo $formShift['status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><label class="form-label" for="shiftDescription">Description</label><textarea class="form-control" id="shiftDescription" name="description" rows="4" placeholder="Optional shift description"><?php echo e($formShift['description']); ?></textarea></div>
        </div>
        <div class="duty-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i><?php echo $isEditMode ? 'Update Shift' : 'Save Shift'; ?></button><?php if (!$isEditMode): ?><button class="btn btn-outline-brand" type="reset"><i class="fa-solid fa-rotate-left"></i>Reset</button><?php endif; ?><a class="btn btn-light" href="<?php echo e(route_url('admin/shift-management')); ?>">Cancel</a></div>
    </article>
</form>

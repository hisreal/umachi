<?php

declare(strict_types=1);

$isEditMode = ($formMode ?? 'add') === 'edit';
$formPump = $formPump ?? [
    'pump_number' => '', 'pump_name' => '', 'fuel_type' => '', 'status' => 'Active', 'manufacturer' => '', 'model' => '', 'serial_number' => '', 'installation_date' => date('Y-m-d'), 'meter' => '', 'notes' => '',
];
?>
<form class="pump-form needs-validation" id="pumpForm" novalidate>
    <article class="app-card card pump-form-card">
        <div class="pump-section-heading"><span><i class="fa-solid fa-gas-pump"></i></span><div><small>Pump Information</small><h2><?php echo $isEditMode ? 'Edit Pump Details' : 'Register New Fuel Pump'; ?></h2></div></div>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="pumpNumber">Pump Number</label><input class="form-control" id="pumpNumber" value="<?php echo e($formPump['pump_number']); ?>" placeholder="Pump 5" required><div class="invalid-feedback">Pump number is required.</div></div>
            <div class="col-md-8"><label class="form-label" for="pumpName">Pump Name</label><input class="form-control" id="pumpName" value="<?php echo e($formPump['pump_name']); ?>" placeholder="Main Forecourt Pump" required><div class="invalid-feedback">Pump name is required.</div></div>
            <div class="col-md-6"><label class="form-label" for="fuelType">Fuel Type</label><select class="form-select" id="fuelType" required><option value="">Select fuel type</option><?php foreach ($fuelTypes as $fuelType): ?><option value="<?php echo e($fuelType); ?>" <?php echo $formPump['fuel_type'] === $fuelType ? 'selected' : ''; ?>><?php echo e($fuelType); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label" for="pumpStatus">Pump Status</label><select class="form-select" id="pumpStatus" required><?php foreach ($pumpStatuses as $status): ?><option value="<?php echo e($status); ?>" <?php echo $formPump['status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label" for="manufacturer">Pump Manufacturer</label><input class="form-control" id="manufacturer" value="<?php echo e($formPump['manufacturer']); ?>" required></div>
            <div class="col-md-4"><label class="form-label" for="pumpModel">Pump Model</label><input class="form-control" id="pumpModel" value="<?php echo e($formPump['model']); ?>" required></div>
            <div class="col-md-4"><label class="form-label" for="serialNumber">Serial Number</label><input class="form-control" id="serialNumber" value="<?php echo e($formPump['serial_number']); ?>" required></div>
            <div class="col-md-6"><label class="form-label" for="installationDate">Installation Date</label><input class="form-control" type="date" id="installationDate" value="<?php echo e($formPump['installation_date']); ?>" required></div>
            <div class="col-md-6"><label class="form-label" for="meterReading"><?php echo $isEditMode ? 'Meter Reading' : 'Initial Meter Reading'; ?></label><input class="form-control" type="number" id="meterReading" value="<?php echo e((string) $formPump['meter']); ?>" min="0" required></div>
            <div class="col-12"><label class="form-label" for="pumpNotes">Notes</label><textarea class="form-control" id="pumpNotes" rows="4" placeholder="Optional pump notes"><?php echo e($formPump['notes']); ?></textarea></div>
        </div>
        <div class="pump-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i><?php echo $isEditMode ? 'Update Pump' : 'Save Pump'; ?></button><?php if (!$isEditMode): ?><button class="btn btn-outline-brand" type="reset"><i class="fa-solid fa-rotate-left"></i>Reset</button><?php endif; ?><a class="btn btn-light" href="<?php echo e(route_url('admin/pumps')); ?>">Cancel</a></div>
    </article>
</form>

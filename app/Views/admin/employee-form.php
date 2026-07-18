<?php

declare(strict_types=1);

$isEditMode = ($formMode ?? 'add') === 'edit';
$formEmployee = $formEmployee ?? [
    'id' => 'EMP007',
    'first_name' => '',
    'last_name' => '',
    'gender' => '',
    'dob' => '',
    'marital_status' => '',
    'phone' => '',
    'email' => '',
    'address' => '',
    'emergency_contact_name' => '',
    'emergency_contact_phone' => '',
    'department' => '',
    'role' => '',
    'employment_type' => '',
    'status' => 'Active',
    'date_joined' => date('Y-m-d'),
    'supervisor' => '',
    'shift' => '',
    'salary' => '',
    'allowance' => '',
    'bank_name' => '',
    'account_name' => '',
    'account_number' => '',
];

if (!isset($formEmployee['emergency_contact_name'], $formEmployee['emergency_contact_phone'])) {
    $parts = array_map('trim', explode('-', (string) ($formEmployee['emergency_contact'] ?? '')));
    $formEmployee['emergency_contact_name'] = $parts[0] ?? '';
    $formEmployee['emergency_contact_phone'] = $parts[1] ?? '';
}
?>
<?php if (!$isEditMode): ?>
    <?php require __DIR__ . '/employee-onboarding-form.php'; ?>
    <?php return; ?>
<?php endif; ?>

<?php $employeeFormAction = $isEditMode ? route_url('admin/employees/update') . '&employee=' . urlencode((string) $formEmployee['id']) : route_url('admin/employees/store'); $employeeCsrf = (new \App\Services\AuthService())->csrfToken(); ?>
<form class="employee-form needs-validation" id="employeeForm" method="post" action="<?php echo e($employeeFormAction); ?>" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="_csrf_token" value="<?php echo e($employeeCsrf); ?>">
    <article class="app-card card employee-form-section">
        <div class="employee-section-heading">
            <span><i class="fa-solid fa-id-card"></i></span>
            <div>
                <small>Section 1</small>
                <h2>Personal Details</h2>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-12 col-lg-4">
                <label class="form-label" for="passportPhoto">Passport Photo Upload</label>
                <div class="employee-photo-upload">
                    <img id="employeePhotoPreview" src="<?php echo e(asset_url($formEmployee['photo'] ?? 'images/sample-passport.svg')); ?>" alt="Employee passport preview">
                    <input class="form-control" type="file" id="passportPhoto" name="passport_photo" accept="image/jpeg,image/png,image/webp" data-image-preview="#employeePhotoPreview">
                    <?php if ($isEditMode): ?><label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_photo" value="1"> Remove current photo</label><?php endif; ?>
                </div>
            </div>
            <div class="col-12 col-lg-8">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="employeeId">Employee ID</label>
                        <input class="form-control" id="employeeId" name="employee_id" value="<?php echo e($formEmployee['id']); ?>" required>
                        <div class="invalid-feedback">Employee ID is required.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="firstName">First Name</label>
                        <input class="form-control" id="firstName" name="first_name" value="<?php echo e($formEmployee['first_name']); ?>" required>
                        <div class="invalid-feedback">First name is required.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="lastName">Last Name</label>
                        <input class="form-control" id="lastName" name="last_name" value="<?php echo e($formEmployee['last_name']); ?>" required>
                        <div class="invalid-feedback">Last name is required.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="gender">Gender</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">Select gender</option>
                            <?php foreach ($genders as $gender): ?>
                                <option value="<?php echo e($gender); ?>" <?php echo $formEmployee['gender'] === $gender ? 'selected' : ''; ?>><?php echo e($gender); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="dob">Date of Birth</label>
                        <input class="form-control" type="date" id="dob" name="dob" value="<?php echo e($formEmployee['dob']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="maritalStatus">Marital Status</label>
                        <select class="form-select" id="maritalStatus" name="marital_status" required>
                            <option value="">Select status</option>
                            <?php foreach (['Single', 'Married', 'Divorced', 'Widowed'] as $maritalStatus): ?>
                                <option value="<?php echo e($maritalStatus); ?>" <?php echo $formEmployee['marital_status'] === $maritalStatus ? 'selected' : ''; ?>><?php echo e($maritalStatus); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input class="form-control" id="phone" name="phone" value="<?php echo e($formEmployee['phone']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email Address</label>
                        <input class="form-control" type="email" id="email" name="email" value="<?php echo e($formEmployee['email']); ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="address">Residential Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2" required><?php echo e($formEmployee['address']); ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="emergencyName">Emergency Contact Name</label>
                        <input class="form-control" id="emergencyName" name="emergency_contact_name" value="<?php echo e($formEmployee['emergency_contact_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="emergencyPhone">Emergency Contact Phone</label>
                        <input class="form-control" id="emergencyPhone" name="emergency_contact_phone" value="<?php echo e($formEmployee['emergency_contact_phone']); ?>" required>
                    </div>
                </div>
            </div>
        </div>
    </article>

    <article class="app-card card employee-form-section">
        <div class="employee-section-heading">
            <span><i class="fa-solid fa-briefcase"></i></span>
            <div><small>Section 2</small><h2>Employment Details</h2></div>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="department">Department</label><select class="form-select" id="department" name="department" required><option value="">Select department</option><?php foreach ($departments as $department): ?><option value="<?php echo e($department); ?>" <?php echo $formEmployee['department'] === $department ? 'selected' : ''; ?>><?php echo e($department); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label" for="role">Role</label><select class="form-select" id="role" name="role" required><option value="">Select role</option><?php foreach ($roles as $role): ?><option value="<?php echo e($role); ?>" <?php echo $formEmployee['role'] === $role ? 'selected' : ''; ?>><?php echo e($role); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label" for="employmentType">Employment Type</label><select class="form-select" id="employmentType" name="employment_type" required><option value="">Select type</option><?php foreach ($employmentTypes as $employmentType): ?><option value="<?php echo e($employmentType); ?>" <?php echo $formEmployee['employment_type'] === $employmentType ? 'selected' : ''; ?>><?php echo e($employmentType); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label" for="employmentStatus">Employment Status</label><select class="form-select" id="employmentStatus" name="status" required><?php foreach ($statuses as $status): ?><option value="<?php echo e($status); ?>" <?php echo $formEmployee['status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label" for="dateJoined">Date Joined</label><input class="form-control" type="date" id="dateJoined" name="date_joined" value="<?php echo e($formEmployee['date_joined']); ?>" required></div>
        </div>
    </article>

    <div class="employee-form-actions">
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i><?php echo $isEditMode ? 'Update Employee' : 'Save Employee'; ?></button>
        <?php if (!$isEditMode): ?><button class="btn btn-outline-brand" type="reset"><i class="fa-solid fa-rotate-left"></i>Reset Form</button><?php endif; ?>
        <a class="btn btn-light" href="<?php echo e(route_url('admin/employees')); ?>">Cancel</a>
    </div>
</form>

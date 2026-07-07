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
<form class="employee-form needs-validation" id="employeeForm" novalidate>
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
                    <input class="form-control" type="file" id="passportPhoto" accept="image/*" data-image-preview="#employeePhotoPreview">
                </div>
            </div>
            <div class="col-12 col-lg-8">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="employeeId">Employee ID</label>
                        <input class="form-control" id="employeeId" value="<?php echo e($formEmployee['id']); ?>" required>
                        <div class="invalid-feedback">Employee ID is required.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="firstName">First Name</label>
                        <input class="form-control" id="firstName" value="<?php echo e($formEmployee['first_name']); ?>" required>
                        <div class="invalid-feedback">First name is required.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="lastName">Last Name</label>
                        <input class="form-control" id="lastName" value="<?php echo e($formEmployee['last_name']); ?>" required>
                        <div class="invalid-feedback">Last name is required.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="gender">Gender</label>
                        <select class="form-select" id="gender" required>
                            <option value="">Select gender</option>
                            <?php foreach ($genders as $gender): ?>
                                <option value="<?php echo e($gender); ?>" <?php echo $formEmployee['gender'] === $gender ? 'selected' : ''; ?>><?php echo e($gender); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="dob">Date of Birth</label>
                        <input class="form-control" type="date" id="dob" value="<?php echo e($formEmployee['dob']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="maritalStatus">Marital Status</label>
                        <select class="form-select" id="maritalStatus" required>
                            <option value="">Select status</option>
                            <?php foreach (['Single', 'Married', 'Divorced', 'Widowed'] as $maritalStatus): ?>
                                <option value="<?php echo e($maritalStatus); ?>" <?php echo $formEmployee['marital_status'] === $maritalStatus ? 'selected' : ''; ?>><?php echo e($maritalStatus); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input class="form-control" id="phone" value="<?php echo e($formEmployee['phone']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email Address</label>
                        <input class="form-control" type="email" id="email" value="<?php echo e($formEmployee['email']); ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="address">Residential Address</label>
                        <textarea class="form-control" id="address" rows="2" required><?php echo e($formEmployee['address']); ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="emergencyName">Emergency Contact Name</label>
                        <input class="form-control" id="emergencyName" value="<?php echo e($formEmployee['emergency_contact_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="emergencyPhone">Emergency Contact Phone</label>
                        <input class="form-control" id="emergencyPhone" value="<?php echo e($formEmployee['emergency_contact_phone']); ?>" required>
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
            <div class="col-md-4"><label class="form-label" for="department">Department</label><select class="form-select" id="department" required><option value="">Select department</option><?php foreach ($departments as $department): ?><option value="<?php echo e($department); ?>" <?php echo $formEmployee['department'] === $department ? 'selected' : ''; ?>><?php echo e($department); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label" for="role">Role</label><select class="form-select" id="role" required><option value="">Select role</option><?php foreach ($roles as $role): ?><option value="<?php echo e($role); ?>" <?php echo $formEmployee['role'] === $role ? 'selected' : ''; ?>><?php echo e($role); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label" for="employmentType">Employment Type</label><select class="form-select" id="employmentType" required><option value="">Select type</option><?php foreach ($employmentTypes as $employmentType): ?><option value="<?php echo e($employmentType); ?>" <?php echo $formEmployee['employment_type'] === $employmentType ? 'selected' : ''; ?>><?php echo e($employmentType); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label" for="employmentStatus">Employment Status</label><select class="form-select" id="employmentStatus" required><?php foreach ($statuses as $status): ?><option value="<?php echo e($status); ?>" <?php echo $formEmployee['status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label" for="dateJoined">Date Joined</label><input class="form-control" type="date" id="dateJoined" value="<?php echo e($formEmployee['date_joined']); ?>" required></div>
            <div class="col-md-4"><label class="form-label" for="supervisor">Supervisor</label><input class="form-control" id="supervisor" value="<?php echo e($formEmployee['supervisor']); ?>" required></div>
            <div class="col-md-4"><label class="form-label" for="workShift">Work Shift</label><select class="form-select" id="workShift" required><option value="">Select shift</option><?php foreach ($shifts as $shift): ?><option value="<?php echo e($shift); ?>" <?php echo $formEmployee['shift'] === $shift ? 'selected' : ''; ?>><?php echo e($shift); ?></option><?php endforeach; ?></select></div>
        </div>
    </article>

    <article class="app-card card employee-form-section">
        <div class="employee-section-heading"><span><i class="fa-solid fa-money-check-dollar"></i></span><div><small>Section 3</small><h2>Salary Information</h2></div></div>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="basicSalary">Basic Salary</label><input class="form-control" type="number" id="basicSalary" value="<?php echo e((string) $formEmployee['salary']); ?>" required></div>
            <div class="col-md-4"><label class="form-label" for="allowance">Allowance</label><input class="form-control" type="number" id="allowance" value="<?php echo e((string) $formEmployee['allowance']); ?>" required></div>
            <div class="col-md-4"><label class="form-label" for="bankName">Bank Name</label><input class="form-control" id="bankName" value="<?php echo e($formEmployee['bank_name']); ?>" required></div>
            <div class="col-md-6"><label class="form-label" for="accountName">Account Name</label><input class="form-control" id="accountName" value="<?php echo e($formEmployee['account_name']); ?>" required></div>
            <div class="col-md-6"><label class="form-label" for="accountNumber">Account Number</label><input class="form-control" id="accountNumber" value="<?php echo e($formEmployee['account_number']); ?>" required></div>
        </div>
    </article>

    <article class="app-card card employee-form-section">
        <div class="employee-section-heading"><span><i class="fa-solid fa-lock"></i></span><div><small>Section 4</small><h2>Login Details</h2></div></div>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="username">Username</label><input class="form-control" id="username" value="<?php echo e(strtolower(str_replace(' ', '.', trim(($formEmployee['first_name'] ?? '') . ' ' . ($formEmployee['last_name'] ?? ''))))); ?>" required></div>
            <div class="col-md-4"><label class="form-label" for="password">Password</label><div class="employee-password-field"><input class="form-control" type="password" id="password" <?php echo $isEditMode ? '' : 'required'; ?>><button class="btn btn-light" type="button" data-toggle-password="#password"><i class="fa-solid fa-eye"></i></button></div><div class="password-strength" id="passwordStrength"><span></span></div></div>
            <div class="col-md-4"><label class="form-label" for="confirmPassword">Confirm Password</label><div class="employee-password-field"><input class="form-control" type="password" id="confirmPassword" <?php echo $isEditMode ? '' : 'required'; ?>><button class="btn btn-light" type="button" data-toggle-password="#confirmPassword"><i class="fa-solid fa-eye"></i></button></div></div>
        </div>
    </article>

    <div class="employee-form-actions">
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i><?php echo $isEditMode ? 'Update Employee' : 'Save Employee'; ?></button>
        <?php if (!$isEditMode): ?><button class="btn btn-outline-brand" type="reset"><i class="fa-solid fa-rotate-left"></i>Reset Form</button><?php endif; ?>
        <a class="btn btn-light" href="<?php echo e(route_url('admin/employees')); ?>">Cancel</a>
    </div>
</form>

<?php

declare(strict_types=1);

$employeeCsrf = (new \App\Services\AuthService())->csrfToken();
?>
<form class="employee-form needs-validation" id="employeeForm" method="post" action="<?php echo e(route_url('admin/employees/store')); ?>" enctype="multipart/form-data" data-employee-ajax-form novalidate>
    <input type="hidden" name="_csrf_token" value="<?php echo e($employeeCsrf); ?>">

    <article class="app-card card employee-form-section">
        <div class="employee-section-heading"><span><i class="fa-solid fa-id-card"></i></span><div><small>Section 1</small><h2>Personal Information</h2></div></div>
        <div class="row g-3">
            <div class="col-md-3"><label class="form-label" for="employeeId">Employee ID</label><input class="form-control" id="employeeId" value="<?php echo e($formEmployee['id']); ?>" readonly aria-describedby="employeeIdHelp"><div id="employeeIdHelp" class="form-text">Generated automatically when the account is saved.</div></div>
            <div class="col-md-3"><label class="form-label" for="firstName">First Name</label><input class="form-control" id="firstName" name="first_name" required><div class="invalid-feedback">First name is required.</div></div>
            <div class="col-md-3"><label class="form-label" for="lastName">Last Name</label><input class="form-control" id="lastName" name="last_name" required><div class="invalid-feedback">Last name is required.</div></div>
            <div class="col-md-3"><label class="form-label" for="gender">Gender</label><select class="form-select" id="gender" name="gender" required><option value="">Select gender</option><?php foreach ($genders as $gender): ?><option value="<?php echo e($gender); ?>"><?php echo e($gender); ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><label class="form-label" for="personalEmail">Personal Email Address</label><input class="form-control" type="email" id="personalEmail" name="personal_email" autocomplete="email" required><div class="form-text">Used for personal contact only. It cannot be used to sign in.</div><div class="invalid-feedback">Enter a valid personal email address.</div></div>
            <div class="col-12"><label class="form-label" for="passportPhoto">Passport Photograph</label><input class="form-control" type="file" id="passportPhoto" name="passport_photo" accept="image/jpeg,image/png,image/webp" data-image-preview="#employeePhotoPreview" data-image-crop data-crop-ratio="1:1" data-compress-type="passport"><div class="form-text">Optional. JPG, PNG, or WEBP up to 5 MB.</div></div>
        </div>
    </article>

    <article class="app-card card employee-form-section">
        <div class="employee-section-heading"><span><i class="fa-solid fa-briefcase"></i></span><div><small>Section 2</small><h2>Employment Information</h2></div></div>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="department">Department</label><select class="form-select" id="department" name="department" required><option value="">Select department</option><?php foreach ($departments as $department): ?><option value="<?php echo e($department); ?>"><?php echo e($department); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label" for="role">Role</label><select class="form-select" id="role" name="role" required><option value="">Select role</option><?php foreach ($roles as $role): ?><option value="<?php echo e($role); ?>"><?php echo e($role); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label" for="employmentType">Employment Type</label><select class="form-select" id="employmentType" name="employment_type" required><option value="">Select type</option><?php foreach ($employmentTypes as $employmentType): ?><option value="<?php echo e($employmentType); ?>"><?php echo e($employmentType); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label" for="employmentStatus">Employment Status</label><select class="form-select" id="employmentStatus" name="status" required><?php foreach ($statuses as $status): ?><option value="<?php echo e($status); ?>" <?php echo $status === 'Active' ? 'selected' : ''; ?>><?php echo e($status); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label" for="dateJoined">Date Joined</label><input class="form-control" type="date" id="dateJoined" name="date_joined" value="<?php echo e(date('Y-m-d')); ?>" required></div>
        </div>
    </article>

    <article class="app-card card employee-form-section">
        <div class="employee-section-heading"><span><i class="fa-solid fa-money-check-dollar"></i></span><div><small>Section 3</small><h2>Salary Information</h2></div></div>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="basicSalary">Basic Salary</label><input class="form-control" type="number" min="0" step="0.01" id="basicSalary" name="salary" required></div>
            <div class="col-md-4"><label class="form-label" for="allowance">Allowance</label><input class="form-control" type="number" min="0" step="0.01" id="allowance" name="allowance" value="0" required></div>
            <div class="col-md-4"><label class="form-label" for="bankName">Bank Name</label><input class="form-control" id="bankName" name="bank_name" required></div>
            <div class="col-md-6"><label class="form-label" for="accountName">Account Name</label><input class="form-control" id="accountName" name="account_name" required></div>
            <div class="col-md-6"><label class="form-label" for="accountNumber">Account Number</label><input class="form-control" id="accountNumber" name="account_number" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" required><div class="invalid-feedback">Enter a valid 10-digit account number.</div></div>
        </div>
    </article>

    <article class="app-card card employee-form-section">
        <div class="employee-section-heading"><span><i class="fa-solid fa-lock"></i></span><div><small>Section 4</small><h2>Company Account Information</h2></div></div>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="companyEmail">Company Email Address</label><input class="form-control" type="email" id="companyEmail" name="company_email" autocomplete="username" required><div class="form-text">The employee may sign in with this email or their Employee ID.</div></div>
            <div class="col-md-4"><label class="form-label" for="password">Password</label><div class="employee-password-field"><input class="form-control" type="password" id="password" name="password" minlength="8" autocomplete="new-password" required><button class="btn btn-light" type="button" data-toggle-password="#password"><i class="fa-solid fa-eye"></i></button></div><div class="password-strength" id="passwordStrength"><span></span></div><div class="form-text">Use uppercase, lowercase, number, and special character.</div></div>
            <div class="col-md-4"><label class="form-label" for="confirmPassword">Confirm Password</label><div class="employee-password-field"><input class="form-control" type="password" id="confirmPassword" name="confirm_password" autocomplete="new-password" required><button class="btn btn-light" type="button" data-toggle-password="#confirmPassword"><i class="fa-solid fa-eye"></i></button></div></div>
        </div>
    </article>

    <div class="employee-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i>Save Employee</button><button class="btn btn-outline-brand" type="reset"><i class="fa-solid fa-rotate-left"></i>Reset Form</button><a class="btn btn-light" href="<?php echo e(route_url('admin/employees')); ?>">Cancel</a></div>
</form>

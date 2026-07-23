<?php

declare(strict_types=1);

$pageHeading = 'Edit Employee Profile';
$completionMode = (bool) ($completionMode ?? false);
$pageTitle = ($completionMode ? 'Complete' : 'Edit') . ' Employee Profile | FuelOps Staff Dashboard';
$topbarSubtitle = 'Pump Attendant Dashboard';
$currentRoute = $currentRoute ?? 'profile';
$extraStyles = ['css/clock-in.css', 'css/profile.css', 'css/edit-profile.css'];
$extraScripts = ['js/edit-profile.js'];

$employee = $employee ?? [];
$profileSuccess = $profileSuccess ?? null;
$profileError = $profileError ?? null;

$genderOptions = ['Male', 'Female'];
$departmentOptions = ['Operations', 'Sales', 'Maintenance', 'Administration'];
$roleOptions = ['Pump Attendant', 'Shift Supervisor', 'Cashier', 'Station Manager'];
$statusOptions = ['Active', 'Suspended', 'On Leave'];

$attendantName = ($employee['name'] ?? 'N/A') ?? 'Station Staff';
$attendantRole = $employee['role'] ?? 'Pump Attendant';

require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page profile-page edit-profile-page">
    <section class="clock-hero profile-hero">
        <div class="container-fluid">
          
            <div class="clock-hero__content edit-profile-hero-card">
                <div>
                    <span class="eyebrow">Employee Profile</span>
                    <h1><?php echo e($pageHeading); ?></h1>
                    <p><?php echo $completionMode ? 'Complete your personal and emergency information before continuing.' : 'Update your permitted personal and contact information.'; ?></p>
                </div>
                <span class="employee-avatar" aria-hidden="true">
                    <i class="fa-solid fa-user-pen"></i>
                </span>
            </div>
        </div>
    </section>

    <section class="container-fluid clock-workspace">
        <form id="editProfileForm" class="needs-validation" method="POST" action="<?php echo e(route_url($completionMode ? 'profile/complete' : 'profile/edit')); ?>" enctype="multipart/form-data" data-image-ajax-form novalidate>
            <?php echo csrf_field(); ?>
            <?php if (!empty($profileError)): ?><div class="alert alert-danger"><?php echo e((string) $profileError); ?></div><?php endif; ?>
            <?php if (!empty($profileSuccess)): ?><div class="alert alert-success"><?php echo e((string) $profileSuccess); ?></div><?php endif; ?>
            <div class="row g-4">
                <div class="col-12 col-xl-4">
                    <article class="app-card card profile-photo-card edit-profile-photo-card">
                        <div class="app-card__header">
                            <div>
                                <span class="eyebrow">Passport Photo</span>
                                <h2>Current Photo</h2>
                            </div>
                            <span class="employee-avatar" aria-hidden="true"><i class="fa-solid fa-camera"></i></span>
                        </div>
                        <div class="profile-photo-panel">
                            <img class="profile-passport" src="<?php echo e(asset_url($employee['passport_photo'] ?? 'images/sample-passport.svg')); ?>" alt="Current passport photo for <?php echo e(($employee['name'] ?? 'N/A')); ?>">
                            <strong><?php echo e(($employee['name'] ?? 'N/A')); ?></strong>
                            <span><?php echo e(($employee['employee_id'] ?? 'N/A')); ?></span>
                        </div>
                        <div class="profile-photo-actions">
                            <button type="button" class="btn btn-outline-brand" data-edit-profile-action="change-photo">
                                <i class="fa-solid fa-image"></i>
                                Change Photo
                            </button>
                            <button type="button" class="btn btn-light border" data-edit-profile-action="clear-photo">
                                <i class="fa-solid fa-trash-can"></i>
                                Remove Photo
                            </button>
                        </div>
                        <input type="file" id="passportPhoto" name="passport_photo" class="d-none" accept="image/jpeg,image/png,image/webp" data-image-crop data-crop-ratio="1:1" data-compress-type="profile" data-image-preview=".profile-passport">
                        <input type="hidden" id="removePhoto" name="remove_photo" value="0">
                    </article>
                </div>

                <div class="col-12 col-xl-8">
                    <article class="app-card card edit-profile-form-card">
                        <div class="app-card__header">
                            <div>
                                <span class="eyebrow">Editable Form</span>
                                <h2>Employee Details</h2>
                            </div>
                            <span class="employee-avatar" aria-hidden="true"><i class="fa-solid fa-file-pen"></i></span>
                        </div>

                        <div class="row g-3 edit-profile-form-grid">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="employeeId">Employee ID</label>
                                <input type="text" id="employeeId" class="form-control" value="<?php echo e(($employee['employee_id'] ?? 'N/A') ?? 'N/A'); ?>" readonly>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="fullName">Full Name</label>
                                <input type="text" id="fullName" class="form-control" value="<?php echo e(($employee['name'] ?? 'N/A') ?? 'N/A'); ?>" readonly>
                                <div class="invalid-feedback">Full name is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="gender">Gender</label>
                                <select id="gender" name="gender" class="form-select" required>
                                    <option value="">Select gender</option>
                                    <?php foreach ($genderOptions as $option): ?>
                                        <option value="<?php echo e($option); ?>" <?php echo $option === $employee['gender'] ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Gender is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="dob">Date of Birth</label>
                                <input type="date" id="dob" name="dob" class="form-control" value="<?php echo e($employee['dob_raw'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Date of birth is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo e($employee['phone'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Phone number is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo e($employee['email'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Enter a valid email address.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="address">Residential Address</label>
                                <textarea id="address" name="address" class="form-control" rows="3" required><?php echo e($employee['address'] ?? ''); ?></textarea>
                                <div class="invalid-feedback">Residential address is required.</div>
                            </div>
                            <?php if ($completionMode): ?>
                            <div class="col-12 col-md-6"><label class="form-label" for="state">State</label><input class="form-control" id="state" name="state" value="<?php echo e($employee['state'] ?? ''); ?>" required></div>
                            <div class="col-12 col-md-6"><label class="form-label" for="localGovernmentArea">Local Government Area</label><input class="form-control" id="localGovernmentArea" name="local_government_area" value="<?php echo e($employee['local_government_area'] ?? ''); ?>" required></div>
                            <div class="col-12 col-md-6"><label class="form-label" for="nationality">Nationality</label><input class="form-control" id="nationality" name="nationality" value="<?php echo e($employee['nationality'] ?? 'Nigerian'); ?>" required></div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="maritalStatus">Marital Status</label>
                                <select class="form-select" id="maritalStatus" name="marital_status" required>
                                    <option value="">Select status</option>
                                    <?php foreach (['Single', 'Married', 'Divorced', 'Widowed'] as $option): ?>
                                        <option value="<?php echo e($option); ?>" <?php echo ($employee['marital_status'] ?? '') === $option ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6"><label class="form-label" for="nationalId">National ID(NIN)</label><input class="form-control" id="nationalId" name="national_id" value="<?php echo e($employee['national_id'] ?? ''); ?>" required></div>
                            <div class="col-12 col-md-6"><label class="form-label" for="driversLicense">Driver's License <span class="text-muted">(if applicable)</span></label><input class="form-control" id="driversLicense" name="drivers_license" value="<?php echo e($employee['drivers_license'] ?? ''); ?>"></div>
                            <?php endif; ?>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="emergencyName">Emergency Contact Name</label>
                                <input class="form-control" id="emergencyName" name="emergency_name" value="<?php echo e($employee['emergency_contact_name'] ?? ''); ?>" <?php echo $completionMode ? 'required' : ''; ?>>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="emergencyContact">Emergency Contact Number</label>
                                <input type="tel" id="emergencyContact"  name="emergency_contact" class="form-control" value="<?php echo e($employee['emergency_contact'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Emergency contact is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="department">Department</label>
                                <select id="department" class="form-select" disabled>
                                    <option value="">Select department</option>
                                    <?php foreach ($departmentOptions as $option): ?>
                                        <option value="<?php echo e($option); ?>" <?php echo $option === $employee['department'] ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Department is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="role">Role</label>
                                <select id="role" class="form-select" disabled>
                                    <option value="">Select role</option>
                                    <?php foreach ($roleOptions as $option): ?>
                                        <option value="<?php echo e($option); ?>" <?php echo $option === $employee['role'] ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Role is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="dateJoined">Date Joined</label>
                                <input type="date" id="dateJoined" class="form-control" readonly value="<?php echo e($employee['date_joined_raw'] ?? ''); ?>">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="salary">Salary</label>
                                <div class="input-group">
                                    <span class="input-group-text">&#8358;</span>
                                    <input type="number" id="salary" class="form-control" value="<?php echo e($employee['salary_raw'] ?? ''); ?>" min="0" step="1000" readonly>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="employmentStatus">Employment Status</label>
                                <select id="employmentStatus" class="form-select" disabled>
                                    <option value="">Select status</option>
                                    <?php foreach ($statusOptions as $option): ?>
                                        <option value="<?php echo e($option); ?>" <?php echo $option === $employee['employment_status'] ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Employment status is required.</div>
                            </div>
                        </div>

                        <div class="edit-profile-actions">
                            <button type="submit" style="background-color: #ed3237; color: white;" class="btn">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <?php echo $completionMode ? 'Complete Profile' : 'Save Changes'; ?>
                            </button>
                            <a href="<?php echo e(route_url($completionMode ? 'auth/logout' : 'profile')); ?>" class="btn btn-secondary" id="cancelEditProfileBtn">
                                <i class="fa-solid fa-xmark"></i>
                                <?php echo $completionMode ? 'Sign Out' : 'Cancel'; ?>
                            </a>
                        </div>
                    </article>
                </div>
            </div>
        </form>
    </section>
</main>
<script>window.defaultProfilePhoto = <?php echo json_encode(asset_url('images/sample-passport.svg'), JSON_THROW_ON_ERROR); ?>;</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>

<?php

declare(strict_types=1);

$pageTitle = 'Edit Employee Profile | FuelOps Staff Dashboard';
$pageHeading = 'Edit Employee Profile';
$topbarSubtitle = 'Pump Attendant Dashboard';
$currentRoute = $currentRoute ?? 'profile';
$extraStyles = ['css/clock-in.css', 'css/profile.css', 'css/edit-profile.css'];
$extraScripts = ['js/edit-profile.js'];

// =======================================
// DATABASE PLACEHOLDER
// Load employee information from MySQL.
// =======================================
$employee = $employee ?? [
    'employee_id' => 'EMP001',
    'name' => 'John Doe',
    'gender' => 'Male',
    'dob' => '1998-03-15',
    'phone' => '+2348012345678',
    'email' => 'john.doe@example.com',
    'address' => '12 Unity Street, Lagos',
    'emergency_contact' => '+2348098765432',
    'department' => 'Operations',
    'role' => 'Pump Attendant',
    'date_joined' => '2024-01-10',
    'salary' => '180000',
    'employment_status' => 'Active',
    'passport_photo' => 'images/sample-passport.svg',
];

$genderOptions = ['Male', 'Female'];
$departmentOptions = ['Operations', 'Sales', 'Maintenance', 'Administration'];
$roleOptions = ['Pump Attendant', 'Shift Supervisor', 'Cashier', 'Station Manager'];
$statusOptions = ['Active', 'Suspended', 'On Leave'];

$attendantName = $employee['name'] ?? 'Station Staff';
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
                    <p>Update sample employee information for future database integration.</p>
                </div>
                <span class="employee-avatar" aria-hidden="true">
                    <i class="fa-solid fa-user-pen"></i>
                </span>
            </div>
        </div>
    </section>

    <section class="container-fluid clock-workspace">
        <form id="editProfileForm" class="needs-validation" novalidate>
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
                            <img class="profile-passport" src="<?php echo e(asset_url($employee['passport_photo'])); ?>" alt="Current passport photo for <?php echo e($employee['name']); ?>">
                            <strong><?php echo e($employee['name']); ?></strong>
                            <span><?php echo e($employee['employee_id']); ?></span>
                        </div>
                        <div class="profile-photo-actions">
                            <button type="button" class="btn btn-outline-brand" data-edit-profile-demo-action="change-photo">
                                <i class="fa-solid fa-image"></i>
                                Change Photo (Demo only)
                            </button>
                            <button type="button" class="btn btn-light border" data-edit-profile-demo-action="remove-photo">
                                <i class="fa-solid fa-trash-can"></i>
                                Remove Photo (Demo only)
                            </button>
                        </div>
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
                                <input type="text" id="employeeId" class="form-control" value="<?php echo e($employee['employee_id']); ?>" readonly>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="fullName">Full Name</label>
                                <input type="text" id="fullName" class="form-control" value="<?php echo e($employee['name']); ?>" required>
                                <div class="invalid-feedback">Full name is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="gender">Gender</label>
                                <select id="gender" class="form-select" required>
                                    <option value="">Select gender</option>
                                    <?php foreach ($genderOptions as $option): ?>
                                        <option value="<?php echo e($option); ?>" <?php echo $option === $employee['gender'] ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Gender is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="dob">Date of Birth</label>
                                <input type="date" id="dob" class="form-control" value="<?php echo e($employee['dob']); ?>" required>
                                <div class="invalid-feedback">Date of birth is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="tel" id="phone" class="form-control" value="<?php echo e($employee['phone']); ?>" required>
                                <div class="invalid-feedback">Phone number is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="email">Email Address</label>
                                <input type="email" id="email" class="form-control" value="<?php echo e($employee['email']); ?>" required>
                                <div class="invalid-feedback">Enter a valid email address.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="address">Residential Address</label>
                                <textarea id="address" class="form-control" rows="3" required><?php echo e($employee['address']); ?></textarea>
                                <div class="invalid-feedback">Residential address is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="emergencyContact">Emergency Contact</label>
                                <input type="tel" id="emergencyContact" class="form-control" value="<?php echo e($employee['emergency_contact']); ?>" required>
                                <div class="invalid-feedback">Emergency contact is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="department">Department</label>
                                <select id="department" class="form-select" required>
                                    <option value="">Select department</option>
                                    <?php foreach ($departmentOptions as $option): ?>
                                        <option value="<?php echo e($option); ?>" <?php echo $option === $employee['department'] ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Department is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="role">Role</label>
                                <select id="role" class="form-select" required>
                                    <option value="">Select role</option>
                                    <?php foreach ($roleOptions as $option): ?>
                                        <option value="<?php echo e($option); ?>" <?php echo $option === $employee['role'] ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Role is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="dateJoined">Date Joined</label>
                                <input type="date" id="dateJoined" class="form-control" value="<?php echo e($employee['date_joined']); ?>">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="salary">Salary</label>
                                <div class="input-group">
                                    <span class="input-group-text">&#8358;</span>
                                    <input type="number" id="salary" class="form-control" value="<?php echo e($employee['salary']); ?>" min="0" step="1000">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="employmentStatus">Employment Status</label>
                                <select id="employmentStatus" class="form-select" required>
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
                                Save Changes
                            </button>
                            <a href="<?php echo e(route_url('profile')); ?>" class="btn btn-secondary" id="cancelEditProfileBtn">
                                <i class="fa-solid fa-xmark"></i>
                                Cancel
                            </a>
                        </div>
                    </article>
                </div>
            </div>
        </form>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

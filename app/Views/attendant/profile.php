<?php

declare(strict_types=1);

$pageTitle = 'Employee Profile | FuelOps Staff Dashboard';
$pageHeading = 'Employee Profile';
$topbarSubtitle = 'Pump Attendant Dashboard';
$currentRoute = $currentRoute ?? 'profile';
$extraStyles = ['css/clock-in.css', 'css/profile.css'];
$extraScripts = ['js/profile.js'];

// ========================================
// DATABASE PLACEHOLDER
// Replace sample employee information
// with values retrieved from MySQL.
// ========================================
$employee = $employee ?? [
    'employee_id' => 'EMP001',
    'name' => 'John Doe',
    'gender' => 'Male',
    'dob' => '15 March 1998',
    'phone' => '+234 801 234 5678',
    'email' => 'john.doe@example.com',
    'address' => '12 Unity Street, Lagos',
    'emergency_contact' => '+234 809 876 5432',
    'department' => 'Operations',
    'role' => 'Pump Attendant',
    'date_joined' => '10 January 2024',
    'salary' => 'â‚¦180,000 / Month',
    'employment_status' => 'Active',
    'passport_photo' => 'images/sample-passport.svg',
];

// ========================================
// DATABASE PLACEHOLDER
// Replace sample profile summary values
// with attendance, roster, and pump data from MySQL.
// ========================================
$profileSummary = $profileSummary ?? [
    ['label' => 'Employee Id', 'value' => $employee['employee_id'], 'icon' => 'fa-solid fa-id-badge'],
    ['label' => 'Department', 'value' => $employee['department'], 'icon' => 'fa-solid fa-building'],
    ['label' => 'Role', 'value' => $employee['role'], 'icon' => 'fa-solid fa-user-gear'],
    ['label' => 'Years of Service', 'value' => '2 Years', 'icon' => 'fa-solid fa-award'],
    ['label' => 'Current Shift', 'value' => 'Morning Shift', 'icon' => 'fa-solid fa-business-time'],
    ['label' => 'Assigned Pump', 'value' => 'Pump 03 - PMS Lane', 'icon' => 'fa-solid fa-gas-pump'],
    ['label' => 'Attendance Rate', 'value' => '96%', 'icon' => 'fa-solid fa-chart-line'],
    ['label' => 'Last Clock In', 'value' => '06:03 AM', 'icon' => 'fa-solid fa-right-to-bracket'],
    ['label' => 'Last Clock Out', 'value' => '02:06 PM', 'icon' => 'fa-solid fa-arrow-right-from-bracket'],
];

$personalInfo = [
    ['label' => 'Full Name', 'value' => $employee['name'], 'icon' => 'fa-solid fa-user'],
    ['label' => 'Gender', 'value' => $employee['gender'], 'icon' => 'fa-solid fa-venus-mars'],
    ['label' => 'Date of Birth', 'value' => $employee['dob'], 'icon' => 'fa-solid fa-cake-candles'],
    ['label' => 'Phone Number', 'value' => $employee['phone'], 'icon' => 'fa-solid fa-phone'],
    ['label' => 'Email Address', 'value' => $employee['email'], 'icon' => 'fa-solid fa-envelope'],
    ['label' => 'Residential Address', 'value' => $employee['address'], 'icon' => 'fa-solid fa-location-dot'],
    ['label' => 'Emergency Contact', 'value' => $employee['emergency_contact'], 'icon' => 'fa-solid fa-kit-medical'],
];

$employmentInfo = [
    ['label' => 'Employee ID', 'value' => $employee['employee_id'], 'icon' => 'fa-solid fa-id-badge'],
    ['label' => 'Department', 'value' => $employee['department'], 'icon' => 'fa-solid fa-building'],
    ['label' => 'Role', 'value' => $employee['role'], 'icon' => 'fa-solid fa-user-gear'],
    ['label' => 'Date Joined', 'value' => $employee['date_joined'], 'icon' => 'fa-solid fa-calendar-check'],
    ['label' => 'Salary', 'value' => $employee['salary'], 'icon' => 'fa-solid fa-naira-sign'],
    ['label' => 'Employment Status', 'value' => $employee['employment_status'], 'icon' => 'fa-solid fa-circle-check'],
];

$statusKey = strtolower(str_replace(' ', '-', $employee['employment_status']));
$attendantName = $employee['name'] ?? 'Station Staff';
$attendantRole = $employee['role'] ?? 'Pump Attendant';

require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page profile-page">
  

    <section class="container-fluid clock-workspace">
        <div class="row g-4">
            <div class="col-12 col-xl-4">
                <article class="app-card card profile-photo-card">
                    <div class="app-card__header profile-card-toolbar">
                        <div>
                            <span class="eyebrow">Passport Photo</span>
                        </div>
                        <a style="background-color: #ed3237; color: #fff;" href="<?php echo e(route_url('profile/edit')); ?>" class="btn profile-edit-btn">
                            <i class="fa-solid fa-pen-to-square"></i>
                            Edit Profile
                        </a>
                    </div>
                    <div class="profile-photo-panel">
                        <img class="profile-passport" src="<?php echo e(asset_url($employee['passport_photo'])); ?>" alt="Passport photo preview for <?php echo e($employee['name']); ?>">
                        <strong><?php echo e($employee['name']); ?></strong>
                        <span class="profile-status profile-status--<?php echo e($statusKey); ?>"><?php echo e($employee['employment_status']); ?></span>

                   
                    </div>
                    <div class="profile-photo-actions">
                        <button type="button" class="btn btn-outline-brand" data-profile-demo-action="change-photo">
                            <i class="fa-solid fa-image"></i>
                            Change Photo (Demo)
                        </button>
                        <button type="button" class="btn btn-light border" data-profile-demo-action="remove-photo">
                            <i class="fa-solid fa-trash-can"></i>
                            Remove Photo (Demo)
                        </button>
                    </div>
                </article>
            </div>

            <div class="col-12 col-xl-8">
                <article class="app-card card profile-summary-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Employee Summary</span>
                            
                        </div>
                        <span class="employee-avatar" aria-hidden="true"><i class="fa-solid fa-chart-simple"></i></span>
                    </div>
                    <div class="profile-summary-grid">
                        <?php foreach ($profileSummary as $item): ?>
                            <div class="profile-summary-item">
                                <span class="profile-summary-icon" aria-hidden="true"><i class="<?php echo e($item['icon']); ?>"></i></span>
                                <div>
                                    <span><?php echo e($item['label']); ?></span>
                                    <strong><?php echo e($item['value']); ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            </div>

            <div class="col-12 col-xl-6">
                <article class="app-card card profile-info-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Personal Information</span>
                         
                        </div>
                        <span class="employee-avatar" aria-hidden="true"><i class="fa-solid fa-address-card"></i></span>
                    </div>
                    <div class="profile-info-list">
                        <?php foreach ($personalInfo as $item): ?>
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="<?php echo e($item['icon']); ?>"></i><?php echo e($item['label']); ?></span>
                                <strong><?php echo e($item['value']); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            </div>

            <div class="col-12 col-xl-6">
                <article class="app-card card profile-info-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Employment Information</span>
                           
                        </div>
                        <span class="employee-avatar" aria-hidden="true"><i class="fa-solid fa-briefcase"></i></span>
                    </div>
                    <div class="profile-info-list">
                        <?php foreach ($employmentInfo as $item): ?>
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="<?php echo e($item['icon']); ?>"></i><?php echo e($item['label']); ?></span>
                                <?php if ($item['label'] === 'Employment Status'): ?>
                                    <strong><span class="profile-status profile-status--<?php echo e($statusKey); ?>"><?php echo e($item['value']); ?></span></strong>
                                <?php else: ?>
                                    <strong><?php echo e($item['value']); ?></strong>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

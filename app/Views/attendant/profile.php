<?php

declare(strict_types=1);

$pageTitle = 'Employee Profile | FuelOps Staff Dashboard';
$pageHeading = 'Employee Profile';
$topbarSubtitle = 'Pump Attendant Dashboard';
$currentRoute = $currentRoute ?? 'profile';
$extraStyles = ['css/clock-in.css', 'css/profile.css'];
$extraScripts = ['js/profile.js'];

$employee = $employee ?? [];
$profileSuccess = $profileSuccess ?? null;
$profileError = $profileError ?? null;

$profileSummary = $profileSummary ?? [];

$personalInfo = [
    ['label' => 'Full Name', 'value' => ($employee['name'] ?? 'N/A'), 'icon' => 'fa-solid fa-user'],
    ['label' => 'Gender', 'value' => ($employee['gender'] ?? 'N/A'), 'icon' => 'fa-solid fa-venus-mars'],
    ['label' => 'Date of Birth', 'value' => ($employee['dob'] ?? 'N/A'), 'icon' => 'fa-solid fa-cake-candles'],
    ['label' => 'Phone Number', 'value' => ($employee['phone'] ?? 'N/A'), 'icon' => 'fa-solid fa-phone'],
    ['label' => 'Email Address', 'value' => ($employee['email'] ?? 'N/A'), 'icon' => 'fa-solid fa-envelope'],
    ['label' => 'Residential Address', 'value' => ($employee['address'] ?? 'N/A'), 'icon' => 'fa-solid fa-location-dot'],
    ['label' => 'Emergency Contact Number', 'value' => ($employee['emergency_contact'] ?? ''), 'icon' => 'fa-solid fa-kit-medical'],
];

$employmentInfo = [
    ['label' => 'Employee ID', 'value' => ($employee['employee_id'] ?? 'N/A'), 'icon' => 'fa-solid fa-id-badge'],
    ['label' => 'Department', 'value' => ($employee['department'] ?? 'N/A'), 'icon' => 'fa-solid fa-building'],
    ['label' => 'Role', 'value' => ($employee['role'] ?? 'N/A'), 'icon' => 'fa-solid fa-user-gear'],
    ['label' => 'Date Joined', 'value' => ($employee['date_joined'] ?? 'N/A'), 'icon' => 'fa-solid fa-calendar-check'],
    ['label' => 'Salary', 'value' => $employee['salary'] ?? 'N/A', 'icon' => 'fa-solid fa-naira-sign'],
    ['label' => 'Username', 'value' => $employee['username'] ?? 'N/A', 'icon' => 'fa-solid fa-user-lock'],
    ['label' => 'Last Login', 'value' => $employee['last_login'] ?? 'N/A', 'icon' => 'fa-solid fa-clock-rotate-left'],
    ['label' => 'Employment Status', 'value' => ($employee['employment_status'] ?? 'N/A'), 'icon' => 'fa-solid fa-circle-check'],
];

$statusKey = strtolower(str_replace(' ', '-', (string) ($employee['employment_status'] ?? 'N/A')));
$attendantName = ($employee['name'] ?? 'N/A') ?? 'Station Staff';
$attendantRole = ($employee['role'] ?? 'N/A') ?? 'Pump Attendant';

require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page profile-page">
    <?php if (!empty($profileSuccess)): ?><div class="container-fluid pt-3"><div class="alert alert-success"><?php echo e((string) $profileSuccess); ?></div></div><?php endif; ?>
    <?php if (!empty($profileError)): ?><div class="container-fluid pt-3"><div class="alert alert-danger"><?php echo e((string) $profileError); ?></div></div><?php endif; ?>
  

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
                        <img class="profile-passport" src="<?php echo e(asset_url($employee['passport_photo'] ?? 'images/sample-passport.svg')); ?>" alt="Passport photo preview for <?php echo e(($employee['name'] ?? 'N/A')); ?>">
                        <strong><?php echo e(($employee['name'] ?? 'N/A')); ?></strong>
                        <span class="profile-status profile-status--<?php echo e($statusKey); ?>"><?php echo e(($employee['employment_status'] ?? 'N/A')); ?></span>

                   
                    </div>
                    <div class="profile-photo-actions">
                        <button type="button" class="btn btn-outline-brand" data-profile-edit-action>
                            <i class="fa-solid fa-image"></i>
                            Change Photo
                        </button>
                        <button type="button" class="btn btn-light border" data-profile-edit-action>
                            <i class="fa-solid fa-trash-can"></i>
                            Manage Photo
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

<?php

declare(strict_types=1);

$pageTitle = 'Attendance Settings | FuelOps Admin Dashboard';
$pageHeading = 'Attendance Settings';
$topbarSubtitle = 'Admin Dashboard';
$currentRoute = 'admin/attendance-settings';
$extraStyles = ['css/clock-in.css', 'css/admin-dashboard.css', 'css/attendance-management.css'];
$extraScripts = ['js/admin-dashboard.js', 'js/attendance-management.js'];
$sidebarVariant = 'admin-sidebar';
$sidebarHomeRoute = 'admin/dashboard';
$sidebarBrandTitle = 'FuelOps';
$sidebarBrandSubtitle = 'Admin Panel';
$navItems = require __DIR__ . '/../includes/admin-nav.php';
$adminUser = ['name' => 'Administrator', 'role' => 'System Administrator'];
$employee = ['name' => $adminUser['name'], 'role' => $adminUser['role']];
$attendantName = $adminUser['name'];
$attendantRole = $adminUser['role'];
require __DIR__ . '/attendance-data.php';
$attendanceSettingRoles = (new \App\Services\AuthService())->roles();
$activeAttendanceSettingRole = trim((string) \App\Core\Session::get('auth.role', ''));
if (in_array(strtolower($activeAttendanceSettingRole), ['manager', 'supervisor', 'accountant'], true)) {
    $attendanceSettingRoles = [$activeAttendanceSettingRole];
}
$canManageAttendanceSettings = (new \App\Services\RbacService())->canAccess(
    'admin/attendance-settings/update',
    $attendanceSettingRoles
);
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page attendance-module-page">
    <section class="clock-hero attendance-hero"><div class="container-fluid"><nav class="attendance-breadcrumb" aria-label="Breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Attendance</span><i class="fa-solid fa-chevron-right"></i><span>Settings</span></nav><div class="clock-hero__content attendance-hero-card"><div><span class="eyebrow">Configuration</span><h1>Attendance Settings</h1><p>Configure attendance rules and verification options in frontend-only demo mode.</p></div><span class="attendance-hero-icon"><i class="fa-solid fa-sliders"></i></span></div></div></section>
    <section class="container-fluid clock-workspace">
        <form class="app-card card attendance-settings-form needs-validation" id="attendanceSettingsForm" novalidate>
            <div class="app-card__header"><div><span class="eyebrow">Rules</span><h2>Attendance Configuration</h2></div></div>
            <div class="row g-3">
               
                <div class="col-md-4"><label class="form-label" for="gracePeriod">Grace Period (Minutes)</label><input class="form-control" type="number" id="gracePeriod" value="<?php echo e((string) $attendanceSettings['grace_period']); ?>" min="0" required><small>Employees arriving within this period are still considered present.</small></div>
                <div class="col-md-4"><label class="form-label" for="lateThreshold">Late Threshold (Minutes)</label><input class="form-control" type="number" id="lateThreshold" value="<?php echo e((string) $attendanceSettings['late_threshold']); ?>" min="1" required><small>Employees arriving after this time are marked late.</small></div>
                <div class="col-md-4"><label class="form-label" for="maxOvertime">Maximum Overtime (Hours)</label><input class="form-control" type="number" id="maxOvertime" value="<?php echo e((string) $attendanceSettings['max_overtime']); ?>" min="0" required></div>
                <div class="col-md-6"><label class="form-label" for="shiftDuration">Shift Duration</label><select class="form-select" id="shiftDuration" required><?php foreach (['8 Hours', '10 Hours', '12 Hours'] as $duration): ?><option value="<?php echo e($duration); ?>" <?php echo $attendanceSettings['shift_duration'] === $duration ? 'selected' : ''; ?>><?php echo e($duration); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label" for="approvalRequired">Attendance Approval Required</label><select class="form-select" id="approvalRequired" required><?php foreach (['No Approval', 'Supervisor', 'Manager', 'Admin'] as $approval): ?><option value="<?php echo e($approval); ?>" <?php echo $attendanceSettings['approval_required'] === $approval ? 'selected' : ''; ?>><?php echo e($approval); ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="attendance-switch-grid mt-4">
                <?php foreach ([['auto_clock_out', 'Auto Clock-Out'], ['photo_required', 'Require Clock-In Photo'], ['face_verification', 'Require Face Verification'], ['gps_verification', 'Require GPS Verification'], ['early_clock_in', 'Allow Early Clock-In'], ['manual_adjustment', 'Allow Manual Attendance Adjustment']] as $switch): ?>
                    <label class="attendance-switch-card" for="<?php echo e($switch[0]); ?>"><span><?php echo e($switch[1]); ?></span><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="<?php echo e($switch[0]); ?>" <?php echo !empty($attendanceSettings[$switch[0]]) ? 'checked' : ''; ?>></div></label>
                <?php endforeach; ?>
            </div>
            <div class="attendance-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i>Save Settings</button><button class="btn btn-outline-brand" type="reset"><i class="fa-solid fa-rotate-left"></i>Reset</button></div>
        </form>
    </section>
</main>
<?php if (!$canManageAttendanceSettings): ?><script>document.querySelectorAll('#attendanceSettingsForm input, #attendanceSettingsForm select, #attendanceSettingsForm button').forEach(function (control) { control.disabled = true; });</script><?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>

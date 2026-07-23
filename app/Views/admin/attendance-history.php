<?php

declare(strict_types=1);

$pageTitle = 'Attendance History | FuelOps Admin Dashboard';
$pageHeading = 'Attendance History';
$topbarSubtitle = 'Admin Dashboard';
$currentRoute = 'admin/attendance-history';
$extraStyles = ['css/clock-in.css', 'css/admin-dashboard.css', 'css/attendance-management.css'];
$extraScripts = ['js/admin-dashboard.js', 'js/attendance-management.js', 'js/attendance-selfies.js'];
$sidebarVariant = 'admin-sidebar';
$sidebarHomeRoute = 'admin/dashboard';
$sidebarBrandTitle = 'FuelOps';
$sidebarBrandSubtitle = 'Admin Panel';
$navItems = require __DIR__ . '/../includes/admin-nav.php';
$adminUser = ['name' => 'Administrator', 'role' => 'System Administrator'];
$employee = ['name' => $adminUser['name'], 'role' => $adminUser['role']];
$attendantName = $adminUser['name'];
$canViewAttendanceSelfies = in_array(
    strtolower(trim((string) \App\Core\Session::get('auth.role', ''))),
    ['admin', 'administrator', 'manager', 'supervisor'],
    true
);
$attendantRole = $adminUser['role'];
$canAdjustAttendance = (new \App\Services\RbacService())->canAccess('admin/attendance-history/adjust', (new \App\Services\AuthService())->roles());
require __DIR__ . '/attendance-data.php';
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page attendance-module-page">
    <section class="clock-hero attendance-hero">
        <div class="container-fluid">
            <nav class="attendance-breadcrumb" aria-label="Breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Attendance</span><i class="fa-solid fa-chevron-right"></i><span>History</span></nav>
            <div class="clock-hero__content attendance-hero-card">
                <div><span class="eyebrow">Attendance Records</span><h1>Attendance History</h1><p>Search, filter, and review employee attendance records.</p></div>
                <div class="dropdown"><button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fa-solid fa-download"></i>Export</button><ul class="dropdown-menu dropdown-menu-end"><li><button class="dropdown-item" type="button" data-attendance-export-type="PDF">Export PDF</button></li><li><button class="dropdown-item" type="button" data-attendance-export-type="Excel">Export Excel</button></li><li><button class="dropdown-item" type="button" data-attendance-export-type="CSV">Export CSV</button></li></ul></div>
            </div>
        </div>
    </section>
    <section class="container-fluid clock-workspace">
        <div class="attendance-summary-grid attendance-stats-grid">
            <?php foreach ($attendanceStats as $stat): ?>
                <article class="attendance-summary-card attendance-summary-card--<?php echo e($stat['tone']); ?>"><span><i class="<?php echo e($stat['icon']); ?>"></i></span><div><small><?php echo e($stat['label']); ?></small><strong><?php echo e((string) $stat['value']); ?></strong></div></article>
            <?php endforeach; ?>
        </div>
        <article class="app-card card attendance-table-card mt-4">
            <div class="attendance-toolbar"><div><span class="eyebrow">Full Records</span><h2>Attendance History</h2></div></div>
            <div class="attendance-filter-grid"><div class="filter-control filter-control--wide"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="attendanceSearch" value="<?php echo e((string) ($_GET['search'] ?? '')); ?>" placeholder="Search employee name or ID"></div><input class="form-control" type="date" id="attendanceDateFilter" value="<?php echo e((string) ($_GET['date'] ?? '')); ?>"><select class="form-select" id="attendanceDepartmentFilter"><option value="">All departments</option><?php foreach ($departments as $department): ?><option value="<?php echo e($department); ?>" <?php echo ($_GET['department'] ?? '') === $department ? 'selected' : ''; ?>><?php echo e($department); ?></option><?php endforeach; ?></select><select class="form-select" id="attendanceRoleFilter"><option value="">All roles</option><?php foreach ($roles as $role): ?><option value="<?php echo e($role); ?>" <?php echo ($_GET['role'] ?? '') === $role ? 'selected' : ''; ?>><?php echo e($role); ?></option><?php endforeach; ?></select><select class="form-select" id="attendanceEmployeeFilter"><option value="">All employees</option><?php foreach ($employees as $staff): ?><option value="<?php echo e($staff); ?>" <?php echo ($_GET['employee'] ?? '') === $staff ? 'selected' : ''; ?>><?php echo e($staff); ?></option><?php endforeach; ?></select><select class="form-select" id="attendanceShiftFilter"><option value="">All shifts</option><?php foreach ($shifts as $shift): ?><option value="<?php echo e($shift); ?>" <?php echo ($_GET['shift'] ?? '') === $shift ? 'selected' : ''; ?>><?php echo e($shift); ?></option><?php endforeach; ?></select><select class="form-select" id="attendanceStatusFilter"><option value="">All statuses</option><?php foreach ($statuses as $status): ?><option value="<?php echo e($status); ?>" <?php echo ($_GET['status'] ?? '') === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option><?php endforeach; ?></select></div>
            <div class="table-responsive">
                <table class="table attendance-table admin-attendance-table align-middle">
                    <thead><tr><th>Date</th><th>Employee ID</th><th>Employee Name</th><th>Department</th><th>Role</th><th>Clock In</th><th>Clock Out</th><th>Opening Meter Photo</th><th>Closing Meter Photo</th><th>Shift</th><th>Status</th><th>Remarks</th><th>Action</th></tr></thead>
                    <tbody id="attendanceHistoryBody">
                    <?php if ($attendanceRecords === []): ?>
                        <tr><td colspan="13" class="text-center py-5">No attendance records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($attendanceRecords as $record): ?>
                            <tr data-attendance-row data-search="<?php echo e(strtolower($record['name'] . ' ' . $record['employee_id'])); ?>" data-date="<?php echo e($record['date']); ?>" data-department="<?php echo e($record['department']); ?>" data-role="<?php echo e($record['role']); ?>" data-employee="<?php echo e($record['name']); ?>" data-shift="<?php echo e($record['shift']); ?>" data-status="<?php echo e($record['status']); ?>">
                                <td><?php echo e(format_date($record['date'])); ?></td><td><strong><?php echo e($record['employee_id']); ?></strong></td><td><?php echo e($record['name']); ?></td><td><?php echo e($record['department']); ?></td><td><?php echo e($record['role']); ?></td><td><?php echo e($record['clock_in']); ?></td><td><?php echo e($record['clock_out']); ?></td><td><?php if ($canViewAttendanceSelfies && ($record['opening_meter_image_status'] ?? '') === 'available'): ?><button class="btn btn-sm btn-light" type="button" data-image-view="<?php echo e(route_url('admin/attendance-history/selfie') . '&id=' . rawurlencode((string) $record['id']) . '&type=opening-meter'); ?>" data-image-title="Opening Meter Photo" title="View opening meter photo" aria-label="View opening meter photo"><i class="fa-solid fa-eye"></i></button><?php else: ?><span class="text-muted">&mdash;</span><?php endif; ?></td><td><?php if ($canViewAttendanceSelfies && ($record['closing_meter_image_status'] ?? '') === 'available'): ?><button class="btn btn-sm btn-light" type="button" data-image-view="<?php echo e(route_url('admin/attendance-history/selfie') . '&id=' . rawurlencode((string) $record['id']) . '&type=closing-meter'); ?>" data-image-title="Closing Meter Photo" title="View closing meter photo" aria-label="View closing meter photo"><i class="fa-solid fa-eye"></i></button><?php else: ?><span class="text-muted">&mdash;</span><?php endif; ?></td><td><?php echo e($record['shift']); ?></td><td><span class="table-badge <?php echo e($attendanceStatusClasses[$record['status']] ?? ''); ?>"><?php echo e($record['status']); ?></span></td><td><?php echo e($record['remarks']); ?></td>
                                <td>
                                    <?php if ($canViewAttendanceSelfies): ?>
                                        <button class="btn btn-sm btn-light" type="button" data-attendance-view="<?php echo e((string) $record['id']); ?>" aria-label="View attendance for <?php echo e($record['name']); ?>"><i class="fa-solid fa-eye"></i><span class="visually-hidden">View Attendance</span></button>
                                        <?php if ($canAdjustAttendance): ?><button class="btn btn-sm btn-light" type="button" data-attendance-adjust="<?php echo e((string) $record['id']); ?>" aria-label="Adjust attendance for <?php echo e($record['name']); ?>"><i class="fa-solid fa-pen-to-square"></i><span class="visually-hidden">Adjust Attendance</span></button><?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted" title="Attendance selfie viewing is not permitted"><i class="fa-solid fa-lock"></i><span class="visually-hidden">Not authorized</span></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="attendance-pagination"><span id="attendancePageSummary">Showing attendance records</span><div><button class="btn btn-outline-brand btn-sm" type="button" id="prevAttendancePage"><i class="fa-solid fa-chevron-left"></i></button><button class="btn btn-outline-brand btn-sm" type="button" id="nextAttendancePage"><i class="fa-solid fa-chevron-right"></i></button></div></div>
        </article>
    </section>
</main>
<div class="modal fade" id="attendanceAdjustmentModal" tabindex="-1" aria-labelledby="attendanceAdjustmentModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
        <form id="attendanceAdjustmentForm" method="post" action="<?php echo e(route_url('admin/attendance-history/adjust')); ?>" novalidate>
            <?php echo csrf_field(); ?><input type="hidden" name="attendance_id" id="adjustmentAttendanceId">
            <div class="modal-header"><h5 class="modal-title" id="attendanceAdjustmentModalTitle">Adjust Attendance</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <div id="attendanceAdjustmentRecord" class="alert alert-light">Loading attendance record...</div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="adjustmentClockIn">Clock In</label><input class="form-control" id="adjustmentClockIn" name="clock_in" type="datetime-local"></div>
                    <div class="col-md-6"><label class="form-label" for="adjustmentClockOut">Clock Out</label><input class="form-control" id="adjustmentClockOut" name="clock_out" type="datetime-local"></div>
                    <div class="col-md-6"><label class="form-label" for="adjustmentStatus">Status</label><select class="form-select" id="adjustmentStatus" name="status" required><?php foreach ($statuses as $status): ?><option value="<?php echo e($status); ?>"><?php echo e($status); ?></option><?php endforeach; ?></select></div>
                    <div class="col-12"><label class="form-label" for="adjustmentReason">Adjustment Reason</label><textarea class="form-control" id="adjustmentReason" name="reason" rows="2" required></textarea></div>
                    <div class="col-12"><label class="form-label" for="adjustmentRemarks">Remarks</label><textarea class="form-control" id="adjustmentRemarks" name="remarks" rows="2"></textarea></div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Save Adjustment</button></div>
        </form>
    </div></div>
</div>

<div class="modal fade" id="attendanceSelfieModal" tabindex="-1" aria-labelledby="attendanceSelfieModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="attendanceSelfieModalTitle"><i class="fa-solid fa-camera me-2"></i>Attendance Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body" id="attendanceSelfieModalContent"></div>
            <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>
<script>window.attendanceSelfieDetailsUrl = <?php echo json_encode(route_url('admin/attendance-history/details'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>

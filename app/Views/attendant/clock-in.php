<?php

declare(strict_types=1);

$pageTitle = 'Clock In | FuelOps Staff Dashboard';
$pageHeading = 'Clock In';
$topbarSubtitle = 'Pump Attendant Dashboard';
$currentRoute = $currentRoute ?? 'attendance/clock-in';
$extraStyles = ['css/clock-in.css'];
$extraScripts = ['js/clock-in.js'];
// DATABASE PLACEHOLDER
// Replace this sample data with values retrieved from the database during backend integration.
$employee = $employee ?? [
    'name' => 'Chinedu Okafor',
    'employee_id' => 'EMP-FS-0017',
    'department' => 'Forecourt Operations',
    'role' => 'Pump Attendant',
    'shift' => 'Morning Shift (06:00 AM - 02:00 PM)',
    'assigned_pump' => 'Pump 03 - PMS Lane',
];

// DATABASE PLACEHOLDER
// Replace this sample status with the employee's current attendance state from the database.
$attendanceStatus = $attendanceStatus ?? [
    'shift_date' => 'Saturday, July 4, 2026',
    'current_time' => '05:54 AM',
    'photo_status' => 'Waiting for Selfie',
];

// DATABASE PLACEHOLDER
// Replace this sample history with attendance records retrieved from the database.
$attendanceHistory = $attendanceHistory ?? [
    [
        'date' => '2026-07-04',
        'clock_in' => 'Not yet clocked in',
        'clock_out' => 'Not yet clocked out',
        'status' => 'Awaiting Clock In',
        'photo_status' => 'Waiting for Selfie',
    ],
    [
        'date' => '2026-07-03',
        'clock_in' => '06:03 AM',
        'clock_out' => '02:06 PM',
        'status' => 'Present',
        'photo_status' => 'Captured',
    ],
    [
        'date' => '2026-07-02',
        'clock_in' => '06:12 AM',
        'clock_out' => '02:04 PM',
        'status' => 'Late',
        'photo_status' => 'Captured',
    ],
    [
        'date' => '2026-07-01',
        'clock_in' => '05:58 AM',
        'clock_out' => '02:00 PM',
        'status' => 'Present',
        'photo_status' => 'Captured',
    ],
];

$attendantName = $employee['name'] ?? 'Station Staff';
$attendantRole = $employee['role'] ?? 'Pump Attendant';

require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page">
    <section class="clock-hero">
        <div class="container-fluid">
            <div class="clock-hero__content">
                <div>
                    <h1><?php echo e($pageHeading); ?></h1>
                    <p>Take a fresh selfie with your device camera and begin your station shift.</p>
                </div>
                <div class="clock-hero__time" aria-live="polite">
                    <span id="currentDate"><?php echo e($attendanceStatus['shift_date'] ?? 'Loading date...'); ?></span>
                    <strong id="liveClock"><?php echo e($attendanceStatus['current_time'] ?? '--:--:--'); ?></strong>
                </div>
            </div>
        </div>
    </section>

    <section class="container-fluid clock-workspace">
        <div class="row g-4">
            <div class="col-12 col-xl-5">
                <article class="employee-card app-card card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Employee Information</span>
                            <h2><?php echo e($employee['name'] ?? 'Station Staff'); ?></h2>
                        </div>
                        <span class="employee-avatar" aria-hidden="true">
                            <i class="fa-solid fa-user-check"></i>
                        </span>
                    </div>
                    <div class="employee-grid">
                        <div>
                            <span>Employee ID</span>
                            <strong><?php echo e($employee['employee_id'] ?? 'Pending'); ?></strong>
                        </div>
                        <div>
                            <span>Department</span>
                            <strong><?php echo e($employee['department'] ?? 'Operations'); ?></strong>
                        </div>
                        <div>
                            <span>Role</span>
                            <strong><?php echo e($employee['role'] ?? 'Pump Attendant'); ?></strong>
                        </div>
                        <div>
                            <span>Assigned Pump</span>
                            <strong><?php echo e($employee['assigned_pump'] ?? 'Unassigned'); ?></strong>
                        </div>
                        <div class="employee-grid__wide">
                            <span>Assigned Shift</span>
                            <strong><?php echo e($employee['shift'] ?? 'Pending'); ?></strong>
                        </div>
                    </div>
                </article>
            </div>

            <div class="col-12 col-xl-7">
                <article class="app-card card capture-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Photo Capture</span>
                            <h2>Take Selfie</h2>
                        </div>
                        <span class="status-pill status-waiting" id="photoStatus"><?php echo e($attendanceStatus['photo_status'] ?? 'Waiting...'); ?></span>
                    </div>
                    <div class="native-camera-stage">
                        <input type="file" id="photoInput" class="visually-hidden" accept="image/*" capture="user">
                        <img id="capturedImage" alt="Captured employee selfie preview" hidden>
                        <div class="camera-placeholder" id="cameraPlaceholder">
                            <i class="fa-solid fa-camera-retro"></i>
                            <strong>No selfie captured yet</strong>
                            <span>Use the button below to open your device camera. Some Android browsers may still show gallery options.</span>
                        </div>
                    </div>
                    <div class="camera-actions">
                        <button type="button" class="btn btn-brand" id="takePictureBtn">
                            <i class="fa-solid fa-camera"></i>
                            Take Picture
                        </button>
                        <button type="button" class="btn btn-outline-brand" id="retakePhotoBtn" disabled>
                            <i class="fa-solid fa-rotate-left"></i>
                            Retake Picture
                        </button>
                        <button type="button" class="btn btn-light border" id="removePhotoBtn" disabled>
                            <i class="fa-solid fa-trash-can"></i>
                            Remove
                        </button>
                    </div>
                </article>
            </div>


            <div class="col-12">
                <article class="app-card card clock-action-card">
                    <div>
                        <span class="eyebrow">Attendance Action</span>
                        <h2>Ready to Start Shift?</h2>
                        <p>Clock-in becomes available after a selfie has been captured.</p>
                    </div>
                    <button type="button" class="btn btn-clock-in" id="clockInBtn" disabled>
                        <i class="fa-solid fa-fingerprint"></i>
                        Clock In
                    </button>
                </article>
            </div>

           
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

<?php

declare(strict_types=1);

$pageTitle = 'Attendance History | FuelOps Staff Dashboard';
$pageHeading = 'Attendance History';
$topbarSubtitle = 'Pump Attendant Dashboard';
$currentRoute = $currentRoute ?? 'attendance/history';
$extraStyles = ['css/clock-in.css', 'css/attendance-history.css'];
$extraScripts = ['js/attendance-history.js'];

// =======================================
// DATABASE PLACEHOLDER
// Replace with employee information from MySQL.
// =======================================
$employee = $employee ?? [
    'employee_id' => 'EMP001',
    'name' => 'John Doe',
    'department' => 'Operations',
    'role' => 'Pump Attendant',
    'shift' => 'Morning',
];

// =======================================
// DATABASE PLACEHOLDER
// Retrieve employee attendance records
// from the MySQL database.
// =======================================
$attendanceSummary = $attendanceSummary ?? [
    ['label' => 'Total Working Days', 'value' => '22', 'icon' => 'fa-solid fa-calendar-days', 'tone' => 'primary'],
    ['label' => 'Present', 'value' => '20', 'icon' => 'fa-solid fa-circle-check', 'tone' => 'success'],
    ['label' => 'Absent', 'value' => '2', 'icon' => 'fa-solid fa-circle-xmark', 'tone' => 'danger'],
    ['label' => 'Total Hours Worked', 'value' => '176 Hours', 'icon' => 'fa-solid fa-clock', 'tone' => 'secondary'],
];

$attendanceStats = $attendanceStats ?? [
    ['label' => 'Attendance Rate', 'value' => '91%', 'icon' => 'fa-solid fa-chart-line'],
    ['label' => 'Late Arrivals', 'value' => '3 Days', 'icon' => 'fa-solid fa-hourglass-half'],
    ['label' => 'Leave Days', 'value' => '1 Day', 'icon' => 'fa-solid fa-person-walking-arrow-right'],
    ['label' => 'Average Daily Hours', 'value' => '8h', 'icon' => 'fa-solid fa-stopwatch'],
];

$months = [
    '01' => 'January',
    '02' => 'February',
    '03' => 'March',
    '04' => 'April',
    '05' => 'May',
    '06' => 'June',
    '07' => 'July',
    '08' => 'August',
    '09' => 'September',
    '10' => 'October',
    '11' => 'November',
    '12' => 'December',
];

$statusClasses = [
    'Present' => 'attendance-status--present',
    'Late' => 'attendance-status--late',
    'Absent' => 'attendance-status--absent',
    'Half Day' => 'attendance-status--half-day',
    'Leave' => 'attendance-status--leave',
];

$attendanceHistory = $attendanceHistory ?? [
    ['date' => '2026-07-01', 'day' => 'Wednesday', 'shift' => 'Morning', 'clock_in' => '06:00 AM', 'clock_out' => '02:05 PM', 'hours' => '8h 5m', 'status' => 'Present', 'remarks' => 'On Time'],
    ['date' => '2026-07-02', 'day' => 'Thursday', 'shift' => 'Evening', 'clock_in' => '02:00 PM', 'clock_out' => '10:00 PM', 'hours' => '8h', 'status' => 'Present', 'remarks' => 'Completed Shift'],
    ['date' => '2026-07-03', 'day' => 'Friday', 'shift' => 'Morning', 'clock_in' => '06:30 AM', 'clock_out' => '02:00 PM', 'hours' => '7h 30m', 'status' => 'Late', 'remarks' => 'Arrived 30 mins late'],
    ['date' => '2026-07-04', 'day' => 'Saturday', 'shift' => 'Morning', 'clock_in' => '—', 'clock_out' => '—', 'hours' => '0h', 'status' => 'Absent', 'remarks' => 'No Clock In'],
    ['date' => '2026-07-05', 'day' => 'Sunday', 'shift' => 'Off', 'clock_in' => '—', 'clock_out' => '—', 'hours' => '0h', 'status' => 'Leave', 'remarks' => 'Approved leave'],
    ['date' => '2026-07-06', 'day' => 'Monday', 'shift' => 'Morning', 'clock_in' => '06:02 AM', 'clock_out' => '02:04 PM', 'hours' => '8h 2m', 'status' => 'Present', 'remarks' => 'On Time'],
    ['date' => '2026-07-07', 'day' => 'Tuesday', 'shift' => 'Evening', 'clock_in' => '02:18 PM', 'clock_out' => '10:05 PM', 'hours' => '7h 47m', 'status' => 'Late', 'remarks' => 'Late due to transport delay'],
    ['date' => '2026-07-08', 'day' => 'Wednesday', 'shift' => 'Morning', 'clock_in' => '06:00 AM', 'clock_out' => '11:58 AM', 'hours' => '5h 58m', 'status' => 'Half Day', 'remarks' => 'Supervisor approved half day'],
    ['date' => '2026-06-28', 'day' => 'Sunday', 'shift' => 'Morning', 'clock_in' => '06:04 AM', 'clock_out' => '02:03 PM', 'hours' => '7h 59m', 'status' => 'Present', 'remarks' => 'Completed Shift'],
    ['date' => '2025-12-19', 'day' => 'Friday', 'shift' => 'Evening', 'clock_in' => '02:00 PM', 'clock_out' => '10:00 PM', 'hours' => '8h', 'status' => 'Present', 'remarks' => 'On Time'],
];

$calendarDays = $calendarDays ?? [
    ['day' => 1, 'status' => 'Present'],
    ['day' => 2, 'status' => 'Present'],
    ['day' => 3, 'status' => 'Late'],
    ['day' => 4, 'status' => 'Absent'],
    ['day' => 5, 'status' => 'Leave'],
    ['day' => 6, 'status' => 'Present'],
    ['day' => 7, 'status' => 'Late'],
    ['day' => 8, 'status' => 'Half Day'],
    ['day' => 9, 'status' => 'Present'],
    ['day' => 10, 'status' => 'Present'],
    ['day' => 11, 'status' => 'Present'],
    ['day' => 12, 'status' => 'Leave'],
    ['day' => 13, 'status' => 'Present'],
    ['day' => 14, 'status' => 'Present'],
    ['day' => 15, 'status' => 'Late'],
    ['day' => 16, 'status' => 'Present'],
    ['day' => 17, 'status' => 'Present'],
    ['day' => 18, 'status' => 'Absent'],
    ['day' => 19, 'status' => 'Leave'],
    ['day' => 20, 'status' => 'Present'],
    ['day' => 21, 'status' => 'Present'],
    ['day' => 22, 'status' => 'Present'],
    ['day' => 23, 'status' => 'Late'],
    ['day' => 24, 'status' => 'Present'],
    ['day' => 25, 'status' => 'Present'],
    ['day' => 26, 'status' => 'Leave'],
    ['day' => 27, 'status' => 'Present'],
    ['day' => 28, 'status' => 'Present'],
    ['day' => 29, 'status' => 'Half Day'],
    ['day' => 30, 'status' => 'Present'],
    ['day' => 31, 'status' => 'Present'],
];

$attendantName = $employee['name'] ?? 'Station Staff';
$attendantRole = $employee['role'] ?? 'Pump Attendant';

require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page attendance-history-page">
    <section class="clock-hero attendance-history-hero">
        <div class="container-fluid">
            

            <div class="clock-hero__content attendance-history-hero-card">
                <div>
                    <span class="eyebrow">My Attendance</span>
                    <h1><?php echo e($pageHeading); ?></h1>
                    <p>Review clock-in records, working hours, statuses, and monthly attendance trends.</p>
                </div>
                <span class="employee-avatar" aria-hidden="true">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </span>
            </div>
        </div>
    </section>

    <section class="container-fluid clock-workspace">
        <div class="row g-4">
            <?php foreach ($attendanceSummary as $card): ?>
                <div class="col-12 col-md-6 col-xl-3">
                    <article class="app-card card attendance-summary-card attendance-summary-card--<?php echo e($card['tone']); ?>">
                        <span class="attendance-summary-icon" aria-hidden="true"><i class="<?php echo e($card['icon']); ?>"></i></span>
                        <div>
                            <span><?php echo e($card['label']); ?></span>
                            <strong><?php echo e($card['value']); ?></strong>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>

            <div class="col-12 col-xl-5">
                <article class="app-card card attendance-stats-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Attendance Statistics</span>
                            <h2>Current Month</h2>
                        </div>
                        <span class="employee-avatar" aria-hidden="true"><i class="fa-solid fa-chart-simple"></i></span>
                    </div>
                    <div class="attendance-stats-list">
                        <?php foreach ($attendanceStats as $stat): ?>
                            <div class="attendance-stat-item">
                                <span class="attendance-stat-icon" aria-hidden="true"><i class="<?php echo e($stat['icon']); ?>"></i></span>
                                <div>
                                    <span><?php echo e($stat['label']); ?></span>
                                    <strong><?php echo e($stat['value']); ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            </div>

            <div class="col-12 col-xl-7">
                <article class="app-card card attendance-calendar-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Monthly Attendance Calendar</span>
                            <h2>July 2026</h2>
                        </div>
                        <div class="attendance-calendar-legend">
                            <span><i class="legend-dot legend-present"></i>Present</span>
                            <span><i class="legend-dot legend-late"></i>Late</span>
                            <span><i class="legend-dot legend-absent"></i>Absent</span>
                            <span><i class="legend-dot legend-leave"></i>Leave</span>
                        </div>
                    </div>
                    <div class="attendance-calendar-grid" aria-label="July 2026 attendance calendar">
                        <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday): ?>
                            <span class="attendance-calendar-weekday"><?php echo e($weekday); ?></span>
                        <?php endforeach; ?>
                        <?php foreach ($calendarDays as $day): ?>
                            <?php $statusKey = strtolower(str_replace(' ', '-', $day['status'])); ?>
                            <button type="button" class="attendance-calendar-day attendance-calendar-day--<?php echo e($statusKey); ?>" data-attendance-day="<?php echo e((string) $day['day']); ?>" data-attendance-status="<?php echo e($day['status']); ?>">
                                <strong><?php echo e((string) $day['day']); ?></strong>
                                <span><?php echo e($day['status']); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </article>
            </div>

            <div class="col-12">
                <article class="app-card card attendance-records-card">
                    <div class="history-toolbar attendance-records-toolbar">
                        <div>
                            <span class="eyebrow">Attendance Records</span>
                            <h2>Clock In / Clock Out History</h2>
                        </div>
                        <div class="attendance-filters">
                            <label class="visually-hidden" for="attendanceSearch">Search attendance records</label>
                            <div class="filter-control">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search" id="attendanceSearch" class="form-control" placeholder="Search date, day, status">
                            </div>
                            <label class="visually-hidden" for="monthFilter">Filter by month</label>
                            <select id="monthFilter" class="form-select">
                                <option value="">All Months</option>
                                <?php foreach ($months as $monthNumber => $monthName): ?>
                                    <option value="<?php echo e($monthNumber); ?>" <?php echo $monthNumber === '07' ? 'selected' : ''; ?>><?php echo e($monthName); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="visually-hidden" for="yearFilter">Filter by year</label>
                            <select id="yearFilter" class="form-select">
                                <option value="">All Years</option>
                                <?php foreach (['2025', '2026', '2027'] as $year): ?>
                                    <option value="<?php echo e($year); ?>" <?php echo $year === '2026' ? 'selected' : ''; ?>><?php echo e($year); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="visually-hidden" for="statusFilter">Filter by attendance status</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">All Statuses</option>
                                <?php foreach (array_keys($statusClasses) as $status): ?>
                                    <option value="<?php echo e($status); ?>"><?php echo e($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table attendance-table attendance-history-table align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Shift</th>
                                    <th>Clock In Time</th>
                                    <th>Clock Out Time</th>
                                    
                                    <th>Attendance Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceHistoryBody">
                                <?php foreach ($attendanceHistory as $record): ?>
                                    <?php $status = $record['status']; ?>
                                    <tr data-attendance-row data-month="<?php echo e(substr($record['date'], 5, 2)); ?>" data-year="<?php echo e(substr($record['date'], 0, 4)); ?>" data-status="<?php echo e($status); ?>" data-date="<?php echo e($record['date']); ?>">
                                        <td><?php echo e($record['date']); ?></td>
                                        <td><?php echo e($record['day']); ?></td>
                                        <td><?php echo e($record['shift']); ?></td>
                                        <td><?php echo e($record['clock_in']); ?></td>
                                        <td><?php echo e($record['clock_out']); ?></td>
                                        <td><span class="table-badge attendance-status <?php echo e($statusClasses[$status] ?? 'attendance-status--leave'); ?>"><?php echo e($status); ?></span></td>
                                        <td><?php echo e($record['remarks']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="history-pagination attendance-pagination">
                        <span id="attendanceHistoryCount">Showing attendance records</span>
                        <div class="btn-group" role="group" aria-label="Attendance history pagination">
                            <button type="button" class="btn btn-outline-brand btn-sm" id="attendancePrevPage">Previous</button>
                            <button type="button" class="btn btn-outline-brand btn-sm" id="attendanceNextPage">Next</button>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

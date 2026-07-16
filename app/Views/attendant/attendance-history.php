<?php

declare(strict_types=1);

$pageTitle = 'Attendance History | FuelOps Staff Dashboard';
$pageHeading = 'Attendance History';
$topbarSubtitle = 'Pump Attendant Dashboard';
$currentRoute = $currentRoute ?? 'attendance/history';
$extraStyles = ['css/clock-in.css', 'css/attendance-history.css'];
$extraScripts = [];

$employee = $employee ?? ['employee_id' => 'N/A', 'name' => 'Station Staff', 'department' => 'Unassigned', 'role' => 'Pump Attendant'];
$attendanceSummary = $attendanceSummary ?? [];
$attendanceStats = $attendanceStats ?? [];
$attendanceHistory = $attendanceHistory ?? [];
$calendarDays = $calendarDays ?? [];
$calendarLabel = $calendarLabel ?? date('F Y');
$filters = $filters ?? [];
$pagination = $pagination ?? ['page' => 1, 'pages' => 1, 'total' => 0, 'from' => 0, 'to' => 0];
$shifts = $shifts ?? [];
$months = ['01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'];
$years = range((int) date('Y'), max(2020, (int) date('Y') - 6));
$statusClasses = ['Present' => 'attendance-status--present', 'Late' => 'attendance-status--late', 'Absent' => 'attendance-status--absent', 'Half Day' => 'attendance-status--half-day', 'On Leave' => 'attendance-status--leave'];
$pageUrl = static function (int $page) use ($filters): string {
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn (mixed $value): bool => $value !== '' && $value !== null);
    return route_url('attendance/history') . '&' . http_build_query($query);
};
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

    <section class="container-fluid clock-workspace"><?php if (!empty($historyError)): ?><div class="alert alert-warning"><?php echo e((string) $historyError); ?></div><?php endif; ?>
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
                            <h2><?php echo e($calendarLabel); ?></h2>
                        </div>
                        <div class="attendance-calendar-legend">
                            <span><i class="legend-dot legend-present"></i>Present</span>
                            <span><i class="legend-dot legend-late"></i>Late</span>
                            <span><i class="legend-dot legend-absent"></i>Absent</span>
                            <span><i class="legend-dot legend-leave"></i>Leave</span>
                        </div>
                    </div>
                    <div class="attendance-calendar-grid" aria-label="<?php echo e($calendarLabel); ?> attendance calendar">
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
                        <form class="attendance-filters" method="get" action="<?php echo e(app_base_url() . '/index.php'); ?>"><input type="hidden" name="route" value="attendance/history">
                            <label class="visually-hidden" for="attendanceSearch">Search attendance records</label>
                            <div class="filter-control">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search" id="attendanceSearch" name="search" value="<?php echo e((string) ($filters['search'] ?? '')); ?>" class="form-control" placeholder="Search date, day, status">
                            </div>
                            <label class="visually-hidden" for="monthFilter">Filter by month</label>
                            <select id="monthFilter" name="month" class="form-select">
                                <option value="">All Months</option>
                                <?php foreach ($months as $monthNumber => $monthName): ?>
                                    <option value="<?php echo e($monthNumber); ?>" <?php echo ($filters['month'] ?? '') === $monthNumber ? 'selected' : ''; ?>><?php echo e($monthName); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="visually-hidden" for="yearFilter">Filter by year</label>
                            <select id="yearFilter" name="year" class="form-select">
                                <option value="">All Years</option>
                                <?php foreach ($years as $year): ?>
                                    <option value="<?php echo e((string) $year); ?>" <?php echo ($filters['year'] ?? '') === (string) $year ? 'selected' : ''; ?>><?php echo e($year); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="visually-hidden" for="statusFilter">Filter by attendance status</label>
                            <select id="statusFilter" name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <?php foreach (array_keys($statusClasses) as $status): ?>
                                    <option value="<?php echo e($status); ?>" <?php echo ($filters['status'] ?? '') === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                                <?php endforeach; ?>
                            </select><select name="shift" class="form-select"><option value="">All Shifts</option><?php foreach ($shifts as $option): ?><option value="<?php echo e($option['value']); ?>" <?php echo ($filters['shift'] ?? '') === $option['value'] ? 'selected' : ''; ?>><?php echo e($option['label']); ?></option><?php endforeach; ?></select><input type="date" name="date_from" class="form-control" value="<?php echo e((string) ($filters['date_from'] ?? '')); ?>"><input type="date" name="date_to" class="form-control" value="<?php echo e((string) ($filters['date_to'] ?? '')); ?>"><select name="sort" class="form-select"><option value="date">Sort by Date</option><option value="clock_in" <?php echo ($filters['sort'] ?? '') === 'clock_in' ? 'selected' : ''; ?>>Sort by Clock-In</option><option value="clock_out" <?php echo ($filters['sort'] ?? '') === 'clock_out' ? 'selected' : ''; ?>>Sort by Clock-Out</option><option value="status" <?php echo ($filters['sort'] ?? '') === 'status' ? 'selected' : ''; ?>>Sort by Status</option></select><select name="direction" class="form-select"><option value="desc">Descending</option><option value="asc" <?php echo ($filters['direction'] ?? '') === 'asc' ? 'selected' : ''; ?>>Ascending</option></select><button class="btn btn-primary btn-sm" type="submit">Apply Filters</button><a class="btn btn-outline-brand btn-sm" href="<?php echo e(route_url('attendance/history')); ?>">Reset</a></form>
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
                                    
                                    <th>Attendance Status</th><th>Lateness</th><th>Overtime</th><th>Attendance Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceHistoryBody">
                                <?php if ($attendanceHistory === []): ?><tr><td colspan="10" class="text-center text-muted py-4">No attendance records found.</td></tr><?php else: ?>
                                <?php foreach ($attendanceHistory as $record): ?>
                                    <?php $status = $record['status']; ?>
                                    <tr data-attendance-row data-month="<?php echo e(substr($record['date'], 5, 2)); ?>" data-year="<?php echo e(substr($record['date'], 0, 4)); ?>" data-status="<?php echo e($status); ?>" data-date="<?php echo e($record['date']); ?>">
                                        <td><?php echo e($record['date']); ?></td>
                                        <td><?php echo e($record['day']); ?></td>
                                        <td><?php echo e($record['shift']); ?></td>
                                        <td><?php echo e($record['clock_in']); ?></td>
                                        <td><?php echo e($record['clock_out']); ?></td>
                                        <td><span class="table-badge attendance-status <?php echo e($statusClasses[$status] ?? 'attendance-status--leave'); ?>"><?php echo e($status); ?></span></td><td><?php echo e((string) $record['lateness']); ?> min</td><td><?php echo e((string) $record['overtime']); ?> min</td><td><?php echo e($record['remarks']); ?></td>
                                    </tr>
                                <?php endforeach; ?><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="history-pagination attendance-pagination">
                        <span id="attendanceHistoryCount">Showing <?php echo e((string) $pagination['from']); ?>-<?php echo e((string) $pagination['to']); ?> of <?php echo e((string) $pagination['total']); ?> records</span><div class="btn-group" role="group" aria-label="Attendance history pagination"><?php if ($pagination['page'] > 1): ?><a class="btn btn-outline-brand btn-sm" href="<?php echo e($pageUrl($pagination['page'] - 1)); ?>">Previous</a><?php endif; ?><?php if ($pagination['page'] < $pagination['pages']): ?><a class="btn btn-outline-brand btn-sm" href="<?php echo e($pageUrl($pagination['page'] + 1)); ?>">Next</a><?php endif; ?></div></div>
                </article>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

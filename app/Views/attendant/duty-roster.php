<?php

declare(strict_types=1);

$pageTitle = 'Duty Roster | FuelOps Staff Dashboard';
$pageHeading = 'Duty Roster';
$topbarSubtitle = 'Pump Attendant Dashboard';
$currentRoute = $currentRoute ?? 'duty-roster';
$extraStyles = ['css/clock-in.css', 'css/duty-roster.css'];
$extraScripts = ['js/duty-roster.js'];

// =======================================
// DATABASE PLACEHOLDER
// Load employee duty roster from MySQL.
// =======================================
$employee = $employee ?? [
    'employee_id' => 'EMP001',
    'name' => 'John Doe',
    'department' => 'Operations',
    'role' => 'Pump Attendant',
    'shift' => 'Morning',
    'current_assignment' => 'Pump 1 - PMS Lane',
    'passport_photo' => 'images/sample-passport.svg',
];

// =======================================
// DATABASE PLACEHOLDER
// Retrieve today's shift assignment.
// =======================================
$todaysDuty = $todaysDuty ?? [
    'date' => '2026-07-06',
    'shift' => 'Morning',
    'assigned_pump' => 'Pump 1',
    'fuel_type' => 'PMS',
    'reporting_time' => '6:00 AM',
    'closing_time' => '2:00 PM',
    'supervisor' => 'Mr. James',
    'status' => 'On Duty',
];

// =======================================
// DATABASE PLACEHOLDER
// Replace with duty roster records from the database.
// =======================================
$roster = $roster ?? [
    ['date' => '2026-07-06', 'day' => 'Monday', 'shift' => 'Morning', 'pump' => 'Pump 1', 'reporting_time' => '6:00 AM', 'closing_time' => '2:00 PM', 'supervisor' => 'Mr. James', 'status' => 'Scheduled'],
    ['date' => '2026-07-07', 'day' => 'Tuesday', 'shift' => 'Evening', 'pump' => 'Pump 3', 'reporting_time' => '2:00 PM', 'closing_time' => '10:00 PM', 'supervisor' => 'Mrs. Grace', 'status' => 'Scheduled'],
    ['date' => '2026-07-08', 'day' => 'Wednesday', 'shift' => 'Off', 'pump' => '-', 'reporting_time' => '-', 'closing_time' => '-', 'supervisor' => '-', 'status' => 'Off Duty'],
    ['date' => '2026-07-09', 'day' => 'Thursday', 'shift' => 'Morning', 'pump' => 'Pump 2', 'reporting_time' => '6:00 AM', 'closing_time' => '2:00 PM', 'supervisor' => 'Mr. James', 'status' => 'Upcoming'],
    ['date' => '2026-07-10', 'day' => 'Friday', 'shift' => 'Evening', 'pump' => 'Pump 4', 'reporting_time' => '2:00 PM', 'closing_time' => '10:00 PM', 'supervisor' => 'Mrs. Grace', 'status' => 'Upcoming'],
    ['date' => '2026-07-11', 'day' => 'Saturday', 'shift' => 'Morning', 'pump' => 'Pump 1', 'reporting_time' => '6:00 AM', 'closing_time' => '2:00 PM', 'supervisor' => 'Mr. James', 'status' => 'Upcoming'],
    ['date' => '2026-07-12', 'day' => 'Sunday', 'shift' => 'Off', 'pump' => '-', 'reporting_time' => '-', 'closing_time' => '-', 'supervisor' => '-', 'status' => 'Off Duty'],
    ['date' => '2026-07-13', 'day' => 'Monday', 'shift' => 'Evening', 'pump' => 'Pump 3', 'reporting_time' => '2:00 PM', 'closing_time' => '10:00 PM', 'supervisor' => 'Mrs. Grace', 'status' => 'Upcoming'],
];

$shiftStats = $shiftStats ?? [
    ['label' => 'Total Working Days This Month', 'value' => '22 Days', 'icon' => 'fa-solid fa-calendar-check'],
    ['label' => 'Morning Shifts', 'value' => '12 Shifts', 'icon' => 'fa-solid fa-sun'],
    ['label' => 'Evening Shifts', 'value' => '10 Shifts', 'icon' => 'fa-solid fa-moon'],
    ['label' => 'Days Off', 'value' => '8 Days', 'icon' => 'fa-solid fa-bed'],
    ['label' => 'Upcoming Shift', 'value' => 'Thu, Jul 9 - Morning', 'icon' => 'fa-solid fa-clock'],
];

$calendarDays = $calendarDays ?? [
    ['day' => 1, 'shift' => 'Morning'],
    ['day' => 2, 'shift' => 'Evening'],
    ['day' => 3, 'shift' => 'Off'],
    ['day' => 4, 'shift' => 'Morning'],
    ['day' => 5, 'shift' => 'Off'],
    ['day' => 6, 'shift' => 'Morning', 'today' => true],
    ['day' => 7, 'shift' => 'Evening'],
    ['day' => 8, 'shift' => 'Off'],
    ['day' => 9, 'shift' => 'Morning'],
    ['day' => 10, 'shift' => 'Evening'],
    ['day' => 11, 'shift' => 'Morning'],
    ['day' => 12, 'shift' => 'Off'],
    ['day' => 13, 'shift' => 'Evening'],
    ['day' => 14, 'shift' => 'Morning'],
    ['day' => 15, 'shift' => 'Off'],
    ['day' => 16, 'shift' => 'Morning'],
    ['day' => 17, 'shift' => 'Evening'],
    ['day' => 18, 'shift' => 'Morning'],
    ['day' => 19, 'shift' => 'Off'],
    ['day' => 20, 'shift' => 'Evening'],
    ['day' => 21, 'shift' => 'Morning'],
    ['day' => 22, 'shift' => 'Off'],
    ['day' => 23, 'shift' => 'Morning'],
    ['day' => 24, 'shift' => 'Evening'],
    ['day' => 25, 'shift' => 'Morning'],
    ['day' => 26, 'shift' => 'Off'],
    ['day' => 27, 'shift' => 'Evening'],
    ['day' => 28, 'shift' => 'Morning'],
    ['day' => 29, 'shift' => 'Off'],
    ['day' => 30, 'shift' => 'Morning'],
    ['day' => 31, 'shift' => 'Evening'],
];

$statusClasses = [
    'On Duty' => 'roster-status--on-duty',
    'Off Duty' => 'roster-status--off-duty',
    'Upcoming' => 'roster-status--upcoming',
    'Scheduled' => 'roster-status--scheduled',
];

$attendantName = $employee['name'] ?? 'Station Staff';
$attendantRole = $employee['role'] ?? 'Pump Attendant';

require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page roster-page">
    <section class="clock-hero roster-hero">
        <div class="container-fluid">
          

            <div class="clock-hero__content roster-hero-card">
                <div>
                    <span class="eyebrow">My Schedule</span>
                    <h1><?php echo e($pageHeading); ?></h1>
                    <p>View assigned shifts, pump duties, and upcoming workdays. Schedule changes are managed by supervisors.</p>
                </div>
                <span class="employee-avatar" aria-hidden="true">
                    <i class="fa-solid fa-calendar-days"></i>
                </span>
            </div>
        </div>
    </section>

    <section class="container-fluid clock-workspace">
        <div class="row g-4">
            <div class="col-12 col-xl-5">
                <article class="app-card card roster-employee-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Employee Information</span>
                            <h2><?php echo e($employee['name']); ?></h2>
                        </div>
                        <img class="roster-passport" src="<?php echo e(asset_url($employee['passport_photo'])); ?>" alt="Passport photo of <?php echo e($employee['name']); ?>">
                    </div>
                    <div class="employee-grid">
                        <div><span>Employee ID</span><strong><?php echo e($employee['employee_id']); ?></strong></div>
                        <div><span>Department</span><strong><?php echo e($employee['department']); ?></strong></div>
                        <div><span>Role</span><strong><?php echo e($employee['role']); ?></strong></div>
                        <div><span>Assigned Shift</span><strong><?php echo e($employee['shift']); ?></strong></div>
                        <div class="employee-grid__wide"><span>Current Assignment</span><strong><?php echo e($employee['current_assignment']); ?></strong></div>
                    </div>
                </article>
            </div>

            <div class="col-12 col-xl-7">
                <article class="app-card card today-duty-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Today's Assignment</span>
                            <h2><?php echo e($todaysDuty['date']); ?></h2>
                        </div>
                        <span class="table-badge roster-status <?php echo e($statusClasses[$todaysDuty['status']] ?? 'roster-status--scheduled'); ?>"><?php echo e($todaysDuty['status']); ?></span>
                    </div>
                    <div class="today-duty-grid">
                        <div><span>Date</span><strong><?php echo e($todaysDuty['date']); ?></strong></div>
                        <div><span>Shift</span><strong><?php echo e($todaysDuty['shift']); ?></strong></div>
                        <div><span>Assigned Pump</span><strong><?php echo e($todaysDuty['assigned_pump']); ?></strong></div>
                        <div><span>Fuel Type</span><strong><?php echo e($todaysDuty['fuel_type']); ?></strong></div>
                        <div><span>Reporting Time</span><strong><?php echo e($todaysDuty['reporting_time']); ?></strong></div>
                        <div><span>Closing Time</span><strong><?php echo e($todaysDuty['closing_time']); ?></strong></div>
                        <div class="today-duty-grid__wide"><span>Supervisor</span><strong><?php echo e($todaysDuty['supervisor']); ?></strong></div>
                    </div>
                </article>
            </div>

            <div class="col-12 col-xl-7">
                <article class="app-card card roster-calendar-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Monthly Calendar View</span>
                            <h2>July 2026</h2>
                        </div>
                        <div class="roster-calendar-legend">
                            <span><i class="legend-dot legend-morning"></i>Morning</span>
                            <span><i class="legend-dot legend-evening"></i>Evening</span>
                            <span><i class="legend-dot legend-off"></i>Off</span>
                        </div>
                    </div>
                    <div class="roster-calendar-grid" aria-label="July 2026 duty calendar">
                        <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday): ?>
                            <span class="roster-calendar-weekday"><?php echo e($weekday); ?></span>
                        <?php endforeach; ?>
                        <?php foreach ($calendarDays as $day): ?>
                            <?php $shiftKey = strtolower($day['shift']); ?>
                            <button type="button" class="roster-calendar-day roster-calendar-day--<?php echo e($shiftKey); ?> <?php echo !empty($day['today']) ? 'is-today' : ''; ?>" data-calendar-day="<?php echo e((string) $day['day']); ?>" data-calendar-shift="<?php echo e($day['shift']); ?>">
                                <strong><?php echo e((string) $day['day']); ?></strong>
                                <span><?php echo e($day['shift']); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </article>
            </div>

            <div class="col-12 col-xl-5">
                <article class="app-card card roster-stats-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Shift Information</span>
                            <h2>Monthly Summary</h2>
                        </div>
                        <span class="employee-avatar" aria-hidden="true"><i class="fa-solid fa-chart-simple"></i></span>
                    </div>
                    <div class="roster-stats-list">
                        <?php foreach ($shiftStats as $stat): ?>
                            <div class="roster-stat-item">
                                <span class="roster-stat-icon" aria-hidden="true"><i class="<?php echo e($stat['icon']); ?>"></i></span>
                                <div>
                                    <span><?php echo e($stat['label']); ?></span>
                                    <strong><?php echo e($stat['value']); ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            </div>

            <div class="col-12">
                <article class="app-card card roster-table-card">
                    <div class="history-toolbar roster-table-toolbar">
                        <div>
                            <span class="eyebrow">Weekly Duty Schedule</span>
                            <h2>Assigned Workdays</h2>
                        </div>
                        <div class="roster-filters">
                            <label class="visually-hidden" for="rosterSearch">Search by date</label>
                            <div class="filter-control">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search" id="rosterSearch" class="form-control" placeholder="Search date or pump">
                            </div>
                            <label class="visually-hidden" for="shiftFilter">Filter by shift</label>
                            <select id="shiftFilter" class="form-select">
                                <option value="">All Shifts</option>
                                <option value="Morning">Morning</option>
                                <option value="Evening">Evening</option>
                                <option value="Off">Off</option>
                            </select>
                            <label class="visually-hidden" for="statusFilter">Filter by status</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="Scheduled">Scheduled</option>
                                <option value="Upcoming">Upcoming</option>
                                <option value="Off Duty">Off Duty</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table attendance-table roster-table align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Shift</th>
                                    <th>Assigned Pump</th>
                                    <th>Reporting Time</th>
                                    <th>Closing Time</th>
                                    <th>Supervisor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="rosterTableBody">
                                <?php foreach ($roster as $record): ?>
                                    <tr data-roster-row data-shift="<?php echo e($record['shift']); ?>" data-status="<?php echo e($record['status']); ?>" data-date="<?php echo e($record['date']); ?>">
                                        <td><?php echo e($record['date']); ?></td>
                                        <td><?php echo e($record['day']); ?></td>
                                        <td><?php echo e($record['shift']); ?></td>
                                        <td><?php echo e($record['pump']); ?></td>
                                        <td><?php echo e($record['reporting_time']); ?></td>
                                        <td><?php echo e($record['closing_time']); ?></td>
                                        <td><?php echo e($record['supervisor']); ?></td>
                                        <td><span class="table-badge roster-status <?php echo e($statusClasses[$record['status']] ?? 'roster-status--scheduled'); ?>"><?php echo e($record['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="history-pagination roster-pagination">
                        <span id="rosterCount">Showing roster records</span>
                        <div class="btn-group" role="group" aria-label="Roster pagination">
                            <button type="button" class="btn btn-outline-brand btn-sm" id="rosterPrevPage">Previous</button>
                            <button type="button" class="btn btn-outline-brand btn-sm" id="rosterNextPage">Next</button>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

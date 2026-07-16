<?php

declare(strict_types=1);

$pageTitle = 'Duty Roster | FuelOps Staff Dashboard';
$pageHeading = 'Duty Roster';
$topbarSubtitle = 'Pump Attendant Dashboard';
$currentRoute = $currentRoute ?? 'duty-roster';
$extraStyles = ['css/clock-in.css', 'css/duty-roster.css'];
$extraScripts = ['js/duty-roster.js'];

$employee = $employee ?? ['employee_id' => 'N/A', 'name' => 'Station Staff', 'department' => 'Unassigned', 'role' => 'Pump Attendant', 'shift' => 'No shift assigned', 'current_assignment' => 'No duty assigned for today', 'passport_photo' => 'images/sample-passport.svg'];
$todaysDuty = $todaysDuty ?? ['date' => date('Y-m-d'), 'shift' => 'No duty assigned for today', 'assigned_pump' => 'N/A', 'fuel_type' => 'N/A', 'reporting_time' => 'N/A', 'closing_time' => 'N/A', 'supervisor' => 'N/A', 'status' => 'No Assignment', 'has_assignment' => false];
$roster = $roster ?? [];
$shiftStats = $shiftStats ?? [];
$calendarAssignments = $calendarAssignments ?? [];
$calendarLabel = $calendarLabel ?? date('F Y');
$calendarMonth = $calendarMonth ?? date('m');
$calendarYear = $calendarYear ?? date('Y');
$filters = $filters ?? [];
$pagination = $pagination ?? ['page' => 1, 'pages' => 1, 'total' => 0, 'from' => 0, 'to' => 0];
$shifts = $shifts ?? [];
$fuelTypes = $fuelTypes ?? [];
$statusClasses = ['Active' => 'roster-status--on-duty', 'Completed' => 'roster-status--off-duty', 'Off Duty' => 'roster-status--off-duty', 'Upcoming' => 'roster-status--upcoming', 'Scheduled' => 'roster-status--scheduled', 'No Assignment' => 'roster-status--off-duty'];
$years = range((int) date('Y'), max(2020, (int) date('Y') - 3));
$pageUrl = static function (int $page) use ($filters): string {
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn (mixed $value): bool => $value !== '' && $value !== null);
    return route_url('duty-roster') . '&' . http_build_query($query);
};
$calendarUrl = static function (int $timestamp) use ($filters): string {
    return route_url('duty-roster') . '&' . http_build_query(array_merge($filters, ['month' => date('m', $timestamp), 'year' => date('Y', $timestamp), 'page' => 1]));
};
$calendarTimestamp = strtotime($calendarYear . '-' . $calendarMonth . '-01');
$daysInMonth = (int) date('t', $calendarTimestamp);
$leadingBlanks = (int) date('N', $calendarTimestamp) - 1;
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

    <section class="container-fluid clock-workspace"><?php if (!empty($dutyError)): ?><div class="alert alert-warning"><?php echo e((string) $dutyError); ?></div><?php endif; ?>
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
                            <h2><a class="btn btn-sm btn-light" href="<?php echo e($calendarUrl(strtotime('-1 month', $calendarTimestamp))); ?>" aria-label="Previous month">&larr;</a> <?php echo e($calendarLabel); ?> <a class="btn btn-sm btn-light" href="<?php echo e($calendarUrl(strtotime('+1 month', $calendarTimestamp))); ?>" aria-label="Next month">&rarr;</a></h2>
                        </div>
                        <div class="roster-calendar-legend">
                            <span><i class="legend-dot legend-morning"></i>Morning</span>
                            <span><i class="legend-dot legend-evening"></i>Evening</span>
                            <span><i class="legend-dot legend-off"></i>Off</span>
                        </div>
                    </div>
                    <div class="roster-calendar-grid" aria-label="<?php echo e($calendarLabel); ?> duty calendar">
                        <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday): ?>
                            <span class="roster-calendar-weekday"><?php echo e($weekday); ?></span>
                        <?php endforeach; ?>
                        <?php for ($blank = 0; $blank < $leadingBlanks; $blank++): ?><span class="roster-calendar-day is-empty" aria-hidden="true"></span><?php endfor; ?>
                        <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                            <?php $assignments = $calendarAssignments[$day] ?? []; $first = $assignments[0] ?? null; $shiftKey = $first === null ? 'off' : strtolower(str_contains(strtolower($first['shift']), 'even') ? 'evening' : 'morning'); ?>
                            <button type="button" class="roster-calendar-day roster-calendar-day--<?php echo e($shiftKey); ?> <?php echo date('Y-m-d') === sprintf('%04d-%02d-%02d', $calendarYear, $calendarMonth, $day) ? 'is-today' : ''; ?>" data-calendar-day="<?php echo e((string) $day); ?>" data-calendar-details="<?php echo e($first === null ? 'No duty assigned.' : $first['shift'] . ' | ' . $first['pump'] . ' | ' . $first['fuel_type'] . ' | ' . $first['reporting_time'] . ' - ' . $first['closing_time']); ?>">
                                <strong><?php echo e((string) $day); ?></strong><span><?php echo e($first['shift'] ?? 'No Duty'); ?></span><?php if (count($assignments) > 1): ?><small>+<?php echo e((string) (count($assignments) - 1)); ?> more</small><?php endif; ?>
                            </button>
                        <?php endfor; ?>
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
                        <form class="roster-filters" method="get" action="<?php echo e(app_base_url() . '/index.php'); ?>"><input type="hidden" name="route" value="duty-roster">
                            <label class="visually-hidden" for="rosterSearch">Search by date</label>
                            <div class="filter-control">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search" id="rosterSearch" name="search" value="<?php echo e((string) ($filters['search'] ?? '')); ?>" class="form-control" placeholder="Search date or pump">
                            </div>
                            <label class="visually-hidden" for="shiftFilter">Filter by shift</label>
                            <select id="shiftFilter" name="shift" class="form-select"><option value="">All Shifts</option><?php foreach ($shifts as $option): ?><option value="<?php echo e($option['value']); ?>" <?php echo ($filters['shift'] ?? '') === $option['value'] ? 'selected' : ''; ?>><?php echo e($option['label']); ?></option><?php endforeach; ?></select>
                            <select name="fuel" class="form-select"><option value="">All Fuel Types</option><?php foreach ($fuelTypes as $option): ?><option value="<?php echo e($option['value']); ?>" <?php echo ($filters['fuel'] ?? '') === $option['value'] ? 'selected' : ''; ?>><?php echo e($option['label']); ?></option><?php endforeach; ?></select><select name="month" class="form-select"><?php foreach (range(1, 12) as $month): $value = str_pad((string) $month, 2, '0', STR_PAD_LEFT); ?><option value="<?php echo e($value); ?>" <?php echo $calendarMonth === $value ? 'selected' : ''; ?>><?php echo e(date('F', mktime(0, 0, 0, $month, 1))); ?></option><?php endforeach; ?></select><select name="year" class="form-select"><?php foreach ($years as $year): ?><option value="<?php echo e((string) $year); ?>" <?php echo (string) $calendarYear === (string) $year ? 'selected' : ''; ?>><?php echo e((string) $year); ?></option><?php endforeach; ?></select><button class="btn btn-primary btn-sm" type="submit">Apply Filters</button><a class="btn btn-outline-brand btn-sm" href="<?php echo e(route_url('duty-roster')); ?>">Reset</a></form>
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
                                <?php if ($roster === []): ?><tr><td colspan="9" class="text-center text-muted py-4">No duty assignments available.</td></tr><?php else: ?>
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
                                <?php endforeach; ?><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="history-pagination roster-pagination">
                        <span id="rosterCount">Showing <?php echo e((string) $pagination['from']); ?>-<?php echo e((string) $pagination['to']); ?> of <?php echo e((string) $pagination['total']); ?> assignments</span><div class="btn-group"><?php if ($pagination['page'] > 1): ?><a class="btn btn-outline-brand btn-sm" href="<?php echo e($pageUrl($pagination['page'] - 1)); ?>">Previous</a><?php endif; ?><?php if ($pagination['page'] < $pagination['pages']): ?><a class="btn btn-outline-brand btn-sm" href="<?php echo e($pageUrl($pagination['page'] + 1)); ?>">Next</a><?php endif; ?></div></div>
                </article>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

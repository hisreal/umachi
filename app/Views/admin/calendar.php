<?php

declare(strict_types=1);

$pageTitle = 'Duty Calendar | FuelOps Admin Dashboard';
$pageHeading = 'Duty Calendar';
$currentRoute = 'admin/duty-calendar';
$extraScripts = [
    'js/admin-dashboard.js',
    'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js',
    'js/duty-roster-management.js',
];
require __DIR__ . '/duty-roster-setup.php';
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page duty-module-page">
    <section class="clock-hero duty-hero"><div class="container-fluid"><nav class="duty-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Duty Roster</span><i class="fa-solid fa-chevron-right"></i><span>Calendar</span></nav><div class="clock-hero__content duty-hero-card"><div><span class="eyebrow">Monthly View</span><h1>Duty Calendar</h1><p>View scheduled duty assignments across shifts, pumps, and fuel types.</p></div><span class="duty-hero-icon"><i class="fa-solid fa-calendar-days"></i></span></div></div></section>
    <section class="container-fluid clock-workspace">
        <article class="app-card card duty-table-card">
            <div class="duty-toolbar"><div><span class="eyebrow">Calendar Filters</span><h2>Monthly Duty Schedule</h2></div></div>
            <div class="duty-filter-grid">
                <select class="form-select" id="calendarEmployeeFilter"><option value="">All employees</option><?php foreach ($employees as $staff): ?><option value="<?php echo e($staff['name']); ?>"><?php echo e($staff['name']); ?></option><?php endforeach; ?></select>
                <select class="form-select" id="calendarDepartmentFilter"><option value="">All departments</option><?php foreach ($departments as $department): ?><option value="<?php echo e($department); ?>"><?php echo e($department); ?></option><?php endforeach; ?></select>
                <select class="form-select" id="calendarShiftFilter"><option value="">All shifts</option><?php foreach ($shiftNames as $shift): ?><option value="<?php echo e($shift); ?>"><?php echo e($shift); ?></option><?php endforeach; ?></select>
                <select class="form-select" id="calendarPumpFilter"><option value="">All pumps</option><?php foreach ($pumps as $pump): ?><option value="<?php echo e($pump); ?>"><?php echo e($pump); ?></option><?php endforeach; ?></select>
                <select class="form-select" id="calendarFuelFilter"><option value="">All fuel types</option><?php foreach ($fuelTypes as $fuelType): ?><option value="<?php echo e($fuelType); ?>"><?php echo e($fuelType); ?></option><?php endforeach; ?></select>
            </div>
            <div class="duty-calendar-layout">
                <div id="dutyCalendar"></div>
                <aside class="calendar-legend">
                    <h3>Legend</h3>
                    <span><i class="legend-dot legend-dot--morning"></i>Morning Shift</span>
                    <span><i class="legend-dot legend-dot--evening"></i>Evening Shift</span>
                    <span><i class="legend-dot legend-dot--leave"></i>Leave</span>
                    <span><i class="legend-dot legend-dot--off"></i>Off Duty</span>
                </aside>
            </div>
        </article>
    </section>
</main>
<script>
    window.dutyRosterEvents = <?php echo json_encode($calendarEvents, JSON_THROW_ON_ERROR); ?>;
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
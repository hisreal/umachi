<?php

declare(strict_types=1);

$pageTitle = 'Shift & Pump Duty Management | FuelOps Manager Dashboard';
$pageHeading = 'Shift & Pump Duty Management';
$topbarSubtitle = 'Manager & Supervisor Dashboard';
$currentRoute = $currentRoute ?? 'supervisor/manage-duty-roster';
$extraStyles = ['css/clock-in.css', 'css/manage-duty-roster.css'];
$extraScripts = ['js/manage-duty-roster.js'];

// ========================================
// DATABASE PLACEHOLDER
// Replace this supervisor profile with the
// authenticated manager or supervisor record.
// ========================================
$employee = $employee ?? [
    'name' => 'Supervisor A',
    'role' => 'Operations Supervisor',
];

$attendantName = $employee['name'];
$attendantRole = $employee['role'];

// ========================================
// DATABASE PLACEHOLDER
// Replace summary values with live counts
// retrieved from MySQL.
// ========================================
$summaryCards = [
    ['label' => 'Total Pump Attendants', 'value' => '10 Attendants', 'icon' => 'fa-solid fa-users', 'tone' => 'primary'],
    ['label' => 'Total Pumps', 'value' => '4 Pumps', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'secondary'],
    ['label' => 'Morning Shift Assigned', 'value' => '5 Employees', 'icon' => 'fa-solid fa-sun', 'tone' => 'success'],
    ['label' => 'Evening Shift Assigned', 'value' => '5 Employees', 'icon' => 'fa-solid fa-moon', 'tone' => 'warning'],
];

// ========================================
// DATABASE PLACEHOLDER
// Retrieve employees from MySQL.
// ========================================
$employees = [
    'John Doe',
    'Mary Johnson',
    'Daniel James',
    'Esther Grace',
    'Chinedu Okafor',
    'Aisha Bello',
    'Samuel Peters',
    'Ngozi Williams',
    'Ibrahim Musa',
    'Faith Emmanuel',
];

$pumps = ['Pump 1', 'Pump 2', 'Pump 3', 'Pump 4'];
$fuelTypes = [
    'Petrol (Petrol)',
    'Diesel (AGO)',
    'Gas (LPG)',
];
$shifts = ['Morning', 'Evening'];
$supervisors = ['Supervisor A', 'Supervisor B'];
$statuses = ['Scheduled', 'Completed', 'Cancelled'];

// ========================================
// DATABASE PLACEHOLDER
// Retrieve duty schedules and pump allocation
// records from MySQL.
// ========================================
$dutyAssignments = [
    ['date' => '2026-07-06', 'employee' => 'John Doe', 'shift' => 'Morning', 'pump' => 'Pump 1', 'fuel_type' => 'Petrol (Petrol)', 'reporting_time' => '06:00 AM', 'closing_time' => '02:00 PM', 'supervisor' => 'Supervisor A', 'status' => 'Scheduled'],
    ['date' => '2026-07-06', 'employee' => 'Mary Johnson', 'shift' => 'Morning', 'pump' => 'Pump 2', 'fuel_type' => 'Diesel (AGO)', 'reporting_time' => '06:00 AM', 'closing_time' => '02:00 PM', 'supervisor' => 'Supervisor A', 'status' => 'Scheduled'],
    ['date' => '2026-07-06', 'employee' => 'Daniel James', 'shift' => 'Evening', 'pump' => 'Pump 1', 'fuel_type' => 'Petrol (Petrol)', 'reporting_time' => '02:00 PM', 'closing_time' => '10:00 PM', 'supervisor' => 'Supervisor B', 'status' => 'Scheduled'],
    ['date' => '2026-07-05', 'employee' => 'Esther Grace', 'shift' => 'Evening', 'pump' => 'Pump 4', 'fuel_type' => 'Gas (LPG)', 'reporting_time' => '02:00 PM', 'closing_time' => '10:00 PM', 'supervisor' => 'Supervisor B', 'status' => 'Completed'],
    ['date' => '2026-07-05', 'employee' => 'Chinedu Okafor', 'shift' => 'Morning', 'pump' => 'Pump 3', 'fuel_type' => 'Petrol (Petrol)', 'reporting_time' => '06:00 AM', 'closing_time' => '02:00 PM', 'supervisor' => 'Supervisor A', 'status' => 'Completed'],
    ['date' => '2026-07-04', 'employee' => 'Aisha Bello', 'shift' => 'Evening', 'pump' => 'Pump 2', 'fuel_type' => 'Diesel (AGO)', 'reporting_time' => '02:00 PM', 'closing_time' => '10:00 PM', 'supervisor' => 'Supervisor B', 'status' => 'Cancelled'],
    ['date' => '2026-07-07', 'employee' => 'Samuel Peters', 'shift' => 'Morning', 'pump' => 'Pump 4', 'fuel_type' => 'Gas (LPG)', 'reporting_time' => '06:00 AM', 'closing_time' => '02:00 PM', 'supervisor' => 'Supervisor A', 'status' => 'Scheduled'],
    ['date' => '2026-07-07', 'employee' => 'Ngozi Williams', 'shift' => 'Evening', 'pump' => 'Pump 3', 'fuel_type' => 'Petrol (Petrol)', 'reporting_time' => '02:00 PM', 'closing_time' => '10:00 PM', 'supervisor' => 'Supervisor B', 'status' => 'Scheduled'],
];

$weeklyRoster = [
    ['employee' => 'John Doe', 'monday' => 'Pump 1 / Petrol / Morning', 'tuesday' => 'Pump 2 / AGO / Morning', 'wednesday' => 'Off', 'thursday' => 'Pump 3 / Petrol / Evening', 'friday' => 'Pump 1 / Petrol / Morning', 'saturday' => 'Off'],
    ['employee' => 'Mary Johnson', 'monday' => 'Pump 2 / AGO / Morning', 'tuesday' => 'Off', 'wednesday' => 'Pump 4 / LPG / Evening', 'thursday' => 'Pump 1 / Petrol / Morning', 'friday' => 'Pump 3 / Petrol / Evening', 'saturday' => 'Pump 2 / AGO / Morning'],
    ['employee' => 'Daniel James', 'monday' => 'Pump 1 / Petrol / Evening', 'tuesday' => 'Pump 3 / Petrol / Evening', 'wednesday' => 'Pump 2 / AGO / Morning', 'thursday' => 'Off', 'friday' => 'Pump 4 / LPG / Evening', 'saturday' => 'Pump 1 / Petrol / Morning'],
    ['employee' => 'Esther Grace', 'monday' => 'Off', 'tuesday' => 'Pump 4 / LPG / Evening', 'wednesday' => 'Pump 1 / Petrol / Morning', 'thursday' => 'Pump 2 / AGO / Evening', 'friday' => 'Off', 'saturday' => 'Pump 3 / Petrol / Morning'],
];

$pumpAllocation = [
    ['pump' => 'Pump 1', 'fuel_type' => 'Petrol (Petrol)', 'morning' => 'John Doe', 'evening' => 'Daniel James'],
    ['pump' => 'Pump 2', 'fuel_type' => 'Diesel (AGO)', 'morning' => 'Mary Johnson', 'evening' => 'Aisha Bello'],
    ['pump' => 'Pump 3', 'fuel_type' => 'Petrol (Petrol)', 'morning' => 'Chinedu Okafor', 'evening' => 'Ngozi Williams'],
    ['pump' => 'Pump 4', 'fuel_type' => 'Gas (LPG)', 'morning' => 'Samuel Peters', 'evening' => 'Esther Grace'],
];

$statusClasses = [
    'Scheduled' => 'duty-status--scheduled',
    'Completed' => 'duty-status--completed',
    'Cancelled' => 'duty-status--cancelled',
];

require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page manage-duty-page">
    <section class="clock-hero manage-duty-hero">
        <div class="container-fluid">
            <nav class="page-breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo e(route_url('dashboard')); ?>">Dashboard</a>
                <i class="fa-solid fa-chevron-right"></i>
                <span>Duty Management</span>
            </nav>

            <div class="clock-hero__content manage-duty-hero-card">
                <div>
                    <span class="eyebrow">Manager & Supervisor Tools</span>
                    <h1><?php echo e($pageHeading); ?></h1>
                    <p>Assign attendants to shifts, pumps, and fuel lanes using sample data ready for future database integration.</p>
                </div>
                <span class="employee-avatar" aria-hidden="true">
                    <i class="fa-solid fa-calendar-check"></i>
                </span>
            </div>
        </div>
    </section>

    <section class="container-fluid clock-workspace">
        <div class="row g-3 g-xl-4 duty-summary-row">
            <?php foreach ($summaryCards as $card): ?>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="duty-summary-card duty-summary-card--<?php echo e($card['tone']); ?>">
                        <span class="duty-summary-icon"><i class="<?php echo e($card['icon']); ?>"></i></span>
                        <div>
                            <span><?php echo e($card['label']); ?></span>
                            <strong><?php echo e($card['value']); ?></strong>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-4 align-items-start mt-1">
            <div class="col-12 col-xl-5">
                <article class="app-card card duty-form-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Assign Duty</span>
                            <h2>Assign Pump Duty</h2>
                        </div>
                        <span class="section-icon"><i class="fa-solid fa-clipboard-check"></i></span>
                    </div>

                    <form id="assignDutyForm" class="needs-validation duty-assignment-form" novalidate>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="employeeName">Employee</label>
                                <select class="form-select" id="employeeName" required>
                                    <option value="">Select employee</option>
                                    <?php foreach ($employees as $staffName): ?>
                                        <option value="<?php echo e($staffName); ?>"><?php echo e($staffName); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Select an employee.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="dutyDate">Duty Date</label>
                                <input class="form-control" type="date" id="dutyDate" value="2026-07-08" required>
                                <div class="invalid-feedback">Choose a duty date.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="shiftName">Shift</label>
                                <select class="form-select" id="shiftName" required>
                                    <option value="">Select shift</option>
                                    <?php foreach ($shifts as $shift): ?>
                                        <option value="<?php echo e($shift); ?>"><?php echo e($shift); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Select a shift.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="pumpName">Pump</label>
                                <select class="form-select" id="pumpName" required>
                                    <option value="">Select pump</option>
                                    <?php foreach ($pumps as $pump): ?>
                                        <option value="<?php echo e($pump); ?>"><?php echo e($pump); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Select a pump.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="fuelType">Fuel Type</label>
                                <select class="form-select" id="fuelType" required>
                                    <option value="">Select fuel type</option>
                                    <?php foreach ($fuelTypes as $fuelType): ?>
                                        <option value="<?php echo e($fuelType); ?>"><?php echo e($fuelType); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Select a fuel type.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="reportingTime">Reporting Time</label>
                                <input class="form-control" type="time" id="reportingTime" value="06:00" required>
                                <div class="invalid-feedback">Enter the reporting time.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="closingTime">Closing Time</label>
                                <input class="form-control" type="time" id="closingTime" value="14:00" required>
                                <div class="invalid-feedback">Closing time must be after reporting time.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="supervisorName">Supervisor</label>
                                <select class="form-select" id="supervisorName" required>
                                    <option value="">Select supervisor</option>
                                    <?php foreach ($supervisors as $supervisor): ?>
                                        <option value="<?php echo e($supervisor); ?>"><?php echo e($supervisor); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Select a supervisor.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="dutyRemarks">Remarks</label>
                                <textarea class="form-control" id="dutyRemarks" rows="3" placeholder="Optional duty notes"></textarea>
                            </div>
                        </div>

                        <div class="duty-form-actions">
                            <button class="btn btn-primary" type="submit">
                                <i class="fa-solid fa-check"></i>
                                Assign Duty
                            </button>
                            <button class="btn btn-outline-brand" type="reset">
                                <i class="fa-solid fa-rotate-left"></i>
                                Reset
                            </button>
                        </div>
                    </form>
                </article>
            </div>

            <div class="col-12 col-xl-7">
                <article class="app-card card duty-table-card">
                    <div class="app-card__header align-items-start">
                        <div>
                            <span class="eyebrow">Roster Control</span>
                            <h2>Current Duty Assignments</h2>
                        </div>
                        <span class="section-icon"><i class="fa-solid fa-table-list"></i></span>
                    </div>

                    <div class="duty-filters" aria-label="Duty assignment filters">
                        <div class="filter-control filter-control--wide">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="search" id="dutySearch" placeholder="Search employee">
                        </div>
                        <input class="form-control" type="date" id="dateFilter" aria-label="Filter by duty date">
                        <select class="form-select" id="shiftFilter" aria-label="Filter by shift">
                            <option value="">All shifts</option>
                            <?php foreach ($shifts as $shift): ?>
                                <option value="<?php echo e($shift); ?>"><?php echo e($shift); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-select" id="pumpFilter" aria-label="Filter by pump">
                            <option value="">All pumps</option>
                            <?php foreach ($pumps as $pump): ?>
                                <option value="<?php echo e($pump); ?>"><?php echo e($pump); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-select" id="fuelFilter" aria-label="Filter by fuel type">
                            <option value="">All fuel types</option>
                            <?php foreach ($fuelTypes as $fuelType): ?>
                                <option value="<?php echo e($fuelType); ?>"><?php echo e($fuelType); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-select" id="supervisorFilter" aria-label="Filter by supervisor">
                            <option value="">All supervisors</option>
                            <?php foreach ($supervisors as $supervisor): ?>
                                <option value="<?php echo e($supervisor); ?>"><?php echo e($supervisor); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-select" id="statusFilter" aria-label="Filter by status">
                            <option value="">All statuses</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?php echo e($status); ?>"><?php echo e($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="table-responsive duty-table-wrap">
                        <table class="table attendance-table manage-duty-table align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Shift</th>
                                    <th>Pump</th>
                                    <th>Fuel Type</th>
                                    <th>Reporting Time</th>
                                    <th>Closing Time</th>
                                    <th>Supervisor</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="dutyAssignmentBody">
                                <?php foreach ($dutyAssignments as $assignment): ?>
                                    <tr data-duty-row
                                        data-date="<?php echo e($assignment['date']); ?>"
                                        data-employee="<?php echo e($assignment['employee']); ?>"
                                        data-shift="<?php echo e($assignment['shift']); ?>"
                                        data-pump="<?php echo e($assignment['pump']); ?>"
                                        data-fuel="<?php echo e($assignment['fuel_type']); ?>"
                                        data-supervisor="<?php echo e($assignment['supervisor']); ?>"
                                        data-status="<?php echo e($assignment['status']); ?>">
                                        <td><?php echo e($assignment['date']); ?></td>
                                        <td><strong><?php echo e($assignment['employee']); ?></strong></td>
                                        <td><?php echo e($assignment['shift']); ?></td>
                                        <td><?php echo e($assignment['pump']); ?></td>
                                        <td><?php echo e($assignment['fuel_type']); ?></td>
                                        <td><?php echo e($assignment['reporting_time']); ?></td>
                                        <td><?php echo e($assignment['closing_time']); ?></td>
                                        <td><?php echo e($assignment['supervisor']); ?></td>
                                        <td><span class="table-badge duty-status <?php echo e($statusClasses[$assignment['status']] ?? 'duty-status--scheduled'); ?>"><?php echo e($assignment['status']); ?></span></td>
                                        <td>
                                            <div class="duty-actions" aria-label="Duty assignment actions">
                                                <button type="button" class="btn btn-sm btn-light" data-duty-action="view" aria-label="View assignment"><i class="fa-solid fa-eye"></i></button>
                                                <button type="button" class="btn btn-sm btn-light" data-duty-action="edit" aria-label="Edit assignment"><i class="fa-solid fa-pen-to-square"></i></button>
                                                <button type="button" class="btn btn-sm btn-light duty-action-danger" data-duty-action="delete" aria-label="Delete assignment"><i class="fa-solid fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="duty-pagination">
                        <span id="dutyPageSummary">Showing sample records</span>
                        <div>
                            <button class="btn btn-outline-brand btn-sm" type="button" id="prevDutyPage"><i class="fa-solid fa-chevron-left"></i></button>
                            <button class="btn btn-outline-brand btn-sm" type="button" id="nextDutyPage"><i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </div>
                </article>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-12 col-xl-8">
                <article class="app-card card weekly-duty-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Weekly View</span>
                            <h2>Weekly Duty Calendar</h2>
                        </div>
                        <span class="section-icon"><i class="fa-solid fa-calendar-week"></i></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table attendance-table weekly-roster-table align-middle">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Monday</th>
                                    <th>Tuesday</th>
                                    <th>Wednesday</th>
                                    <th>Thursday</th>
                                    <th>Friday</th>
                                    <th>Saturday</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($weeklyRoster as $week): ?>
                                    <tr>
                                        <td><strong><?php echo e($week['employee']); ?></strong></td>
                                        <td><?php echo e($week['monday']); ?></td>
                                        <td><?php echo e($week['tuesday']); ?></td>
                                        <td><?php echo e($week['wednesday']); ?></td>
                                        <td><?php echo e($week['thursday']); ?></td>
                                        <td><?php echo e($week['friday']); ?></td>
                                        <td><?php echo e($week['saturday']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>

            <div class="col-12 col-xl-4">
                <article class="app-card card pump-allocation-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Pump Coverage</span>
                            <h2>Pump Allocation Summary</h2>
                        </div>
                        <span class="section-icon"><i class="fa-solid fa-gas-pump"></i></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table attendance-table pump-allocation-table align-middle">
                            <thead>
                                <tr>
                                    <th>Pump</th>
                                    <th>Fuel Type</th>
                                    <th>Morning Attendant</th>
                                    <th>Evening Attendant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pumpAllocation as $allocation): ?>
                                    <tr>
                                        <td><strong><?php echo e($allocation['pump']); ?></strong></td>
                                        <td><?php echo e($allocation['fuel_type']); ?></td>
                                        <td><?php echo e($allocation['morning']); ?></td>
                                        <td><?php echo e($allocation['evening']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

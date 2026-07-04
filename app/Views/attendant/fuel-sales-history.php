<?php

declare(strict_types=1);

$pageTitle = 'Fuel Sales History | FuelOps Staff Dashboard';
$pageHeading = 'Fuel Sales History';
$topbarSubtitle = 'Pump Attendant Dashboard';
$currentRoute = $currentRoute ?? 'fuel-sales/history';
$extraStyles = ['css/clock-in.css', 'css/fuel-sales-history.css'];
$extraScripts = ['js/fuel-sales-history.js'];

// =======================================
// DATABASE PLACEHOLDER
// Replace with employee information from the database.
// =======================================
$employee = $employee ?? [
    'employee_id' => 'EMP001',
    'name' => 'John Doe',
    'department' => 'Operations',
    'role' => 'Pump Attendant',
    'shift' => 'Morning',
    'assigned_pump' => 'Pump 1 - PMS Lane',
];

// =======================================
// DATABASE PLACEHOLDER
// Load sales summary statistics.
// =======================================
$salesSummary = $salesSummary ?? [
    ['label' => 'Total Shifts Worked', 'value' => '24 Shifts', 'icon' => 'fa-solid fa-business-time', 'tone' => 'primary'],
    ['label' => 'Total Liters Sold', 'value' => '18,750 Liters', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'success'],
    ['label' => 'Total Sales Amount', 'value' => '₦12,850,000', 'icon' => 'fa-solid fa-naira-sign', 'tone' => 'danger'],
];

$salesStats = $salesStats ?? [
    ['label' => 'Average Sales / Shift', 'value' => '₦535,417', 'icon' => 'fa-solid fa-chart-line'],
    ['label' => 'Average Liters / Shift', 'value' => '781 L', 'icon' => 'fa-solid fa-gauge-high'],
    ['label' => 'Pumps Operated', 'value' => '4 Pumps', 'icon' => 'fa-solid fa-oil-well'],
    ['label' => 'Verified Records', 'value' => '18 Entries', 'icon' => 'fa-solid fa-circle-check'],
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

$pumps = ['Pump 1', 'Pump 2', 'Pump 3', 'Pump 4'];
$fuelTypes = ['Petrol', 'Diesel', 'Gas'];
$statusClasses = [
    'Submitted' => 'sales-status--submitted',
    'Pending Review' => 'sales-status--pending',
    'Verified' => 'sales-status--verified',
    'Rejected' => 'sales-status--rejected',
];

// =======================================
// DATABASE PLACEHOLDER
// Retrieve fuel sales records from MySQL.
// =======================================
$fuelSales = $fuelSales ?? [
    ['date' => '2026-07-01', 'shift' => 'Morning', 'pump' => 'Pump 1', 'fuel' => 'Petrol', 'opening_meter' => 12500, 'closing_meter' => 13180, 'liters' => 680, 'amount' => 748000, 'status' => 'Submitted'],
    ['date' => '2026-07-02', 'shift' => 'Evening', 'pump' => 'Pump 3', 'fuel' => 'Diesel', 'opening_meter' => 8250, 'closing_meter' => 8620, 'liters' => 370, 'amount' => 629000, 'status' => 'Verified'],
    ['date' => '2026-07-03', 'shift' => 'Morning', 'pump' => 'Pump 2', 'fuel' => 'Petrol', 'opening_meter' => 14220, 'closing_meter' => 14905, 'liters' => 685, 'amount' => 753500, 'status' => 'Verified'],
    ['date' => '2026-07-04', 'shift' => 'Evening', 'pump' => 'Pump 4', 'fuel' => 'Gas', 'opening_meter' => 3310, 'closing_meter' => 3515, 'liters' => 205, 'amount' => 369000, 'status' => 'Pending Review'],
    ['date' => '2026-07-05', 'shift' => 'Morning', 'pump' => 'Pump 1', 'fuel' => 'Petrol', 'opening_meter' => 13180, 'closing_meter' => 13860, 'liters' => 680, 'amount' => 748000, 'status' => 'Submitted'],
    ['date' => '2026-07-06', 'shift' => 'Evening', 'pump' => 'Pump 3', 'fuel' => 'Diesel', 'opening_meter' => 8620, 'closing_meter' => 9015, 'liters' => 395, 'amount' => 671500, 'status' => 'Verified'],
    ['date' => '2026-07-07', 'shift' => 'Morning', 'pump' => 'Pump 2', 'fuel' => 'Petrol', 'opening_meter' => 14905, 'closing_meter' => 15490, 'liters' => 585, 'amount' => 643500, 'status' => 'Rejected'],
    ['date' => '2026-06-28', 'shift' => 'Evening', 'pump' => 'Pump 4', 'fuel' => 'Gas', 'opening_meter' => 3030, 'closing_meter' => 3310, 'liters' => 280, 'amount' => 504000, 'status' => 'Verified'],
    ['date' => '2025-12-19', 'shift' => 'Morning', 'pump' => 'Pump 1', 'fuel' => 'Petrol', 'opening_meter' => 9800, 'closing_meter' => 10420, 'liters' => 620, 'amount' => 682000, 'status' => 'Submitted'],
];

$attendantName = $employee['name'] ?? 'Station Staff';
$attendantRole = $employee['role'] ?? 'Pump Attendant';

require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page fuel-sales-history-page">
    <section class="clock-hero fuel-sales-history-hero">
        <div class="container-fluid">
           

            <div class="clock-hero__content fuel-sales-history-hero-card">
                <div>
                    <span class="eyebrow">Fuel Sales</span>
                    <h1><?php echo e($pageHeading); ?></h1>
                    <p>View submitted sales records, pump performance, and shift totals for completed duty periods.</p>
                </div>
                <span class="employee-avatar" aria-hidden="true">
                    <i class="fa-solid fa-gas-pump"></i>
                </span>
            </div>
        </div>
    </section>

    <section class="container-fluid clock-workspace">
        <div class="row g-4">
            <?php foreach ($salesSummary as $card): ?>
                <div class="col-12 col-md-4">
                    <article class="app-card card sales-summary-card sales-summary-card--<?php echo e($card['tone']); ?>">
                        <span class="sales-summary-icon" aria-hidden="true"><i class="<?php echo e($card['icon']); ?>"></i></span>
                        <div>
                            <span><?php echo e($card['label']); ?></span>
                            <strong><?php echo e($card['value']); ?></strong>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>

           
            <div class="col-12">
                <article class="app-card card sales-records-card">
                    <div class="history-toolbar sales-records-toolbar">
                        <div>
                            <span class="eyebrow">Fuel Sales Records</span>
                            <h2>Completed Shift Sales</h2>
                        </div>
                        <div class="sales-filters">
                            <label class="visually-hidden" for="salesSearch">Search fuel sales records</label>
                            <div class="filter-control">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search" id="salesSearch" class="form-control" placeholder="Search date, pump, fuel">
                            </div>
                            <label class="visually-hidden" for="shiftFilter">Filter by shift</label>
                            <select id="shiftFilter" class="form-select">
                                <option value="">All Shifts</option>
                                <option value="Morning">Morning</option>
                                <option value="Evening">Evening</option>
                            </select>
                            <label class="visually-hidden" for="pumpFilter">Filter by pump</label>
                            <select id="pumpFilter" class="form-select">
                                <option value="">All Pumps</option>
                                <?php foreach ($pumps as $pump): ?>
                                    <option value="<?php echo e($pump); ?>"><?php echo e($pump); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="visually-hidden" for="fuelFilter">Filter by fuel type</label>
                            <select id="fuelFilter" class="form-select">
                                <option value="">All Fuel Types</option>
                                <?php foreach ($fuelTypes as $fuelType): ?>
                                    <option value="<?php echo e($fuelType); ?>"><?php echo e($fuelType); ?></option>
                                <?php endforeach; ?>
                            </select>
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
                            <label class="visually-hidden" for="statusFilter">Filter by status</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">All Statuses</option>
                                <?php foreach (array_keys($statusClasses) as $status): ?>
                                    <option value="<?php echo e($status); ?>"><?php echo e($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table attendance-table fuel-sales-table align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Shift</th>
                                    <th>Pump</th>
                                    <th>Fuel Type</th>
                                    <th>Opening Meter Reading</th>
                                    <th>Closing Meter Reading</th>
                                    <th>Liters Sold</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="fuelSalesTableBody">
                                <?php foreach ($fuelSales as $record): ?>
                                    <?php $status = $record['status']; ?>
                                    <tr data-sales-row data-date="<?php echo e($record['date']); ?>" data-month="<?php echo e(substr($record['date'], 5, 2)); ?>" data-year="<?php echo e(substr($record['date'], 0, 4)); ?>" data-shift="<?php echo e($record['shift']); ?>" data-pump="<?php echo e($record['pump']); ?>" data-fuel="<?php echo e($record['fuel']); ?>" data-status="<?php echo e($status); ?>">
                                        <td><?php echo e($record['date']); ?></td>
                                        <td><?php echo e($record['shift']); ?></td>
                                        <td><?php echo e($record['pump']); ?></td>
                                        <td><?php echo e($record['fuel']); ?></td>
                                        <td><?php echo e(number_format((float) $record['opening_meter'])); ?></td>
                                        <td><?php echo e(number_format((float) $record['closing_meter'])); ?></td>
                                        <td><?php echo e(number_format((float) $record['liters'])); ?> L</td>
                                        <td>&#8358;<?php echo e(number_format((float) $record['amount'])); ?></td>
                                        <td><span class="table-badge sales-status <?php echo e($statusClasses[$status] ?? 'sales-status--pending'); ?>"><?php echo e($status); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="history-pagination sales-pagination">
                        <span id="fuelSalesCount">Showing fuel sales records</span>
                        <div class="btn-group" role="group" aria-label="Fuel sales pagination">
                            <button type="button" class="btn btn-outline-brand btn-sm" id="salesPrevPage">Previous</button>
                            <button type="button" class="btn btn-outline-brand btn-sm" id="salesNextPage">Next</button>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

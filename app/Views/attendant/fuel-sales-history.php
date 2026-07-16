<?php

declare(strict_types=1);

$pageTitle = 'Fuel Sales History | FuelOps Staff Dashboard';
$pageHeading = 'Fuel Sales History';
$topbarSubtitle = 'Pump Attendant Dashboard';
$currentRoute = $currentRoute ?? 'fuel-sales/history';
$extraStyles = ['css/clock-in.css', 'css/fuel-sales-history.css'];
$extraScripts = [];

$employee = $employee ?? ['employee_id' => 'N/A', 'name' => 'Station Staff', 'department' => 'Unassigned', 'role' => 'Pump Attendant'];
$salesSummary = $salesSummary ?? [];
$salesStats = $salesStats ?? [];
$fuelSales = $fuelSales ?? [];
$filters = $filters ?? [];
$pagination = $pagination ?? ['page' => 1, 'pages' => 1, 'total' => 0, 'from' => 0, 'to' => 0];
$pumps = $pumps ?? [];
$fuelTypes = $fuelTypes ?? [];
$shifts = $shifts ?? [];
$months = ['01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'];
$years = range((int) date('Y'), max(2020, (int) date('Y') - 6));
$statusClasses = ['Pending' => 'sales-status--pending', 'Verified' => 'sales-status--verified', 'Rejected' => 'sales-status--rejected', 'Correction Requested' => 'sales-status--pending', 'Cancelled' => 'sales-status--rejected'];
$statusOptions = ['pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected', 'correction_requested' => 'Correction Requested', 'cancelled' => 'Cancelled'];
$pageUrl = static function (int $page) use ($filters): string {
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn (mixed $value): bool => $value !== '' && $value !== null);
    return route_url('fuel-sales/history') . '&' . http_build_query($query);
};
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

    <section class="container-fluid clock-workspace"><?php if (!empty($historyError)): ?><div class="alert alert-warning"><?php echo e((string) $historyError); ?></div><?php endif; ?>
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
                        <form class="sales-filters" method="get" action="<?php echo e(app_base_url() . '/index.php'); ?>"><input type="hidden" name="route" value="fuel-sales/history">
                            <label class="visually-hidden" for="salesSearch">Search fuel sales records</label>
                            <div class="filter-control">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search" id="salesSearch" name="search" value="<?php echo e((string) ($filters['search'] ?? '')); ?>" class="form-control" placeholder="Search date, pump, fuel">
                            </div>
                            <label class="visually-hidden" for="shiftFilter">Filter by shift</label>
                            <select id="shiftFilter" name="shift" class="form-select"><option value="">All Shifts</option><?php foreach ($shifts as $option): ?><option value="<?php echo e($option['value']); ?>" <?php echo ($filters['shift'] ?? '') === $option['value'] ? 'selected' : ''; ?>><?php echo e($option['label']); ?></option><?php endforeach; ?></select>
                            <label class="visually-hidden" for="pumpFilter">Filter by pump</label>
                            <select id="pumpFilter" name="pump" class="form-select">
                                <option value="">All Pumps</option>
                                <?php foreach ($pumps as $option): ?><option value="<?php echo e($option['value']); ?>" <?php echo ($filters['pump'] ?? '') === $option['value'] ? 'selected' : ''; ?>><?php echo e($option['value'] . ' - ' . $option['label']); ?></option><?php endforeach; ?>
                            </select>
                            <label class="visually-hidden" for="fuelFilter">Filter by fuel type</label>
                            <select id="fuelFilter" name="fuel" class="form-select">
                                <option value="">All Fuel Types</option>
                                <?php foreach ($fuelTypes as $option): ?><option value="<?php echo e($option['value']); ?>" <?php echo ($filters['fuel'] ?? '') === $option['value'] ? 'selected' : ''; ?>><?php echo e($option['label']); ?></option><?php endforeach; ?>
                            </select>
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
                            <label class="visually-hidden" for="statusFilter">Filter by status</label>
                            <select id="statusFilter" name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <?php foreach ($statusOptions as $value => $label): ?><option value="<?php echo e($value); ?>" <?php echo ($filters['status'] ?? '') === $value ? 'selected' : ''; ?>><?php echo e($label); ?></option><?php endforeach; ?>
                            </select><input type="date" name="date_from" class="form-control" value="<?php echo e((string) ($filters['date_from'] ?? '')); ?>" aria-label="Start date"><input type="date" name="date_to" class="form-control" value="<?php echo e((string) ($filters['date_to'] ?? '')); ?>" aria-label="End date"><select name="sort" class="form-select"><option value="date">Sort by Date</option><option value="pump" <?php echo ($filters['sort'] ?? '') === 'pump' ? 'selected' : ''; ?>>Sort by Pump</option><option value="litres" <?php echo ($filters['sort'] ?? '') === 'litres' ? 'selected' : ''; ?>>Sort by Litres</option><option value="amount" <?php echo ($filters['sort'] ?? '') === 'amount' ? 'selected' : ''; ?>>Sort by Amount</option></select><select name="direction" class="form-select"><option value="desc">Descending</option><option value="asc" <?php echo ($filters['direction'] ?? '') === 'asc' ? 'selected' : ''; ?>>Ascending</option></select><button class="btn btn-primary btn-sm" type="submit">Apply Filters</button><a class="btn btn-outline-brand btn-sm" href="<?php echo e(route_url('fuel-sales/history')); ?>">Reset</a></form>
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
                                    <th>Fuel Price / Litre</th><th>Amount Collected</th><th>Verification Status</th><th>Verified By</th><th>Submission Time</th>
                                </tr>
                            </thead>
                            <tbody id="fuelSalesTableBody">
                                <?php if ($fuelSales === []): ?><tr><td colspan="12" class="text-center text-muted py-4">No fuel sales records found.</td></tr><?php else: ?>
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
                                        <td>NGN <?php echo e(number_format((float) $record['unit_price'], 2)); ?></td><td>NGN <?php echo e(number_format((float) $record['amount'], 2)); ?></td><td><span class="table-badge sales-status <?php echo e($statusClasses[$status] ?? 'sales-status--pending'); ?>"><?php echo e($status); ?></span></td><td><?php echo e($record['verified_by']); ?></td><td><?php echo e($record['submitted_time']); ?></td>
                                    </tr>
                                <?php endforeach; ?><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="history-pagination sales-pagination">
                        <span id="fuelSalesCount">Showing <?php echo e((string) $pagination['from']); ?>-<?php echo e((string) $pagination['to']); ?> of <?php echo e((string) $pagination['total']); ?> records</span><div class="btn-group" role="group" aria-label="Fuel sales pagination"><?php if ($pagination['page'] > 1): ?><a class="btn btn-outline-brand btn-sm" href="<?php echo e($pageUrl($pagination['page'] - 1)); ?>">Previous</a><?php endif; ?><?php if ($pagination['page'] < $pagination['pages']): ?><a class="btn btn-outline-brand btn-sm" href="<?php echo e($pageUrl($pagination['page'] + 1)); ?>">Next</a><?php endif; ?></div></div>
                </article>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

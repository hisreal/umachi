<?php

declare(strict_types=1);

$pageTitle = 'Clock Out | FuelOps Staff Dashboard';
$pageHeading = 'Clock Out';
$topbarSubtitle = 'Staff Dashboard';
$currentRoute = $currentRoute ?? 'attendance/clock-out';
$extraStyles = ['css/clock-in.css', 'css/clock-out.css'];
$extraScripts = ['js/clock-out.js'];
$canSubmitFuelSales = (bool) ($canSubmitFuelSales ?? false);

$employee = $employee ?? [
    'name' => 'Chinedu Okafor',
    'employee_id' => 'EMP-FS-0017',
    'department' => 'Forecourt Operations',
    'role' => 'Pump Attendant',
    'shift' => 'Morning Shift (06:00 AM - 02:00 PM)',
    'assigned_pump' => 'Pump 03 - PMS Lane',
];
$automaticDuty = (bool) ($employee['automatic_duty'] ?? false);
$topbarSubtitle = (string) ($employee['role'] ?? 'Staff') . ' Dashboard';

$attendanceStatus = $attendanceStatus ?? [
    'shift_date' => 'Saturday, July 4, 2026',
    'current_time' => '01:54 PM',
];

$clockOutOptions = $clockOutOptions ?? [
    'pumps' => ['Pump 1', 'Pump 2', 'Pump 3', 'Pump 4'],
    'fuel_types' => ['PMS', 'AGO', 'DPK', 'LPG'],
];


$attendantName = $employee['name'] ?? 'Station Staff';
$attendantRole = $employee['role'] ?? 'Pump Attendant';

require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page clock-out-page">
    <section class="clock-hero">
        <div class="container-fluid">
            <div class="clock-hero__content">
                <div>
                    <h1><?php echo e($pageHeading); ?></h1>
                    <p><?php echo $canSubmitFuelSales
                        ? 'Record final meter readings, submit shift sales, and close your station shift.'
                        : 'Record the end of your work shift and submit your attendance.'; ?></p>
                </div>
                <div class="clock-hero__time" aria-live="polite">
                    <span id="currentDate"><?php echo e($attendanceStatus['shift_date'] ?? 'Loading date...'); ?></span>
                    <strong id="liveClock"><?php echo e($attendanceStatus['current_time'] ?? '--:--:--'); ?></strong>
                </div>
            </div>
        </div>
    </section>

    <section class="container-fluid clock-workspace">
        <?php if (!empty($attendanceSuccess)): ?><div class="alert alert-success"><?php echo e((string) $attendanceSuccess); ?></div><?php endif; ?>
        <?php if (!empty($attendanceError)): ?><div class="alert alert-danger"><?php echo e((string) $attendanceError); ?></div><?php endif; ?>
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
                        <?php if ($automaticDuty): ?>
                        <div>
                            <span>Current Date</span>
                            <strong><?php echo e($attendanceStatus['shift_date'] ?? date('l, F j, Y')); ?></strong>
                        </div>
                        <div class="employee-grid__wide">
                            <span>Status</span>
                            <strong><?php echo e($employee['duty_status'] ?? 'Automatically Assigned'); ?></strong>
                        </div>
                        <?php else: ?>
                        <?php if ($canSubmitFuelSales): ?>
                        <div>
                            <span>Assigned Pump</span>
                            <strong><?php echo e($employee['assigned_pump'] ?? 'Unassigned'); ?></strong>
                        </div>
                        <?php endif; ?>
                        <div class="employee-grid__wide">
                            <span>Assigned Shift</span>
                            <strong><?php echo e($employee['shift'] ?? 'Pending'); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </article>
            </div>

            <div class="col-12 col-xl-7">
                <?php if ($canSubmitFuelSales): ?>
                <article class="app-card card fuel-sales-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Fuel Sales Module</span>
                            <h2>Shift Sales Entry</h2>
                        </div>
                        <span class="status-pill status-waiting">Pending Review</span>
                    </div>
                    <form id="clockOutForm" class="clock-out-form" method="post" action="<?php echo e(route_url('attendance/clock-out')); ?>" enctype="multipart/form-data" data-unit-price="<?php echo e($fuelSalesSummary['unit_price_value'] ?? '0.00'); ?>" novalidate><?php echo csrf_field(); ?>
                        <?php if (empty($fuelSalesSummary['price_available'])): ?><div class="alert alert-warning">Current fuel price is not configured for your assigned fuel type. Please contact your manager before clocking out.</div><?php endif; ?>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="pumpSelection">Pump Selection</label>
                                <select id="pumpSelection" name="pump" class="form-select" required>
                                    <?php foreach ($clockOutOptions['pumps'] as $pump): ?>
                                        <option value="<?php echo e($pump); ?>" <?php echo $pump === $fuelSalesSummary['assigned_pump'] ? 'selected' : ''; ?>><?php echo e($pump); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="fuelType">Fuel Type</label>
                                <select id="fuelType" name="fuel_type" class="form-select" required>
                                    <?php foreach ($clockOutOptions['fuel_types'] as $fuelType): ?>
                                        <option value="<?php echo e($fuelType); ?>" <?php echo $fuelType === $fuelSalesSummary['fuel_type'] ? 'selected' : ''; ?>><?php echo e($fuelType); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="openingMeter">Opening Meter Reading</label>
                                <input type="number" step="0.01" min="0" id="openingMeter" name="opening_meter" class="form-control" value="<?php echo e($fuelSalesSummary['opening_meter']); ?>" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="closingMeter">Closing Meter Reading</label>
                                <input type="number" step="0.01" min="0" id="closingMeter" name="closing_meter" class="form-control" value="<?php echo e($fuelSalesSummary['closing_meter']); ?>" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="litersSold">Liters Sold</label>
                                <input type="number" step="0.01" min="0" id="litersSold" name="liters_sold" class="form-control" value="<?php echo e($fuelSalesSummary['liters_sold']); ?>" readonly required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="amountCollected">Amount Collected</label>
                                <input type="text" inputmode="decimal" id="amountCollected" name="amount_collected" class="form-control" value="<?php echo e($fuelSalesSummary['amount_collected']); ?>" readonly required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="remarks">Remarks</label>
                                <textarea id="remarks" name="remarks" class="form-control" rows="4" placeholder="Optional shift remarks"><?php echo e($fuelSalesSummary['remarks']); ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="clockOutPhoto">Clock-Out Selfie</label>
                                <input class="form-control" id="clockOutPhoto" name="clock_out_photo" type="file" accept="image/*" capture="user" required>
                            </div>
                        </div>
                    </form>
                </article>
                <?php else: ?>
                <article class="app-card card fuel-sales-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Attendance</span>
                            <h2>Complete Your Shift</h2>
                        </div>
                        <span class="status-pill status-waiting">Ready</span>
                    </div>
                    <form id="clockOutForm" class="clock-out-form" method="post" action="<?php echo e(route_url('attendance/clock-out')); ?>" enctype="multipart/form-data" novalidate><?php echo csrf_field(); ?>
                        <div class="row g-3">
                            <div class="col-12"><label class="form-label" for="remarks">Remarks</label><textarea id="remarks" name="remarks" class="form-control" rows="4" placeholder="Optional shift remarks"></textarea></div>
                            <div class="col-12"><label class="form-label" for="clockOutPhoto">Clock-Out Selfie</label><input class="form-control" id="clockOutPhoto" name="clock_out_photo" type="file" accept="image/*" capture="user" required></div>
                        </div>
                    </form>
                </article>
                <?php endif; ?>
            </div>

            <?php if ($canSubmitFuelSales): ?>
            <div class="col-12 col-xl-5">
                <article class="app-card card shift-summary-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Shift Summary</span>
                            <h2>Sales Snapshot</h2>
                        </div>
                        <span class="employee-avatar employee-avatar--small" aria-hidden="true">
                            <i class="fa-solid fa-gas-pump"></i>
                        </span>
                    </div>
                    <dl class="summary-grid">
                        <div>
                            <dt>Assigned Pump</dt>
                            <dd id="summaryPump"><?php echo e($fuelSalesSummary['assigned_pump']); ?></dd>
                        </div>
                        <div>
                            <dt>Fuel Type</dt>
                            <dd id="summaryFuelType"><?php echo e($fuelSalesSummary['fuel_type']); ?></dd>
                        </div>
                        <div>
                            <dt>Opening Meter</dt>
                            <dd id="summaryOpeningMeter"><?php echo e($fuelSalesSummary['opening_meter']); ?></dd>
                        </div>
                        <div>
                            <dt>Closing Meter</dt>
                            <dd id="summaryClosingMeter"><?php echo e($fuelSalesSummary['closing_meter']); ?></dd>
                        </div>
                        <div>
                            <dt>Liters Sold</dt>
                            <dd id="summaryLitersSold"><?php echo e($fuelSalesSummary['liters_sold']); ?></dd>
                        </div>
                        <div>
                            <dt>Amount Collected</dt>
                            <dd id="summaryAmountCollected"><?php echo e($fuelSalesSummary['amount_collected']); ?></dd>
                        </div>
                        <div class="summary-grid__wide">
                            <dt>Shift</dt>
                            <dd><?php echo e($fuelSalesSummary['shift']); ?></dd>
                        </div>
                        <div class="summary-grid__wide">
                            <dt>Date</dt>
                            <dd><?php echo e($fuelSalesSummary['date']); ?></dd>
                        </div>
                    </dl>
                </article>
            </div>
            <?php endif; ?>

            <div class="col-12 <?php echo $canSubmitFuelSales ? 'col-xl-7' : ''; ?>">
                <article class="app-card card clock-action-card clock-out-action-card">
                    <div>
                        <span class="eyebrow">Clock Out Section</span>
                        <h2>Ready to Submit Shift?</h2>
                        <p><?php echo $canSubmitFuelSales ? 'Review meter readings and collected amount before closing your shift.' : 'Confirm your clock-out selfie, then close your work shift.'; ?></p>
                    </div>
                    <button type="submit" form="clockOutForm" class="btn btn-clock-in" id="clockOutBtn">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        Clock Out
                    </button>
                </article>
            </div>

          
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>





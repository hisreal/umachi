<?php

declare(strict_types=1);

$pageTitle = 'Leave Management | FuelOps Staff Dashboard';
$pageHeading = 'Leave Management';
$topbarSubtitle = 'Pump Attendant Dashboard';
$currentRoute = $currentRoute ?? 'leave-requests';
$extraStyles = ['css/clock-in.css', 'css/leave.css'];
$extraScripts = ['js/leave.js'];

// =======================================
// DATABASE PLACEHOLDER
// Load employee leave balance and history
// from the MySQL database.
// =======================================
$employee = $employee ?? [
    'employee_id' => 'EMP001',
    'name' => 'John Doe',
    'department' => 'Operations',
    'role' => 'Pump Attendant',
];

// =======================================
// DATABASE PLACEHOLDER
// Replace sample leave balances with values
// retrieved from the database.
// =======================================


$leaveTypes = [
    'Annual Leave',
    'Sick Leave',
    'Casual Leave',
    'Maternity Leave',
    'Paternity Leave',
    'Emergency Leave',
    'Study Leave',
    'Compassionate Leave',
    'Other',
];

$statusClasses = [
    'Pending' => 'leave-status--pending',
    'Approved' => 'leave-status--approved',
    'Rejected' => 'leave-status--rejected',
    'Cancelled' => 'leave-status--cancelled',
];

// =======================================
// DATABASE PLACEHOLDER
// Replace with leave records from the database.
// =======================================
$leaveHistory = $leaveHistory ?? [
    [
        'request_id' => 'LV001',
        'leave_type' => 'Annual Leave',
        'start_date' => '2026-07-15',
        'end_date' => '2026-07-20',
        'days' => 6,
        'date_applied' => '2026-07-01',
        'status' => 'Approved',
        'approved_by' => 'Manager',
        'remarks' => 'Approved',
    ],
    [
        'request_id' => 'LV002',
        'leave_type' => 'Sick Leave',
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-12',
        'days' => 3,
        'date_applied' => '2026-06-09',
        'status' => 'Pending',
        'approved_by' => '-',
        'remarks' => 'Awaiting Review',
    ],
    [
        'request_id' => 'LV003',
        'leave_type' => 'Casual Leave',
        'start_date' => '2026-05-03',
        'end_date' => '2026-05-03',
        'days' => 1,
        'date_applied' => '2026-04-29',
        'status' => 'Approved',
        'approved_by' => 'Supervisor',
        'remarks' => 'Shift covered',
    ],
    [
        'request_id' => 'LV004',
        'leave_type' => 'Emergency Leave',
        'start_date' => '2026-03-18',
        'end_date' => '2026-03-19',
        'days' => 2,
        'date_applied' => '2026-03-18',
        'status' => 'Rejected',
        'approved_by' => 'Manager',
        'remarks' => 'Insufficient notice',
    ],
    [
        'request_id' => 'LV005',
        'leave_type' => 'Study Leave',
        'start_date' => '2025-11-12',
        'end_date' => '2025-11-14',
        'days' => 3,
        'date_applied' => '2025-10-30',
        'status' => 'Cancelled',
        'approved_by' => '-',
        'remarks' => 'Cancelled by employee',
    ],
    [
        'request_id' => 'LV006',
        'leave_type' => 'Compassionate Leave',
        'start_date' => '2025-09-08',
        'end_date' => '2025-09-09',
        'days' => 2,
        'date_applied' => '2025-09-07',
        'status' => 'Approved',
        'approved_by' => 'Manager',
        'remarks' => 'Approved',
    ],
];

$historyYears = array_values(array_unique(array_map(static fn (array $record): string => substr($record['date_applied'], 0, 4), $leaveHistory)));
rsort($historyYears);

$attendantName = $employee['name'] ?? 'Station Staff';
$attendantRole = $employee['role'] ?? 'Pump Attendant';

require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page leave-page">
    <section class="clock-hero leave-hero">
        <div class="container-fluid">
           

            <div class="clock-hero__content leave-hero-card">
                <div>
                    <span class="eyebrow">Leave Requests</span>
                    <h1><?php echo e($pageHeading); ?></h1>
                    <p>Apply for leave, review balances, and track request decisions in demo mode.</p>
                </div>
                <span class="employee-avatar" aria-hidden="true">
                    <i class="fa-solid fa-person-walking-arrow-right"></i>
                </span>
            </div>
        </div>
    </section>

    <section class="container-fluid clock-workspace">
        <div class="row g-4">
           

            <div class="col-12 col-xl-10 offset-xl-1">
                <article class="app-card card leave-form-card">
                    <div class="app-card__header">
                        <div>
                            <span class="eyebrow">Apply for Leave</span>
                            <h2>Leave Application Form</h2>
                        </div>
                        <span class="employee-avatar" aria-hidden="true"><i class="fa-solid fa-file-pen"></i></span>
                    </div>

                    <form id="leaveApplicationForm" class="needs-validation" novalidate>
                        <div class="leave-section-title">Employee Information</div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="employeeName">Employee Name</label>
                                <input type="text" id="employeeName" class="form-control" value="<?php echo e($employee['name']); ?>" readonly>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="employeeId">Employee ID</label>
                                <input type="text" id="employeeId" class="form-control" value="<?php echo e($employee['employee_id']); ?>" readonly>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="department">Department</label>
                                <input type="text" id="department" class="form-control" value="<?php echo e($employee['department']); ?>" readonly>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="role">Role</label>
                                <input type="text" id="role" class="form-control" value="<?php echo e($employee['role']); ?>" readonly>
                            </div>
                        </div>

                        <div class="leave-section-title">Leave Details</div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="leaveType">Leave Type</label>
                                <select id="leaveType" class="form-select" required>
                                    <option value="">Select leave type</option>
                                    <?php foreach ($leaveTypes as $leaveType): ?>
                                        <option value="<?php echo e($leaveType); ?>"><?php echo e($leaveType); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Leave type is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="numberOfDays">Number of Days</label>
                                <?php // DATABASE PLACEHOLDER: This value can be automatically calculated from start and end dates later. ?>
                                <input type="number" id="numberOfDays" class="form-control" min="1" step="1" placeholder="Enter days" required>
                                <div class="invalid-feedback">Number of days is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="startDate">Start Date</label>
                                <input type="date" id="startDate" class="form-control" required>
                                <div class="invalid-feedback">Start date is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="endDate">End Date</label>
                                <input type="date" id="endDate" class="form-control" required>
                                <div class="invalid-feedback">End date must be the same as or after start date.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="reason">Reason for Leave</label>
                                <textarea id="reason" class="form-control" rows="4" placeholder="Enter reason for leave" required></textarea>
                                <div class="invalid-feedback">Reason for leave is required.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="supportingDocument">Supporting Document (Optional)</label>
                                <input type="file" id="supportingDocument" class="form-control" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                                <div class="form-text">Accepted formats: PDF, JPG, PNG. Upload is for UI demo only.</div>
                            </div>
                        </div>

                        <div class="leave-form-actions">
                            <button type="submit" style="background-color: #ed3237; color: #fff;" class="btn">
                                <i class="fa-solid fa-paper-plane"></i>
                                Submit Leave Request
                            </button>
                            <button type="reset" class="btn btn-secondary" id="resetLeaveFormBtn">
                                <i class="fa-solid fa-rotate-left"></i>
                                Reset Form
                            </button>
                        </div>
                    </form>
                </article>
            </div>

            <div class="col-12">
                <article class="app-card card leave-history-card">
                    <div class="history-toolbar leave-history-toolbar">
                        <div>
                            <span class="eyebrow">Leave History</span>
                            <h2>Previous Requests</h2>
                        </div>
                        <div class="leave-history-filters">
                            <label class="visually-hidden" for="leaveSearch">Search leave history</label>
                            <div class="filter-control">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search" id="leaveSearch" class="form-control" placeholder="Search requests">
                            </div>
                            <label class="visually-hidden" for="filterLeaveType">Filter by leave type</label>
                            <select id="filterLeaveType" class="form-select">
                                <option value="">All Leave Types</option>
                                <?php foreach ($leaveTypes as $leaveType): ?>
                                    <option value="<?php echo e($leaveType); ?>"><?php echo e($leaveType); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="visually-hidden" for="filterStatus">Filter by status</label>
                            <select id="filterStatus" class="form-select">
                                <option value="">All Statuses</option>
                                <?php foreach (array_keys($statusClasses) as $status): ?>
                                    <option value="<?php echo e($status); ?>"><?php echo e($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="visually-hidden" for="filterYear">Filter by year</label>
                            <select id="filterYear" class="form-select">
                                <option value="">All Years</option>
                                <?php foreach ($historyYears as $year): ?>
                                    <option value="<?php echo e($year); ?>"><?php echo e($year); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table attendance-table leave-table align-middle">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Leave Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Days</th>
                                    <th>Date Applied</th>
                                    <th>Status</th>
                                    <th>Approved By</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="leaveHistoryBody">
                                <?php foreach ($leaveHistory as $record): ?>
                                    <?php $status = $record['status']; ?>
                                    <tr data-leave-row data-leave-type="<?php echo e($record['leave_type']); ?>" data-status="<?php echo e($status); ?>" data-year="<?php echo e(substr($record['date_applied'], 0, 4)); ?>">
                                        <td><?php echo e($record['request_id']); ?></td>
                                        <td><?php echo e($record['leave_type']); ?></td>
                                        <td><?php echo e($record['start_date']); ?></td>
                                        <td><?php echo e($record['end_date']); ?></td>
                                        <td><?php echo e($record['days']); ?></td>
                                        <td><?php echo e($record['date_applied']); ?></td>
                                        <td><span class="table-badge leave-status <?php echo e($statusClasses[$status] ?? 'leave-status--cancelled'); ?>"><?php echo e($status); ?></span></td>
                                        <td><?php echo e($record['approved_by']); ?></td>
                                        <td><?php echo e($record['remarks']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="history-pagination leave-pagination">
                        <span id="leaveHistoryCount">Showing leave requests</span>
                        <div class="btn-group" role="group" aria-label="Leave history pagination">
                            <button type="button" class="btn btn-outline-brand btn-sm" id="leavePrevPage">Previous</button>
                            <button type="button" class="btn btn-outline-brand btn-sm" id="leaveNextPage">Next</button>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

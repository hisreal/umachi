<?php

declare(strict_types=1);

$pageTitle = 'Leave Management | FuelOps Staff Dashboard';
$pageHeading = 'Leave Management';
$topbarSubtitle = 'Pump Attendant Dashboard';
$currentRoute = $currentRoute ?? 'leave-requests';
$extraStyles = ['css/clock-in.css', 'css/leave.css'];
$extraScripts = ['js/leave.js'];

$employee = $employee ?? [
    'employee_id' => 'N/A',
    'name' => 'Employee',
    'department' => 'Unassigned',
    'role' => 'Staff',
];

$leaveTypes = $leaveTypes ?? [];
$statusClasses = $statusClasses ?? [
    'Pending' => 'leave-status--pending',
    'Forwarded' => 'leave-status--forwarded',
    'Approved' => 'leave-status--approved',
    'Rejected' => 'leave-status--rejected',
    'Cancelled' => 'leave-status--cancelled',
];
$leaveHistory = $leaveHistory ?? [];
$filters = $filters ?? [];
$pagination = $pagination ?? ['page' => 1, 'pages' => 1, 'total' => 0, 'from' => 0, 'to' => 0];
$pageUrl = static function (int $page) use ($filters): string { $query = array_filter(array_merge($filters, ['page' => $page]), static fn (mixed $value): bool => $value !== '' && $value !== null && $value !== 0); return route_url('leave-requests') . '&' . http_build_query($query); };
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
                    <p>Apply for leave, review balances, and track request decisions.</p>
                </div>
                <span class="employee-avatar" aria-hidden="true">
                    <i class="fa-solid fa-person-walking-arrow-right"></i>
                </span>
            </div>
        </div>
    </section>

    <section class="container-fluid clock-workspace">
            <?php if (!empty($leaveSuccess)): ?>
                <div class="alert alert-success" role="alert"><?php echo e($leaveSuccess); ?></div>
            <?php endif; ?>
            <?php if (!empty($leaveError)): ?>
                <div class="alert alert-danger" role="alert"><?php echo e($leaveError); ?></div>
            <?php endif; ?>
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

                    <form id="leaveApplicationForm" class="needs-validation" method="post" action="<?php echo e(route_url('leave-requests')); ?>" enctype="multipart/form-data" novalidate><?php echo csrf_field(); ?>
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
                                <select id="leaveType" name="leave_type_id" class="form-select" required>
                                    <option value="">Select leave type</option>
                                    <?php foreach ($leaveTypes as $leaveType): ?>
                                        <option value="<?php echo e((string) $leaveType['id']); ?>" data-requires-attachment="<?php echo !empty($leaveType['requires_attachment']) ? '1' : '0'; ?>"><?php echo e($leaveType['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Leave type is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="numberOfDays">Number of Days</label>
                                <input type="number" id="numberOfDays" name="total_days" class="form-control" min="1" step="1" placeholder="Calculated automatically" readonly required>
                                <div class="invalid-feedback">Number of days is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="startDate">Start Date</label>
                                <input type="date" id="startDate" name="start_date" class="form-control" required>
                                <div class="invalid-feedback">Start date is required.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="endDate">End Date</label>
                                <input type="date" id="endDate" name="end_date" class="form-control" required>
                                <div class="invalid-feedback">End date must be the same as or after start date.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="reason">Reason for Leave</label>
                                <textarea id="reason" name="reason" class="form-control" rows="4" placeholder="Enter reason for leave" required></textarea>
                                <div class="invalid-feedback">Reason for leave is required.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="supportingDocument">Supporting Document (Optional)</label>
                                <input type="file" id="supportingDocument" name="supporting_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                                <div class="form-text" id="supportingDocumentHelp">Accepted formats: PDF, JPG, PNG, DOC, DOCX. Maximum size: 5MB.</div>
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
                        <form class="leave-history-filters" method="get" action="<?php echo e(app_base_url() . '/index.php'); ?>"><input type="hidden" name="route" value="leave-requests">
                            <label class="visually-hidden" for="leaveSearch">Search leave history</label>
                            <div class="filter-control">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search" id="leaveSearch" name="search" value="<?php echo e((string) ($filters['search'] ?? '')); ?>" class="form-control" placeholder="Search requests">
                            </div>
                            <label class="visually-hidden" for="filterLeaveType">Filter by leave type</label>
                            <select id="filterLeaveType" name="type" class="form-select">
                                <option value="">All Leave Types</option>
                                <?php foreach ($leaveTypes as $leaveType): ?>
                                    <option value="<?php echo e((string) $leaveType['id']); ?>" <?php echo (int) ($filters['type'] ?? 0) === (int) $leaveType['id'] ? 'selected' : ''; ?>><?php echo e($leaveType['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="visually-hidden" for="filterStatus">Filter by status</label>
                            <select id="filterStatus" name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <?php foreach (array_keys($statusClasses) as $status): ?>
                                    <option value="<?php echo e($status); ?>" <?php echo ($filters['status'] ?? '') === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="date" name="date_from" class="form-control" value="<?php echo e((string) ($filters['date_from'] ?? '')); ?>"><input type="date" name="date_to" class="form-control" value="<?php echo e((string) ($filters['date_to'] ?? '')); ?>"><button class="btn btn-primary btn-sm" type="submit">Apply Filters</button><a class="btn btn-outline-brand btn-sm" href="<?php echo e(route_url('leave-requests')); ?>">Reset</a></form>
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
                                    <th>Current Approval Stage</th>
                                    <th>Remarks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="leaveHistoryBody">
                                <?php if ($leaveHistory === []): ?><tr><td colspan="10" class="text-center text-muted py-4">No leave requests found.</td></tr><?php else: ?>
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
                                        <td><?php echo e($record['stage'] ?? 'Waiting for Review'); ?></td>
                                        <td><?php echo e($record['remarks']); ?></td>
                                        <td><?php if (in_array($status, ['Pending', 'Forwarded'], true)): ?><form method="post" action="<?php echo e(route_url('leave-requests/cancel')); ?>" onsubmit="return confirm('Cancel this leave request?');"><?php echo csrf_field(); ?><input type="hidden" name="request_id" value="<?php echo e((string) $record['db_id']); ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Cancel</button></form><?php else: ?>-<?php endif; ?></td>
                                    </tr>
                                <?php endforeach; ?><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="history-pagination leave-pagination">
                        <span id="leaveHistoryCount">Showing <?php echo e((string) $pagination['from']); ?>-<?php echo e((string) $pagination['to']); ?> of <?php echo e((string) $pagination['total']); ?> requests</span><div class="btn-group"><?php if ($pagination['page'] > 1): ?><a class="btn btn-outline-brand btn-sm" href="<?php echo e($pageUrl($pagination['page'] - 1)); ?>">Previous</a><?php endif; ?><?php if ($pagination['page'] < $pagination['pages']): ?><a class="btn btn-outline-brand btn-sm" href="<?php echo e($pageUrl($pagination['page'] + 1)); ?>">Next</a><?php endif; ?></div></div>
                </article>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
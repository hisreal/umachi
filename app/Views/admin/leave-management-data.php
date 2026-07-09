<?php

declare(strict_types=1);

$departments = ['Operations', 'Finance', 'Security', 'Administration'];
$roles = ['Admin', 'Manager', 'Supervisor', 'Pump Attendant', 'Cashier', 'Accountant', 'Security'];
$leaveStatuses = ['Pending', 'Approved', 'Rejected', 'Forwarded', 'Cancelled'];
$leaveTypeNames = ['Annual Leave', 'Sick Leave', 'Casual Leave', 'Maternity Leave', 'Paternity Leave', 'Study Leave', 'Emergency Leave', 'Compassionate Leave'];

// ===============================================
// DATABASE PLACEHOLDER
// Replace dashboard statistics with MySQL aggregates.
// ===============================================
$leaveStats = [
    ['label' => 'Total Leave Requests', 'value' => '128', 'icon' => 'fa-solid fa-file-signature', 'tone' => 'primary'],
    ['label' => 'Pending Requests', 'value' => '14', 'icon' => 'fa-solid fa-hourglass-half', 'tone' => 'warning'],
    ['label' => 'Approved Requests', 'value' => '92', 'icon' => 'fa-solid fa-circle-check', 'tone' => 'success'],
    ['label' => 'Rejected Requests', 'value' => '9', 'icon' => 'fa-solid fa-circle-xmark', 'tone' => 'danger'],
    ['label' => 'Employees Currently on Leave', 'value' => '6', 'icon' => 'fa-solid fa-person-walking-arrow-right', 'tone' => 'info'],
    ['label' => 'Leave Requests This Month', 'value' => '21', 'icon' => 'fa-solid fa-calendar-week', 'tone' => 'orange'],
];

// ===============================================
// DATABASE PLACEHOLDER
// Retrieve leave requests from MySQL.
// ===============================================
$leaveRequests = [
    ['id' => 1, 'employee_id' => 'EMP001', 'employee' => 'John Doe', 'department' => 'Operations', 'role' => 'Pump Attendant', 'type' => 'Annual Leave', 'reason' => 'Family vacation and personal rest period.', 'start' => '2026-08-01', 'end' => '2026-08-07', 'days' => 7, 'applied' => '2026-07-06', 'stage' => 'Supervisor Review', 'status' => 'Pending', 'documents' => 'Travel itinerary (demo)', 'history' => ['Submitted by employee', 'Forwarded to Supervisor'], 'notes' => 'Awaiting supervisor recommendation.'],
    ['id' => 2, 'employee_id' => 'EMP002', 'employee' => 'Mary Johnson', 'department' => 'Finance', 'role' => 'Cashier', 'type' => 'Sick Leave', 'reason' => 'Medical appointment and recovery.', 'start' => '2026-07-15', 'end' => '2026-07-17', 'days' => 3, 'applied' => '2026-07-05', 'stage' => 'Manager Review', 'status' => 'Forwarded', 'documents' => 'Medical note (demo)', 'history' => ['Submitted by employee', 'Supervisor forwarded'], 'notes' => 'Manager review pending.'],
    ['id' => 3, 'employee_id' => 'EMP003', 'employee' => 'Chinedu Okafor', 'department' => 'Operations', 'role' => 'Supervisor', 'type' => 'Study Leave', 'reason' => 'Professional certification workshop.', 'start' => '2026-07-22', 'end' => '2026-07-24', 'days' => 3, 'applied' => '2026-07-02', 'stage' => 'Admin Review', 'status' => 'Approved', 'documents' => 'Admission letter (demo)', 'history' => ['Submitted', 'Manager approved', 'Admin approved'], 'notes' => 'Approved for training attendance.'],
    ['id' => 4, 'employee_id' => 'EMP004', 'employee' => 'Aisha Bello', 'department' => 'Security', 'role' => 'Security', 'type' => 'Emergency Leave', 'reason' => 'Urgent family matter.', 'start' => '2026-07-10', 'end' => '2026-07-10', 'days' => 1, 'applied' => '2026-07-09', 'stage' => 'Admin Review', 'status' => 'Pending', 'documents' => 'Not required (demo)', 'history' => ['Submitted by employee'], 'notes' => 'Emergency approval required.'],
    ['id' => 5, 'employee_id' => 'EMP005', 'employee' => 'Grace Williams', 'department' => 'Administration', 'role' => 'Manager', 'type' => 'Casual Leave', 'reason' => 'Personal errand.', 'start' => '2026-07-18', 'end' => '2026-07-18', 'days' => 1, 'applied' => '2026-07-04', 'stage' => 'Completed', 'status' => 'Rejected', 'documents' => 'None', 'history' => ['Submitted', 'Admin rejected'], 'notes' => 'Staffing shortage on requested date.'],
];

// ===============================================
// DATABASE PLACEHOLDER
// Retrieve leave history from MySQL.
// ===============================================
$leaveHistory = [
    ['employee' => 'John Doe', 'department' => 'Operations', 'type' => 'Annual Leave', 'start' => '2026-05-01', 'end' => '2026-05-05', 'days' => 5, 'approved_by' => 'Grace Williams', 'status' => 'Approved'],
    ['employee' => 'Mary Johnson', 'department' => 'Finance', 'type' => 'Sick Leave', 'start' => '2026-06-10', 'end' => '2026-06-12', 'days' => 3, 'approved_by' => 'Grace Williams', 'status' => 'Approved'],
    ['employee' => 'Samuel Eze', 'department' => 'Operations', 'type' => 'Emergency Leave', 'start' => '2026-06-20', 'end' => '2026-06-20', 'days' => 1, 'approved_by' => 'Chinedu Okafor', 'status' => 'Cancelled'],
    ['employee' => 'Aisha Bello', 'department' => 'Security', 'type' => 'Casual Leave', 'start' => '2026-04-12', 'end' => '2026-04-13', 'days' => 2, 'approved_by' => 'Grace Williams', 'status' => 'Rejected'],
    ['employee' => 'Chinedu Okafor', 'department' => 'Operations', 'type' => 'Study Leave', 'start' => '2026-03-18', 'end' => '2026-03-20', 'days' => 3, 'approved_by' => 'Administrator', 'status' => 'Approved'],
    ['employee' => 'Fatima Yusuf', 'department' => 'Administration', 'type' => 'Maternity Leave', 'start' => '2026-02-01', 'end' => '2026-04-30', 'days' => 90, 'approved_by' => 'Administrator', 'status' => 'Approved'],
];

// ===============================================
// DATABASE PLACEHOLDER
// Retrieve leave types from MySQL.
// ===============================================
$leaveTypes = [
    ['name' => 'Annual Leave', 'description' => 'Paid yearly vacation leave for eligible staff.', 'max_days' => 21, 'paid' => true, 'status' => 'Active'],
    ['name' => 'Sick Leave', 'description' => 'Medical leave supported by a health note when required.', 'max_days' => 14, 'paid' => true, 'status' => 'Active'],
    ['name' => 'Casual Leave', 'description' => 'Short personal leave for urgent non-medical reasons.', 'max_days' => 5, 'paid' => true, 'status' => 'Active'],
    ['name' => 'Maternity Leave', 'description' => 'Maternity leave for eligible female employees.', 'max_days' => 90, 'paid' => true, 'status' => 'Active'],
    ['name' => 'Paternity Leave', 'description' => 'Paternity leave for eligible male employees.', 'max_days' => 14, 'paid' => true, 'status' => 'Active'],
    ['name' => 'Study Leave', 'description' => 'Approved leave for training and professional development.', 'max_days' => 10, 'paid' => false, 'status' => 'Active'],
    ['name' => 'Emergency Leave', 'description' => 'Urgent leave for sudden family or personal emergencies.', 'max_days' => 3, 'paid' => true, 'status' => 'Active'],
    ['name' => 'Compassionate Leave', 'description' => 'Leave granted for bereavement or serious family events.', 'max_days' => 5, 'paid' => true, 'status' => 'Inactive'],
];

$historyStats = [
    ['label' => 'Total Leave Records', 'value' => '156', 'icon' => 'fa-solid fa-folder-open', 'tone' => 'primary'],
    ['label' => 'Total Approved', 'value' => '118', 'icon' => 'fa-solid fa-circle-check', 'tone' => 'success'],
    ['label' => 'Total Rejected', 'value' => '19', 'icon' => 'fa-solid fa-ban', 'tone' => 'danger'],
    ['label' => 'Total Cancelled', 'value' => '8', 'icon' => 'fa-solid fa-rotate-left', 'tone' => 'warning'],
];

$typeStats = [
    ['label' => 'Total Leave Types', 'value' => '8', 'icon' => 'fa-solid fa-list-check', 'tone' => 'primary'],
    ['label' => 'Active Leave Types', 'value' => '7', 'icon' => 'fa-solid fa-toggle-on', 'tone' => 'success'],
    ['label' => 'Inactive Leave Types', 'value' => '1', 'icon' => 'fa-solid fa-toggle-off', 'tone' => 'warning'],
];

$leaveStatusClasses = [
    'Pending' => 'leave-status--pending',
    'Approved' => 'leave-status--approved',
    'Rejected' => 'leave-status--rejected',
    'Forwarded' => 'leave-status--forwarded',
    'Cancelled' => 'leave-status--cancelled',
    'Active' => 'leave-status--approved',
    'Inactive' => 'leave-status--cancelled',
];

$monthlyLeaveRequests = [8, 12, 9, 15, 13, 18, 21, 16, 14, 19, 17, 22];
$leaveTypeDistribution = [28, 18, 16, 8, 5, 7, 10, 6];
$approvalStatusDistribution = [14, 92, 9, 13];

$approvalWorkflows = [
    'manager' => ['label' => 'Manager Approves', 'steps' => ['Employee', 'Manager', 'Approved']],
    'supervisor' => ['label' => 'Supervisor Approves', 'steps' => ['Employee', 'Supervisor', 'Approved']],
    'manager_supervisor' => ['label' => 'Manager AND Supervisor', 'steps' => ['Employee', 'Supervisor', 'Manager', 'Approved']],
    'admin' => ['label' => 'Admin Only', 'steps' => ['Employee', 'Admin', 'Approved']],
    'multi_level' => ['label' => 'Multi-Level Approval', 'steps' => ['Employee', 'Supervisor', 'Manager', 'Admin', 'Approved']],
];
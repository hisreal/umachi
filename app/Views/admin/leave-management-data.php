<?php

declare(strict_types=1);

use App\Models\LeaveManagement;

try {
    $leaveData = (new LeaveManagement())->adminData();
} catch (Throwable $exception) {
    error_log('[LeaveManagement] Admin data load failed: ' . $exception->getMessage());

    $leaveData = [
        'departments' => [],
        'roles' => [],
        'leaveStatuses' => ['Pending', 'Approved', 'Rejected', 'Forwarded', 'Cancelled'],
        'leaveTypeNames' => [],
        'leaveStats' => [],
        'leaveRequests' => [],
        'leaveHistory' => [],
        'leaveTypes' => [],
        'historyStats' => [],
        'typeStats' => [],
        'leaveStatusClasses' => [
            'Pending' => 'leave-status--pending',
            'Approved' => 'leave-status--approved',
            'Rejected' => 'leave-status--rejected',
            'Forwarded' => 'leave-status--forwarded',
            'Cancelled' => 'leave-status--cancelled',
            'Active' => 'leave-status--approved',
            'Inactive' => 'leave-status--cancelled',
        ],
        'monthlyLeaveRequests' => [0, 0, 0, 0, 0, 0],
        'leaveTypeDistribution' => [],
        'approvalStatusDistribution' => [0, 0, 0, 0],
        'approvalWorkflows' => [],
        'activeApprovalWorkflow' => 'multi_level',
        'leavePolicySettings' => ['allow_cancellation'=>1,'require_documents'=>1,'notify_approvers'=>1,'auto_approve_emergency'=>0,'allow_half_day'=>1,'max_requests_per_year'=>12,'approval_deadline_hours'=>48],
        'leaveSuccess' => null,
        'leaveError' => 'Leave management data could not be loaded. Please verify the database schema.',
    ];
}

$departments = $leaveData['departments'];
$roles = $leaveData['roles'];
$leaveStatuses = $leaveData['leaveStatuses'];
$leaveTypeNames = $leaveData['leaveTypeNames'];
$leaveStats = $leaveData['leaveStats'];
$leaveRequests = $leaveData['leaveRequests'];
$leaveHistory = $leaveData['leaveHistory'];
$leaveTypes = $leaveData['leaveTypes'];
$historyStats = $leaveData['historyStats'];
$typeStats = $leaveData['typeStats'];
$leaveStatusClasses = $leaveData['leaveStatusClasses'];
$monthlyLeaveRequests = $leaveData['monthlyLeaveRequests'];
$leaveTypeDistribution = $leaveData['leaveTypeDistribution'];
$approvalStatusDistribution = $leaveData['approvalStatusDistribution'];
$approvalWorkflows = $leaveData['approvalWorkflows'];
$activeApprovalWorkflow = $leaveData['activeApprovalWorkflow'];
$leavePolicySettings = $leaveData['leavePolicySettings'];
$leaveSuccess = $leaveData['leaveSuccess'];
$leaveError = $leaveData['leaveError'];

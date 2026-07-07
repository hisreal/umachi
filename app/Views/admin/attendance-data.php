<?php

declare(strict_types=1);

// ===========================================
// DATABASE PLACEHOLDER
// Load attendance statistics from MySQL.
// ===========================================
$attendanceSummary = [
    'total_employees' => 16,
    'present_today' => 14,
    'absent_today' => 2,
    'late_today' => 1,
    'on_leave' => 1,
    'attendance_rate' => '87.5%',
];

$departments = ['Administration', 'Operations', 'Finance', 'Security'];
$roles = ['Admin', 'Manager', 'Supervisor', 'Pump Attendant', 'Cashier', 'Accountant', 'Security'];
$shifts = ['Morning', 'Evening'];
$statuses = ['Present', 'Absent', 'Late', 'On Leave'];
$employees = ['John Doe', 'Mary Johnson', 'Daniel James', 'Esther Grace', 'Aisha Bello', 'Samuel Peters'];

// ===========================================
// DATABASE PLACEHOLDER
// Retrieve attendance history.
// ===========================================
$attendanceRecords = [
    ['date' => '2026-07-08', 'employee_id' => 'EMP001', 'name' => 'John Doe', 'department' => 'Operations', 'role' => 'Pump Attendant', 'clock_in' => '06:00 AM', 'clock_out' => '02:00 PM', 'shift' => 'Morning', 'status' => 'Present', 'remarks' => 'On time'],
    ['date' => '2026-07-08', 'employee_id' => 'EMP002', 'name' => 'Mary Johnson', 'department' => 'Finance', 'role' => 'Cashier', 'clock_in' => '06:25 AM', 'clock_out' => '02:00 PM', 'shift' => 'Morning', 'status' => 'Late', 'remarks' => 'Arrived after grace period'],
    ['date' => '2026-07-08', 'employee_id' => 'EMP003', 'name' => 'Daniel James', 'department' => 'Operations', 'role' => 'Pump Attendant', 'clock_in' => '-', 'clock_out' => '-', 'shift' => 'Morning', 'status' => 'Absent', 'remarks' => 'No clock-in record'],
    ['date' => '2026-07-08', 'employee_id' => 'EMP004', 'name' => 'Esther Grace', 'department' => 'Operations', 'role' => 'Supervisor', 'clock_in' => '05:55 AM', 'clock_out' => '02:05 PM', 'shift' => 'Morning', 'status' => 'Present', 'remarks' => 'Shift closed'],
    ['date' => '2026-07-08', 'employee_id' => 'EMP005', 'name' => 'Aisha Bello', 'department' => 'Administration', 'role' => 'Manager', 'clock_in' => '-', 'clock_out' => '-', 'shift' => 'Morning', 'status' => 'On Leave', 'remarks' => 'Annual leave'],
    ['date' => '2026-07-07', 'employee_id' => 'EMP006', 'name' => 'Samuel Peters', 'department' => 'Security', 'role' => 'Security', 'clock_in' => '05:50 AM', 'clock_out' => '02:01 PM', 'shift' => 'Morning', 'status' => 'Present', 'remarks' => 'Security post covered'],
    ['date' => '2026-07-07', 'employee_id' => 'EMP001', 'name' => 'John Doe', 'department' => 'Operations', 'role' => 'Pump Attendant', 'clock_in' => '06:03 AM', 'clock_out' => '02:02 PM', 'shift' => 'Morning', 'status' => 'Present', 'remarks' => 'On time'],
    ['date' => '2026-07-07', 'employee_id' => 'EMP002', 'name' => 'Mary Johnson', 'department' => 'Finance', 'role' => 'Cashier', 'clock_in' => '06:06 AM', 'clock_out' => '02:00 PM', 'shift' => 'Morning', 'status' => 'Present', 'remarks' => 'Cash desk balanced'],
];

$recentActivities = [
    ['icon' => 'fa-solid fa-right-to-bracket', 'message' => 'John Doe clocked in at 6:03 AM', 'time' => '5 mins ago'],
    ['icon' => 'fa-solid fa-right-from-bracket', 'message' => 'Mary Johnson clocked out at 2:02 PM', 'time' => '22 mins ago'],
    ['icon' => 'fa-solid fa-user-xmark', 'message' => 'Daniel James marked absent', 'time' => '45 mins ago'],
    ['icon' => 'fa-solid fa-calendar-days', 'message' => 'Aisha Bello is currently on leave', 'time' => '1 hour ago'],
];

$attendanceSettings = [
    'clock_in' => '06:00',
    'clock_out' => '14:00',
    'grace_period' => 15,
    'late_threshold' => 16,
    'overtime_start' => '14:30',
    'max_overtime' => 4,
    'shift_duration' => '8 Hours',
    'auto_clock_out' => true,
    'photo_required' => true,
    'face_verification' => true,
    'gps_verification' => false,
    'early_clock_in' => true,
    'manual_adjustment' => true,
    'approval_required' => 'Supervisor',
];

$attendanceChartData = [
    'monthly' => ['labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], 'values' => [94, 91, 95, 93, 96, 88]],
    'daily' => ['labels' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'], 'values' => [15, 14, 14, 13, 16, 12, 10]],
    'status' => ['labels' => ['Present', 'Absent', 'Late', 'On Leave'], 'values' => [14, 2, 1, 1]],
];

$attendanceStatusClasses = [
    'Present' => 'attendance-status--present',
    'Absent' => 'attendance-status--absent',
    'Late' => 'attendance-status--late',
    'On Leave' => 'attendance-status--leave',
];

$attendanceStats = [
    ['label' => 'Total Records', 'value' => count($attendanceRecords), 'icon' => 'fa-solid fa-database', 'tone' => 'primary'],
    ['label' => 'Present', 'value' => count(array_filter($attendanceRecords, static fn (array $record): bool => $record['status'] === 'Present')), 'icon' => 'fa-solid fa-user-check', 'tone' => 'success'],
    ['label' => 'Absent', 'value' => count(array_filter($attendanceRecords, static fn (array $record): bool => $record['status'] === 'Absent')), 'icon' => 'fa-solid fa-user-xmark', 'tone' => 'danger'],
    ['label' => 'Late', 'value' => count(array_filter($attendanceRecords, static fn (array $record): bool => $record['status'] === 'Late')), 'icon' => 'fa-solid fa-clock', 'tone' => 'warning'],
    ['label' => 'On Leave', 'value' => count(array_filter($attendanceRecords, static fn (array $record): bool => $record['status'] === 'On Leave')), 'icon' => 'fa-solid fa-calendar-days', 'tone' => 'info'],
];

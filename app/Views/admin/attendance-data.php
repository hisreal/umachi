<?php

declare(strict_types=1);

use App\Models\Attendance;

$attendanceModel = new Attendance();
$attendanceModel->boot();
$attendanceSummary = $attendanceModel->adminSummary();
$attendanceRecords = $attendanceModel->getAttendanceHistory(null, [
    'search' => (string) ($_GET['search'] ?? ''),
    'status' => (string) ($_GET['status'] ?? ''),
    'department' => (string) ($_GET['department'] ?? ''),
    'role' => (string) ($_GET['role'] ?? ''),
    'date' => (string) ($_GET['date'] ?? ''),
]);

$departments = array_column(\App\Core\Database::getInstance()->select("SELECT name FROM departments WHERE deleted_at IS NULL ORDER BY name"), 'name');
$roles = array_column(\App\Core\Database::getInstance()->select("SELECT name FROM job_titles WHERE deleted_at IS NULL ORDER BY name"), 'name');
$shifts = array_column(\App\Core\Database::getInstance()->select("SELECT name FROM shifts WHERE deleted_at IS NULL ORDER BY name"), 'name');
$statuses = ['Present', 'Late', 'Absent', 'Half Day', 'On Leave'];
$employees = array_values(array_unique(array_column($attendanceRecords, 'name')));

$recentActivities = array_map(static fn (array $record): array => [
    'icon' => $record['clock_out'] !== '-' ? 'fa-solid fa-right-from-bracket' : 'fa-solid fa-right-to-bracket',
    'message' => $record['name'] . ' - ' . $record['status'],
    'time' => $record['date'],
], array_slice($attendanceRecords, 0, 5));

$attendanceSettings = [
    'clock_in' => '06:00',
    'clock_out' => '14:00',
    'grace_period' => 0,
    'late_threshold' => 0,
    'overtime_start' => '14:00',
    'max_overtime' => 4,
    'shift_duration' => '8 Hours',
    'auto_clock_out' => false,
    'photo_required' => true,
    'face_verification' => false,
    'gps_verification' => false,
    'early_clock_in' => true,
    'manual_adjustment' => true,
    'approval_required' => 'Supervisor',
];

$attendanceChartData = [
    'monthly' => ['labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'], 'values' => [0, 0, 0, 0, 0, 0, (int) $attendanceSummary['present_today']]],
    'daily' => ['labels' => ['Present', 'Late', 'Absent', 'On Leave'], 'values' => [(int) $attendanceSummary['present_today'], (int) $attendanceSummary['late_today'], (int) $attendanceSummary['absent_today'], (int) $attendanceSummary['on_leave']]],
    'status' => ['labels' => ['Present', 'Absent', 'Late', 'On Leave'], 'values' => [(int) $attendanceSummary['present_today'], (int) $attendanceSummary['absent_today'], (int) $attendanceSummary['late_today'], (int) $attendanceSummary['on_leave']]],
];

$attendanceStatusClasses = [
    'Present' => 'attendance-status--present',
    'Absent' => 'attendance-status--absent',
    'Late' => 'attendance-status--late',
    'Half Day' => 'attendance-status--late',
    'On Leave' => 'attendance-status--leave',
];

$attendanceStats = [
    ['label' => 'Total Records', 'value' => count($attendanceRecords), 'icon' => 'fa-solid fa-database', 'tone' => 'primary'],
    ['label' => 'Present', 'value' => count(array_filter($attendanceRecords, static fn (array $record): bool => $record['status'] === 'Present')), 'icon' => 'fa-solid fa-user-check', 'tone' => 'success'],
    ['label' => 'Absent', 'value' => count(array_filter($attendanceRecords, static fn (array $record): bool => $record['status'] === 'Absent')), 'icon' => 'fa-solid fa-user-xmark', 'tone' => 'danger'],
    ['label' => 'Late', 'value' => count(array_filter($attendanceRecords, static fn (array $record): bool => $record['status'] === 'Late')), 'icon' => 'fa-solid fa-clock', 'tone' => 'warning'],
    ['label' => 'On Leave', 'value' => count(array_filter($attendanceRecords, static fn (array $record): bool => $record['status'] === 'On Leave')), 'icon' => 'fa-solid fa-calendar-days', 'tone' => 'info'],
];
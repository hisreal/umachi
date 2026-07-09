<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AttendanceController;
use App\Controllers\AuthController;
use App\Controllers\SupervisorController;
use App\Core\Router;

return static function (Router $router): void {
    $router->get(['login', 'auth/login'], [AuthController::class, 'login']);
    $router->get(['admin', 'admin/dashboard'], [AdminController::class, 'dashboard']);
    $router->get(['', 'dashboard', 'attendance'], [AttendanceController::class, 'dashboard']);
    $router->get('attendance/clock-in', [AttendanceController::class, 'clockIn']);
    $router->get('attendance/clock-out', [AttendanceController::class, 'clockOut']);
    $router->get(['attendance/attendance_history', 'attendance/history'], [AttendanceController::class, 'attendanceHistoryPage']);
    $router->get('fuel-sales/history', [AttendanceController::class, 'fuelSalesHistory']);
    $router->get('duty-roster', [AttendanceController::class, 'dutyRoster']);
    $router->get(['supervisor/manage-duty-roster', 'manager/manage-duty-roster'], [SupervisorController::class, 'manageDutyRoster']);
    $router->get('leave-requests', [AttendanceController::class, 'leaveRequests']);
    $router->get('profile', [AttendanceController::class, 'profile']);
    $router->get('profile/edit', [AttendanceController::class, 'editProfile']);
    $router->get('settings', [AttendanceController::class, 'settings']);
    $router->fallback([AttendanceController::class, 'notFound']);
};
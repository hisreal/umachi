<?php

declare(strict_types=1);

use App\Controllers\AttendanceController;
use App\Controllers\AuthController;

require_once __DIR__ . '/app/Controllers/AttendanceController.php';
require_once __DIR__ . '/app/Controllers/AuthController.php';

$route = trim((string) ($_GET['route'] ?? 'dashboard'), '/');
$controller = new AttendanceController();
$authController = new AuthController();

switch ($route) {
    case 'login':
    case 'auth/login':
        $authController->login();
        break;

    case '':
    case 'dashboard':
    case 'attendance':
        $controller->dashboard();
        break;

    case 'attendance/clock-in':
        $controller->clockIn();
        break;

    case 'attendance/clock-out':
        $controller->clockOut();
        break;

    case 'attendance/attendance_history':
    case 'attendance/history':
        $controller->attendanceHistoryPage();
        break;

    case 'fuel-sales/history':
        $controller->fuelSalesHistory();
        break;

    // case 'duty-roster':
    //     $controller->dutyRoster();
    //     break;

    case 'leave-requests':
        $controller->leaveRequests();
        break;

    case 'profile':
        $controller->profile();
        break;

    case 'profile/edit':
        $controller->editProfile();
        break;

    case 'settings':
        $controller->settings();
        break;

    default:
        http_response_code(404);
        $controller->dashboard();
        break;
}

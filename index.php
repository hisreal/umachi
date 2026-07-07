<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AttendanceController;
use App\Controllers\AuthController;
use App\Controllers\SupervisorController;

require_once __DIR__ . '/app/Controllers/AdminController.php';
require_once __DIR__ . '/app/Controllers/AttendanceController.php';
require_once __DIR__ . '/app/Controllers/AuthController.php';
require_once __DIR__ . '/app/Controllers/SupervisorController.php';

$route = trim((string) ($_GET['route'] ?? 'dashboard'), '/');
$adminController = new AdminController();
$controller = new AttendanceController();
$authController = new AuthController();
$supervisorController = new SupervisorController();

switch ($route) {
    case 'login':
    case 'auth/login':
        $authController->login();
        break;

    case 'admin':
    case 'admin/dashboard':
        $adminController->dashboard();
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

    case 'duty-roster':
        $controller->dutyRoster();
        break;

    case 'supervisor/manage-duty-roster':
    case 'manager/manage-duty-roster':
        $supervisorController->manageDutyRoster();
        break;

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
        if (strpos($route, 'admin/') === 0) {
            $adminController->placeholderPage($route);
            break;
        }

        http_response_code(404);
        $controller->dashboard();
        break;
}

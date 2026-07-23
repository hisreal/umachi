<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Session;
use App\Models\DutyManagement;
use App\Models\Shift;
use App\Services\DutyManagementService;
use App\Services\ShiftManagementService;

$dutyModel = new DutyManagement();
$dutyService = new DutyManagementService($dutyModel);
$dutyRequest = Request::capture();
$dutyModel->boot();

$dutyFilters = [
    'search' => (string) $dutyRequest->query('search', ''),
    'status' => (string) $dutyRequest->query('status', ''),
    'date' => (string) $dutyRequest->query('date', ''),
    'start_date' => (string) $dutyRequest->query('start_date', ''),
    'end_date' => (string) $dutyRequest->query('end_date', ''),
    'shift_id' => (string) $dutyRequest->query('shift_id', ''),
    'fuel_type' => (string) $dutyRequest->query('fuel_type', ''),
    'pump_id' => (string) $dutyRequest->query('pump_id', ''),
    'department' => (string) $dutyRequest->query('department', ''),
];

$options = $dutyModel->formOptions();
$rosters = $dutyModel->rosterList(['search' => $dutyFilters['search'], 'status' => $dutyFilters['status']]);
$rosterAssignments = $dutyModel->assignments($dutyFilters);
$pumpAllocations = $rosterAssignments;
$calendarEvents = $dutyModel->calendarEvents($dutyFilters);
$dutySummary = $dutyModel->stats();
$canManageDuties = $dutyService->canManage();
$dutySuccess = Session::pullFlash('duty_success');
$dutyError = Session::pullFlash('duty_error');

$employees = $options['employees'];
$employeeOptions = $options['employees'];
$pumpOptions = $options['pumps'];
$shiftOptions = $options['shifts'];
$rosterOptions = $options['rosters'];
$supervisorOptions = $options['supervisors'];
$departments = $options['departments'];
$roles = $options['roles'];
$fuelTypes = array_map(static fn (string $fuelType): string => match ($fuelType) {
    'Petrol' => 'Petrol (Petrol)',
    'Diesel' => 'Diesel (AGO)',
    'Gas' => 'Gas (LPG)',
    default => $fuelType,
}, $options['fuel_types']);
$pumps = array_map(static fn (array $pump): string => trim($pump['pump_code'] . ' - ' . $pump['pump_name'], ' -'), $pumpOptions);
$shiftNames = array_column($shiftOptions, 'name');

$dutyStats = [
    ['label' => 'Total Rosters', 'value' => (string) $dutySummary['total_rosters'], 'icon' => 'fa-solid fa-clipboard-list', 'tone' => 'primary'],
    ['label' => "Today's Assignments", 'value' => (string) $dutySummary['today_assignments'], 'icon' => 'fa-solid fa-user-check', 'tone' => 'success'],
    ['label' => 'Morning Assignments', 'value' => (string) $dutySummary['morning_assignments'], 'icon' => 'fa-solid fa-sun', 'tone' => 'warning'],
    ['label' => 'Evening Assignments', 'value' => (string) $dutySummary['evening_assignments'], 'icon' => 'fa-solid fa-moon', 'tone' => 'info'],
    ['label' => 'Available Employees', 'value' => (string) $dutySummary['available_employees'], 'icon' => 'fa-solid fa-users', 'tone' => 'orange'],
    ['label' => 'Published Rosters', 'value' => (string) $dutySummary['published_rosters'], 'icon' => 'fa-solid fa-circle-check', 'tone' => 'success'],
];

$shiftStats = [
    ['label' => 'Morning Shift Employees', 'value' => (string) $dutySummary['morning_assignments'], 'icon' => 'fa-solid fa-sun', 'tone' => 'success'],
    ['label' => 'Evening Shift Employees', 'value' => (string) $dutySummary['evening_assignments'], 'icon' => 'fa-solid fa-moon', 'tone' => 'info'],
    ['label' => 'Available Pumps', 'value' => (string) $dutySummary['available_pumps'], 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'primary'],
    ['label' => 'Inactive Pumps', 'value' => (string) $dutySummary['inactive_pumps'], 'icon' => 'fa-solid fa-triangle-exclamation', 'tone' => 'warning'],
];

$dutyStatusClasses = [
    'Draft' => 'duty-status--off',
    'Published' => 'duty-status--active',
    'Archived' => 'duty-status--completed',
    'Scheduled' => 'duty-status--scheduled',
    'Completed' => 'duty-status--completed',
    'Off Duty' => 'duty-status--off',
    'On Leave' => 'duty-status--leave',
    'Active' => 'duty-status--active',
    'Leave' => 'duty-status--leave',
    'Assigned' => 'duty-status--scheduled',
    'Cancelled' => 'duty-status--off',
];

if (($currentRoute ?? '') === 'admin/shift-management' || ($currentRoute ?? '') === 'admin/add-shift' || ($currentRoute ?? '') === 'admin/edit-shift') {
    $shiftModel = new Shift();
    $shiftFilters = [
        'search' => (string) $dutyRequest->query('search', ''),
        'status' => (string) $dutyRequest->query('status', ''),
        'reporting_time' => (string) $dutyRequest->query('reporting_time', ''),
        'closing_time' => (string) $dutyRequest->query('closing_time', ''),
        'sort' => (string) $dutyRequest->query('sort', 'shift_name'),
        'direction' => (string) $dutyRequest->query('direction', 'asc'),
        'page' => (int) $dutyRequest->query('page', 1),
        'per_page' => 20,
    ];
    $shiftResult = $shiftModel->paginated($shiftFilters);
    $shiftConfigurations = $shiftResult['records'];
    $shiftPagination = $shiftResult['pagination'];
    $shiftSuccess = Session::pullFlash('shift_success');
    $shiftError = Session::pullFlash('shift_error');
    $canManageShifts = (new ShiftManagementService($shiftModel))->canManage();
    $shiftStatuses = ['Active', 'Inactive'];
    $selectedShift = null;
    $requestedShiftId = (int) $dutyRequest->query('shift', 0);
    if ($requestedShiftId > 0) {
        $selectedShift = $shiftModel->findForView($requestedShiftId);
    }
    if ($selectedShift === null) {
        $selectedShift = ['id' => 0, 'shift_code' => '', 'shift_name' => '', 'name' => '', 'reporting_time' => '06:00', 'closing_time' => '14:00', 'maximum_employees' => 10, 'max_employees' => 10, 'grace_period' => 0, 'status' => 'Active', 'description' => ''];
    }
    $summary = $shiftModel->summary();
    $shiftStats = [
        ['label' => 'Total Shifts', 'value' => (string) $summary['total'], 'icon' => 'fa-solid fa-business-time', 'tone' => 'primary'],
        ['label' => 'Active Shifts', 'value' => (string) $summary['active'], 'icon' => 'fa-solid fa-circle-check', 'tone' => 'success'],
        ['label' => 'Inactive Shifts', 'value' => (string) $summary['inactive'], 'icon' => 'fa-solid fa-circle-pause', 'tone' => 'warning'],
        ['label' => 'Total Employees Assigned', 'value' => (string) $summary['assigned'], 'icon' => 'fa-solid fa-user-check', 'tone' => 'info'],
        ['label' => 'Morning Shift Employees', 'value' => (string) $summary['morning'], 'icon' => 'fa-solid fa-sun', 'tone' => 'success'],
        ['label' => 'Evening Shift Employees', 'value' => (string) $summary['evening'], 'icon' => 'fa-solid fa-moon', 'tone' => 'info'],
    ];
    $shiftAssignments = array_map(static fn (array $shift): array => ['employee' => 'Assigned Employees', 'department' => 'All Departments', 'shift' => $shift['shift_name'], 'reporting' => $shift['reporting'], 'closing' => $shift['closing'], 'status' => $shift['status']], $shiftConfigurations);
}
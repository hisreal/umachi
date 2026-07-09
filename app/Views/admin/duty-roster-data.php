<?php

declare(strict_types=1);

// ===============================================
// DATABASE PLACEHOLDER
// Replace these sample records with MySQL records.
// The arrays are intentionally structured like future query results.
// ===============================================
$departments = ['Operations', 'Finance', 'Security'];
$roles = ['Manager', 'Supervisor', 'Pump Attendant', 'Cashier', 'Accountant', 'Security'];
$fuelTypes = ['Petrol (PMS)', 'Diesel (AGO)', 'Gas (LPG)'];
$pumps = ['Pump 1', 'Pump 2', 'Pump 3', 'Pump 4'];
$shiftNames = ['Morning Shift', 'Evening Shift'];

$employees = [
    ['id' => 'EMP001', 'name' => 'John Doe', 'department' => 'Operations', 'role' => 'Pump Attendant'],
    ['id' => 'EMP002', 'name' => 'Mary Johnson', 'department' => 'Operations', 'role' => 'Pump Attendant'],
    ['id' => 'EMP003', 'name' => 'Chinedu Okafor', 'department' => 'Operations', 'role' => 'Supervisor'],
    ['id' => 'EMP004', 'name' => 'Aisha Bello', 'department' => 'Finance', 'role' => 'Cashier'],
    ['id' => 'EMP005', 'name' => 'Grace Williams', 'department' => 'Security', 'role' => 'Security'],
    ['id' => 'EMP006', 'name' => 'Samuel Eze', 'department' => 'Operations', 'role' => 'Pump Attendant'],
];

// ===============================================
// DATABASE PLACEHOLDER
// Retrieve duty roster from MySQL.
// ===============================================
$rosterAssignments = [
    ['date' => '2026-07-08', 'employee_id' => 'EMP001', 'employee' => 'John Doe', 'department' => 'Operations', 'role' => 'Pump Attendant', 'shift' => 'Morning Shift', 'pump' => 'Pump 1', 'fuel_type' => 'Petrol (PMS)', 'reporting' => '06:00 AM', 'closing' => '02:00 PM', 'status' => 'Scheduled'],
    ['date' => '2026-07-08', 'employee_id' => 'EMP002', 'employee' => 'Mary Johnson', 'department' => 'Operations', 'role' => 'Pump Attendant', 'shift' => 'Morning Shift', 'pump' => 'Pump 2', 'fuel_type' => 'Diesel (AGO)', 'reporting' => '06:00 AM', 'closing' => '02:00 PM', 'status' => 'Scheduled'],
    ['date' => '2026-07-08', 'employee_id' => 'EMP006', 'employee' => 'Samuel Eze', 'department' => 'Operations', 'role' => 'Pump Attendant', 'shift' => 'Evening Shift', 'pump' => 'Pump 3', 'fuel_type' => 'Petrol (PMS)', 'reporting' => '02:00 PM', 'closing' => '10:00 PM', 'status' => 'Scheduled'],
    ['date' => '2026-07-07', 'employee_id' => 'EMP004', 'employee' => 'Aisha Bello', 'department' => 'Finance', 'role' => 'Cashier', 'shift' => 'Evening Shift', 'pump' => 'Pump 4', 'fuel_type' => 'Gas (LPG)', 'reporting' => '02:00 PM', 'closing' => '10:00 PM', 'status' => 'Completed'],
    ['date' => '2026-07-08', 'employee_id' => 'EMP005', 'employee' => 'Grace Williams', 'department' => 'Security', 'role' => 'Security', 'shift' => 'Morning Shift', 'pump' => 'Gate Post', 'fuel_type' => 'N/A', 'reporting' => '06:00 AM', 'closing' => '02:00 PM', 'status' => 'Off Duty'],
    ['date' => '2026-07-09', 'employee_id' => 'EMP003', 'employee' => 'Chinedu Okafor', 'department' => 'Operations', 'role' => 'Supervisor', 'shift' => 'Morning Shift', 'pump' => 'Forecourt', 'fuel_type' => 'All Fuel Types', 'reporting' => '06:00 AM', 'closing' => '02:00 PM', 'status' => 'On Leave'],
];

$shiftConfigurations = [
    ['name' => 'Morning Shift', 'start' => '06:00', 'end' => '14:00', 'max_employees' => 8, 'status' => 'Active', 'assigned' => 12],
    ['name' => 'Evening Shift', 'start' => '14:00', 'end' => '22:00', 'max_employees' => 8, 'status' => 'Active', 'assigned' => 10],
];

$shiftAssignments = [
    ['employee' => 'John Doe', 'department' => 'Operations', 'shift' => 'Morning Shift', 'reporting' => '06:00 AM', 'closing' => '02:00 PM', 'status' => 'Active'],
    ['employee' => 'Mary Johnson', 'department' => 'Operations', 'shift' => 'Morning Shift', 'reporting' => '06:00 AM', 'closing' => '02:00 PM', 'status' => 'Active'],
    ['employee' => 'Samuel Eze', 'department' => 'Operations', 'shift' => 'Evening Shift', 'reporting' => '02:00 PM', 'closing' => '10:00 PM', 'status' => 'Active'],
    ['employee' => 'Grace Williams', 'department' => 'Security', 'shift' => 'Morning Shift', 'reporting' => '06:00 AM', 'closing' => '02:00 PM', 'status' => 'Off Duty'],
    ['employee' => 'Chinedu Okafor', 'department' => 'Operations', 'shift' => 'Morning Shift', 'reporting' => '06:00 AM', 'closing' => '02:00 PM', 'status' => 'Leave'],
];

// ===============================================
// DATABASE PLACEHOLDER
// Retrieve pump allocations from MySQL.
// ===============================================
$pumpAllocations = [
    ['date' => '2026-07-08', 'employee_id' => 'EMP001', 'employee' => 'John Doe', 'department' => 'Operations', 'role' => 'Pump Attendant', 'pump' => 'Pump 1', 'fuel_type' => 'Petrol (PMS)', 'shift' => 'Morning Shift', 'reporting' => '06:00 AM', 'closing' => '02:00 PM', 'supervisor' => 'Chinedu Okafor', 'status' => 'Assigned'],
    ['date' => '2026-07-08', 'employee_id' => 'EMP002', 'employee' => 'Mary Johnson', 'department' => 'Operations', 'role' => 'Pump Attendant', 'pump' => 'Pump 2', 'fuel_type' => 'Diesel (AGO)', 'shift' => 'Morning Shift', 'reporting' => '06:00 AM', 'closing' => '02:00 PM', 'supervisor' => 'Chinedu Okafor', 'status' => 'Assigned'],
    ['date' => '2026-07-08', 'employee_id' => 'EMP006', 'employee' => 'Samuel Eze', 'department' => 'Operations', 'role' => 'Pump Attendant', 'pump' => 'Pump 3', 'fuel_type' => 'Petrol (PMS)', 'shift' => 'Evening Shift', 'reporting' => '02:00 PM', 'closing' => '10:00 PM', 'supervisor' => 'Chinedu Okafor', 'status' => 'Assigned'],
    ['date' => '2026-07-07', 'employee_id' => 'EMP004', 'employee' => 'Aisha Bello', 'department' => 'Finance', 'role' => 'Cashier', 'pump' => 'Pump 4', 'fuel_type' => 'Gas (LPG)', 'shift' => 'Evening Shift', 'reporting' => '02:00 PM', 'closing' => '10:00 PM', 'supervisor' => 'Chinedu Okafor', 'status' => 'Completed'],
];

$dutyStats = [
    ['label' => 'Total Employees Assigned Today', 'value' => '24', 'icon' => 'fa-solid fa-user-check', 'tone' => 'primary'],
    ['label' => 'Morning Shift Employees', 'value' => '12', 'icon' => 'fa-solid fa-sun', 'tone' => 'success'],
    ['label' => 'Evening Shift Employees', 'value' => '10', 'icon' => 'fa-solid fa-moon', 'tone' => 'info'],
    ['label' => 'Available Employees', 'value' => '8', 'icon' => 'fa-solid fa-users', 'tone' => 'warning'],
    ['label' => 'Total Pump Assignments', 'value' => '18', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'orange'],
    ['label' => 'Pending Shift Changes', 'value' => '3', 'icon' => 'fa-solid fa-clock', 'tone' => 'danger'],
];

$shiftStats = [
    ['label' => 'Morning Shift Employees', 'value' => '12', 'icon' => 'fa-solid fa-sun', 'tone' => 'success'],
    ['label' => 'Evening Shift Employees', 'value' => '10', 'icon' => 'fa-solid fa-moon', 'tone' => 'info'],
    ['label' => 'Total Shifts Today', 'value' => '2', 'icon' => 'fa-solid fa-business-time', 'tone' => 'primary'],
    ['label' => 'Employees Off Duty', 'value' => '4', 'icon' => 'fa-solid fa-bed', 'tone' => 'warning'],
];

$dutyStatusClasses = [
    'Scheduled' => 'duty-status--scheduled',
    'Completed' => 'duty-status--completed',
    'Off Duty' => 'duty-status--off',
    'On Leave' => 'duty-status--leave',
    'Active' => 'duty-status--active',
    'Leave' => 'duty-status--leave',
    'Assigned' => 'duty-status--scheduled',
    'Cancelled' => 'duty-status--off',
];

$calendarEvents = array_map(static function (array $assignment): array {
    $shiftColor = match ($assignment['status']) {
        'On Leave', 'Leave' => '#f97316',
        'Off Duty' => '#64748b',
        default => $assignment['shift'] === 'Evening Shift' ? '#0ea5e9' : '#16a34a',
    };

    return [
        'title' => $assignment['employee'] . ' - ' . $assignment['shift'] . ' - ' . $assignment['pump'],
        'start' => $assignment['date'],
        'backgroundColor' => $shiftColor,
        'borderColor' => $shiftColor,
        'extendedProps' => $assignment,
    ];
}, $rosterAssignments);
<?php

declare(strict_types=1);

use App\Services\EmployeeManagementService;

$departments = ['Administration', 'Operations', 'Finance', 'Security'];
$roles = ['Admin', 'Manager', 'Supervisor', 'Pump Attendant', 'Cashier', 'Accountant', 'Security'];
$statuses = ['Active', 'Probation', 'Suspended', 'Resigned'];
$genders = ['Male', 'Female'];
$employmentTypes = ['Full Time', 'Part Time', 'Contract', 'Casual', 'Intern'];
$shifts = ['Morning Shift', 'Evening Shift', 'Night Shift', 'Rotational', 'Day Shift'];

try {
    $employeeService = new EmployeeManagementService();
    $employees = $employeeService->model()->directory();
    $options = $employeeService->model()->options();
    $departments = $options['departments'] !== [] ? $options['departments'] : $departments;
    $roles = $options['roles'] !== [] ? $options['roles'] : $roles;
    $shifts = $options['shifts'] !== [] ? $options['shifts'] : $shifts;
} catch (Throwable) {
    $employees = [
        ['id' => 'EMP001', 'first_name' => 'John', 'last_name' => 'Doe', 'name' => 'John Doe', 'gender' => 'Male', 'dob' => '1994-03-15', 'marital_status' => 'Married', 'phone' => '08031234567', 'email' => 'john.doe@example.com', 'address' => '12 Unity Street, Lagos', 'emergency_contact' => 'Grace Doe - 08035551234', 'emergency_contact_name' => 'Grace Doe', 'emergency_contact_phone' => '08035551234', 'department' => 'Operations', 'role' => 'Pump Attendant', 'employment_type' => 'Full Time', 'status' => 'Active', 'account_status' => 'active', 'date_joined' => '2025-01-15', 'supervisor' => 'Supervisor A', 'shift' => 'Morning Shift', 'salary' => 180000, 'allowance' => 25000, 'bank_name' => 'Access Bank', 'account_name' => 'John Doe', 'account_number' => '0123456789', 'username' => 'john.doe', 'photo' => 'images/sample-passport.svg'],
        ['id' => 'EMP002', 'first_name' => 'Mary', 'last_name' => 'Johnson', 'name' => 'Mary Johnson', 'gender' => 'Female', 'dob' => '1991-08-22', 'marital_status' => 'Single', 'phone' => '08039876543', 'email' => 'mary.johnson@example.com', 'address' => '8 Broad Avenue, Ikeja', 'emergency_contact' => 'Peter Johnson - 08038889999', 'emergency_contact_name' => 'Peter Johnson', 'emergency_contact_phone' => '08038889999', 'department' => 'Finance', 'role' => 'Cashier', 'employment_type' => 'Full Time', 'status' => 'Active', 'account_status' => 'active', 'date_joined' => '2024-11-01', 'supervisor' => 'Manager A', 'shift' => 'Morning Shift', 'salary' => 220000, 'allowance' => 30000, 'bank_name' => 'GTBank', 'account_name' => 'Mary Johnson', 'account_number' => '0234567891', 'username' => 'mary.johnson', 'photo' => 'images/sample-passport.svg'],
        ['id' => 'EMP003', 'first_name' => 'Daniel', 'last_name' => 'James', 'name' => 'Daniel James', 'gender' => 'Male', 'dob' => '1996-01-10', 'marital_status' => 'Single', 'phone' => '08027654321', 'email' => 'daniel.james@example.com', 'address' => '19 Market Road, Lagos', 'emergency_contact' => 'Ruth James - 08021110000', 'emergency_contact_name' => 'Ruth James', 'emergency_contact_phone' => '08021110000', 'department' => 'Operations', 'role' => 'Pump Attendant', 'employment_type' => 'Full Time', 'status' => 'Inactive', 'account_status' => 'inactive', 'date_joined' => '2025-03-03', 'supervisor' => 'Supervisor B', 'shift' => 'Evening Shift', 'salary' => 175000, 'allowance' => 20000, 'bank_name' => 'Zenith Bank', 'account_name' => 'Daniel James', 'account_number' => '0345678912', 'username' => 'daniel.james', 'photo' => 'images/sample-passport.svg'],
    ];
}

$selectedEmployee = $employees[0] ?? [
    'id' => 'EMP001', 'first_name' => '', 'last_name' => '', 'name' => 'Employee', 'gender' => '', 'dob' => '', 'marital_status' => '', 'phone' => '', 'email' => '', 'address' => '', 'emergency_contact' => '', 'emergency_contact_name' => '', 'emergency_contact_phone' => '', 'department' => '', 'role' => '', 'employment_type' => '', 'status' => 'Active', 'account_status' => 'active', 'date_joined' => date('Y-m-d'), 'supervisor' => '', 'shift' => '', 'salary' => 0, 'allowance' => 0, 'bank_name' => '', 'account_name' => '', 'account_number' => '', 'username' => '', 'photo' => 'images/sample-passport.svg',
];
$requestedEmployeeId = (string) ($_GET['employee'] ?? '');
foreach ($employees as $employeeRecord) {
    if ($employeeRecord['id'] === $requestedEmployeeId) {
        $selectedEmployee = $employeeRecord;
        break;
    }
}

$employeeStats = [
    ['label' => 'Total Employees', 'value' => count($employees), 'icon' => 'fa-solid fa-users', 'tone' => 'primary'],
    ['label' => 'Active Employees', 'value' => count(array_filter($employees, static fn (array $employee): bool => $employee['status'] === 'Active')), 'icon' => 'fa-solid fa-user-check', 'tone' => 'success'],
    ['label' => 'Inactive Employees', 'value' => count(array_filter($employees, static fn (array $employee): bool => in_array($employee['status'], ['Inactive', 'Deleted'], true))), 'icon' => 'fa-solid fa-user-xmark', 'tone' => 'danger'],
    ['label' => 'Employees on Leave', 'value' => count(array_filter($employees, static fn (array $employee): bool => $employee['status'] === 'On Leave')), 'icon' => 'fa-solid fa-calendar-days', 'tone' => 'warning'],
];

$statusClasses = [
    'Active' => 'employee-status--active',
    'Inactive' => 'employee-status--inactive',
    'Deleted' => 'employee-status--inactive',
    'On Leave' => 'employee-status--leave',
    'Present' => 'employee-status--active',
    'Late' => 'employee-status--leave',
    'Approved' => 'employee-status--active',
    'Pending' => 'employee-status--leave',
    'Scheduled' => 'employee-status--active',
];
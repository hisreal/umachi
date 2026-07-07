<?php

declare(strict_types=1);

// ============================================
// DATABASE PLACEHOLDER
// Retrieve employees from MySQL.
// ============================================
$employees = [
    ['id' => 'EMP001', 'first_name' => 'John', 'last_name' => 'Doe', 'name' => 'John Doe', 'gender' => 'Male', 'dob' => '1994-03-15', 'marital_status' => 'Married', 'phone' => '08031234567', 'email' => 'john.doe@example.com', 'address' => '12 Unity Street, Lagos', 'emergency_contact' => 'Grace Doe - 08035551234', 'department' => 'Operations', 'role' => 'Pump Attendant', 'employment_type' => 'Full Time', 'status' => 'Active', 'date_joined' => '2025-01-15', 'supervisor' => 'Supervisor A', 'shift' => 'Morning Shift', 'salary' => 180000, 'allowance' => 25000, 'bank_name' => 'Access Bank', 'account_name' => 'John Doe', 'account_number' => '0123456789', 'photo' => 'images/sample-passport.svg'],
    ['id' => 'EMP002', 'first_name' => 'Mary', 'last_name' => 'Johnson', 'name' => 'Mary Johnson', 'gender' => 'Female', 'dob' => '1991-08-22', 'marital_status' => 'Single', 'phone' => '08039876543', 'email' => 'mary.johnson@example.com', 'address' => '8 Broad Avenue, Ikeja', 'emergency_contact' => 'Peter Johnson - 08038889999', 'department' => 'Finance', 'role' => 'Cashier', 'employment_type' => 'Full Time', 'status' => 'Active', 'date_joined' => '2024-11-01', 'supervisor' => 'Manager A', 'shift' => 'Morning Shift', 'salary' => 220000, 'allowance' => 30000, 'bank_name' => 'GTBank', 'account_name' => 'Mary Johnson', 'account_number' => '0234567891', 'photo' => 'images/sample-passport.svg'],
    ['id' => 'EMP003', 'first_name' => 'Daniel', 'last_name' => 'James', 'name' => 'Daniel James', 'gender' => 'Male', 'dob' => '1996-01-10', 'marital_status' => 'Single', 'phone' => '08027654321', 'email' => 'daniel.james@example.com', 'address' => '19 Market Road, Lagos', 'emergency_contact' => 'Ruth James - 08021110000', 'department' => 'Operations', 'role' => 'Pump Attendant', 'employment_type' => 'Full Time', 'status' => 'Inactive', 'date_joined' => '2025-03-03', 'supervisor' => 'Supervisor B', 'shift' => 'Evening Shift', 'salary' => 175000, 'allowance' => 20000, 'bank_name' => 'Zenith Bank', 'account_name' => 'Daniel James', 'account_number' => '0345678912', 'photo' => 'images/sample-passport.svg'],
    ['id' => 'EMP004', 'first_name' => 'Esther', 'last_name' => 'Grace', 'name' => 'Esther Grace', 'gender' => 'Female', 'dob' => '1989-06-04', 'marital_status' => 'Married', 'phone' => '08025554433', 'email' => 'esther.grace@example.com', 'address' => '4 Central Close, Surulere', 'emergency_contact' => 'Michael Grace - 08024443322', 'department' => 'Operations', 'role' => 'Supervisor', 'employment_type' => 'Full Time', 'status' => 'Active', 'date_joined' => '2023-09-18', 'supervisor' => 'Manager A', 'shift' => 'Rotational', 'salary' => 320000, 'allowance' => 45000, 'bank_name' => 'UBA', 'account_name' => 'Esther Grace', 'account_number' => '0456789123', 'photo' => 'images/sample-passport.svg'],
    ['id' => 'EMP005', 'first_name' => 'Aisha', 'last_name' => 'Bello', 'name' => 'Aisha Bello', 'gender' => 'Female', 'dob' => '1993-12-01', 'marital_status' => 'Single', 'phone' => '08039991122', 'email' => 'aisha.bello@example.com', 'address' => '21 Adeniyi Jones, Ikeja', 'emergency_contact' => 'Fatima Bello - 08030001122', 'department' => 'Administration', 'role' => 'Manager', 'employment_type' => 'Full Time', 'status' => 'On Leave', 'date_joined' => '2022-05-10', 'supervisor' => 'Admin Office', 'shift' => 'Day Shift', 'salary' => 450000, 'allowance' => 65000, 'bank_name' => 'First Bank', 'account_name' => 'Aisha Bello', 'account_number' => '0567891234', 'photo' => 'images/sample-passport.svg'],
    ['id' => 'EMP006', 'first_name' => 'Samuel', 'last_name' => 'Peters', 'name' => 'Samuel Peters', 'gender' => 'Male', 'dob' => '1990-04-11', 'marital_status' => 'Married', 'phone' => '08028887766', 'email' => 'samuel.peters@example.com', 'address' => '31 Allen Avenue, Ikeja', 'emergency_contact' => 'Blessing Peters - 08027776655', 'department' => 'Security', 'role' => 'Security', 'employment_type' => 'Contract', 'status' => 'Active', 'date_joined' => '2024-02-20', 'supervisor' => 'Supervisor A', 'shift' => 'Night Shift', 'salary' => 160000, 'allowance' => 15000, 'bank_name' => 'Sterling Bank', 'account_name' => 'Samuel Peters', 'account_number' => '0678912345', 'photo' => 'images/sample-passport.svg'],
];

$departments = ['Administration', 'Operations', 'Finance', 'Security'];
$roles = ['Admin', 'Manager', 'Supervisor', 'Pump Attendant', 'Cashier', 'Accountant', 'Security'];
$statuses = ['Active', 'Inactive', 'On Leave'];
$genders = ['Male', 'Female'];
$employmentTypes = ['Full Time', 'Part Time', 'Contract', 'Intern'];
$shifts = ['Morning Shift', 'Evening Shift', 'Night Shift', 'Rotational', 'Day Shift'];

$selectedEmployee = $employees[0];
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
    ['label' => 'Inactive Employees', 'value' => count(array_filter($employees, static fn (array $employee): bool => $employee['status'] === 'Inactive')), 'icon' => 'fa-solid fa-user-xmark', 'tone' => 'danger'],
    ['label' => 'Employees on Leave', 'value' => count(array_filter($employees, static fn (array $employee): bool => $employee['status'] === 'On Leave')), 'icon' => 'fa-solid fa-calendar-days', 'tone' => 'warning'],
];

$statusClasses = [
    'Active' => 'employee-status--active',
    'Inactive' => 'employee-status--inactive',
    'On Leave' => 'employee-status--leave',
    'Present' => 'employee-status--active',
    'Late' => 'employee-status--leave',
    'Approved' => 'employee-status--active',
    'Pending' => 'employee-status--leave',
    'Scheduled' => 'employee-status--active',
];

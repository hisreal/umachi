<?php

declare(strict_types=1);

$roles = ['Admin', 'Manager', 'Supervisor', 'Pump Attendant', 'Cashier', 'Accountant', 'Security'];
$activityTypes = ['Login', 'Logout', 'Fuel Price Update', 'Employee Update', 'Leave Approval', 'Attendance Adjustment', 'Pump Assignment', 'Shift Assignment', 'Profile Update', 'Password Reset', 'System Settings', 'Duty Assignment'];
$activityStatuses = ['Success', 'Failed', 'Warning', 'Information'];

try {
    $settingsModel = new \App\Models\SettingsModel();
    $fuelPrices = $settingsModel->currentFuelPrices();
    $priceHistory = $settingsModel->fuelPriceHistory();
} catch (Throwable) {
    $fuelPrices = [
        'pms' => ['fuel' => 'Petrol (PMS)', 'price' => 0.00, 'updated_by' => 'System', 'effective_date' => date('Y-m-d'), 'effective_time' => date('H:i'), 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'primary'],
        'ago' => ['fuel' => 'Diesel (AGO)', 'price' => 0.00, 'updated_by' => 'System', 'effective_date' => date('Y-m-d'), 'effective_time' => date('H:i'), 'icon' => 'fa-solid fa-oil-can', 'tone' => 'warning'],
        'lpg' => ['fuel' => 'Gas (LPG)', 'price' => 0.00, 'updated_by' => 'System', 'effective_date' => date('Y-m-d'), 'effective_time' => date('H:i'), 'icon' => 'fa-solid fa-fire-flame-simple', 'tone' => 'info'],
    ];
    $priceHistory = [];
}

$priceCards = [];
foreach ($fuelPrices as $price) {
    $priceCards[] = [
        'label' => $price['fuel'] . ' Price',
        'value' => 'NGN ' . number_format((float) $price['price'], 2) . '/Litre',
        'icon' => $price['icon'],
        'tone' => $price['tone'],
    ];
}
$lastUpdated = $priceHistory[0]['date'] ?? 'N/A';
$priceCards[] = ['label' => 'Last Updated', 'value' => $lastUpdated, 'icon' => 'fa-solid fa-clock-rotate-left', 'tone' => 'success'];
$activityStats = [
    ['label' => 'Total Activities', 'value' => '2,486', 'icon' => 'fa-solid fa-list-check', 'tone' => 'primary'],
    ['label' => "Today's Activities", 'value' => '86', 'icon' => 'fa-solid fa-calendar-day', 'tone' => 'info'],
    ['label' => 'Failed Logins', 'value' => '7', 'icon' => 'fa-solid fa-triangle-exclamation', 'tone' => 'danger'],
    ['label' => 'Successful Logins', 'value' => '64', 'icon' => 'fa-solid fa-right-to-bracket', 'tone' => 'success'],
    ['label' => 'Price Changes', 'value' => '5', 'icon' => 'fa-solid fa-tags', 'tone' => 'warning'],
    ['label' => 'Employee Updates', 'value' => '18', 'icon' => 'fa-solid fa-user-pen', 'tone' => 'orange'],
    ['label' => 'Leave Approvals', 'value' => '12', 'icon' => 'fa-solid fa-circle-check', 'tone' => 'success'],
    ['label' => 'Attendance Adjustments', 'value' => '9', 'icon' => 'fa-solid fa-calendar-check', 'tone' => 'primary'],
];

// ===============================================
// DATABASE PLACEHOLDER
// Retrieve activity logs from MySQL.
// ===============================================
$activityLogs = [
    ['id' => 'ACT-1001', 'datetime' => '2026-07-09 08:30 AM', 'user' => 'Administrator', 'employee_id' => 'ADM001', 'role' => 'Admin', 'activity' => 'Updated Petrol Price', 'type' => 'Fuel Price Update', 'module' => 'Fuel Pricing', 'ip' => '192.168.1.10', 'browser' => 'Chrome 138', 'os' => 'Windows 11', 'device' => 'Desktop', 'old_value' => '₦920', 'new_value' => '₦945', 'status' => 'Success', 'notes' => 'Scheduled price update for next business day.'],
    ['id' => 'ACT-1002', 'datetime' => '2026-07-09 08:12 AM', 'user' => 'Grace Williams', 'employee_id' => 'EMP005', 'role' => 'Manager', 'activity' => 'Approved Leave Request', 'type' => 'Leave Approval', 'module' => 'Leave Management', 'ip' => '192.168.1.15', 'browser' => 'Edge 126', 'os' => 'Windows 10', 'device' => 'Laptop', 'old_value' => 'Pending', 'new_value' => 'Approved', 'status' => 'Success', 'notes' => 'Annual leave approved after supervisor recommendation.'],
    ['id' => 'ACT-1003', 'datetime' => '2026-07-09 07:55 AM', 'user' => 'Chinedu Okafor', 'employee_id' => 'EMP003', 'role' => 'Supervisor', 'activity' => 'Assigned Pump', 'type' => 'Pump Assignment', 'module' => 'Duty Roster', 'ip' => '192.168.1.22', 'browser' => 'Firefox 127', 'os' => 'Android 14', 'device' => 'Tablet', 'old_value' => 'Pump 2', 'new_value' => 'Pump 4', 'status' => 'Information', 'notes' => 'Morning shift reassignment due to pump maintenance.'],
    ['id' => 'ACT-1004', 'datetime' => '2026-07-09 07:40 AM', 'user' => 'Mary Johnson', 'employee_id' => 'EMP002', 'role' => 'Cashier', 'activity' => 'Failed Login Attempt', 'type' => 'Login', 'module' => 'Authentication', 'ip' => '192.168.1.31', 'browser' => 'Chrome Mobile', 'os' => 'Android 13', 'device' => 'Mobile', 'old_value' => 'N/A', 'new_value' => 'Invalid Password', 'status' => 'Failed', 'notes' => 'Incorrect password entered twice.'],
    ['id' => 'ACT-1005', 'datetime' => '2026-07-09 07:25 AM', 'user' => 'Samuel Eze', 'employee_id' => 'EMP006', 'role' => 'Pump Attendant', 'activity' => 'Updated Profile Phone Number', 'type' => 'Profile Update', 'module' => 'Employee Profile', 'ip' => '192.168.1.46', 'browser' => 'Chrome Mobile', 'os' => 'Android 14', 'device' => 'Mobile', 'old_value' => '+234 801 111 2233', 'new_value' => '+234 802 444 7788', 'status' => 'Success', 'notes' => 'Employee updated contact phone number in demo mode.'],
    ['id' => 'ACT-1006', 'datetime' => '2026-07-08 06:05 PM', 'user' => 'Administrator', 'employee_id' => 'ADM001', 'role' => 'Admin', 'activity' => 'Changed System Settings', 'type' => 'System Settings', 'module' => 'Settings', 'ip' => '192.168.1.10', 'browser' => 'Chrome 138', 'os' => 'Windows 11', 'device' => 'Desktop', 'old_value' => 'Manual Approval', 'new_value' => 'Multi-Level Approval', 'status' => 'Warning', 'notes' => 'Approval workflow changed for leave requests.'],
];

$activityStatusClasses = [
    'Success' => 'settings-status--success',
    'Failed' => 'settings-status--failed',
    'Warning' => 'settings-status--warning',
    'Information' => 'settings-status--info',
];


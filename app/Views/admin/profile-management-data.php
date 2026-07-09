<?php

declare(strict_types=1);

// ===========================================
// DATABASE PLACEHOLDER
// Retrieve administrator profile from MySQL.
// ===========================================
$admin = [
    'id' => 'ADM001',
    'name' => 'System Administrator',
    'username' => 'admin',
    'gender' => 'Male',
    'dob' => '1988-03-15',
    'email' => 'admin@abcfillingstation.com',
    'phone' => '+234 801 234 5678',
    'address' => '12 Unity Avenue, Ikeja, Lagos',
    'emergency_contact_name' => 'Jane Administrator',
    'emergency_contact_phone' => '+234 809 876 5432',
    'department' => 'Administration',
    'role' => 'Administrator',
    'joined' => '2026-01-10',
    'status' => 'Active',
    'account_status' => 'Active',
    'last_login' => '09 Jul 2026, 08:15 AM',
    'last_password_change' => '15 Jun 2026, 10:30 AM',
    'two_factor' => 'Not Enabled',
    'avatar' => 'images/sample-passport.svg',
];

$activitySummary = [
    ['label' => 'Total Login Sessions', 'value' => '248', 'icon' => 'fa-solid fa-right-to-bracket', 'tone' => 'primary'],
    ['label' => 'Last Login', 'value' => '09 Jul 2026', 'icon' => 'fa-solid fa-clock', 'tone' => 'info'],
    ['label' => 'Profile Updated', 'value' => '02 Jul 2026', 'icon' => 'fa-solid fa-user-pen', 'tone' => 'success'],
    ['label' => 'Password Changed', 'value' => '15 Jun 2026', 'icon' => 'fa-solid fa-key', 'tone' => 'warning'],
];

$loginHistory = [
    ['date' => '09 Jul 2026, 08:15 AM', 'ip' => '192.168.1.10', 'browser' => 'Chrome 138', 'status' => 'Success'],
    ['date' => '08 Jul 2026, 06:05 PM', 'ip' => '192.168.1.10', 'browser' => 'Chrome 138', 'status' => 'Success'],
    ['date' => '08 Jul 2026, 07:40 AM', 'ip' => '192.168.1.18', 'browser' => 'Edge 126', 'status' => 'Success'],
];
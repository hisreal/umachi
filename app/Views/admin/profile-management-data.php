<?php

declare(strict_types=1);

use App\Models\Profile;

try {
    $profile = (new Profile())->currentUserProfile();

    $admin = [
        'id' => $profile['employee_id'] ?? 'N/A',
        'name' => $profile['name'] ?? 'Administrator',
        'username' => $profile['username'] ?? 'N/A',
        'gender' => $profile['gender'] ?? 'N/A',
        'dob' => $profile['dob_raw'] ?? '',
        'email' => $profile['email'] ?? '',
        'phone' => $profile['phone'] ?? '',
        'address' => $profile['address'] ?? '',
        'emergency_contact_name' => $profile['emergency_contact_name'] ?? 'Primary Emergency Contact',
        'emergency_contact_phone' => $profile['emergency_contact_phone'] ?? ($profile['emergency_contact'] ?? ''),
        'department' => $profile['department'] ?? 'Administration',
        'role' => $profile['role'] ?? 'Administrator',
        'joined' => $profile['date_joined_raw'] ?? '',
        'status' => $profile['employment_status'] ?? 'Active',
        'account_status' => $profile['account_status'] ?? 'Active',
        'last_login' => $profile['last_login'] ?? 'N/A',
        'last_password_change' => $profile['last_password_change'] ?? 'N/A',
        'two_factor' => 'Not Enabled',
        'avatar' => $profile['passport_photo'] ?? 'images/sample-passport.svg',
    ];
} catch (Throwable) {
    $authUser = \App\Core\Session::get('auth.user', []);
    $admin = [
        'id' => 'N/A',
        'name' => is_array($authUser) ? (string) ($authUser['name'] ?? 'Administrator') : 'Administrator',
        'username' => is_array($authUser) ? (string) ($authUser['username'] ?? 'N/A') : 'N/A',
        'gender' => 'N/A',
        'dob' => '',
        'email' => is_array($authUser) ? (string) ($authUser['email'] ?? '') : '',
        'phone' => is_array($authUser) ? (string) ($authUser['phone'] ?? '') : '',
        'address' => '',
        'emergency_contact_name' => 'Primary Emergency Contact',
        'emergency_contact_phone' => '',
        'department' => 'Administration',
        'role' => \App\Core\Session::get('auth.role', 'Administrator'),
        'joined' => '',
        'status' => 'Active',
        'account_status' => 'Active',
        'last_login' => 'N/A',
        'last_password_change' => 'N/A',
        'two_factor' => 'Not Enabled',
        'avatar' => 'images/sample-passport.svg',
    ];
}

$activitySummary = [
    ['label' => 'Total Login Sessions', 'value' => 'Current Account', 'icon' => 'fa-solid fa-right-to-bracket', 'tone' => 'primary'],
    ['label' => 'Last Login', 'value' => $admin['last_login'], 'icon' => 'fa-solid fa-clock', 'tone' => 'info'],
    ['label' => 'Profile Status', 'value' => $admin['status'], 'icon' => 'fa-solid fa-user-pen', 'tone' => 'success'],
    ['label' => 'Password Changed', 'value' => $admin['last_password_change'], 'icon' => 'fa-solid fa-key', 'tone' => 'warning'],
];

$loginHistory = [
    ['date' => $admin['last_login'], 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A', 'browser' => 'Current Browser', 'status' => 'Success'],
];
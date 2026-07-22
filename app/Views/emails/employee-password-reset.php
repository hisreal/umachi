<?php

declare(strict_types=1);

$emailTitle = 'Your UMACHI Employee Account Password Has Been Reset';
$emailHeading = 'Password Reset Notification';
$emailPreheader = 'Your employee account password has been reset. Use your new temporary login credentials.';
$emailIntro = 'Your account password has been successfully reset by the system administrator. A new temporary password has been generated for you. Please use the login credentials below to access your account.';
$emailCardTitle = 'Login Details';
$isPasswordReset = true;
$securityTitle = 'Important Security Notice';
$securityItems = [
    'This is a temporary password.',
    'Change your password immediately after your next login.',
    'Do not share your login credentials with anyone.',
    'Keep your account secure.',
];

require __DIR__ . '/employee-welcome.php';

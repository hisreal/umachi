<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/autoload.php';

use App\Services\MailService;

$mail = new MailService([]);
$html = $mail->renderTemplate('employee-welcome', [
    'employeeName' => 'Ada <Okafor>',
    'employeeId' => 'UMACHI-0001',
    'companyEmail' => 'ada@company.test',
    'password' => 'Temp@1234',
    'department' => 'Operations',
    'role' => 'Pump Attendant',
    'dateJoined' => 'July 21, 2026',
    'loginUrl' => 'https://portal.example.test/login?source=email',
    'companyLogo' => 'https://portal.example.test/logo.png',
    'companyName' => 'UMACHI Oil & Gas Ltd',
    'companyAddress' => 'Company Address',
    'companyPhone' => '+234 000 0000',
    'companyContactEmail' => 'support@example.test',
    'emailSubject' => 'Welcome',
]);

$required = [
    'UMACHI-0001',
    'ada@company.test',
    'Temp@1234',
    'Operations',
    'Pump Attendant',
    'July 21, 2026',
    'https://portal.example.test/login?source=email',
    'Ada &lt;Okafor&gt;',
];

foreach ($required as $value) {
    if (!str_contains($html, $value)) {
        throw new RuntimeException('Rendered welcome email is missing: ' . $value);
    }
}
if (str_contains($html, 'Ada <Okafor>')) {
    throw new RuntimeException('Dynamic email output was not escaped.');
}

$resetHtml = $mail->renderTemplate('employee-password-reset', [
    'employeeName' => 'Ada Okafor',
    'employeeId' => 'UMACHI-0001',
    'companyEmail' => 'ada@company.test',
    'password' => 'Um@abcdef123456',
    'department' => 'Operations',
    'role' => 'Pump Attendant',
    'dateJoined' => '',
    'loginUrl' => 'https://portal.example.test/login',
    'companyLogo' => 'https://portal.example.test/logo.png',
    'companyName' => 'UMACHI Oil & Gas Ltd',
    'companyAddress' => 'Company Address',
    'companyPhone' => '+234 000 0000',
    'companyContactEmail' => 'support@example.test',
    'emailSubject' => 'Your UMACHI Employee Account Password Has Been Reset',
]);

foreach (['Password Reset Notification', 'Ada Okafor', 'UMACHI-0001', 'ada@company.test', 'Um@abcdef123456', 'Change your password immediately after your next login.'] as $value) {
    if (!str_contains($resetHtml, $value)) {
        throw new RuntimeException('Rendered password reset email is missing: ' . $value);
    }
}
if (str_contains($resetHtml, 'Date Joined')) {
    throw new RuntimeException('Password reset email should not include Date Joined.');
}

echo "Password reset template tests passed.\n";

echo "Mail service template tests passed.\n";

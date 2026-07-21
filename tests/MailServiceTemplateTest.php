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

echo "Mail service template tests passed.\n";

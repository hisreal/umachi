<?php

declare(strict_types=1);

return [
    'host' => env('MAIL_HOST', ''),
    'port' => (int) env('MAIL_PORT', 587),
    'username' => env('MAIL_USERNAME', ''),
    'password' => env('MAIL_PASSWORD', ''),
    'encryption' => strtolower((string) env('MAIL_ENCRYPTION', 'tls')),
    'smtp_auth' => filter_var(env('MAIL_SMTP_AUTH', true), FILTER_VALIDATE_BOOLEAN),
    'from_address' => env('MAIL_FROM_ADDRESS', ''),
    'from_name' => env('MAIL_FROM_NAME', env('APP_NAME', 'FuelOps')),
    'company_name' => env('MAIL_COMPANY_NAME', env('APP_NAME', 'FuelOps')),
    'company_logo' => env('MAIL_COMPANY_LOGO_URL', ''),
    'company_address' => env('MAIL_COMPANY_ADDRESS', ''),
    'company_phone' => env('MAIL_COMPANY_PHONE', ''),
    'company_email' => env('MAIL_COMPANY_EMAIL', env('MAIL_FROM_ADDRESS', '')),
    'login_url' => env('MAIL_LOGIN_URL', ''),
    'timeout' => (int) env('MAIL_TIMEOUT', 15),
];

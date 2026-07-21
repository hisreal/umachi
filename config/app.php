<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'FuelOps'),
    'env' => env('APP_ENV', 'local'),
    'debug' => filter_var(env('APP_DEBUG', true), FILTER_VALIDATE_BOOLEAN),
    'url' => env('APP_URL', ''),
    'timezone' => env('APP_TIMEZONE', 'Africa/Lagos'),
    'views_path' => dirname(__DIR__) . '/app/Views',
    'assets_path' => '/public/assets',
    'administrator_quick_links' => [
        ['label' => 'Gmail', 'url' => env('GMAIL_URL', 'https://mail.google.com/'), 'icon' => 'fa-solid fa-envelope', 'tooltip' => 'Open Gmail', 'variant' => 'gmail'],
        ['label' => 'WebMail', 'url' => env('WEBMAIL_URL', ''), 'icon' => 'fa-solid fa-envelope-open-text', 'tooltip' => 'Open Company WebMail', 'variant' => 'webmail'],
    ],
];
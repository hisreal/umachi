<?php

declare(strict_types=1);

return [
    // Default administrator account for first-time installation.
    // Change DEFAULT_ADMIN_PASSWORD in .env before seeding if you want a different first-login password.
    'default_admin' => [
        'username' => env('DEFAULT_ADMIN_USERNAME', 'admin'),
        'password' => env('DEFAULT_ADMIN_PASSWORD', 'password1'),
        'role' => env('DEFAULT_ADMIN_ROLE', 'Admin'),
        'full_name' => env('DEFAULT_ADMIN_FULL_NAME', 'System Administrator'),
        'email' => env('DEFAULT_ADMIN_EMAIL', 'admin@umachi.com'),
        'status' => env('DEFAULT_ADMIN_STATUS', 'active'),
        'must_change_password' => true,
    ],
];

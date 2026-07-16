<?php

declare(strict_types=1);

return [
    'timeout_minutes' => (int) env('SESSION_TIMEOUT_MINUTES', 30),
    'failed_login_limit' => (int) env('AUTH_FAILED_LOGIN_LIMIT', 5),
    'failed_login_window_minutes' => (int) env('AUTH_FAILED_LOGIN_WINDOW_MINUTES', 15),
    'lockout_minutes' => (int) env('AUTH_LOCKOUT_MINUTES', 30),
    'default_redirect' => env('AUTH_DEFAULT_REDIRECT', 'dashboard'),
    'admin_redirect' => env('AUTH_ADMIN_REDIRECT', 'admin/dashboard'),
];

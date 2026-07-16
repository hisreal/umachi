<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

if (!defined('VIEW_PATH')) {
    define('VIEW_PATH', dirname(__DIR__));
}

$autoloadPath = BASE_PATH . '/bootstrap/autoload.php';

if (!class_exists(\App\Services\AssetService::class) && is_file($autoloadPath)) {
    require_once $autoloadPath;
}

$helpersPath = BASE_PATH . '/app/Helpers/functions.php';

if (is_file($helpersPath)) {
    require_once $helpersPath;
}
if (!function_exists('format_date')) {
    /**
     * Safely format user, database, or sample date values for display.
     */
    function format_date(mixed $value, string $format = 'd M Y', string $fallback = 'N/A'): string
    {
        if ($value === null || trim((string) $value) === '') {
            return $fallback;
        }

        $timestamp = is_numeric($value) ? (int) $value : strtotime((string) $value);

        if ($timestamp === false) {
            return $fallback;
        }

        return date($format, $timestamp);
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Safely format date-time values while falling back for empty or invalid data.
     */
    function format_datetime(mixed $value, string $format = 'd M Y, h:i A', string $fallback = 'N/A'): string
    {
        return format_date($value, $format, $fallback);
    }
}


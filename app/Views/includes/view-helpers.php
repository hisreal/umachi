<?php

declare(strict_types=1);

if (!function_exists('e')) {
    function e(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('app_base_url')) {
    function app_base_url(): string
    {
        if (isset($GLOBALS['appBaseUrl'])) {
            return rtrim((string) $GLOBALS['appBaseUrl'], '/');
        }

        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/umachi/index.php'));
        $appPosition = strpos($scriptName, '/app/');

        if ($appPosition !== false) {
            return rtrim(substr($scriptName, 0, $appPosition), '/');
        }

        $publicPosition = strpos($scriptName, '/public/');

        if ($publicPosition !== false) {
            return rtrim(substr($scriptName, 0, $publicPosition), '/');
        }

        $basePath = rtrim(dirname($scriptName), '/');

        return $basePath === '' || $basePath === '.' ? '' : $basePath;
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        $assetBaseUrl = $GLOBALS['assetBaseUrl'] ?? app_base_url() . '/public/assets';

        return rtrim($assetBaseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('route_url')) {
    function route_url(string $route): string
    {
        $normalizedRoute = trim($route, '/');
        $baseUrl = app_base_url();
        $frontController = ($baseUrl === '' ? '' : $baseUrl) . '/index.php';

        return $frontController . '?route=' . rawurlencode($normalizedRoute);
    }
}

if (!function_exists('view_path')) {
    function view_path(string $path): string
    {
        return __DIR__ . '/../' . ltrim($path, '/');
    }
}

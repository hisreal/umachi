<?php

declare(strict_types=1);

use App\Services\AssetService;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}

if (!function_exists('e')) {
    function e(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('app_base_url')) {
    function app_base_url(): string
    {
        return AssetService::appBaseUrl();
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        return AssetService::assetUrl($path);
    }
}

if (!function_exists('route_url')) {
    function route_url(string $route): string
    {
        $baseUrl = app_base_url();
        $frontController = ($baseUrl === '' ? '' : $baseUrl) . '/index.php';

        return $frontController . '?route=' . rawurlencode(trim($route, '/'));
    }
}

if (!function_exists('view_path')) {
    function view_path(string $path): string
    {
        return VIEW_PATH . '/' . ltrim($path, '/');
    }
}
<?php

declare(strict_types=1);

namespace App\Services;

class AssetService
{
    public static function appBaseUrl(): string
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

    public static function baseUrl(): string
    {
        return self::appBaseUrl() . '/public/assets';
    }

    public static function assetUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        $assetBaseUrl = $GLOBALS['assetBaseUrl'] ?? self::baseUrl();

        return rtrim($assetBaseUrl, '/') . '/' . ltrim($path, '/');
    }
}
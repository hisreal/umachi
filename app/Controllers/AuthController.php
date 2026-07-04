<?php

declare(strict_types=1);

namespace App\Controllers;

class AuthController
{
    private string $assetBaseUrl;

    public function __construct()
    {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $basePath = rtrim($scriptDir === '/' ? '' : $scriptDir, '/');
        $this->assetBaseUrl = $basePath . '/public/assets';
    }

    /**
     * Load the standalone login page with no dashboard shell.
     */
    public function login(): void
    {
        $GLOBALS['assetBaseUrl'] = $this->assetBaseUrl;

        require __DIR__ . '/../Views/auth/login.php';
    }
}

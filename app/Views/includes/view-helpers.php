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

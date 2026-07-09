<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('ROUTES_PATH', BASE_PATH . '/routes');
define('VIEW_PATH', APP_PATH . '/Views');

require BASE_PATH . '/bootstrap/autoload.php';
require APP_PATH . '/Helpers/functions.php';

use App\Core\Application;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Services\ViewService;
use App\Utilities\Env;

Env::load(BASE_PATH . '/.env');

$config = new Config(CONFIG_PATH);
date_default_timezone_set((string) $config->get('app.timezone', 'Africa/Lagos'));

$request = Request::capture();
$response = new Response();
$view = new ViewService((string) $config->get('app.views_path', VIEW_PATH));
$router = new Router($request, $response);

$app = new Application($config, $router, $request, $response, $view);

(require ROUTES_PATH . '/web.php')($router);

return $app;
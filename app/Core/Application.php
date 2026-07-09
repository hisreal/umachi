<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\ViewService;
use Throwable;

class Application
{
    public function __construct(
        private Config $config,
        private Router $router,
        private Request $request,
        private Response $response,
        private ViewService $view
    ) {
    }

    public function run(): void
    {
        try {
            $this->router->dispatch();
        } catch (Throwable $exception) {
            if ((bool) $this->config->get('app.debug', false)) {
                throw $exception;
            }

            $this->response->setStatusCode(500);
            echo 'Internal Server Error';
        }
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function view(): ViewService
    {
        return $this->view;
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\AssetService;
use App\Services\ViewService;

abstract class Controller
{
    protected string $assetBaseUrl;
    protected ViewService $view;

    public function __construct()
    {
        $this->assetBaseUrl = AssetService::baseUrl();
        $this->view = new ViewService(VIEW_PATH);
    }

    protected function render(string $view, array $data = []): void
    {
        $this->view->render($view, array_merge([
            'assetBaseUrl' => $this->assetBaseUrl,
        ], $data));
    }
}
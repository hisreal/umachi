<?php

declare(strict_types=1);

namespace App\Services;

class ViewService
{
    public function __construct(private string $viewPath)
    {
    }

    public function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        require rtrim($this->viewPath, '/\\') . '/' . ltrim($view, '/');
    }

    public function path(string $view): string
    {
        return rtrim($this->viewPath, '/\\') . '/' . ltrim($view, '/');
    }
}

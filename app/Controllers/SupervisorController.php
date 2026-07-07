<?php

declare(strict_types=1);

namespace App\Controllers;

class SupervisorController
{
    private string $assetBaseUrl;

    public function __construct()
    {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $basePath = rtrim($scriptDir === '/' ? '' : $scriptDir, '/');
        $this->assetBaseUrl = $basePath . '/public/assets';
    }

    /**
     * Load the manager/supervisor duty roster management page.
     */
    public function manageDutyRoster(): void
    {
        $this->render('supervisor/manage-duty-roster.php', [
            'assetBaseUrl' => $this->assetBaseUrl,
            'currentRoute' => 'supervisor/manage-duty-roster',
        ]);
    }

    /**
     * Render a view with scoped variables.
     */
    private function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        require __DIR__ . '/../Views/' . ltrim($view, '/');
    }
}

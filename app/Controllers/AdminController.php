<?php

declare(strict_types=1);

namespace App\Controllers;

class AdminController
{
    private string $assetBaseUrl;

    public function __construct()
    {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $basePath = rtrim($scriptDir === '/' ? '' : $scriptDir, '/');
        $this->assetBaseUrl = $basePath . '/public/assets';
    }

    /**
     * Load the administrator dashboard landing page.
     */
    public function dashboard(): void
    {
        $this->render('admin/dashboard.php', [
            'assetBaseUrl' => $this->assetBaseUrl,
            'currentRoute' => 'admin/dashboard',
            'navItems' => $this->adminNavItems(),
        ]);
    }

    /**
     * Load a frontend-only placeholder page for future admin modules.
     */
    public function placeholderPage(string $route): void
    {
        $employeeViews = [
            'admin/employees' => 'admin/employee-list.php',
            'admin/add-employee' => 'admin/add-employee.php',
            'admin/edit-employee' => 'admin/edit-employee.php',
            'admin/employee-profile' => 'admin/employee-profile.php',
            'admin/employee-documents' => 'admin/employee-documents.php',
            'admin/attendance-dashboard' => 'admin/attendance-dashboard.php',
            'admin/attendance-history' => 'admin/attendance-history.php',
            'admin/attendance-settings' => 'admin/attendance-settings.php',
            'admin/fuel-sales' => 'admin/fuel-sales.php',
            'admin/fuel-sales-dashboard' => 'admin/fuel-sales.php',
            'admin/verify-sales' => 'admin/verify-sales.php',
            'admin/fuel-sales-history' => 'admin/fuel-sales-history.php',
            'admin/fuel-sales-report' => 'admin/fuel-sales-report.php',
            'admin/fuel-sales-reports' => 'admin/fuel-sales-report.php',
            'admin/pump-meter-history' => 'admin/pump-meter-history.php',
        ];

        if (isset($employeeViews[$route])) {
            $this->render($employeeViews[$route], [
                'assetBaseUrl' => $this->assetBaseUrl,
                'currentRoute' => $route,
                'navItems' => $this->adminNavItems(),
            ]);
            return;
        }

        $pageHeading = $this->titleFromRoute($route);

        $this->render('attendant/dashboard-page.php', [
            'assetBaseUrl' => $this->assetBaseUrl,
            'currentRoute' => $route,
            'pageTitle' => $pageHeading . ' | FuelOps Admin Dashboard',
            'pageHeading' => $pageHeading,
            'topbarSubtitle' => 'Admin Dashboard',
            'pageIntro' => 'This admin module is prepared as a placeholder for future implementation.',
            'pageIcon' => $this->iconForRoute($route),
            'extraStyles' => ['css/clock-in.css', 'css/admin-dashboard.css'],
            'extraScripts' => ['js/admin-dashboard.js'],
            'employee' => [
                'name' => 'Administrator',
                'role' => 'System Administrator',
            ],
            'attendantName' => 'Administrator',
            'attendantRole' => 'System Administrator',
            'sidebarVariant' => 'admin-sidebar',
            'sidebarHomeRoute' => 'admin/dashboard',
            'sidebarBrandTitle' => 'FuelOps',
            'sidebarBrandSubtitle' => 'Admin Panel',
            'navItems' => $this->adminNavItems(),
        ]);
    }

    /**
     * Load the reusable admin navigation definition.
     */
    private function adminNavItems(): array
    {
        return require __DIR__ . '/../Views/includes/admin-nav.php';
    }

    /**
     * Convert a route slug into a human-readable placeholder title.
     */
    private function titleFromRoute(string $route): string
    {
        $slug = preg_replace('/^admin\//', '', trim($route, '/'));
        $title = str_replace('-', ' ', (string) $slug);

        return ucwords($title);
    }

    /**
     * Pick a sensible icon for placeholder admin modules.
     */
    private function iconForRoute(string $route): string
    {
        if (str_contains($route, 'employee')) {
            return 'fa-solid fa-users';
        }

        if (str_contains($route, 'attendance')) {
            return 'fa-solid fa-calendar-check';
        }

        if (str_contains($route, 'fuel') || str_contains($route, 'pump')) {
            return 'fa-solid fa-gas-pump';
        }

        if (str_contains($route, 'leave')) {
            return 'fa-solid fa-person-walking-arrow-right';
        }

        if (str_contains($route, 'report')) {
            return 'fa-solid fa-chart-bar';
        }

        if (str_contains($route, 'setting') || str_contains($route, 'backup')) {
            return 'fa-solid fa-gears';
        }

        return 'fa-solid fa-gauge-high';
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

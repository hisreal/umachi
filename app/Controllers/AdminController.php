<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class AdminController extends Controller
{
    /**
     * Load the administrator dashboard landing page.
     */
    public function dashboard(): void
    {
        $this->render('admin/dashboard.php', [
            'currentRoute' => 'admin/dashboard',
            'navItems' => $this->adminNavItems(),
        ]);
    }

    /**
     * Load an admin page or a frontend-only placeholder for future modules.
     */
    public function placeholderPage(string $route): void
    {
        $adminViews = [
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
            'admin/fuel-inventory' => 'admin/fuel-inventory.php',
            'admin/pump-meter-history' => 'admin/pump-meter-history.php',
            'admin/pumps' => 'admin/pumps.php',
            'admin/add-pump' => 'admin/add-pump.php',
            'admin/edit-pump' => 'admin/edit-pump.php',
            'admin/duty-roster' => 'admin/duty-roster.php',
            'admin/manage-duty-roster' => 'admin/duty-roster.php',
            'admin/calendar' => 'admin/calendar.php',
            'admin/duty-calendar' => 'admin/calendar.php',
            'admin/shift-management' => 'admin/shift-management.php',
            'admin/pump-allocation' => 'admin/pump-allocation.php',
            'admin/leave-dashboard' => 'admin/leave-dashboard.php',
            'admin/leave-requests' => 'admin/leave-requests.php',
            'admin/leave-history' => 'admin/leave-history.php',
            'admin/leave-types' => 'admin/leave-types.php',
            'admin/approval-settings' => 'admin/leave-approval-settings.php',
            'admin/leave-approval-settings' => 'admin/leave-approval-settings.php',
            'admin/fuel-pricing' => 'admin/fuel-pricing.php',
            'admin/announcements' => 'admin/announcements.php',
            'admin/add-announcement' => 'admin/add-announcement.php',
            'admin/edit-announcement' => 'admin/edit-announcement.php',
            'admin/announcement-details' => 'admin/announcement-details.php',
            'admin/activity-log' => 'admin/activity-log.php',
            'admin/profile' => 'admin/profile.php',
            'admin/edit-profile' => 'admin/edit-profile.php',
            'admin/change-password' => 'admin/change-password.php',
        ];

        $view = $adminViews[$route] ?? null;

        if ($view !== null && is_file(VIEW_PATH . '/' . $view)) {
            $this->render($view, [
                'currentRoute' => $route,
                'navItems' => $this->adminNavItems(),
            ]);
            return;
        }

        $this->renderAdminPlaceholder($route);
    }

    private function renderAdminPlaceholder(string $route): void
    {
        $pageHeading = $this->titleFromRoute($route);

        $this->render('attendant/dashboard-page.php', [
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

    private function adminNavItems(): array
    {
        return require VIEW_PATH . '/includes/admin-nav.php';
    }

    private function titleFromRoute(string $route): string
    {
        $slug = preg_replace('/^admin\//', '', trim($route, '/'));
        $title = str_replace('-', ' ', (string) $slug);

        return ucwords($title);
    }

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
}
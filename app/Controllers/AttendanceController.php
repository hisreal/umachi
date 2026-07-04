<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Attendance;

class AttendanceController
{
    private Attendance $attendance;
    private string $assetBaseUrl;

    public function __construct()
    {
        if (!class_exists(Attendance::class)) {
            require_once __DIR__ . '/../Models/Attendance.php';
        }

        $this->attendance = new Attendance();
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $basePath = rtrim($scriptDir === '/' ? '' : $scriptDir, '/');
        $this->assetBaseUrl = $basePath . '/public/assets';
    }

    /**
     * Load the staff dashboard overview.
     */
    public function index(): void
    {
        $this->dashboard();
    }

    /**
     * Load the dashboard overview page with static summary data.
     */
    public function dashboard(): void
    {
        $this->renderStaticPage('dashboard', [
            'pageTitle' => 'Dashboard | FuelOps Staff Dashboard',
            'pageHeading' => 'Dashboard',
            'pageIntro' => 'Review today\'s shift status, assigned pump, and recent station activity.',
            'pageIcon' => 'fa-solid fa-gauge-high',
            'extraStyles' => ['css/clock-in.css', 'css/dashboard.css'],
            'quickActions' => [
                [
                    'title' => 'Clock In',
                    'description' => 'Start your work shift and record today\'s attendance.',
                    'route' => 'attendance/clock-in',
                    'icon' => 'fa-solid fa-fingerprint',
                ],
                [
                    'title' => 'Clock Out',
                    'description' => 'End your work shift and submit today\'s fuel sales.',
                    'route' => 'attendance/clock-out',
                    'icon' => 'fa-solid fa-arrow-right-from-bracket',
                ],
                [
                    'title' => 'Duty Roster',
                    'description' => 'View your assigned shifts and pump schedule.',
                    'route' => 'duty-roster',
                    'icon' => 'fa-solid fa-calendar-days',
                ],
            ],
            // DATABASE PLACEHOLDER
            // Replace with announcements retrieved from the database.
            'announcements' => [
                [
                    'title' => 'System Maintenance',
                    'message' => 'The attendance system will undergo scheduled maintenance on Sunday from 11:00 PM to 1:00 AM.',
                    'date' => '2026-07-04',
                    'icon' => 'fa-solid fa-screwdriver-wrench',
                ],
                [
                    'title' => 'Monthly Staff Meeting',
                    'message' => 'All attendants are required to attend the monthly operations meeting on Monday at 9:00 AM.',
                    'date' => '2026-07-07',
                    'icon' => 'fa-solid fa-users',
                ],
                [
                    'title' => 'Safety Reminder',
                    'message' => 'Ensure all pump readings are verified before submitting your clock-out report.',
                    'date' => '2026-07-08',
                    'icon' => 'fa-solid fa-shield-halved',
                ],
            ],
            'summaryCards' => [
                ['label' => 'Current Shift', 'value' => 'Morning Shift'],
                ['label' => 'Assigned Pump', 'value' => 'Pump 03 - PMS Lane'],
                ['label' => 'Attendance Status', 'value' => 'Clocked In'],
                ['label' => 'Pending Action', 'value' => 'Clock Out & Sales Entry'],
            ],
            'tableColumns' => ['Activity', 'Time', 'Status'],
            'tableRows' => [
                ['Clock In completed', '06:03 AM', 'Successful'],
                ['Pump assignment confirmed', '06:05 AM', 'Active'],
                ['Shift sales entry pending', '01:54 PM', 'Awaiting Clock Out'],
            ],
        ]);
    }

    /**
     * Load the clock-in frontend prototype with static staff data.
     */
    public function clockIn(): void
    {
        $this->render('attendant/clock-in.php', [
            'assetBaseUrl' => $this->assetBaseUrl,
            'currentRoute' => 'attendance/clock-in',
            'employee' => $this->attendance->getEmployee(),
            'attendanceStatus' => $this->attendance->getAttendanceStatus(),
            'attendanceHistory' => $this->attendance->getAttendanceHistory(),
        ]);
    }

    /**
     * Load the clock-out and fuel sales frontend prototype with static data.
     */
    public function clockOut(): void
    {
        $this->render('attendant/clock-out.php', [
            'assetBaseUrl' => $this->assetBaseUrl,
            'currentRoute' => 'attendance/clock-out',
            'employee' => $this->attendance->getEmployee(),
            'attendanceStatus' => $this->attendance->getAttendanceStatus(),
            'clockOutOptions' => $this->attendance->getClockOutOptions(),
            'fuelSalesSummary' => $this->attendance->getFuelSalesSummary(),
            'previousShiftHistory' => $this->attendance->getPreviousShiftHistory(),
        ]);
    }

    /**
     * Load attendance history as a routed page.
     */
    public function attendanceHistoryPage(): void
    {
        $this->render('attendant/attendance-history.php', [
            'assetBaseUrl' => $this->assetBaseUrl,
            'currentRoute' => 'attendance/history',
        ]);
    }
    /**
     * Load the duty roster page with static sample data.
     */
    public function dutyRoster(): void
    {
        $this->render('attendant/duty-roster.php', [
            'assetBaseUrl' => $this->assetBaseUrl,
            'currentRoute' => 'duty-roster',
        ]);
    }
    /**
     * Load fuel sales history with static sample records.
     */
    public function fuelSalesHistory(): void
    {
        $this->render('attendant/fuel-sales-history.php', [
            'assetBaseUrl' => $this->assetBaseUrl,
            'currentRoute' => 'fuel-sales/history',
        ]);
    }
    /**
     * Load leave management with static sample records.
     */
    public function leaveRequests(): void
    {
        $this->render('attendant/leave.php', [
            'assetBaseUrl' => $this->assetBaseUrl,
            'currentRoute' => 'leave-requests',
        ]);
    }
    /**
     * Load the staff profile page with static employee data.
     */
    public function profile(): void
    {
        $this->render('attendant/profile.php', [
            'assetBaseUrl' => $this->assetBaseUrl,
            'currentRoute' => 'profile',
        ]);
    }

    /**
     * Load the frontend-only edit profile page with static employee data.
     */
    public function editProfile(): void
    {
        $this->render('attendant/edit_profile.php', [
            'assetBaseUrl' => $this->assetBaseUrl,
            'currentRoute' => 'profile',
        ]);
    }
    /**
     * Load settings with static preferences.
     */
    public function settings(): void
    {
        $this->renderStaticPage('settings', [
            'pageTitle' => 'Settings | FuelOps Staff Dashboard',
            'pageHeading' => 'Settings',
            'pageIntro' => 'Review sample account and notification preferences.',
            'pageIcon' => 'fa-solid fa-gear',
            'summaryCards' => [
                ['label' => 'Account Status', 'value' => 'Active'],
                ['label' => 'Notifications', 'value' => 'Enabled'],
                ['label' => 'Language', 'value' => 'English'],
                ['label' => 'Theme', 'value' => 'FuelOps Default'],
            ],
            'tableColumns' => ['Setting', 'Current Value', 'Status'],
            'tableRows' => [
                ['SMS Alerts', 'Enabled', 'Active'],
                ['Email Alerts', 'Enabled', 'Active'],
                ['Password Review', 'Required every 90 days', 'Configured'],
            ],
        ]);
    }

    /**
     * Return static attendance records for display.
     */
    public function attendanceHistory(): array
    {
        return $this->attendance->getAttendanceHistory();
    }

    /**
     * Render one of the simple routed dashboard pages.
     */
    private function renderStaticPage(string $route, array $data): void
    {
        $this->render('attendant/dashboard-page.php', array_merge([
            'assetBaseUrl' => $this->assetBaseUrl,
            'currentRoute' => $route,
            'employee' => $this->attendance->getEmployee(),
        ], $data));
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

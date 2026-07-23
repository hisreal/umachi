<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Attendance;
use App\Models\LeaveManagement;
use App\Models\Profile;
use App\Services\AuthService;
use App\Services\DashboardLabelService;
use App\Services\AttendanceDutyPolicyService;
use App\Services\AttendantHistoryService;
use App\Services\AttendantDutyService;
use App\Services\DashboardService;

class AttendanceController extends Controller
{
    private Attendance $attendance;

    public function __construct()
    {
        parent::__construct();

        $this->attendance = new Attendance();
    }

    public function index(): void
    {
        $this->dashboard();
    }

    public function notFound(): void
    {
        $this->dashboard();
    }

    public function dashboard(): void
    {
        $dashboardLabel = DashboardLabelService::forCurrentUser();
        $auth = new AuthService();
        $dutyPolicy = new AttendanceDutyPolicyService();
        $isPumpAttendant = array_filter($auth->roles(), [$dutyPolicy, 'requiresManualDuty']) !== [];
        $quickActions = [
            ['title' => 'Clock In', 'description' => 'Start your work shift and record today\'s attendance.', 'route' => 'attendance/clock-in', 'icon' => 'fa-solid fa-fingerprint'],
            ['title' => 'Clock Out', 'description' => $isPumpAttendant ? 'End your work shift and submit today\'s fuel sales.' : 'End your work shift and record today\'s attendance.', 'route' => 'attendance/clock-out', 'icon' => 'fa-solid fa-arrow-right-from-bracket'],
            ['title' => 'Attendance History', 'description' => 'Review your attendance records and status.', 'route' => 'attendance/history', 'icon' => 'fa-solid fa-clock-rotate-left'],
        ];
        if ($isPumpAttendant) {
            $quickActions[] = ['title' => 'Fuel Sales History', 'description' => 'Review your submitted and verified fuel sales.', 'route' => 'fuel-sales/history', 'icon' => 'fa-solid fa-receipt'];
            $quickActions[] = ['title' => 'Duty Roster', 'description' => 'View your assigned shifts and pump schedule.', 'route' => 'duty-roster', 'icon' => 'fa-solid fa-calendar-days'];
        }
        $quickActions[] = ['title' => 'Apply Leave', 'description' => 'Submit and monitor your leave requests.', 'route' => 'leave-requests', 'icon' => 'fa-solid fa-calendar-plus'];
        $quickActions[] = ['title' => 'Announcements', 'description' => 'Read the latest station notices and updates.', 'route' => 'announcements', 'icon' => 'fa-solid fa-bullhorn'];

        $this->renderStaticPage('dashboard', array_merge((new DashboardService())->attendant(), [
            'pageTitle' => $dashboardLabel . ' | FuelOps Staff Dashboard',
            'pageHeading' => $dashboardLabel,
            'pageIntro' => $isPumpAttendant ? 'Review today\'s shift status, assigned pump, and recent station activity.' : 'Record attendance and review recent station announcements.',
            'pageIcon' => 'fa-solid fa-gauge-high',
            'extraStyles' => ['css/clock-in.css', 'css/dashboard.css', 'css/admin-dashboard.css'],
            'quickActions' => $quickActions,
        ]));
    }

    public function submitClockIn(): void
    {
        $this->handleAttendanceMutation(static function (Attendance $attendance): array {
            $attendance->clockIn($_FILES);
            return ['_redirect' => route_url('dashboard')];
        }, route_url('attendance/clock-in'), 'Clock-In successful. Your attendance is pending verification.');
    }

    public function submitClockOut(): void
    {
        $this->handleAttendanceMutation(static function (Attendance $attendance, Request $request): array {
            $attendance->clockOut($request->all(), $_FILES);
            return ['_redirect' => route_url('dashboard')];
        }, route_url('attendance/clock-out'), 'Clock-Out successful. Your attendance is pending verification.');
    }

    public function clockIn(): void
    {
        $auth = new AuthService();
        $dutyPolicy = new AttendanceDutyPolicyService();
        $isPumpAttendant = array_filter($auth->roles(), [$dutyPolicy, 'requiresManualDuty']) !== [];
        $this->render('attendant/clock-in.php', [
            'currentRoute' => 'attendance/clock-in',
            'employee' => $this->attendance->getEmployee(),
            'attendanceStatus' => $this->attendance->getAttendanceStatus(),
            'attendanceHistory' => $this->attendance->getAttendanceHistory(),
            'canSubmitFuelSales' => $isPumpAttendant,
            'fuelSalesSummary' => $isPumpAttendant ? $this->attendance->getFuelSalesSummary() : [],
            'attendanceSuccess' => Session::pullFlash('attendance_success'),
            'attendanceError' => Session::pullFlash('attendance_error'),
        ]);
    }

    public function clockOut(): void
    {
        $dutyPolicy = new AttendanceDutyPolicyService();
        $employee = $this->attendance->getEmployee();
        $role = (string) ($employee['role'] ?? '');
        $isPumpAttendant = $dutyPolicy->requiresManualDuty($role);
        $data = [
            'currentRoute' => 'attendance/clock-out',
            'employee' => $employee,
            'attendanceStatus' => $this->attendance->getAttendanceStatus(),
            'canSubmitFuelSales' => $isPumpAttendant,
            'requiresClockOutSelfie' => $dutyPolicy->requiresClockOutSelfie($role),
            'attendanceSuccess' => Session::pullFlash('attendance_success'),
            'attendanceError' => Session::pullFlash('attendance_error'),
        ];
        if ($isPumpAttendant) {
            $data['clockOutOptions'] = $this->attendance->getClockOutOptions();
            $data['fuelSalesSummary'] = $this->attendance->getFuelSalesSummary();
            $data['previousShiftHistory'] = $this->attendance->getPreviousShiftHistory();
        }
        $this->render('attendant/clock-out.php', $data);
    }

    public function attendanceHistoryPage(): void
    {
        try {
            $data = (new AttendantHistoryService())->attendance(Request::capture()->all());
        } catch (\Throwable $exception) {
            error_log('[Attendance History] ' . $exception->getMessage());
            $data = ['historyError' => 'Attendance records are temporarily unavailable. Please try again.'];
        }

        $this->render('attendant/attendance-history.php', array_merge(['currentRoute' => 'attendance/history'], $data));
    }
    public function dutyRoster(): void
    {
        try {
            $data = (new AttendantDutyService())->data(Request::capture()->all());
        } catch (\Throwable $exception) {
            error_log('[Duty Roster] ' . $exception->getMessage());
            $data = ['dutyError' => 'Duty assignments are temporarily unavailable. Please try again.'];
        }

        $this->render('attendant/duty-roster.php', array_merge(['currentRoute' => 'duty-roster'], $data));
    }
    public function fuelSalesHistory(): void
    {
        try {
            $data = (new AttendantHistoryService())->fuelSales(Request::capture()->all());
        } catch (\Throwable $exception) {
            error_log('[Fuel Sales History] ' . $exception->getMessage());
            $data = ['historyError' => 'Fuel sales records are temporarily unavailable. Please try again.'];
        }

        $this->render('attendant/fuel-sales-history.php', array_merge(['currentRoute' => 'fuel-sales/history'], $data));
    }

    public function announcements(): void
    {
        $role = (string) Session::get('auth.role', '');
        $this->render('attendant/announcements.php', [
            'currentRoute' => 'announcements',
            'employee' => $this->attendance->getEmployee(),
            'announcements' => (new \App\Models\Announcement())->dashboardAnnouncements($role, 50),
        ]);
    }

    public function leaveRequests(): void
    {
        $leave = new LeaveManagement();

        $this->render('attendant/leave.php', array_merge([
            'currentRoute' => 'leave-requests',
        ], $leave->employeeData(Request::capture()->all())));
    }

    public function submitLeaveRequest(): void
    {
        $this->handleLeaveMutation(static function (LeaveManagement $leave, Request $request): string {
            $leave->submitRequest($request->all(), $_FILES);
            Session::flash('leave_success', 'Leave request submitted successfully.');

            return route_url('leave-requests');
        }, route_url('leave-requests'));
    }

    public function cancelLeaveRequest(): void
    {
        $this->handleLeaveMutation(static function (LeaveManagement $leave, Request $request): string {
            $leave->cancelOwnRequest((int) $request->post('request_id', 0));
            Session::flash('leave_success', 'Leave request cancelled successfully.');
            return route_url('leave-requests');
        }, route_url('leave-requests'));
    }
    public function profile(): void
    {
        $profile = new Profile();

        $this->render('attendant/profile.php', [
            'currentRoute' => 'profile',
            'employee' => $profile->currentUserProfile(),
            'profileSummary' => $profile->profileSummary(),
            'profileSuccess' => Session::pullFlash('profile_success'),
            'profileError' => Session::pullFlash('profile_error'),
        ]);
    }

    public function editProfile(): void
    {
        $profile = new Profile();

        $this->render('attendant/edit_profile.php', [
            'currentRoute' => 'profile',
            'employee' => $profile->currentUserProfile(),
            'profileSuccess' => Session::pullFlash('profile_success'),
            'profileError' => Session::pullFlash('profile_error'),
        ]);
    }

    public function completeProfile(): void
    {
        $profile = new Profile();
        $this->render('attendant/edit_profile.php', [
            'currentRoute' => 'profile/complete',
            'completionMode' => true,
            'employee' => $profile->currentUserProfile(),
            'profileSuccess' => Session::pullFlash('profile_success'),
            'profileError' => Session::pullFlash('profile_error'),
        ]);
    }

    public function storeCompletedProfile(): void
    {
        $request = Request::capture();
        $auth = new AuthService();
        $this->mutationResponse($request, function () use ($request): array {
            (new Profile())->completeCurrentUser($request->all(), $_FILES);
            return [];
        }, 'Your profile has been completed successfully.', route_url($auth->mustChangePassword() ? $auth->passwordChangeRoute() : 'dashboard'), route_url('profile/complete'), 'profile_error');
    }
    public function updateProfile(): void
    {
        $request = Request::capture();
        $this->mutationResponse($request, function () use ($request): array {
            (new Profile())->updateCurrentUser($request->all(), $_FILES);
            return [];
        }, 'Profile updated successfully.', route_url('profile'), route_url('profile/edit'), 'profile_error');
    }
    public function changePassword(): void
    {
        $this->render('attendant/change-password.php', ['currentRoute' => 'profile/change-password']);
    }

    public function updatePassword(): void
    {
        $request = Request::capture();
        $response = new Response();
        $auth = new AuthService();

        if (!$auth->validateCsrf((string) $request->post('_csrf_token', ''))) {
            Session::flash('password_error', 'Your password form expired. Please try again.');
            $response->redirect(route_url('profile/change-password'));
        }

        $result = $auth->changePassword(
            (string) $request->post('current_password', ''),
            (string) $request->post('new_password', ''),
            (string) $request->post('confirm_password', '')
        );

        if (($result['success'] ?? false) === true) {
            Session::flash('password_success', (string) $result['message']);
            $response->redirect(route_url('dashboard'));
        }

        Session::flash('password_error', (string) ($result['message'] ?? 'Password could not be updated.'));
        $response->redirect(route_url('profile/change-password'));
    }

    public function settings(): void
    {
        $this->renderStaticPage('settings', [
            'pageTitle' => 'Settings | FuelOps Staff Dashboard',
            'pageHeading' => 'Settings',
            'pageIntro' => 'Review account and notification preferences.',
            'pageIcon' => 'fa-solid fa-gear',
        ]);
    }
    public function attendanceHistory(): array
    {
        return $this->attendance->getAttendanceHistory();
    }

    private function handleAttendanceMutation(callable $callback, string $fallbackUrl, string $successMessage): void
    {
        $request = Request::capture();
        $this->mutationResponse($request, fn (): array => $callback($this->attendance, $request), $successMessage, route_url('dashboard'), $fallbackUrl, 'attendance_error');
    }

    private function handleLeaveMutation(callable $callback, string $fallbackUrl): void
    {
        $request = Request::capture();
        $this->mutationResponse($request, static function () use ($callback, $request): array {
            return ['_redirect' => (string) $callback(new LeaveManagement(), $request)];
        }, 'Leave request updated successfully.', $fallbackUrl, $fallbackUrl, 'leave_error');
    }

    private function renderStaticPage(string $route, array $data): void
    {
        $this->render('attendant/dashboard-page.php', array_merge([
            'currentRoute' => $route,
            'employee' => $this->attendance->getEmployee(),
        ], $data));
    }
}

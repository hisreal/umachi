<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Attendance;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\FuelInventory;
use App\Models\FuelSale;
use App\Models\LeaveManagement;
use App\Models\Profile;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use App\Services\DutyManagementService;
use App\Services\DashboardService;
use App\Services\EmployeeManagementService;
use App\Services\PumpManagementService;
use App\Services\SettingsService;
use App\Services\ShiftManagementService;
use RuntimeException;

class AdminController extends Controller
{
    /**
     * Load the administrator dashboard landing page.
     */
    public function dashboard(): void
    {
        $this->render('admin/dashboard.php', array_merge((new DashboardService())->admin(), [
            'currentRoute' => 'admin/dashboard',
            'navItems' => $this->adminNavItems(),
        ]));
    }

    public function activityLog(): void
    {
        $request = Request::capture();
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'employee' => trim((string) $request->query('employee', '')),
            'role' => trim((string) $request->query('role', '')),
            'module' => trim((string) $request->query('module', '')),
            'action' => trim((string) $request->query('action', '')),
            'status' => trim((string) $request->query('status', '')),
            'sort' => trim((string) $request->query('sort', 'date')),
            'direction' => trim((string) $request->query('direction', 'desc')),
            'page' => max(1, (int) $request->query('page', 1)),
            'per_page' => min(100, max(10, (int) $request->query('per_page', 20))),
        ];

        try {
            $model = new ActivityLog();
            $activityPage = $model->page($filters);
            $activityStats = $model->stats();
            $activityOptions = $model->options();
        } catch (\Throwable $exception) {
            error_log('[Activity Log Page] ' . $exception->getMessage());
            $activityPage = ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 20, 'pages' => 1, 'from' => 0, 'to' => 0];
            $activityStats = [];
            $activityOptions = ['employees' => [], 'roles' => [], 'modules' => [], 'actions' => [], 'statuses' => []];
            Session::flash('activity_log_error', 'Activity records could not be loaded. Please try again.');
        }

        $this->render('admin/activity-log.php', [
            'currentRoute' => 'admin/activity-log',
            'navItems' => $this->adminNavItems(),
            'activityLogs' => $activityPage['items'],
            'activityPage' => $activityPage,
            'activityStats' => $activityStats,
            'activityOptions' => $activityOptions,
            'activityFilters' => $filters,
            'activityLogError' => Session::pullFlash('activity_log_error'),
        ]);
    }

    public function attendanceDetails(): void
    {
        $request = Request::capture();
        if (!$this->canViewAttendanceSelfies()) {
            (new Response())->error('You do not have permission to view attendance selfies.', [], 403);
        }

        $recordId = (int) $request->query('id', 0);
        try {
            $details = (new Attendance())->attendanceDetails($recordId);
            if ($details === null) {
                (new Response())->error('Attendance record not found.', [], 404);
            }

            $imageRoute = route_url('admin/attendance-history/selfie') . '&id=' . $recordId . '&type=';
            $details['clock_in_selfie_url'] = $details['clock_in_selfie_status'] === 'available'
                ? $imageRoute . 'clock-in'
                : null;
            $details['clock_out_selfie_url'] = $details['clock_out_selfie_status'] === 'available'
                ? $imageRoute . 'clock-out'
                : null;

            (new ActivityLogService())->record(
                'Attendance Selfie Viewed',
                'Attendance',
                'Attendance selfies viewed for ' . $details['employee_name'] . ' on ' . $details['attendance_date'] . '.',
                [
                    'employee_id' => (int) $details['employee_db_id'],
                    'entity_type' => 'attendance',
                    'entity_id' => $recordId,
                ],
                'success',
                null,
                ['attendance_record_id' => $recordId, 'attendance_date' => $details['attendance_date']],
                $request
            );

            unset($details['employee_db_id']);
            (new Response())->success('Attendance details loaded.', ['record' => $details]);
        } catch (\Throwable $exception) {
            error_log('[Attendance Selfie Details] ' . $exception->getMessage());
            (new Response())->error('Attendance details could not be loaded.', [], 500);
        }
    }

    public function attendanceSelfie(): void
    {
        $request = Request::capture();
        if (!$this->canViewAttendanceSelfies()) {
            http_response_code(403);
            echo '403 Forbidden - You do not have permission to view attendance selfies.';
            return;
        }

        $recordId = (int) $request->query('id', 0);
        $type = trim((string) $request->query('type', ''));
        try {
            $file = (new Attendance())->attendanceSelfieFile($recordId, $type);
            if ($file === null) {
                http_response_code(404);
                header('Content-Type: text/plain; charset=utf-8');
                echo 'Image not available.';
                return;
            }

            header('Content-Type: ' . $file['mime']);
            header('Content-Length: ' . (string) filesize($file['path']));
            header('Content-Disposition: inline');
            header('Cache-Control: private, no-store, max-age=0');
            header('X-Content-Type-Options: nosniff');
            readfile($file['path']);
        } catch (\Throwable $exception) {
            error_log('[Attendance Selfie Image] ' . $exception->getMessage());
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Image not available.';
        }
    }

    public function saveAttendanceSettings(): void
    {
        $request = Request::capture();
        $url = route_url('admin/attendance-settings');
        $this->mutationResponse($request, static function () use ($request): array {
            (new Attendance())->saveAdminSettings($request->all());
            return ['_redirect' => route_url('admin/attendance-settings')];
        }, 'Attendance settings saved successfully.', $url, $url, 'attendance_error');
    }

    public function adjustAttendance(): void
    {
        $request = Request::capture();
        $url = route_url('admin/attendance-history');
        $this->mutationResponse($request, static function () use ($request): array {
            (new Attendance())->adjustAttendance($request->all());
            return ['attendance_id' => (int) $request->post('attendance_id', 0), '_redirect' => route_url('admin/attendance-history')];
        }, 'Attendance adjustment saved successfully.', $url, $url, 'attendance_error');
    }

    private function canViewAttendanceSelfies(): bool
    {
        return in_array(
            strtolower(trim((string) Session::get('auth.role', ''))),
            ['admin', 'administrator', 'manager', 'supervisor'],
            true
        );
    }

    /**
     * Load an admin page or a frontend-only placeholder for future modules.
     */
    public function placeholderPage(string $route): void
    {
        $adminViews = [
            'admin/employees' => 'admin/employee-list.php',
            'admin/add-employee' => 'admin/add-employee.php',
            'admin/departments' => 'admin/departments.php',
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



    public function updateProfile(): void
    {
        $request = Request::capture();
        $response = new Response();
        $auth = new AuthService();

        if (!$auth->validateCsrf((string) $request->post('_csrf_token', ''))) {
            Session::flash('profile_error', 'Your profile form expired. Please try again.');
            $response->redirect(route_url('admin/edit-profile'));
        }

        $data = $request->all();
        $data['emergency_contact'] = (string) $request->post('emergency_phone', '');

        try {
            (new Profile())->updateCurrentUser($data, $_FILES, true);
            Session::flash('profile_success', 'Profile updated successfully.');
            $response->redirect(route_url('admin/profile'));
        } catch (\RuntimeException $exception) {
            Session::flash('profile_error', $exception->getMessage());
            $response->redirect(route_url('admin/edit-profile'));
        } catch (\Throwable $exception) {
            error_log('[Admin Profile] ' . $exception->getMessage());
            Session::flash('profile_error', 'Profile could not be updated. Please try again.');
            $response->redirect(route_url('admin/edit-profile'));
        }
    }
    public function updatePassword(): void
    {
        $request = Request::capture();
        $response = new Response();
        $auth = new AuthService();

        if (!$auth->validateCsrf((string) $request->post('_csrf_token', ''))) {
            Session::flash('password_error', 'Your password form expired. Please try again.');
            $response->redirect(route_url('admin/change-password'));
        }

        $result = $auth->changePassword(
            (string) $request->post('current_password', ''),
            (string) $request->post('new_password', ''),
            (string) $request->post('confirm_password', '')
        );

        if (($result['success'] ?? false) === true) {
            Session::flash('password_success', (string) $result['message']);
            $response->redirect(route_url('admin/dashboard'));
        }

        Session::flash('password_error', (string) ($result['message'] ?? 'Unable to update password.'));
        $response->redirect(route_url('admin/change-password'));
    }

    public function storeEmployee(): void
    {
        $this->handleEmployeeMutation(static function (EmployeeManagementService $service, Request $request): array {
            $result = $service->store($request->all(), $_FILES, self::requestContext($request));
            $result['_message'] = !empty($result['mail_sent'])
                ? 'Employee created successfully and welcome email sent successfully.'
                : 'Employee created successfully, but the welcome email could not be sent.';
            $result['_redirect'] = route_url('admin/employees');
            $result['_notification'] = !empty($result['mail_sent']) ? 'success' : 'warning';
            return $result;
        }, route_url('admin/add-employee'), 'Employee created successfully.');
    }

    public function updateEmployee(): void
    {
        $employeeCode = (string) Request::capture()->input('employee', '');
        $this->handleEmployeeMutation(static function (EmployeeManagementService $service, Request $request) use ($employeeCode): array {
            $service->update($employeeCode, $request->all(), $_FILES);
            $updatedCode = (string) $request->post('employee_id', $employeeCode);
            return ['employee' => $updatedCode, '_redirect' => route_url('admin/employee-profile') . '&employee=' . urlencode($updatedCode)];
        }, route_url('admin/edit-employee') . '&employee=' . urlencode($employeeCode), 'Employee updated successfully.');
    }

    public function deleteEmployee(): void
    {
        $employeeCode = (string) Request::capture()->post('employee', '');
        $this->handleEmployeeMutation(static function (EmployeeManagementService $service) use ($employeeCode): array {
            $service->model()->deleteByCode($employeeCode);
            return ['employee' => $employeeCode, '_redirect' => route_url('admin/employees')];
        }, route_url('admin/employees'), 'Employee deleted successfully.');
    }

    public function toggleEmployeeAccount(): void
    {
        $employeeCode = (string) Request::capture()->post('employee', '');
        $this->handleEmployeeMutation(static function (EmployeeManagementService $service) use ($employeeCode): array {
            $service->model()->toggleAccount($employeeCode);
            return ['employee' => $employeeCode, '_redirect' => route_url('admin/employees')];
        }, route_url('admin/employees'), 'Employee account status updated successfully.');
    }

    public function resetEmployeePassword(): void
    {
        $employeeCode = (string) Request::capture()->post('employee', '');
        $this->handleEmployeeMutation(static function (EmployeeManagementService $service, Request $request) use ($employeeCode): array {
            $result = $service->resetPassword($employeeCode, self::requestContext($request));
            $result['_message'] = !empty($result['mail_sent'])
                ? "Password reset successfully. A temporary password has been sent to the employee's personal email."
                : 'Password reset successfully, but the email could not be sent.';
            $result['_notification'] = !empty($result['mail_sent']) ? 'success' : 'warning';
            $result['_redirect'] = route_url('admin/employees');
            return $result;
        }, route_url('admin/employees'), 'Password reset successfully.');
    }

    public function uploadEmployeeDocument(): void
    {
        $employeeCode = (string) Request::capture()->post('employee', '');
        $this->handleEmployeeMutation(static function (EmployeeManagementService $service, Request $request) use ($employeeCode): array {
            $service->uploadDocument($employeeCode, $request->all(), $_FILES);
            return ['employee' => $employeeCode, '_redirect' => route_url('admin/employee-documents') . '&employee=' . urlencode($employeeCode)];
        }, route_url('admin/employee-documents') . '&employee=' . urlencode($employeeCode), 'Employee document uploaded successfully.');
    }

    public function deleteEmployeeDocument(): void
    {
        $employeeCode = (string) Request::capture()->post('employee', '');
        $documentId = (int) Request::capture()->post('document_id', 0);
        $this->handleEmployeeMutation(static function (EmployeeManagementService $service) use ($employeeCode, $documentId): array {
            $service->model()->deleteDocument($documentId);
            return ['employee' => $employeeCode, '_redirect' => route_url('admin/employee-documents') . '&employee=' . urlencode($employeeCode)];
        }, route_url('admin/employee-documents') . '&employee=' . urlencode($employeeCode), 'Employee document deleted successfully.');
    }
    public function saveDepartment(): void
    {
        $this->handleEmployeeMutation(static function (EmployeeManagementService $service, Request $request): string {
            $service->model()->saveDepartment($request->all());
            Session::flash('employee_success', 'Department saved successfully.');

            return route_url('admin/departments');
        }, route_url('admin/departments'));
    }

    public function deactivateDepartment(): void
    {
        $departmentId = (int) Request::capture()->post('department_id', 0);
        $this->handleEmployeeMutation(static function (EmployeeManagementService $service) use ($departmentId): string {
            $service->model()->deactivateDepartment($departmentId);
            Session::flash('employee_success', 'Department deactivated successfully.');

            return route_url('admin/departments');
        }, route_url('admin/departments'));
    }
    private function handleEmployeeMutation(callable $callback, string $fallbackUrl, string $successMessage = 'Operation completed successfully.'): void
    {
        $request = Request::capture();
        $this->mutationResponse($request, static function () use ($callback, $request): array {
            $result = $callback(new EmployeeManagementService(), $request);
            return is_array($result) ? $result : ['_redirect' => (string) $result];
        }, $successMessage, $fallbackUrl, $fallbackUrl, 'employee_error');
    }


    public function pumps(): void
    {
        $this->render('admin/pumps.php', [
            'currentRoute' => 'admin/pumps',
            'navItems' => $this->adminNavItems(),
        ]);
    }
    public function pumpMaintenance(): void
    {
        $this->render('admin/maintenance.php', [
            'currentRoute' => 'admin/maintenance',
            'navItems' => $this->adminNavItems(),
        ]);
    }


    public function addPump(): void
    {
        $service = new PumpManagementService();
        if (!$service->canManage()) {
            (new Response())->setStatusCode(403);
            echo '403 Forbidden - You do not have permission to manage pumps.';
            return;
        }

        $this->render('admin/add-pump.php', [
            'currentRoute' => 'admin/add-pump',
            'navItems' => $this->adminNavItems(),
        ]);
    }

    public function editPump(): void
    {
        $service = new PumpManagementService();
        if (!$service->canManage()) {
            (new Response())->setStatusCode(403);
            echo '403 Forbidden - You do not have permission to manage pumps.';
            return;
        }

        $this->render('admin/edit-pump.php', [
            'currentRoute' => 'admin/edit-pump',
            'navItems' => $this->adminNavItems(),
        ]);
    }

    public function storePump(): void
    {
        $this->handlePumpMutation(static function (PumpManagementService $service, Request $request): string {
            $service->store($request->all(), self::requestContext($request));
            return route_url('admin/pumps');
        }, route_url('admin/add-pump'), 'Pump created successfully.');
    }

    public function updatePump(): void
    {
        $pumpId = (int) Request::capture()->post('pump_id', 0);
        $this->handlePumpMutation(static function (PumpManagementService $service, Request $request) use ($pumpId): string {
            $service->update($pumpId, $request->all(), self::requestContext($request));
            return route_url('admin/pumps');
        }, route_url('admin/edit-pump') . '&pump=' . $pumpId, 'Pump updated successfully.');
    }

    public function deletePump(): void
    {
        $this->handlePumpMutation(static function (PumpManagementService $service, Request $request): string {
            $service->delete((int) $request->post('pump_id', 0), self::requestContext($request));
            return route_url('admin/pumps');
        }, route_url('admin/pumps'), 'Pump deleted successfully.');
    }

    public function togglePump(): void
    {
        $this->handlePumpMutation(static function (PumpManagementService $service, Request $request): string {
            $service->toggle((int) $request->post('pump_id', 0), self::requestContext($request));
            return route_url('admin/pumps');
        }, route_url('admin/pumps'), 'Pump status updated successfully.');
    }

    public function exportPumps(): void
    {
        $request = Request::capture();
        $type = strtolower((string) $request->query('type', 'csv'));
        $rows = (new PumpManagementService())->model()->exportRows($request->all());
        $filename = 'pump-records-' . date('Ymd-His');

        if ($type === 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
            echo $this->buildPumpPdf($rows);
            return;
        }

        header('Content-Type: ' . ($type === 'excel' ? 'application/vnd.ms-excel' : 'text/csv') . '; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . ($type === 'excel' ? '.xls' : '.csv') . '"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Pump Number', 'Pump Name', 'Fuel Type', 'Status', 'Meter Reading', 'Manufacturer', 'Model', 'Serial Number', 'Installation Date']);
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['pump_number'],
                $row['pump_name'],
                $row['fuel_type'],
                $row['status'],
                number_format((float) $row['meter'], 2, '.', ''),
                $row['manufacturer'],
                $row['model'],
                $row['serial_number'],
                $row['installation_date'],
            ]);
        }
    }
    public function shiftManagement(): void
    {
        $this->render('admin/shift-management.php', [
            'currentRoute' => 'admin/shift-management',
            'navItems' => $this->adminNavItems(),
        ]);
    }

    public function addShift(): void
    {
        $service = new ShiftManagementService();
        if (!$service->canManage()) {
            (new Response())->setStatusCode(403);
            echo '403 Forbidden - You do not have permission to manage shifts.';
            return;
        }

        $this->render('admin/add-shift.php', [
            'currentRoute' => 'admin/add-shift',
            'navItems' => $this->adminNavItems(),
        ]);
    }

    public function editShift(): void
    {
        $service = new ShiftManagementService();
        if (!$service->canManage()) {
            (new Response())->setStatusCode(403);
            echo '403 Forbidden - You do not have permission to manage shifts.';
            return;
        }

        $this->render('admin/edit-shift.php', [
            'currentRoute' => 'admin/edit-shift',
            'navItems' => $this->adminNavItems(),
        ]);
    }

    public function storeShift(): void
    {
        $this->handleShiftMutation(static function (ShiftManagementService $service, Request $request): string {
            $service->store($request->all(), self::requestContext($request));
            Session::flash('shift_success', 'Shift created successfully.');

            return route_url('admin/shift-management');
        }, route_url('admin/add-shift'));
    }

    public function updateShift(): void
    {
        $shiftId = (int) Request::capture()->post('shift_id', 0);
        $this->handleShiftMutation(static function (ShiftManagementService $service, Request $request) use ($shiftId): string {
            $service->update($shiftId, $request->all(), self::requestContext($request));
            Session::flash('shift_success', 'Shift updated successfully.');

            return route_url('admin/shift-management');
        }, route_url('admin/edit-shift') . '&shift=' . $shiftId);
    }

    public function deleteShift(): void
    {
        $this->handleShiftMutation(static function (ShiftManagementService $service, Request $request): string {
            $service->delete((int) $request->post('shift_id', 0), self::requestContext($request));
            Session::flash('shift_success', 'Shift deleted successfully.');

            return route_url('admin/shift-management');
        }, route_url('admin/shift-management'));
    }

    public function toggleShift(): void
    {
        $this->handleShiftMutation(static function (ShiftManagementService $service, Request $request): string {
            $service->toggle((int) $request->post('shift_id', 0), self::requestContext($request));
            Session::flash('shift_success', 'Shift status updated successfully.');

            return route_url('admin/shift-management');
        }, route_url('admin/shift-management'));
    }

    private function handleShiftMutation(callable $callback, string $fallbackUrl): void
    {
        $request = Request::capture();
        $this->mutationResponse($request, static function () use ($callback, $request): array {
            return ['_redirect' => (string) $callback(new ShiftManagementService(), $request)];
        }, 'Shift operation completed successfully.', route_url('admin/shift-management'), $fallbackUrl, 'shift_error');
    }

    public function saveDutyRoster(): void
    {
        $this->handleDutyMutation(static function (DutyManagementService $service, Request $request): string {
            $service->saveRoster($request->all(), self::requestContext($request));
            Session::flash('duty_success', 'Duty roster saved successfully.');

            return route_url('admin/duty-roster');
        }, route_url('admin/duty-roster'));
    }

    public function publishDutyRoster(): void
    {
        $this->handleDutyMutation(static function (DutyManagementService $service, Request $request): string {
            $service->changeRosterStatus((int) $request->post('roster_id', 0), 'Published', self::requestContext($request));
            Session::flash('duty_success', 'Duty roster published successfully.');

            return route_url('admin/duty-roster');
        }, route_url('admin/duty-roster'));
    }

    public function archiveDutyRoster(): void
    {
        $this->handleDutyMutation(static function (DutyManagementService $service, Request $request): string {
            $service->changeRosterStatus((int) $request->post('roster_id', 0), 'Archived', self::requestContext($request));
            Session::flash('duty_success', 'Duty roster archived successfully.');

            return route_url('admin/duty-roster');
        }, route_url('admin/duty-roster'));
    }

    public function deleteDutyRoster(): void
    {
        $this->handleDutyMutation(static function (DutyManagementService $service, Request $request): string {
            $service->deleteRoster((int) $request->post('roster_id', 0), self::requestContext($request));
            Session::flash('duty_success', 'Duty roster deleted successfully.');

            return route_url('admin/duty-roster');
        }, route_url('admin/duty-roster'));
    }

    public function saveDutyAssignment(): void
    {
        $this->handleDutyMutation(static function (DutyManagementService $service, Request $request): string {
            $service->saveAssignment($request->all(), self::requestContext($request));
            Session::flash('duty_success', 'Duty assignment saved successfully.');

            return route_url('admin/pump-allocation');
        }, route_url('admin/pump-allocation'));
    }

    public function cancelDutyAssignment(): void
    {
        $this->handleDutyMutation(static function (DutyManagementService $service, Request $request): string {
            $service->cancelAssignment((int) $request->post('assignment_id', 0), self::requestContext($request));
            Session::flash('duty_success', 'Duty assignment cancelled successfully.');

            return route_url('admin/pump-allocation');
        }, route_url('admin/pump-allocation'));
    }

    public function deleteDutyAssignment(): void
    {
        $this->handleDutyMutation(static function (DutyManagementService $service, Request $request): string {
            $service->deleteAssignment((int) $request->post('assignment_id', 0), self::requestContext($request));
            Session::flash('duty_success', 'Duty assignment deleted successfully.');

            return route_url('admin/pump-allocation');
        }, route_url('admin/pump-allocation'));
    }

    private function handleDutyMutation(callable $callback, string $fallbackUrl): void
    {
        $request = Request::capture();
        $this->mutationResponse($request, static function () use ($callback, $request): array {
            return ['_redirect' => (string) $callback(new DutyManagementService(), $request)];
        }, 'Duty roster updated successfully.', $fallbackUrl, $fallbackUrl, 'duty_error');
    }
    public function saveFuelDelivery(): void
    {
        $this->handleFuelInventoryMutation(static function (FuelInventory $inventory, Request $request): string {
            $inventory->saveDelivery($request->all());
            Session::flash('inventory_success', 'Fuel delivery saved and inventory updated successfully.');

            return route_url('admin/fuel-inventory');
        }, route_url('admin/fuel-inventory'));
    }

    public function adjustFuelStock(): void
    {
        $this->handleFuelInventoryMutation(static function (FuelInventory $inventory, Request $request): string {
            $inventory->adjustStock($request->all());
            return route_url('admin/fuel-inventory');
        }, route_url('admin/fuel-inventory'));
    }

    public function deleteFuelDelivery(): void
    {
        $this->handleFuelInventoryMutation(static function (FuelInventory $inventory, Request $request): string {
            $inventory->deleteDelivery((int)$request->post('delivery_id', 0));
            return route_url('admin/fuel-inventory');
        }, route_url('admin/fuel-inventory'));
    }

    private function handleFuelInventoryMutation(callable $callback, string $fallbackUrl): void
    {
        $request = Request::capture();
        $this->mutationResponse($request, static function () use ($callback, $request): array {
            return ['_redirect' => (string)$callback(new FuelInventory(), $request)];
        }, 'Fuel inventory updated successfully.', $fallbackUrl, $fallbackUrl, 'inventory_error');
    }
    public function verifyFuelSale(): void
    {
        $this->handleFuelSalesMutation(static function (FuelSale $fuelSale, Request $request): string {
            $saleCode = trim((string) $request->post('sale_code', ''));
            $action = trim((string) $request->post('action', ''));
            $notes = trim((string) $request->post('verification_notes', ''));

            $fuelSale->verify($saleCode, $action, $notes);
            Session::flash('fuel_success', 'Fuel sale verification saved successfully.');

            return route_url('admin/verify-sales') . '&transaction=' . urlencode($saleCode);
        }, route_url('admin/verify-sales'));
    }

    private function handleFuelSalesMutation(callable $callback, string $fallbackUrl): void
    {
        $request = Request::capture();
        $this->mutationResponse($request, static function () use ($callback, $request): array {
            return ['_redirect' => (string) $callback(new FuelSale(), $request)];
        }, 'Fuel sale verification updated successfully.', $fallbackUrl, $fallbackUrl, 'fuel_error');
    }
    public function storeAnnouncement(): void
    {
        $this->handleAnnouncementMutation(static function (Announcement $announcement, Request $request): string {
            $id = $announcement->store($request->all());
            Session::flash('announcement_success', 'Announcement saved successfully.');

            return route_url('admin/announcement-details') . '&id=' . $id;
        }, route_url('admin/add-announcement'));
    }

    public function updateAnnouncement(): void
    {
        $id = (int) Request::capture()->post('announcement_id', 0);
        $this->handleAnnouncementMutation(static function (Announcement $announcement, Request $request) use ($id): string {
            $announcement->updateAnnouncement($id, $request->all());
            Session::flash('announcement_success', 'Announcement updated successfully.');

            return route_url('admin/announcement-details') . '&id=' . $id;
        }, route_url('admin/edit-announcement') . '&id=' . $id);
    }

    public function processAnnouncement(): void
    {
        $this->handleAnnouncementMutation(static function (Announcement $announcement, Request $request): string {
            $announcement->changeStatus((int) $request->post('announcement_id', 0), (string) $request->post('action', ''));
            Session::flash('announcement_success', 'Announcement action completed successfully.');

            return route_url('admin/announcements');
        }, route_url('admin/announcements'));
    }

    private function handleAnnouncementMutation(callable $callback, string $fallbackUrl): void
    {
        $request = Request::capture();
        $this->mutationResponse($request, static function () use ($callback, $request): array {
            return ['_redirect' => (string)$callback(new Announcement(), $request)];
        }, 'Announcement updated successfully.', $fallbackUrl, $fallbackUrl, 'announcement_error');
    }
    public function processLeaveRequest(): void
    {
        $this->handleLeaveMutation(static function (LeaveManagement $leave, Request $request): string {
            $leave->processRequest(
                (int) $request->post('request_id', 0),
                (string) $request->post('action', ''),
                trim((string) $request->post('approval_notes', ''))
            );
            Session::flash('leave_success', 'Leave request updated successfully.');

            return route_url('admin/leave-requests');
        }, route_url('admin/leave-requests'));
    }

    public function saveLeaveType(): void
    {
        $this->handleLeaveMutation(static function (LeaveManagement $leave, Request $request): string {
            $leave->saveType($request->all());
            Session::flash('leave_success', 'Leave type saved successfully.');

            return route_url('admin/leave-types');
        }, route_url('admin/leave-types'));
    }

    public function toggleLeaveType(): void
    {
        $this->handleLeaveMutation(static function (LeaveManagement $leave, Request $request): string {
            $leave->toggleType((int) $request->post('leave_type_id', 0));
            Session::flash('leave_success', 'Leave type status updated successfully.');

            return route_url('admin/leave-types');
        }, route_url('admin/leave-types'));
    }

    public function saveLeaveSettings(): void
    {
        $this->handleLeaveMutation(static function (LeaveManagement $leave, Request $request): string {
            $leave->saveSettings($request->all());
            Session::flash('leave_success', 'Leave approval settings saved successfully.');

            return route_url('admin/leave-approval-settings');
        }, route_url('admin/leave-approval-settings'));
    }

    private function handleLeaveMutation(callable $callback, string $fallbackUrl): void
    {
        $request = Request::capture();
        $this->mutationResponse($request, static function () use ($callback, $request): array {
            return ['_redirect' => (string) $callback(new LeaveManagement(), $request)];
        }, 'Leave management updated successfully.', $fallbackUrl, $fallbackUrl, 'leave_error');
    }
    public function saveFuelPrices(): void
    {
        $this->handleSettingsMutation(static function (SettingsService $service, Request $request): string {
            $service->saveFuelPrices($request->all());
            Session::flash('settings_success', 'Fuel prices saved successfully.');

            return route_url('admin/fuel-pricing');
        }, route_url('admin/fuel-pricing'));
    }

    public function saveCompanyInformation(): void
    {
        $this->handleSettingsMutation(static function (SettingsService $service, Request $request): string {
            $service->saveCompanyInformation($request->all());
            Session::flash('settings_success', 'Company information saved successfully.');

            return route_url('admin/company-settings');
        }, route_url('admin/company-settings'));
    }

    public function saveSystemSettings(): void
    {
        $this->handleSettingsMutation(static function (SettingsService $service, Request $request): string {
            $service->saveSystemSettings($request->all());
            Session::flash('settings_success', 'System settings saved successfully.');

            return route_url('admin/company-settings');
        }, route_url('admin/company-settings'));
    }

    private function handleSettingsMutation(callable $callback, string $fallbackUrl): void
    {
        $request = Request::capture();
        $response = new Response();
        $auth = new AuthService();

        if (!$auth->validateCsrf((string) $request->post('_csrf_token', ''))) {
            Session::flash('settings_error', 'Your settings form expired. Please try again.');
            $response->redirect($fallbackUrl);
        }

        try {
            $response->redirect($callback(new SettingsService(), $request));
        } catch (RuntimeException $exception) {
            Session::flash('settings_error', $exception->getMessage());
            $response->redirect($fallbackUrl);
        } catch (\Throwable) {
            Session::flash('settings_error', 'Settings could not be saved. Please verify the database schema and try again.');
            $response->redirect($fallbackUrl);
        }
    }
    private function handlePumpMutation(callable $callback, string $fallbackUrl, string $successMessage): void
    {
        $request = Request::capture();

        $successUrl = route_url('admin/pumps');
        $this->mutationResponse($request, static fn (): mixed => $callback(new PumpManagementService(), $request), $successMessage, $successUrl, $fallbackUrl, 'pump_error');
    }

    private static function requestContext(Request $request): array
    {
        return [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
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








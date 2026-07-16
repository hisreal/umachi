<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Announcement;
use App\Models\FuelInventory;
use App\Models\FuelSale;
use App\Models\LeaveManagement;
use App\Models\Profile;
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
            (new Profile())->updateCurrentUser($data, $_FILES);
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
        $this->handleEmployeeMutation(static function (EmployeeManagementService $service, Request $request): string {
            $service->store($request->all(), $_FILES, self::requestContext($request));
            Session::flash('employee_success', 'Employee created successfully.');

            return route_url('admin/employees');
        }, route_url('admin/add-employee'));
    }

    public function updateEmployee(): void
    {
        $employeeCode = (string) Request::capture()->input('employee', '');
        $this->handleEmployeeMutation(static function (EmployeeManagementService $service, Request $request) use ($employeeCode): string {
            $service->update($employeeCode, $request->all(), $_FILES);
            Session::flash('employee_success', 'Employee updated successfully.');

            return route_url('admin/employee-profile') . '&employee=' . urlencode((string) $request->post('employee_id', $employeeCode));
        }, route_url('admin/edit-employee') . '&employee=' . urlencode($employeeCode));
    }

    public function deleteEmployee(): void
    {
        $employeeCode = (string) Request::capture()->post('employee', '');
        $this->handleEmployeeMutation(static function (EmployeeManagementService $service) use ($employeeCode): string {
            $service->model()->deleteByCode($employeeCode);
            Session::flash('employee_success', 'Employee deleted successfully.');

            return route_url('admin/employees');
        }, route_url('admin/employees'));
    }

    public function toggleEmployeeAccount(): void
    {
        $employeeCode = (string) Request::capture()->post('employee', '');
        $this->handleEmployeeMutation(static function (EmployeeManagementService $service) use ($employeeCode): string {
            $service->model()->toggleAccount($employeeCode);
            Session::flash('employee_success', 'Employee account status updated successfully.');

            return route_url('admin/employees');
        }, route_url('admin/employees'));
    }

    public function resetEmployeePassword(): void
    {
        $employeeCode = (string) Request::capture()->post('employee', '');
        $this->handleEmployeeMutation(static function (EmployeeManagementService $service) use ($employeeCode): string {
            $password = $service->model()->resetPassword($employeeCode);
            Session::flash('employee_success', 'Password reset successfully. Temporary password: ' . $password);

            return route_url('admin/employees');
        }, route_url('admin/employees'));
    }

    public function uploadEmployeeDocument(): void
    {
        $employeeCode = (string) Request::capture()->post('employee', '');
        $this->handleEmployeeMutation(static function (EmployeeManagementService $service, Request $request) use ($employeeCode): string {
            $service->uploadDocument($employeeCode, $request->all(), $_FILES);
            Session::flash('employee_success', 'Employee document uploaded successfully.');

            return route_url('admin/employee-documents') . '&employee=' . urlencode($employeeCode);
        }, route_url('admin/employee-documents') . '&employee=' . urlencode($employeeCode));
    }


    public function deleteEmployeeDocument(): void
    {
        $employeeCode = (string) Request::capture()->post('employee', '');
        $documentId = (int) Request::capture()->post('document_id', 0);
        $this->handleEmployeeMutation(static function (EmployeeManagementService $service) use ($employeeCode, $documentId): string {
            $service->model()->deleteDocument($documentId);
            Session::flash('employee_success', 'Employee document deleted successfully.');

            return route_url('admin/employee-documents') . '&employee=' . urlencode($employeeCode);
        }, route_url('admin/employee-documents') . '&employee=' . urlencode($employeeCode));
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
    private function handleEmployeeMutation(callable $callback, string $fallbackUrl): void
    {
        $request = Request::capture();
        $response = new Response();
        $auth = new AuthService();

        if (!$auth->validateCsrf((string) $request->post('_csrf_token', ''))) {
            Session::flash('employee_error', 'Your form expired. Please try again.');
            $response->redirect($fallbackUrl);
        }

        try {
            $redirect = $callback(new EmployeeManagementService(), $request);
            $response->redirect((string) $redirect);
        } catch (RuntimeException $exception) {
            Session::flash('employee_error', $exception->getMessage());
            $response->redirect($fallbackUrl);
        } catch (\Throwable) {
            Session::flash('employee_error', 'Employee operation failed. Please verify the database schema and try again.');
            $response->redirect($fallbackUrl);
        }
    }


    public function pumps(): void
    {
        $this->render('admin/pumps.php', [
            'currentRoute' => 'admin/pumps',
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
            Session::flash('pump_success', 'Pump created successfully.');

            return route_url('admin/pumps');
        }, route_url('admin/add-pump'));
    }

    public function updatePump(): void
    {
        $pumpId = (int) Request::capture()->post('pump_id', 0);
        $this->handlePumpMutation(static function (PumpManagementService $service, Request $request) use ($pumpId): string {
            $service->update($pumpId, $request->all(), self::requestContext($request));
            Session::flash('pump_success', 'Pump updated successfully.');

            return route_url('admin/pumps');
        }, route_url('admin/edit-pump') . '&pump=' . $pumpId);
    }

    public function deletePump(): void
    {
        $this->handlePumpMutation(static function (PumpManagementService $service, Request $request): string {
            $service->delete((int) $request->post('pump_id', 0), self::requestContext($request));
            Session::flash('pump_success', 'Pump deleted successfully.');

            return route_url('admin/pumps');
        }, route_url('admin/pumps'));
    }

    public function togglePump(): void
    {
        $this->handlePumpMutation(static function (PumpManagementService $service, Request $request): string {
            $service->toggle((int) $request->post('pump_id', 0), self::requestContext($request));
            Session::flash('pump_success', 'Pump status updated successfully.');

            return route_url('admin/pumps');
        }, route_url('admin/pumps'));
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
        $response = new Response();
        $auth = new AuthService();

        if (!$auth->validateCsrf((string) $request->post('_csrf_token', ''))) {
            Session::flash('shift_error', 'Your shift form expired. Please try again.');
            $response->redirect($fallbackUrl);
        }

        try {
            $response->redirect((string) $callback(new ShiftManagementService(), $request));
        } catch (RuntimeException $exception) {
            Session::flash('shift_error', $exception->getMessage());
            $response->redirect($fallbackUrl);
        } catch (\Throwable) {
            Session::flash('shift_error', 'Shift operation failed. Please verify the database schema and try again.');
            $response->redirect($fallbackUrl);
        }
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
        $response = new Response();
        $auth = new AuthService();

        if (!$auth->validateCsrf((string) $request->post('_csrf_token', ''))) {
            Session::flash('duty_error', 'Your duty management form expired. Please try again.');
            $response->redirect($fallbackUrl);
        }

        try {
            $response->redirect((string) $callback(new DutyManagementService(), $request));
        } catch (RuntimeException $exception) {
            Session::flash('duty_error', $exception->getMessage());
            $response->redirect($fallbackUrl);
        } catch (\Throwable) {
            Session::flash('duty_error', 'Duty management action failed. Please verify the database schema and try again.');
            $response->redirect($fallbackUrl);
        }
    }
    public function saveFuelDelivery(): void
    {
        $this->handleFuelInventoryMutation(static function (FuelInventory $inventory, Request $request): string {
            $inventory->saveDelivery($request->all());
            Session::flash('inventory_success', 'Fuel delivery saved and inventory updated successfully.');

            return route_url('admin/fuel-inventory');
        }, route_url('admin/fuel-inventory'));
    }

    private function handleFuelInventoryMutation(callable $callback, string $fallbackUrl): void
    {
        $request = Request::capture();
        $response = new Response();
        $auth = new AuthService();

        if (!$auth->validateCsrf((string) $request->post('_csrf_token', ''))) {
            Session::flash('inventory_error', 'Your fuel inventory form expired. Please try again.');
            $response->redirect($fallbackUrl);
        }

        try {
            $response->redirect((string) $callback(new FuelInventory(), $request));
        } catch (RuntimeException $exception) {
            Session::flash('inventory_error', $exception->getMessage());
            $response->redirect($fallbackUrl);
        } catch (\Throwable) {
            Session::flash('inventory_error', 'Fuel inventory action failed. Please verify the database schema and try again.');
            $response->redirect($fallbackUrl);
        }
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
        $response = new Response();
        $auth = new AuthService();

        if (!$auth->validateCsrf((string) $request->post('_csrf_token', ''))) {
            Session::flash('fuel_error', 'Your fuel sales form expired. Please try again.');
            $response->redirect($fallbackUrl);
        }

        try {
            $response->redirect((string) $callback(new FuelSale(), $request));
        } catch (RuntimeException $exception) {
            Session::flash('fuel_error', $exception->getMessage());
            $response->redirect($fallbackUrl);
        } catch (\Throwable) {
            Session::flash('fuel_error', 'Fuel sales action failed. Please verify the database schema and try again.');
            $response->redirect($fallbackUrl);
        }
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
        $response = new Response();
        $auth = new AuthService();

        if (!$auth->validateCsrf((string) $request->post('_csrf_token', ''))) {
            Session::flash('announcement_error', 'Your announcement form expired. Please try again.');
            $response->redirect($fallbackUrl);
        }

        try {
            $response->redirect((string) $callback(new Announcement(), $request));
        } catch (RuntimeException $exception) {
            Session::flash('announcement_error', $exception->getMessage());
            $response->redirect($fallbackUrl);
        } catch (\Throwable $exception) {
            error_log('[Announcement] ' . $exception->getMessage());
            Session::flash('announcement_error', 'Announcement action failed. Please verify the database schema and try again.');
            $response->redirect($fallbackUrl);
        }
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
        $response = new Response();
        $auth = new AuthService();

        if (!$auth->validateCsrf((string) $request->post('_csrf_token', ''))) {
            Session::flash('leave_error', 'Your leave management form expired. Please try again.');
            $response->redirect($fallbackUrl);
        }

        try {
            $response->redirect((string) $callback(new LeaveManagement(), $request));
        } catch (RuntimeException $exception) {
            Session::flash('leave_error', $exception->getMessage());
            $response->redirect($fallbackUrl);
        } catch (\Throwable $exception) {
            error_log('[LeaveManagement] ' . $exception->getMessage());
            Session::flash('leave_error', 'Leave management action failed. Please verify the database schema and try again.');
            $response->redirect($fallbackUrl);
        }
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
    private function handlePumpMutation(callable $callback, string $fallbackUrl): void
    {
        $request = Request::capture();
        $response = new Response();
        $auth = new AuthService();

        if (!$auth->validateCsrf((string) $request->post('_csrf_token', ''))) {
            Session::flash('pump_error', 'Your pump form expired. Please try again.');
            $response->redirect($fallbackUrl);
        }

        try {
            $response->redirect((string) $callback(new PumpManagementService(), $request));
        } catch (RuntimeException $exception) {
            Session::flash('pump_error', $exception->getMessage());
            $response->redirect($fallbackUrl);
        } catch (\Throwable) {
            Session::flash('pump_error', 'Something went wrong. Please try again later.');
            $response->redirect($fallbackUrl);
        }
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








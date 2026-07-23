<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Config;
use App\Core\Session;
use App\Core\Request;
use App\Models\Announcement;
use RuntimeException;
use Throwable;

final class DashboardService
{
    public function __construct(private ?Database $database = null)
    {
        $this->database ??= Database::getInstance();
    }

    public function admin(): array
    {
        $role = (string) Session::get('auth.role', 'Admin');
        $normalizedRole = strtolower(trim($role));
        $isAdministrator = in_array($normalizedRole, ['admin', 'administrator'], true);
        $sections = [
            'employees' => $isAdministrator || $normalizedRole === 'manager',
            'attendance' => $isAdministrator || in_array($normalizedRole, ['manager', 'supervisor', 'accountant'], true),
            'sales' => $isAdministrator || in_array($normalizedRole, ['manager', 'accountant'], true),
            'inventory' => $isAdministrator || $normalizedRole === 'manager',
            'pumps' => $isAdministrator || $normalizedRole === 'manager',
            'duty' => $isAdministrator || in_array($normalizedRole, ['manager', 'supervisor', 'accountant'], true),
            'leave' => $isAdministrator || in_array($normalizedRole, ['manager', 'supervisor', 'accountant'], true),
            'announcements' => true,
            'notifications' => $isAdministrator,
            'reports' => true,
        ];
        $this->logAccess(($role !== '' ? $role : 'Admin') . ' Dashboard');
        $today = date('Y-m-d');
        $employee = $sections['employees'] ? $this->row("SELECT COUNT(*) total, SUM(employment_status = 'active') active FROM employees WHERE deleted_at IS NULL") : [];
        $attendance = $sections['attendance'] ? $this->row("SELECT SUM(attendance_status = 'Present') present, SUM(attendance_status = 'Absent') absent, SUM(attendance_status = 'Late') late, SUM(attendance_status = 'On Leave') on_leave, SUM(overtime_minutes > 0) overtime FROM attendance WHERE attendance_date = :today", ['today' => $today]) : [];
        $sales = $sections['sales'] ? $this->row("SELECT COALESCE(SUM(amount_collected), 0) revenue, COALESCE(SUM(litres_sold), 0) litres, SUM(status = 'pending') pending FROM fuel_sales WHERE deleted_at IS NULL AND sale_date = :today", ['today' => $today]) : [];
        $leave = $sections['leave'] ? $this->row("SELECT SUM(status IN ('pending','forwarded')) pending, SUM(status = 'approved' AND DATE(final_approved_at) = :approved_today) approved_today, SUM(status = 'approved' AND :active_today BETWEEN start_date AND end_date) on_leave FROM leave_requests WHERE deleted_at IS NULL", ['approved_today' => $today, 'active_today' => $today]) : [];
        $pumps = $sections['pumps'] ? $this->row("SELECT COUNT(*) total, SUM(status = 'active') active, SUM(status = 'under_maintenance') maintenance FROM pumps WHERE deleted_at IS NULL") : [];
        $inventory = $sections['inventory'] ? $this->fuelInventory() : ['Petrol' => ['stock' => 0.0, 'low' => 0], 'Diesel' => ['stock' => 0.0, 'low' => 0], 'Gas' => ['stock' => 0.0, 'low' => 0]];
        $currentShift = $sections['duty'] ? (string) ($this->value("SELECT name FROM shifts WHERE deleted_at IS NULL AND status = 'active' AND ((start_time <= end_time AND CURTIME() BETWEEN start_time AND end_time) OR (start_time > end_time AND (CURTIME() >= start_time OR CURTIME() <= end_time))) ORDER BY start_time LIMIT 1") ?? 'No active shift') : '';
        $dutyCount = $sections['duty'] ? (int) $this->value("SELECT COUNT(*) FROM roster_assignments WHERE deleted_at IS NULL AND roster_date = :today AND status NOT IN ('cancelled','off_duty')", ['today' => $today]) : 0;

        $dashboard = [
            'employees' => (int) ($employee['total'] ?? 0),
            'active_employees' => (int) ($employee['active'] ?? 0),
            'present' => (int) ($attendance['present'] ?? 0),
            'absent' => (int) ($attendance['absent'] ?? 0),
            'leave' => (int) ($leave['on_leave'] ?? 0),
            'sales' => (float) ($sales['revenue'] ?? 0),
            'litres' => (float) ($sales['litres'] ?? 0),
            'petrol' => $inventory['Petrol']['stock'],
            'diesel' => $inventory['Diesel']['stock'],
            'gas' => $inventory['Gas']['stock'],
            'active_pumps' => (int) ($pumps['active'] ?? 0),
            'total_pumps' => (int) ($pumps['total'] ?? 0),
            'maintenance_pumps' => (int) ($pumps['maintenance'] ?? 0),
            'pending_leave' => (int) ($leave['pending'] ?? 0),
            'pending_sales' => (int) ($sales['pending'] ?? 0),
            'duty_assignments' => $dutyCount,
            'current_shift' => $currentShift,
            'low_stock' => array_sum(array_column($inventory, 'low')),
        ];

        return [
            'adminUser' => $this->sessionUser(),
            'dashboardSections' => $sections,
            'administratorQuickLinks' => $isAdministrator ? $this->administratorQuickLinks() : [],
            'dashboardQuickActionRoutes' => match ($normalizedRole) {
                'manager' => ['admin/duty-roster', 'admin/verify-sales', 'admin/leave-requests', 'admin/fuel-inventory', 'admin/fuel-sales-report'],
                'supervisor' => ['admin/duty-roster', 'admin/leave-requests'],
                'accountant' => ['admin/duty-roster', 'admin/verify-sales', 'admin/fuel-sales-report'],
                default => ['admin/add-employee', 'admin/duty-roster', 'admin/verify-sales', 'admin/leave-requests', 'admin/fuel-inventory', 'admin/fuel-sales-report'],
            },
            'dashboard' => $dashboard,
            'attendanceSummary' => array_map('intval', $attendance),
            'fuelSalesSummary' => $sections['sales'] ? $this->fuelSalesSummary($today) : [],
            'leaveSummary' => array_map('intval', $leave),
            'dutySummary' => $sections['duty'] ? $this->dutySummary($today) : [],
            'attendanceRecords' => $sections['attendance'] ? $this->recentAttendance($today) : [],
            'fuelSales' => $sections['sales'] ? $this->recentSales() : [],
            'leaveRequests' => $sections['leave'] ? $this->recentLeaveRequests() : [],
            'announcements' => (new Announcement())->dashboardAnnouncements($isAdministrator ? 'Admin' : $role, 5),
            'notifications' => $sections['notifications'] ? $this->notifications() : [],
            'chartData' => $this->adminCharts($sections),
        ];
    }
    private function administratorQuickLinks(): array
    {
        $configPath = defined('CONFIG_PATH') ? CONFIG_PATH : dirname(__DIR__, 2) . '/config';
        $links = (new Config($configPath))->get('app.administrator_quick_links', []);
        if (!is_array($links)) {
            return [];
        }

        return array_values(array_filter(array_map(static function (mixed $link): ?array {
            if (!is_array($link)) {
                return null;
            }
            $url = trim((string) ($link['url'] ?? ''));
            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
            if (filter_var($url, FILTER_VALIDATE_URL) === false || !in_array($scheme, ['http', 'https'], true)) {
                return null;
            }

            $variant = strtolower(trim((string) ($link['variant'] ?? 'default')));
            $variant = in_array($variant, ['gmail', 'webmail'], true) ? $variant : 'default';
            return [
                'label' => trim((string) ($link['label'] ?? '')),
                'url' => $url,
                'icon' => trim((string) ($link['icon'] ?? 'fa-solid fa-arrow-up-right-from-square')),
                'tooltip' => trim((string) ($link['tooltip'] ?? 'Open external service')),
                'variant' => $variant,
            ];
        }, $links)));
    }


    public function attendant(): array
    {
        $roles = Session::get('auth.roles', []);
        $roles = is_array($roles) ? array_map('strval', $roles) : [];
        $displayRole = (string) Session::get('auth.role', $roles[0] ?? 'Employee');
        $policy = new AttendanceDutyPolicyService();
        $effectiveRoles = $roles;
        $effectiveRoles[] = $displayRole;
        $isPumpAttendant = array_filter($effectiveRoles, [$policy, 'requiresManualDuty']) !== [];
        $isAutomaticDuty = array_filter($effectiveRoles, [$policy, 'isAutomaticDutyRole']) !== [];
        $this->logAccess($displayRole . ' Dashboard');
        $employeeId = (int) Session::get('auth.employee_id', 0);
        if ($employeeId <= 0) {
            throw new RuntimeException('Your account is not linked to an employee profile.');
        }

        $today = date('Y-m-d');
        $employee = $this->row("SELECT e.employee_code, e.first_name, e.last_name, e.photo_path, jt.name role FROM employees e LEFT JOIN job_titles jt ON jt.id = e.job_title_id WHERE e.id = :id AND e.deleted_at IS NULL", ['id' => $employeeId]);
        if ($employee === []) {
            throw new RuntimeException('Employee profile not found.');
        }

        $attendance = $this->row("SELECT attendance_status, clock_in, clock_out FROM attendance WHERE employee_id = :employee_id AND attendance_date = :today ORDER BY id DESC LIMIT 1", ['employee_id' => $employeeId, 'today' => $today]);
        $assignment = [];
        $sale = [];
        $leave = [];
        $monthAttendance = [];
        $saleTotals = [];
        if ($isPumpAttendant) {
            $assignment = $this->row("SELECT ra.roster_date, ra.status, p.pump_code, p.pump_name, ft.name fuel_type, s.name shift FROM roster_assignments ra INNER JOIN shifts s ON s.id = ra.shift_id LEFT JOIN pumps p ON p.id = ra.pump_id LEFT JOIN fuel_types ft ON ft.id = p.fuel_type_id WHERE ra.employee_id = :employee_id AND ra.roster_date = :today AND ra.deleted_at IS NULL AND ra.status <> 'cancelled' ORDER BY ra.id DESC LIMIT 1", ['employee_id' => $employeeId, 'today' => $today]);
            $sale = $this->row("SELECT opening_meter, closing_meter, litres_sold, amount_collected, status FROM fuel_sales WHERE employee_id = :employee_id AND sale_date = :today AND deleted_at IS NULL ORDER BY id DESC LIMIT 1", ['employee_id' => $employeeId, 'today' => $today]);
            $leave = $this->row("SELECT SUM(status IN ('pending','forwarded')) pending, SUM(status = 'approved') approved, SUM(status = 'approved' AND :today BETWEEN start_date AND end_date) active FROM leave_requests WHERE employee_id = :employee_id AND deleted_at IS NULL", ['employee_id' => $employeeId, 'today' => $today]);
            $monthAttendance = $this->row("SELECT SUM(attendance_status = 'Present') present, SUM(attendance_status = 'Late') late, SUM(attendance_status = 'On Leave') leave_days FROM attendance WHERE employee_id = :employee_id AND attendance_date BETWEEN DATE_FORMAT(CURDATE(), '%Y-%m-01') AND LAST_DAY(CURDATE())", ['employee_id' => $employeeId]);
            $saleTotals = $this->row("SELECT COALESCE(SUM(CASE WHEN sale_date = CURDATE() THEN amount_collected ELSE 0 END), 0) today, COALESCE(SUM(CASE WHEN sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) THEN amount_collected ELSE 0 END), 0) weekly, COALESCE(SUM(CASE WHEN sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN amount_collected ELSE 0 END), 0) monthly FROM fuel_sales WHERE employee_id = :employee_id AND deleted_at IS NULL AND status = 'verified'", ['employee_id' => $employeeId]);
        }

        return [
            'canViewFuel' => $isPumpAttendant,
            'showOperationalSummary' => $isPumpAttendant,
            'employee' => [
                'name' => trim((string) $employee['first_name'] . ' ' . (string) $employee['last_name']),
                'employee_id' => (string) $employee['employee_code'],
                'role' => $displayRole !== '' ? $displayRole : (string) ($employee['role'] ?? 'Employee'),
                'photo' => (string) (($employee['photo_path'] ?? '') ?: ProfilePhotoService::DEFAULT_PHOTO),
                'pump' => $isAutomaticDuty ? 'Not Required' : (string) (($assignment['pump_name'] ?? $assignment['pump_code'] ?? '') ?: 'No Duty Assigned Today'),
                'fuel_type' => (string) (($assignment['fuel_type'] ?? '') ?: 'N/A'),
                'shift' => $isAutomaticDuty ? 'Automatically Assigned' : (string) (($assignment['shift'] ?? '') ?: 'No active shift'),
                'automatic_duty' => $isAutomaticDuty,
            ],
            'assignment' => $assignment,
            'attendance' => $attendance,
            'sale' => $sale,
            'leave' => array_map('intval', $leave),
            'attendanceMonth' => array_map('intval', $monthAttendance),
            'saleTotals' => array_map('floatval', $saleTotals),
            'announcements' => (new Announcement())->dashboardAnnouncements($displayRole, 6),
        ];
    }

    private function fuelInventory(): array
    {
        $result = ['Petrol' => ['stock' => 0.0, 'low' => 0], 'Diesel' => ['stock' => 0.0, 'low' => 0], 'Gas' => ['stock' => 0.0, 'low' => 0]];
        foreach ($this->select("SELECT ft.name, COALESCE(SUM(fil.current_stock_litres), 0) stock, SUM(fil.current_stock_litres <= fil.minimum_stock_litres) low_stock FROM fuel_types ft LEFT JOIN fuel_inventory_levels fil ON fil.fuel_type_id = ft.id GROUP BY ft.id, ft.name") as $row) {
            $name = $this->fuelName((string) $row['name']);
            $result[$name] = ['stock' => (float) $row['stock'], 'low' => (int) $row['low_stock']];
        }
        return $result;
    }

    private function fuelSalesSummary(string $today): array
    {
        $summary = ['revenue' => 0.0, 'litres' => 0.0, 'pending' => 0, 'Petrol' => 0.0, 'Diesel' => 0.0, 'Gas' => 0.0];
        foreach ($this->select("SELECT ft.name, SUM(fs.amount_collected) revenue, SUM(fs.litres_sold) litres, SUM(fs.status = 'pending') pending FROM fuel_sales fs INNER JOIN fuel_types ft ON ft.id = fs.fuel_type_id WHERE fs.deleted_at IS NULL AND fs.sale_date = :today GROUP BY ft.id, ft.name", ['today' => $today]) as $row) {
            $name = $this->fuelName((string) $row['name']);
            $summary[$name] = (float) $row['litres'];
            $summary['revenue'] += (float) $row['revenue'];
            $summary['litres'] += (float) $row['litres'];
            $summary['pending'] += (int) $row['pending'];
        }
        return $summary;
    }

    private function dutySummary(string $today): array
    {
        $rows = $this->select("SELECT s.name, COUNT(*) total, SUM(ra.pump_id IS NOT NULL) pumps FROM roster_assignments ra INNER JOIN shifts s ON s.id = ra.shift_id WHERE ra.deleted_at IS NULL AND ra.roster_date = :today AND ra.status <> 'cancelled' GROUP BY s.id, s.name", ['today' => $today]);
        $summary = ['morning' => 0, 'evening' => 0, 'pumps' => 0];
        foreach ($rows as $row) {
            $key = str_contains(strtolower((string) $row['name']), 'even') ? 'evening' : 'morning';
            $summary[$key] += (int) $row['total'];
            $summary['pumps'] += (int) $row['pumps'];
        }
        return $summary;
    }

    private function recentAttendance(string $today): array
    {
        return $this->select("SELECT CONCAT(e.first_name, ' ', e.last_name) employee, COALESCE(jt.name, 'Staff') role, a.clock_in, a.clock_out, COALESCE(s.name, 'Unassigned') shift, a.attendance_status status FROM attendance a INNER JOIN employees e ON e.id = a.employee_id LEFT JOIN job_titles jt ON jt.id = e.job_title_id LEFT JOIN shifts s ON s.id = a.shift_id WHERE a.attendance_date = :today ORDER BY COALESCE(a.clock_in, a.created_at) DESC LIMIT 8", ['today' => $today]);
    }

    private function recentSales(): array
    {
        return $this->select("SELECT p.pump_name pump, ft.name fuel_type, CONCAT(e.first_name, ' ', e.last_name) attendant, fs.litres_sold litres, fs.amount_collected amount, COALESCE(s.name, 'Unassigned') shift, fs.submitted_at FROM fuel_sales fs INNER JOIN employees e ON e.id = fs.employee_id INNER JOIN pumps p ON p.id = fs.pump_id INNER JOIN fuel_types ft ON ft.id = fs.fuel_type_id LEFT JOIN shifts s ON s.id = fs.shift_id WHERE fs.deleted_at IS NULL AND fs.status = 'verified' ORDER BY COALESCE(fs.verified_at, fs.submitted_at, fs.created_at) DESC LIMIT 8");
    }

    private function recentLeaveRequests(): array
    {
        return $this->select("SELECT lr.request_code, CONCAT(e.first_name, ' ', e.last_name) employee, lt.name type, lr.total_days duration, DATE(lr.applied_at) date_applied, lr.status FROM leave_requests lr INNER JOIN employees e ON e.id = lr.employee_id INNER JOIN leave_types lt ON lt.id = lr.leave_type_id WHERE lr.deleted_at IS NULL AND lr.status IN ('pending','forwarded') ORDER BY lr.applied_at DESC LIMIT 8");
    }

    private function notifications(): array
    {
        return array_map(static fn (array $row): array => ['message' => (string) $row['activity'], 'time' => date('d M, H:i', strtotime((string) $row['created_at'])), 'unread' => false], $this->select("SELECT activity, created_at FROM activity_logs ORDER BY created_at DESC LIMIT 6"));
    }

    private function adminCharts(array $sections): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $key = date('Y-m', strtotime("-{$i} months"));
            $months[$key] = ['label' => date('M Y', strtotime($key . '-01')), 'attendance' => 0, 'revenue' => 0.0, 'litres' => 0.0];
        }
        if (!empty($sections['attendance'])) {
            foreach ($this->select("SELECT DATE_FORMAT(attendance_date, '%Y-%m') month_key, COUNT(*) total FROM attendance WHERE attendance_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 11 MONTH), '%Y-%m-01') GROUP BY month_key") as $row) {
                if (isset($months[$row['month_key']])) $months[$row['month_key']]['attendance'] = (int) $row['total'];
            }
        }
        if (!empty($sections['sales'])) {
            foreach ($this->select("SELECT DATE_FORMAT(sale_date, '%Y-%m') month_key, SUM(amount_collected) revenue, SUM(litres_sold) litres FROM fuel_sales WHERE deleted_at IS NULL AND sale_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 11 MONTH), '%Y-%m-01') GROUP BY month_key") as $row) {
                if (isset($months[$row['month_key']])) { $months[$row['month_key']]['revenue'] = (float) $row['revenue']; $months[$row['month_key']]['litres'] = (float) $row['litres']; }
            }
        }
        $inventory = !empty($sections['inventory']) ? $this->select("SELECT DATE(created_at) trend_date, SUM(new_balance) stock FROM fuel_inventory_transactions WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY trend_date") : [];
        $leave = !empty($sections['leave']) ? $this->select("SELECT lt.name, COUNT(*) total FROM leave_requests lr INNER JOIN leave_types lt ON lt.id = lr.leave_type_id WHERE lr.deleted_at IS NULL GROUP BY lt.id, lt.name ORDER BY total DESC") : [];
        return [
            'attendance' => ['labels' => array_column($months, 'label'), 'values' => array_column($months, 'attendance')],
            'sales' => ['labels' => array_column($months, 'label'), 'values' => array_column($months, 'revenue'), 'litres' => array_column($months, 'litres')],
            'leave' => ['labels' => array_column($leave, 'name'), 'values' => array_map('intval', array_column($leave, 'total'))],
            'inventory' => ['labels' => array_column($inventory, 'trend_date'), 'values' => array_map('floatval', array_column($inventory, 'stock'))],
        ];
    }

    private function logAccess(string $dashboard): void
    {
        $userId = (int) Session::get('auth.user_id', 0);
        $sessionKey = 'dashboard.access.' . md5($dashboard) . '.' . date('Y-m-d');
        if ($userId <= 0 || Session::has($sessionKey)) {
            return;
        }

        try {
            $request = Request::capture();
            $this->database->insert('activity_logs', [
                'log_code' => 'ACT-' . date('YmdHis') . '-' . random_int(100, 999),
                'user_id' => $userId,
                'employee_id' => ($employeeId = (int) Session::get('auth.employee_id', 0)) > 0 ? $employeeId : null,
                'activity_type' => 'Dashboard Access',
                'module' => 'Dashboard',
                'activity' => $dashboard . ' accessed',
                'entity_type' => 'dashboard',
                'entity_id' => null,
                'old_value' => null,
                'new_value' => json_encode(['dashboard' => $dashboard, 'role' => Session::get('auth.role'), 'requested_url' => $request->route()], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'browser' => substr($request->userAgent(), 0, 120),
                'status' => 'success',
                'notes' => 'Role: ' . (string) Session::get('auth.role', 'Unknown'),
            ]);
            Session::put($sessionKey, true);
        } catch (Throwable $exception) {
            error_log('[Dashboard Activity] ' . $exception->getMessage());
        }
    }
    private function sessionUser(): array
    {
        $user = Session::get('auth.user', []);
        return ['name' => is_array($user) ? (string) ($user['name'] ?? 'Administrator') : 'Administrator', 'role' => (string) Session::get('auth.role', 'Administrator')];
    }

    private function fuelName(string $name): string
    {
        return match (strtoupper($name)) { 'Petrol', 'PETROL' => 'Petrol', 'AGO', 'DIESEL' => 'Diesel', 'LPG', 'GAS' => 'Gas', default => $name };
    }

    private function row(string $sql, array $params = []): array
    {
        try { return $this->database->selectOne($sql, $params) ?? []; } catch (Throwable $exception) { error_log('[Dashboard] ' . $exception->getMessage()); return []; }
    }

    private function select(string $sql, array $params = []): array
    {
        try { return $this->database->select($sql, $params); } catch (Throwable $exception) { error_log('[Dashboard] ' . $exception->getMessage()); return []; }
    }

    private function value(string $sql, array $params = []): mixed
    {
        try { return $this->database->value($sql, $params); } catch (Throwable $exception) { error_log('[Dashboard] ' . $exception->getMessage()); return null; }
    }
}

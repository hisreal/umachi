<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use RuntimeException;
use Throwable;

final class AttendantDutyService
{
    private const PAGE_SIZE = 8;

    public function __construct(private ?Database $database = null)
    {
        $this->database ??= Database::getInstance();
    }

    public function data(array $input): array
    {
        $employeeId = (int) Session::get('auth.employee_id', 0);
        if ($employeeId <= 0) {
            throw new RuntimeException('Your account is not linked to an employee record.');
        }

        $month = preg_match('/^(0[1-9]|1[0-2])$/', (string) ($input['month'] ?? '')) ? (string) $input['month'] : date('m');
        $year = preg_match('/^20\d{2}$/', (string) ($input['year'] ?? '')) ? (string) $input['year'] : date('Y');
        $page = max(1, (int) ($input['page'] ?? 1));
        $filters = [
            'month' => $month,
            'year' => $year,
            'shift' => mb_substr(trim((string) ($input['shift'] ?? '')), 0, 100),
            'fuel' => mb_substr(trim((string) ($input['fuel'] ?? '')), 0, 100),
            'search' => mb_substr(trim((string) ($input['search'] ?? '')), 0, 100),
            'page' => $page,
        ];

        [$where, $bindings] = $this->where($employeeId, $filters);
        $total = (int) $this->database->value(
            "SELECT COUNT(*) FROM roster_assignments ra LEFT JOIN shifts s ON s.id = ra.shift_id LEFT JOIN pumps p ON p.id = ra.pump_id LEFT JOIN fuel_types ft ON ft.id = p.fuel_type_id WHERE {$where}",
            $bindings
        );
        $pages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $page = min($page, $pages);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $roster = array_map([$this, 'mapAssignment'], $this->database->select(
            $this->selectSql("WHERE {$where} ORDER BY CASE WHEN ra.roster_date >= CURDATE() THEN 0 ELSE 1 END, CASE WHEN ra.roster_date >= CURDATE() THEN ra.roster_date END ASC, CASE WHEN ra.roster_date < CURDATE() THEN ra.roster_date END DESC, ra.id DESC LIMIT " . self::PAGE_SIZE . " OFFSET {$offset}"),
            $bindings
        ));

        $today = $this->database->selectOne($this->selectSql('WHERE ra.employee_id = :employee_id AND ra.roster_date = CURDATE() AND ra.deleted_at IS NULL AND ra.status <> \'cancelled\' ORDER BY ra.id DESC LIMIT 1'), ['employee_id' => $employeeId]);
        $calendarRows = $this->database->select(
            $this->selectSql('WHERE ra.employee_id = :employee_id AND MONTH(ra.roster_date) = :month AND YEAR(ra.roster_date) = :year AND ra.deleted_at IS NULL ORDER BY ra.roster_date, ra.id'),
            ['employee_id' => $employeeId, 'month' => (int) $month, 'year' => (int) $year]
        );
        $calendar = [];
        foreach ($calendarRows as $row) {
            $day = (int) date('j', strtotime((string) $row['roster_date']));
            $calendar[$day][] = $this->mapAssignment($row);
        }

        $stats = $this->database->selectOne(
            "SELECT COUNT(*) total, SUM(LOWER(s.name) LIKE '%morning%') morning, SUM(LOWER(s.name) LIKE '%evening%') evening, SUM(ra.status = 'off_duty') days_off FROM roster_assignments ra INNER JOIN shifts s ON s.id = ra.shift_id WHERE ra.employee_id = :employee_id AND MONTH(ra.roster_date) = :month AND YEAR(ra.roster_date) = :year AND ra.deleted_at IS NULL",
            ['employee_id' => $employeeId, 'month' => (int) $month, 'year' => (int) $year]
        ) ?? [];
        $next = $this->database->selectOne($this->selectSql("WHERE ra.employee_id = :employee_id AND ra.roster_date > CURDATE() AND ra.deleted_at IS NULL AND ra.status NOT IN ('cancelled','off_duty') ORDER BY ra.roster_date LIMIT 1"), ['employee_id' => $employeeId]);

        $this->logView($employeeId, $filters);

        return [
            'employee' => $this->employee($employeeId, $today),
            'todaysDuty' => $today === null ? $this->emptyToday() : $this->mapToday($today),
            'roster' => $roster,
            'shiftStats' => [
                ['label' => 'Total Working Days This Month', 'value' => (int) ($stats['total'] ?? 0) . ' Days', 'icon' => 'fa-solid fa-calendar-check'],
                ['label' => 'Morning Shifts', 'value' => (int) ($stats['morning'] ?? 0) . ' Shifts', 'icon' => 'fa-solid fa-sun'],
                ['label' => 'Evening Shifts', 'value' => (int) ($stats['evening'] ?? 0) . ' Shifts', 'icon' => 'fa-solid fa-moon'],
                ['label' => 'Days Off', 'value' => (int) ($stats['days_off'] ?? 0) . ' Days', 'icon' => 'fa-solid fa-bed'],
                ['label' => 'Upcoming Shift', 'value' => $next === null ? 'No upcoming duty' : date('D, M j', strtotime((string) $next['roster_date'])) . ' - ' . (string) $next['shift_name'], 'icon' => 'fa-solid fa-clock'],
            ],
            'calendarAssignments' => $calendar,
            'calendarLabel' => date('F Y', strtotime("{$year}-{$month}-01")),
            'calendarMonth' => $month,
            'calendarYear' => $year,
            'filters' => $filters,
            'pagination' => ['page' => $page, 'pages' => $pages, 'total' => $total, 'from' => $total === 0 ? 0 : $offset + 1, 'to' => min($offset + self::PAGE_SIZE, $total)],
            'shifts' => $this->options('shifts', $employeeId),
            'fuelTypes' => $this->options('fuel', $employeeId),
        ];
    }

    private function where(int $employeeId, array $filters): array
    {
        $where = ['ra.employee_id = :employee_id', 'ra.deleted_at IS NULL'];
        $bindings = ['employee_id' => $employeeId];
        if ($filters['month'] !== '') {
            $where[] = 'MONTH(ra.roster_date) = :month';
            $bindings['month'] = (int) $filters['month'];
        }
        if ($filters['year'] !== '') {
            $where[] = 'YEAR(ra.roster_date) = :year';
            $bindings['year'] = (int) $filters['year'];
        }
        foreach (['shift' => 's.name', 'fuel' => 'ft.name'] as $key => $column) {
            if ($filters[$key] !== '') {
                $where[] = "{$column} = :{$key}";
                $bindings[$key] = $filters[$key];
            }
        }
        if ($filters['search'] !== '') {
            $term = '%' . $filters['search'] . '%';
            $where[] = '(p.pump_code LIKE :search_pump OR DATE_FORMAT(ra.roster_date, \'%Y-%m-%d\') LIKE :search_date)';
            $bindings['search_pump'] = $term;
            $bindings['search_date'] = $term;
        }
        return [implode(' AND ', $where), $bindings];
    }

    private function selectSql(string $suffix): string
    {
        return "SELECT ra.*, s.name shift_name, p.pump_code, p.pump_name, ft.name fuel_name,
                       COALESCE(CONCAT(assigner.first_name, ' ', assigner.last_name), creator.username, 'System') assigned_by
                FROM roster_assignments ra
                INNER JOIN shifts s ON s.id = ra.shift_id
                LEFT JOIN pumps p ON p.id = ra.pump_id
                LEFT JOIN fuel_types ft ON ft.id = p.fuel_type_id
                LEFT JOIN users creator ON creator.id = ra.created_by
                LEFT JOIN employees assigner ON assigner.id = creator.employee_id {$suffix}";
    }

    private function mapAssignment(array $row): array
    {
        return [
            'date' => (string) $row['roster_date'],
            'day' => date('l', strtotime((string) $row['roster_date'])),
            'shift' => (string) $row['shift_name'],
            'pump' => trim((string) ($row['pump_code'] ?? '') . ' - ' . (string) ($row['pump_name'] ?? ''), ' -') ?: 'No Pump Assigned',
            'fuel_type' => (string) (($row['fuel_name'] ?? '') ?: 'N/A'),
            'reporting_time' => date('h:i A', strtotime((string) $row['reporting_time'])),
            'closing_time' => date('h:i A', strtotime((string) $row['closing_time'])),
            'supervisor' => (string) $row['assigned_by'],
            'status' => ucwords(str_replace('_', ' ', (string) $row['status'])),
        ];
    }

    private function mapToday(array $row): array
    {
        $mapped = $this->mapAssignment($row);
        return ['date' => $mapped['date'], 'shift' => $mapped['shift'], 'assigned_pump' => $mapped['pump'], 'fuel_type' => $mapped['fuel_type'], 'reporting_time' => $mapped['reporting_time'], 'closing_time' => $mapped['closing_time'], 'supervisor' => $mapped['supervisor'], 'status' => $mapped['status'], 'has_assignment' => true];
    }

    private function emptyToday(): array
    {
        return ['date' => date('Y-m-d'), 'shift' => 'No duty assigned for today', 'assigned_pump' => 'N/A', 'fuel_type' => 'N/A', 'reporting_time' => 'N/A', 'closing_time' => 'N/A', 'supervisor' => 'N/A', 'status' => 'No Assignment', 'has_assignment' => false];
    }

    private function employee(int $employeeId, ?array $today): array
    {
        $row = $this->database->selectOne("SELECT e.employee_code employee_id, CONCAT(e.first_name, ' ', e.last_name) name, e.photo_path, COALESCE(d.name, 'Unassigned') department, COALESCE(jt.name, 'Pump Attendant') role FROM employees e LEFT JOIN departments d ON d.id = e.department_id LEFT JOIN job_titles jt ON jt.id = e.job_title_id WHERE e.id = :id AND e.deleted_at IS NULL", ['id' => $employeeId]) ?? [];
        return ['employee_id' => (string) ($row['employee_id'] ?? 'N/A'), 'name' => (string) ($row['name'] ?? 'Station Staff'), 'department' => (string) ($row['department'] ?? 'Unassigned'), 'role' => (string) ($row['role'] ?? 'Pump Attendant'), 'shift' => (string) ($today['shift_name'] ?? 'No shift assigned'), 'current_assignment' => $today === null ? 'No duty assigned for today' : trim((string) ($today['pump_code'] ?? '') . ' - ' . (string) ($today['pump_name'] ?? ''), ' -'), 'passport_photo' => (string) (($row['photo_path'] ?? '') ?: ProfilePhotoService::DEFAULT_PHOTO)];
    }

    private function options(string $type, int $employeeId): array
    {
        $sql = $type === 'shifts'
            ? 'SELECT DISTINCT s.name value, s.name label FROM roster_assignments ra INNER JOIN shifts s ON s.id = ra.shift_id WHERE ra.employee_id = :employee_id AND ra.deleted_at IS NULL ORDER BY s.name'
            : 'SELECT DISTINCT ft.name value, ft.name label FROM roster_assignments ra INNER JOIN pumps p ON p.id = ra.pump_id INNER JOIN fuel_types ft ON ft.id = p.fuel_type_id WHERE ra.employee_id = :employee_id AND ra.deleted_at IS NULL ORDER BY ft.name';
        return $this->database->select($sql, ['employee_id' => $employeeId]);
    }

    private function logView(int $employeeId, array $filters): void
    {
        $key = 'duty-roster.view.' . date('Y-m') . '.' . md5(json_encode($filters));
        if (Session::has($key)) {
            return;
        }
        try {
            $this->database->insert('activity_logs', ['log_code' => 'ACT-' . date('YmdHis') . '-' . random_int(100, 999), 'user_id' => (int) Session::get('auth.user_id', 0), 'employee_id' => $employeeId, 'activity_type' => 'Viewed Duty Roster', 'module' => 'Duty Roster', 'activity' => 'Viewed Duty Roster and Duty Calendar', 'entity_type' => 'duty_roster', 'entity_id' => $employeeId, 'old_value' => null, 'new_value' => json_encode(['filters' => $filters], JSON_THROW_ON_ERROR), 'status' => 'success']);
            Session::put($key, true);
        } catch (Throwable $exception) {
            error_log('[Duty Roster Activity] ' . $exception->getMessage());
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use RuntimeException;
use Throwable;

final class AttendantHistoryService
{
    private const PAGE_SIZE = 10;

    public function __construct(private ?Database $database = null)
    {
        $this->database ??= Database::getInstance();
    }

    public function fuelSales(array $input): array
    {
        $employeeId = $this->employeeId();
        $filters = $this->filters($input, 'sales');
        [$where, $bindings] = $this->salesWhere($employeeId, $filters);
        $sorts = ['date' => 'fs.sale_date', 'pump' => 'p.pump_code', 'litres' => 'fs.litres_sold', 'amount' => 'fs.amount_collected'];
        $sort = $sorts[$filters['sort']] ?? $sorts['date'];
        $direction = $filters['direction'] === 'asc' ? 'ASC' : 'DESC';
        $total = (int) $this->value("SELECT COUNT(*) FROM fuel_sales fs INNER JOIN pumps p ON p.id = fs.pump_id INNER JOIN fuel_types ft ON ft.id = fs.fuel_type_id LEFT JOIN shifts s ON s.id = fs.shift_id WHERE {$where}", $bindings);
        $pagination = $this->pagination($total, $filters['page']);
        $rows = $this->select(
            "SELECT fs.sale_date, COALESCE(s.name, 'Unassigned') shift_name, p.pump_code, p.pump_name,
                    ft.name fuel_name, fs.opening_meter, fs.closing_meter, fs.litres_sold, fs.unit_price,
                    fs.amount_collected, fs.status, fs.verification_status, fs.submitted_at,
                    fs.expected_amount, fs.cash_received, fs.pos_received, fs.bank_transfer_received, fs.total_received, fs.difference_amount, fs.balance_status, fs.payment_remark,
                    COALESCE(v.username, 'Not Verified') verified_by
             FROM fuel_sales fs
             INNER JOIN pumps p ON p.id = fs.pump_id
             INNER JOIN fuel_types ft ON ft.id = fs.fuel_type_id
             LEFT JOIN shifts s ON s.id = fs.shift_id
             LEFT JOIN users v ON v.id = fs.verified_by
             WHERE {$where}
             ORDER BY {$sort} {$direction}, fs.id DESC
             LIMIT " . self::PAGE_SIZE . ' OFFSET ' . $pagination['offset'],
            $bindings
        );

        $summary = $this->row(
            "SELECT COALESCE(SUM(CASE WHEN sale_date = CURDATE() AND status = 'verified' THEN amount_collected ELSE 0 END), 0) today_sales,
                    COALESCE(SUM(CASE WHEN sale_date = CURDATE() AND status = 'verified' THEN litres_sold ELSE 0 END), 0) today_litres,
                    COALESCE(SUM(CASE WHEN sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND status = 'verified' THEN amount_collected ELSE 0 END), 0) weekly_sales,
                    COALESCE(SUM(CASE WHEN sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND status = 'verified' THEN amount_collected ELSE 0 END), 0) monthly_sales,
                    COUNT(*) total_records, SUM(status = 'pending') pending, SUM(status = 'verified') verified
             FROM fuel_sales WHERE employee_id = :employee_id AND deleted_at IS NULL",
            ['employee_id' => $employeeId]
        );

        $this->logView('Viewed Fuel Sales History', $employeeId, $filters);

        return [
            'employee' => $this->employee($employeeId),
            'fuelSales' => array_map([$this, 'mapSale'], $rows),
            'salesSummary' => $this->salesSummaryCards($summary),
            'salesStats' => [],
            'filters' => $filters,
            'pagination' => $pagination,
            'pumps' => $this->options("SELECT DISTINCT p.pump_code value, p.pump_name label FROM fuel_sales fs INNER JOIN pumps p ON p.id = fs.pump_id WHERE fs.employee_id = :employee_id AND fs.deleted_at IS NULL ORDER BY p.pump_code", $employeeId),
            'fuelTypes' => $this->options("SELECT DISTINCT ft.name value, ft.name label FROM fuel_sales fs INNER JOIN fuel_types ft ON ft.id = fs.fuel_type_id WHERE fs.employee_id = :employee_id AND fs.deleted_at IS NULL ORDER BY ft.name", $employeeId),
            'shifts' => $this->options("SELECT DISTINCT s.name value, s.name label FROM fuel_sales fs INNER JOIN shifts s ON s.id = fs.shift_id WHERE fs.employee_id = :employee_id AND fs.deleted_at IS NULL ORDER BY s.name", $employeeId),
        ];
    }

    public function attendance(array $input): array
    {
        $employeeId = $this->employeeId();
        $filters = $this->filters($input, 'attendance');
        [$where, $bindings] = $this->attendanceWhere($employeeId, $filters);
        $sorts = ['date' => 'a.attendance_date', 'clock_in' => 'a.clock_in', 'clock_out' => 'a.clock_out', 'status' => 'a.attendance_status'];
        $sort = $sorts[$filters['sort']] ?? $sorts['date'];
        $direction = $filters['direction'] === 'asc' ? 'ASC' : 'DESC';
        $total = (int) $this->value("SELECT COUNT(*) FROM attendance a LEFT JOIN shifts s ON s.id = a.shift_id WHERE {$where}", $bindings);
        $pagination = $this->pagination($total, $filters['page']);
        $rows = $this->select(
            "SELECT a.attendance_date, COALESCE(s.name, 'Unassigned') shift_name, a.clock_in, a.clock_out,
                    a.attendance_status, a.lateness_minutes, a.overtime_minutes, a.remarks
             FROM attendance a LEFT JOIN shifts s ON s.id = a.shift_id
             WHERE {$where}
             ORDER BY {$sort} {$direction}, a.id DESC
             LIMIT " . self::PAGE_SIZE . ' OFFSET ' . $pagination['offset'],
            $bindings
        );

        $summaryMonth = $filters['month'] !== '' ? $filters['month'] : date('m');
        $summaryYear = $filters['year'] !== '' ? $filters['year'] : date('Y');
        $summary = $this->row(
            "SELECT COUNT(*) working_days, SUM(attendance_status = 'Present') present,
                    SUM(attendance_status = 'Absent') absent, SUM(attendance_status = 'Late') late,
                    SUM(attendance_status = 'On Leave') leave_days, COALESCE(SUM(overtime_minutes), 0) overtime_minutes
             FROM attendance
             WHERE employee_id = :employee_id AND MONTH(attendance_date) = :month AND YEAR(attendance_date) = :year",
            ['employee_id' => $employeeId, 'month' => (int) $summaryMonth, 'year' => (int) $summaryYear]
        );
        $workingDays = (int) ($summary['working_days'] ?? 0);
        $attended = (int) ($summary['present'] ?? 0) + (int) ($summary['late'] ?? 0);
        $summary['percentage'] = $workingDays > 0 ? round(($attended / $workingDays) * 100, 1) : 0.0;

        $calendarRows = $this->select(
            'SELECT DAY(attendance_date) day, attendance_status status FROM attendance WHERE employee_id = :employee_id AND MONTH(attendance_date) = :month AND YEAR(attendance_date) = :year ORDER BY attendance_date',
            ['employee_id' => $employeeId, 'month' => (int) $summaryMonth, 'year' => (int) $summaryYear]
        );

        $this->logView('Viewed Attendance History', $employeeId, $filters);

        return [
            'employee' => $this->employee($employeeId),
            'attendanceHistory' => array_map([$this, 'mapAttendance'], $rows),
            'attendanceSummary' => $this->attendanceSummaryCards($summary),
            'attendanceStats' => $this->attendanceStats($summary),
            'calendarDays' => $calendarRows,
            'calendarLabel' => date('F Y', strtotime("{$summaryYear}-{$summaryMonth}-01")),
            'filters' => $filters,
            'pagination' => $pagination,
            'shifts' => $this->options("SELECT DISTINCT s.name value, s.name label FROM attendance a INNER JOIN shifts s ON s.id = a.shift_id WHERE a.employee_id = :employee_id ORDER BY s.name", $employeeId),
        ];
    }

    private function filters(array $input, string $type): array
    {
        $allowedStatuses = $type === 'sales'
            ? ['pending', 'verified', 'rejected', 'correction_requested', 'cancelled']
            : ['Present', 'Late', 'Absent', 'Half Day', 'On Leave'];
        $status = trim((string) ($input['status'] ?? ''));
        return [
            'search' => mb_substr(trim((string) ($input['search'] ?? '')), 0, 100),
            'date_from' => $this->date((string) ($input['date_from'] ?? '')),
            'date_to' => $this->date((string) ($input['date_to'] ?? '')),
            'month' => preg_match('/^(0[1-9]|1[0-2])$/', (string) ($input['month'] ?? '')) ? (string) $input['month'] : '',
            'year' => preg_match('/^20\d{2}$/', (string) ($input['year'] ?? '')) ? (string) $input['year'] : '',
            'shift' => mb_substr(trim((string) ($input['shift'] ?? '')), 0, 100),
            'pump' => mb_substr(trim((string) ($input['pump'] ?? '')), 0, 40),
            'fuel' => mb_substr(trim((string) ($input['fuel'] ?? '')), 0, 100),
            'status' => in_array($status, $allowedStatuses, true) ? $status : '',
            'sort' => trim((string) ($input['sort'] ?? 'date')),
            'direction' => strtolower((string) ($input['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc',
            'page' => max(1, (int) ($input['page'] ?? 1)),
        ];
    }

    private function salesWhere(int $employeeId, array $filters): array
    {
        $where = ['fs.employee_id = :employee_id', 'fs.deleted_at IS NULL'];
        $bindings = ['employee_id' => $employeeId];
        $this->dateWhere($where, $bindings, 'fs.sale_date', $filters);
        foreach (['shift' => 's.name', 'pump' => 'p.pump_code', 'fuel' => 'ft.name', 'status' => 'fs.status'] as $key => $column) {
            if ($filters[$key] !== '') {
                $where[] = "{$column} = :{$key}";
                $bindings[$key] = $filters[$key];
            }
        }
        if ($filters['search'] !== '') {
            $where[] = '(p.pump_code LIKE :search_code OR p.pump_name LIKE :search_pump OR ft.name LIKE :search_fuel OR DATE_FORMAT(fs.sale_date, \'%Y-%m-%d\') LIKE :search_date)';
            $term = '%' . $filters['search'] . '%';
            $bindings['search_code'] = $term;
            $bindings['search_pump'] = $term;
            $bindings['search_fuel'] = $term;
            $bindings['search_date'] = $term;
        }
        return [implode(' AND ', $where), $bindings];
    }

    private function attendanceWhere(int $employeeId, array $filters): array
    {
        $where = ['a.employee_id = :employee_id'];
        $bindings = ['employee_id' => $employeeId];
        $this->dateWhere($where, $bindings, 'a.attendance_date', $filters);
        foreach (['shift' => 's.name', 'status' => 'a.attendance_status'] as $key => $column) {
            if ($filters[$key] !== '') {
                $where[] = "{$column} = :{$key}";
                $bindings[$key] = $filters[$key];
            }
        }
        if ($filters['search'] !== '') {
            $where[] = '(s.name LIKE :search_shift OR DATE_FORMAT(a.attendance_date, \'%Y-%m-%d\') LIKE :search_date)';
            $term = '%' . $filters['search'] . '%';
            $bindings['search_shift'] = $term;
            $bindings['search_date'] = $term;
        }
        return [implode(' AND ', $where), $bindings];
    }

    private function dateWhere(array &$where, array &$bindings, string $column, array $filters): void
    {
        if ($filters['date_from'] !== '') {
            $where[] = "{$column} >= :date_from";
            $bindings['date_from'] = $filters['date_from'];
        }
        if ($filters['date_to'] !== '') {
            $where[] = "{$column} <= :date_to";
            $bindings['date_to'] = $filters['date_to'];
        }
        if ($filters['month'] !== '') {
            $where[] = "MONTH({$column}) = :month";
            $bindings['month'] = (int) $filters['month'];
        }
        if ($filters['year'] !== '') {
            $where[] = "YEAR({$column}) = :year";
            $bindings['year'] = (int) $filters['year'];
        }
    }

    private function employeeId(): int
    {
        $employeeId = (int) Session::get('auth.employee_id', 0);
        if ($employeeId <= 0) {
            throw new RuntimeException('Your account is not linked to an employee record.');
        }
        return $employeeId;
    }

    private function employee(int $employeeId): array
    {
        return $this->row("SELECT e.employee_code employee_id, CONCAT(e.first_name, ' ', e.last_name) name, COALESCE(d.name, 'Unassigned') department, COALESCE(jt.name, 'Pump Attendant') role FROM employees e LEFT JOIN departments d ON d.id = e.department_id LEFT JOIN job_titles jt ON jt.id = e.job_title_id WHERE e.id = :id AND e.deleted_at IS NULL LIMIT 1", ['id' => $employeeId]);
    }

    private function mapSale(array $row): array
    {
        return ['date' => (string) $row['sale_date'], 'shift' => (string) $row['shift_name'], 'pump' => trim((string) $row['pump_code'] . ' - ' . (string) $row['pump_name']), 'pump_code' => (string) $row['pump_code'], 'fuel' => (string) $row['fuel_name'], 'opening_meter' => (float) $row['opening_meter'], 'closing_meter' => (float) $row['closing_meter'], 'liters' => (float) $row['litres_sold'], 'unit_price' => (float) $row['unit_price'], 'expected_amount' => (float) $row['expected_amount'], 'cash_received' => (float) $row['cash_received'], 'pos_received' => (float) $row['pos_received'], 'bank_transfer_received' => (float) $row['bank_transfer_received'], 'amount' => (float) $row['total_received'], 'difference_amount' => (float) $row['difference_amount'], 'balance_status' => (string) $row['balance_status'], 'payment_remark' => (string) ($row['payment_remark'] ?? ''), 'status' => ucwords(str_replace('_', ' ', (string) $row['status'])), 'verified_by' => (string) $row['verified_by'], 'submitted_time' => empty($row['submitted_at']) ? 'N/A' : date('h:i A', strtotime((string) $row['submitted_at']))];
    }

    private function mapAttendance(array $row): array
    {
        return ['date' => (string) $row['attendance_date'], 'day' => date('l', strtotime((string) $row['attendance_date'])), 'shift' => (string) $row['shift_name'], 'clock_in' => empty($row['clock_in']) ? '-' : date('h:i A', strtotime((string) $row['clock_in'])), 'clock_out' => empty($row['clock_out']) ? '-' : date('h:i A', strtotime((string) $row['clock_out'])), 'status' => (string) $row['attendance_status'], 'lateness' => (int) $row['lateness_minutes'], 'overtime' => (int) $row['overtime_minutes'], 'remarks' => (string) (($row['remarks'] ?? '') ?: 'No remarks')];
    }

    private function salesSummaryCards(array $row): array
    {
        return [
            ['label' => "Today's Total Sales", 'value' => 'NGN ' . number_format((float) ($row['today_sales'] ?? 0), 2), 'icon' => 'fa-solid fa-naira-sign', 'tone' => 'primary'],
            ['label' => "Today's Litres Sold", 'value' => number_format((float) ($row['today_litres'] ?? 0), 2) . ' L', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'success'],
            ['label' => 'Weekly Sales', 'value' => 'NGN ' . number_format((float) ($row['weekly_sales'] ?? 0), 2), 'icon' => 'fa-solid fa-calendar-week', 'tone' => 'danger'],
            ['label' => 'Monthly Sales', 'value' => 'NGN ' . number_format((float) ($row['monthly_sales'] ?? 0), 2), 'icon' => 'fa-solid fa-calendar', 'tone' => 'primary'],
            ['label' => 'Total Records', 'value' => (string) ((int) ($row['total_records'] ?? 0)), 'icon' => 'fa-solid fa-receipt', 'tone' => 'success'],
            ['label' => 'Pending Verification', 'value' => (string) ((int) ($row['pending'] ?? 0)), 'icon' => 'fa-solid fa-hourglass-half', 'tone' => 'danger'],
            ['label' => 'Verified Sales', 'value' => (string) ((int) ($row['verified'] ?? 0)), 'icon' => 'fa-solid fa-circle-check', 'tone' => 'success'],
        ];
    }

    private function attendanceSummaryCards(array $row): array
    {
        return [
            ['label' => 'Total Working Days', 'value' => (string) ((int) ($row['working_days'] ?? 0)), 'icon' => 'fa-solid fa-calendar-days', 'tone' => 'primary'],
            ['label' => 'Present Days', 'value' => (string) ((int) ($row['present'] ?? 0)), 'icon' => 'fa-solid fa-circle-check', 'tone' => 'success'],
            ['label' => 'Absent Days', 'value' => (string) ((int) ($row['absent'] ?? 0)), 'icon' => 'fa-solid fa-circle-xmark', 'tone' => 'danger'],
            ['label' => 'Late Days', 'value' => (string) ((int) ($row['late'] ?? 0)), 'icon' => 'fa-solid fa-hourglass-half', 'tone' => 'secondary'],
        ];
    }

    private function attendanceStats(array $row): array
    {
        return [
            ['label' => 'Attendance Percentage', 'value' => number_format((float) ($row['percentage'] ?? 0), 1) . '%', 'icon' => 'fa-solid fa-chart-line'],
            ['label' => 'Late Days', 'value' => (int) ($row['late'] ?? 0) . ' Days', 'icon' => 'fa-solid fa-hourglass-half'],
            ['label' => 'Leave Days', 'value' => (int) ($row['leave_days'] ?? 0) . ' Days', 'icon' => 'fa-solid fa-person-walking-arrow-right'],
            ['label' => 'Overtime Hours', 'value' => number_format(((int) ($row['overtime_minutes'] ?? 0)) / 60, 1) . ' Hours', 'icon' => 'fa-solid fa-stopwatch'],
        ];
    }

    private function pagination(int $total, int $requestedPage): array
    {
        $pages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $page = min($requestedPage, $pages);
        return ['page' => $page, 'pages' => $pages, 'total' => $total, 'per_page' => self::PAGE_SIZE, 'offset' => ($page - 1) * self::PAGE_SIZE, 'from' => $total === 0 ? 0 : (($page - 1) * self::PAGE_SIZE) + 1, 'to' => min($page * self::PAGE_SIZE, $total)];
    }

    private function options(string $sql, int $employeeId): array
    {
        return $this->select($sql, ['employee_id' => $employeeId]);
    }

    private function date(string $value): string
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value ? $value : '';
    }

    private function logView(string $activity, int $employeeId, array $filters): void
    {
        $filtered = array_filter($filters, static fn (mixed $value, string $key): bool => !in_array($key, ['page', 'sort', 'direction'], true) && $value !== '', ARRAY_FILTER_USE_BOTH);
        $key = 'history.log.' . md5($activity . json_encode($filters));
        if (Session::has($key)) {
            return;
        }
        try {
            $this->database->insert('activity_logs', ['log_code' => 'ACT-' . date('YmdHis') . '-' . random_int(100, 999), 'user_id' => (int) Session::get('auth.user_id', 0), 'employee_id' => $employeeId, 'activity_type' => $filtered === [] ? $activity : 'Applied Filters', 'module' => str_contains($activity, 'Fuel') ? 'Fuel Sales' : 'Attendance', 'activity' => $activity, 'entity_type' => 'history', 'entity_id' => $employeeId, 'old_value' => null, 'new_value' => json_encode(['filters' => $filtered], JSON_THROW_ON_ERROR), 'status' => 'success']);
            Session::put($key, true);
        } catch (Throwable $exception) {
            error_log('[History Activity] ' . $exception->getMessage());
        }
    }

    private function row(string $sql, array $bindings): array
    {
        return $this->database->selectOne($sql, $bindings) ?? [];
    }

    private function select(string $sql, array $bindings): array
    {
        return $this->database->select($sql, $bindings);
    }

    private function value(string $sql, array $bindings): mixed
    {
        return $this->database->value($sql, $bindings);
    }
}

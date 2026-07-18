<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Session;
use App\Core\Request;
use App\Services\AttendanceDutyPolicyService;
use RuntimeException;
use Throwable;

class Attendance extends BaseModel
{
    public function boot(): void
    {
        $this->database()->execute("CREATE TABLE IF NOT EXISTS attendance (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, employee_id BIGINT UNSIGNED NOT NULL, duty_assignment_id BIGINT UNSIGNED NULL, shift_id BIGINT UNSIGNED NULL, attendance_date DATE NOT NULL, role VARCHAR(100) NULL, clock_in DATETIME NULL, clock_out DATETIME NULL, attendance_status ENUM('Present','Late','Absent','Half Day','On Leave') NOT NULL DEFAULT 'Present', lateness_minutes INT NOT NULL DEFAULT 0, overtime_minutes INT NOT NULL DEFAULT 0, remarks TEXT NULL, clock_in_photo VARCHAR(255) NULL, clock_out_photo VARCHAR(255) NULL, verification_status ENUM('Verified','Pending','Rejected') NOT NULL DEFAULT 'Pending', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE KEY uq_attendance_employee_shift_date (employee_id, shift_id, attendance_date), KEY idx_attendance_date_status (attendance_date, attendance_status), KEY idx_attendance_employee (employee_id), KEY idx_attendance_duty (duty_assignment_id), CONSTRAINT fk_attendance_employee_live FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT ON UPDATE CASCADE, CONSTRAINT fk_attendance_shift_live FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE SET NULL ON UPDATE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->addColumnIfMissing('attendance', 'role', 'ALTER TABLE attendance ADD COLUMN role VARCHAR(100) NULL AFTER attendance_date');
    }

    public function getEmployee(?int $employeeId = null): array
    {
        $employee = $this->currentEmployee($employeeId);
        $role = (string) ($employee['role'] ?? '');
        $duty = $employee === null ? null : $this->attendanceDuty((int) $employee['db_id'], $role);

        return [
            'db_id' => (int) ($employee['db_id'] ?? 0),
            'name' => (string) ($employee['name'] ?? 'Unassigned Employee'),
            'employee_id' => (string) ($employee['employee_id'] ?? 'N/A'),
            'role' => (string) ($employee['role'] ?? 'Unassigned'),
            'assigned_pump' => (string) ($duty['pump'] ?? 'No duty assigned today'),
            'shift' => (string) ($duty['shift_label'] ?? 'No shift assigned today'),
            'department' => (string) ($employee['department'] ?? 'Unassigned'),
            'automatic_duty' => !empty($duty['is_automatic']),
            'duty_status' => !empty($duty['is_automatic']) ? 'Automatically Assigned' : ($duty === null ? 'Duty Assignment Required' : 'Manually Assigned'),
        ];
    }

    public function getAttendanceStatus(?int $employeeId = null): array
    {
        $this->boot();
        $employee = $this->currentEmployee($employeeId);
        $role = (string) ($employee['role'] ?? '');
        $duty = $employee === null ? null : $this->attendanceDuty((int) $employee['db_id'], $role);
        $record = $duty === null ? null : $this->todayRecord((int) $employee['db_id'], isset($duty['shift_id']) ? (int) $duty['shift_id'] : null);

        $label = 'No Duty Assigned';
        $detail = 'You do not have an active duty assignment for today.';
        $type = 'waiting';
        $photo = 'Waiting for Selfie';

        if ($duty !== null) {
            $automatic = !empty($duty['is_automatic']);
            $label = $automatic ? 'Automatically Assigned' : 'Awaiting Clock In';
            $detail = $automatic ? 'You are automatically considered on duty today. Take a fresh selfie to clock in.' : 'Take a fresh selfie to start your assigned shift.';
            if ($record !== null && !empty($record['clock_in']) && empty($record['clock_out'])) {
                $label = 'Clocked In';
                $detail = 'Your shift is active. Clock out when your duty is complete.';
                $type = 'verified';
                $photo = 'Clock-In Captured';
            } elseif ($record !== null && !empty($record['clock_out'])) {
                $label = 'Clocked Out';
                $detail = 'Your shift attendance is complete for today.';
                $type = 'verified';
                $photo = 'Completed';
            }
        }

        return [
            'label' => $label,
            'detail' => $detail,
            'shift_date' => date('l, F j, Y'),
            'current_time' => date('h:i A'),
            'expected_start' => $duty['reporting'] ?? 'N/A',
            'station' => 'Umachi Main Filling Station',
            'status_type' => $type,
            'photo_status' => $photo,
        ];
    }

    public function getAttendanceHistory(?int $employeeId = null, array $filters = []): array
    {
        $this->boot();
        $bindings = [];
        $where = ['1 = 1'];
        $employee = $this->currentEmployee($employeeId);
        if ($employeeId !== null || !$this->canViewAll()) {
            $where[] = 'a.employee_id = :employee_id';
            $bindings['employee_id'] = (int) ($employee['db_id'] ?? 0);
        }

        foreach (['status' => 'a.attendance_status', 'department' => 'd.name', 'role' => 'COALESCE(a.role, jt.name)'] as $key => $column) {
            if (trim((string) ($filters[$key] ?? '')) !== '') {
                $where[] = $column . ' = :' . $key;
                $bindings[$key] = (string) $filters[$key];
            }
        }
        if (trim((string) ($filters['date'] ?? '')) !== '') {
            $where[] = 'a.attendance_date = :date';
            $bindings['date'] = (string) $filters['date'];
        }
        if (trim((string) ($filters['search'] ?? '')) !== '') {
            $where[] = "(e.employee_code LIKE :search_code OR CONCAT(e.first_name, ' ', e.last_name) LIKE :search_name)";
            $bindings['search_code'] = '%' . trim((string) $filters['search']) . '%';
            $bindings['search_name'] = '%' . trim((string) $filters['search']) . '%';
        }

        $rows = $this->query($this->attendanceSelectSql('WHERE ' . implode(' AND ', $where) . ' ORDER BY a.attendance_date DESC, a.id DESC LIMIT 200'), $bindings);
        return array_map([$this, 'mapAttendanceRow'], $rows);
    }

    public function attendanceDetails(int $recordId): ?array
    {
        $this->boot();
        if ($recordId <= 0) {
            return null;
        }

        $row = $this->queryOne(
            $this->attendanceSelectSql('WHERE a.id = :id LIMIT 1'),
            ['id' => $recordId]
        );
        if ($row === null) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'employee_db_id' => (int) $row['employee_id'],
            'employee_name' => (string) $row['employee_name'],
            'employee_id' => (string) $row['employee_code'],
            'department' => (string) ($row['department_name'] ?? 'Unassigned'),
            'role' => (string) ($row['role_name'] ?? 'Unassigned'),
            'attendance_date' => (string) $row['attendance_date'],
            'shift' => (string) ($row['shift_name'] ?? 'Unassigned'),
            'status' => (string) $row['attendance_status'],
            'clock_in_time' => empty($row['clock_in']) ? 'Not recorded' : date('h:i A', strtotime((string) $row['clock_in'])),
            'clock_out_time' => empty($row['clock_out']) ? 'Not recorded' : date('h:i A', strtotime((string) $row['clock_out'])),
            'clock_in_selfie_status' => $this->attendancePhotoStatus($row['clock_in_photo'] ?? null, 'clock-in'),
            'clock_out_selfie_status' => $this->attendancePhotoStatus($row['clock_out_photo'] ?? null, 'clock-out'),
            'lateness' => $this->minutesLabel((int) ($row['lateness_minutes'] ?? 0)),
            'overtime' => $this->minutesLabel((int) ($row['overtime_minutes'] ?? 0)),
            'remarks' => trim((string) ($row['remarks'] ?? '')) ?: 'No attendance remarks.',
        ];
    }

    /** @return array{path: string, mime: string}|null */
    public function attendanceSelfieFile(int $recordId, string $type): ?array
    {
        $this->boot();
        if ($recordId <= 0 || !in_array($type, ['clock-in', 'clock-out'], true)) {
            return null;
        }
        $column = $type === 'clock-in' ? 'clock_in_photo' : 'clock_out_photo';
        $row = $this->queryOne(
            "SELECT {$column} AS photo_path FROM attendance WHERE id = :id LIMIT 1",
            ['id' => $recordId]
        );

        return $row === null ? null : $this->resolveAttendancePhoto($row['photo_path'] ?? null, $type);
    }

    private function attendancePhotoStatus(mixed $path, string $type): string
    {
        if (trim((string) $path) === '') {
            return 'none';
        }

        return $this->resolveAttendancePhoto($path, $type) === null ? 'missing' : 'available';
    }

    private function minutesLabel(int $minutes): string
    {
        if ($minutes <= 0) {
            return 'None';
        }
        $hours = intdiv($minutes, 60);
        $remainder = $minutes % 60;
        if ($hours === 0) {
            return $minutes . ' minute' . ($minutes === 1 ? '' : 's');
        }

        return $hours . ' hour' . ($hours === 1 ? '' : 's')
            . ($remainder > 0 ? ' ' . $remainder . ' minute' . ($remainder === 1 ? '' : 's') : '');
    }

    public function adminSummary(): array
    {
        $this->boot();
        $today = date('Y-m-d');
        $total = (int) $this->database()->value("SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL AND employment_status = 'active'");
        $present = (int) $this->database()->value("SELECT COUNT(*) FROM attendance WHERE attendance_date = :today AND attendance_status IN ('Present','Late')", ['today' => $today]);
        $late = (int) $this->database()->value("SELECT COUNT(*) FROM attendance WHERE attendance_date = :today AND attendance_status = 'Late'", ['today' => $today]);
        $leave = (int) $this->database()->value("SELECT COUNT(*) FROM attendance WHERE attendance_date = :today AND attendance_status = 'On Leave'", ['today' => $today]);
        $clockedOut = (int) $this->database()->value("SELECT COUNT(*) FROM attendance WHERE attendance_date = :today AND clock_out IS NOT NULL", ['today' => $today]);
        $pending = (int) $this->database()->value("SELECT COUNT(*) FROM attendance WHERE attendance_date = :today AND verification_status = 'Pending'", ['today' => $today]);
        $absent = max(0, $total - $present - $leave);

        return [
            'total_employees' => $total,
            'present_today' => $present,
            'absent_today' => $absent,
            'late_today' => $late,
            'on_leave' => $leave,
            'attendance_rate' => $total > 0 ? number_format(($present / $total) * 100, 1) . '%' : '0%',
            'clocked_in' => max(0, $present - $clockedOut),
            'clocked_out' => $clockedOut,
            'overtime' => (int) $this->database()->value("SELECT COUNT(*) FROM attendance WHERE attendance_date = :today AND overtime_minutes > 0", ['today' => $today]),
            'pending_verification' => $pending,
        ];
    }

    public function clockIn(array $files): void
    {
        $this->boot();
        $employee = $this->requireCurrentActiveEmployee();
        $duty = $this->requireAttendanceDuty((int) $employee['db_id'], (string) $employee['role']);
        $shiftId = isset($duty['shift_id']) ? (int) $duty['shift_id'] : null;
        $existing = $this->todayRecord((int) $employee['db_id'], $shiftId);
        if ($existing !== null && !empty($existing['clock_in'])) {
            throw new RuntimeException('You have already clocked in for this shift.');
        }

        $photo = $this->storePhoto($files['clock_in_photo'] ?? null, 'clock-in');
        $now = new \DateTimeImmutable('now');
        $automatic = !empty($duty['is_automatic']);
        $reporting = $automatic ? $now : new \DateTimeImmutable(date('Y-m-d') . ' ' . $duty['start_time']);
        $late = $automatic ? 0 : max(0, (int) floor(($now->getTimestamp() - $reporting->getTimestamp()) / 60));
        $grace = (int) ($duty['grace_period'] ?? 0);
        $status = !$automatic && $late > $grace ? 'Late' : 'Present';

        $this->transaction(function (Database $database) use ($employee, $duty, $shiftId, $photo, $now, $late, $status): void {
            $database->insert('attendance', [
                'employee_id' => (int) $employee['db_id'],
                'duty_assignment_id' => !empty($duty['duty_assignment_id']) ? (int) $duty['duty_assignment_id'] : null,
                'shift_id' => $shiftId,
                'attendance_date' => date('Y-m-d'),
                'role' => (string) $employee['role'],
                'clock_in' => $now->format('Y-m-d H:i:s'),
                'attendance_status' => $status,
                'lateness_minutes' => $late,
                'overtime_minutes' => 0,
                'remarks' => null,
                'clock_in_photo' => $photo,
                'verification_status' => 'Pending',
            ]);
            if (!empty($duty['is_automatic'])) {
                $this->logActivity('Automatic Duty Assignment Applied', (int) $employee['db_id'], null, ['date' => date('Y-m-d'), 'role' => $employee['role']]);
            }
            $this->logActivity('Clock-In', (int) $employee['db_id'], null, ['status' => $status, 'late' => $late]);
        });
    }

    public function clockOut(array $data, array $files): void
    {
        $this->boot();
        $employee = $this->requireCurrentActiveEmployee();
        $duty = $this->requireAttendanceDuty((int) $employee['db_id'], (string) $employee['role']);
        $shiftId = isset($duty['shift_id']) ? (int) $duty['shift_id'] : null;
        $record = $this->todayRecord((int) $employee['db_id'], $shiftId);
        if ($record === null || empty($record['clock_in'])) {
            throw new RuntimeException('Clock-Out requires an existing Clock-In record.');
        }
        if (!empty($record['clock_out'])) {
            throw new RuntimeException('You have already clocked out for this shift.');
        }

        $photo = $this->storePhoto($files['clock_out_photo'] ?? null, 'clock-out');
        $now = new \DateTimeImmutable('now');
        $automatic = !empty($duty['is_automatic']);
        $closing = $automatic ? $now : new \DateTimeImmutable(date('Y-m-d') . ' ' . $duty['end_time']);
        $overtime = $automatic ? 0 : max(0, (int) floor(($now->getTimestamp() - $closing->getTimestamp()) / 60));
        $remarks = trim((string) ($data['remarks'] ?? ''));
        $fuelSale = null;
        if ((new AttendanceDutyPolicyService())->requiresManualDuty((string) $employee['role'])) {
            $fuelSale = new FuelSale();
            $fuelSale->boot();
        }


        $this->transaction(function (Database $database) use ($record, $employee, $duty, $data, $photo, $now, $overtime, $remarks, $fuelSale): void {
            if ($fuelSale !== null) {
                $fuelSale->recordClockOutSale($database, $employee, $duty, $record, $data);
            }

            $database->update('attendance', [
                'clock_out' => $now->format('Y-m-d H:i:s'),
                'overtime_minutes' => $overtime,
                'remarks' => $remarks !== '' ? $remarks : $record['remarks'],
                'clock_out_photo' => $photo,
                'verification_status' => 'Pending',
            ], ['id' => (int) $record['id']]);
            $this->logActivity('Clock-Out', (int) $employee['db_id'], ['id' => $record['id']], ['overtime' => $overtime]);
        });
    }

    public function getClockOutOptions(): array
    {
        $employee = $this->currentEmployee();
        $duty = $employee === null ? null : $this->todayDuty((int) $employee['db_id']);
        return [
            'pumps' => [$duty['pump'] ?? 'No assigned pump'],
            'fuel_types' => [$duty['fuel_short_name'] ?? $duty['fuel_type'] ?? 'N/A'],
        ];
    }

    public function getFuelSalesSummary(): array
    {
        $employee = $this->currentEmployee();
        $duty = $employee === null ? null : $this->todayDuty((int) $employee['db_id']);
        $openingMeter = $duty === null ? 0.0 : (float) ($this->database()->value('SELECT current_meter_reading FROM pumps WHERE id = :pump_id LIMIT 1', ['pump_id' => (int) $duty['pump_id']]) ?? 0);
        $unitPrice = $duty === null ? 0.0 : (float) ($this->database()->value("SELECT price_per_litre FROM fuel_prices WHERE fuel_type_id = :fuel_type_id AND status = 'active' ORDER BY effective_from DESC, id DESC LIMIT 1", ['fuel_type_id' => (int) $duty['fuel_type_id']]) ?? 0);

        return [
            'assigned_pump' => $duty['pump'] ?? 'No assigned pump',
            'fuel_type' => $duty['fuel_short_name'] ?? $duty['fuel_type'] ?? 'N/A',
            'opening_meter' => number_format($openingMeter, 2, '.', ''),
            'closing_meter' => number_format($openingMeter, 2, '.', ''),
            'liters_sold' => '0.00',
            'amount_collected' => 'NGN 0.00',
            'unit_price' => 'NGN ' . number_format($unitPrice, 2) . '/Litre',
            'unit_price_value' => number_format($unitPrice, 2, '.', ''),
            'price_available' => $unitPrice > 0,
            'shift' => $duty['shift_label'] ?? 'No shift assigned today',
            'date' => date('l, F j, Y'),
            'remarks' => '',
        ];
    }

    public function getPreviousShiftHistory(): array
    {
        return array_map(static fn (array $row): array => [
            'date' => $row['date'], 'shift' => $row['shift'], 'pump' => 'Assigned Pump', 'fuel_type' => 'N/A', 'liters_sold' => '0.00', 'amount' => 'NGN 0.00', 'clock_out_time' => $row['clock_out'], 'status' => $row['status'],
        ], array_slice($this->getAttendanceHistory(), 0, 10));
    }

    private function currentEmployee(?int $employeeId = null): ?array
    {
        $id = $employeeId ?? (Session::get('auth.employee_id') !== null ? (int) Session::get('auth.employee_id') : null);
        $where = $id !== null && $id > 0 ? 'e.id = :id' : 'u.id = :user_id';
        $bindings = $id !== null && $id > 0 ? ['id' => $id] : ['user_id' => (int) Session::get('auth.user_id', 0)];
        $row = $this->queryOne("SELECT e.id AS db_id, e.employee_code AS employee_id, CONCAT(e.first_name, ' ', e.last_name) AS name, e.employment_status, d.name AS department, jt.name AS role FROM employees e LEFT JOIN departments d ON d.id = e.department_id LEFT JOIN job_titles jt ON jt.id = e.job_title_id LEFT JOIN users u ON u.employee_id = e.id WHERE {$where} AND e.deleted_at IS NULL LIMIT 1", $bindings);
        return $row;
    }

    private function canViewAll(): bool
    {
        $roles = Session::get('auth.roles', []);
        return is_array($roles) && array_intersect($roles, ['Admin', 'Manager', 'Supervisor', 'Accountant']) !== [];
    }

    private function todayDuty(int $employeeId): ?array
    {
        return $this->queryOne("SELECT da.id AS duty_assignment_id, da.legacy_roster_assignment_id, da.employee_id, da.pump_id, da.shift_id, da.assignment_date, da.fuel_type, p.pump_code, p.pump_name, CONCAT(p.pump_code, ' - ', p.pump_name) AS pump, p.fuel_type_id, ft.short_name AS fuel_short_name, ft.name AS fuel_type_name, s.name AS shift_name, CONCAT(s.name, ' (', DATE_FORMAT(s.start_time, '%h:%i %p'), ' - ', DATE_FORMAT(s.end_time, '%h:%i %p'), ')') AS shift_label, DATE_FORMAT(s.start_time, '%h:%i %p') AS reporting, s.start_time, s.end_time, COALESCE(s.grace_period, 0) AS grace_period FROM duty_assignments da INNER JOIN pumps p ON p.id = da.pump_id AND p.deleted_at IS NULL AND p.status = 'active' INNER JOIN fuel_types ft ON ft.id = p.fuel_type_id INNER JOIN shifts s ON s.id = da.shift_id AND s.deleted_at IS NULL AND s.status = 'active' WHERE da.deleted_at IS NULL AND da.status = 'Assigned' AND da.employee_id = :employee_id AND da.assignment_date = :today LIMIT 1", ['employee_id' => $employeeId, 'today' => date('Y-m-d')]);
    }

    private function requireCurrentActiveEmployee(): array
    {
        $employee = $this->currentEmployee();
        if ($employee === null || ($employee['employment_status'] ?? '') !== 'active') {
            throw new RuntimeException('Only active employees can use attendance clocking.');
        }
        return $employee;
    }

    private function attendanceDuty(int $employeeId, string $role): ?array
    {
        $policy = new AttendanceDutyPolicyService();
        if ($policy->requiresManualDuty($role)) {
            return $this->todayDuty($employeeId);
        }
        return $policy->isAutomaticDutyRole($role) ? $policy->virtualDutyContext($role) : null;
    }

    private function requireAttendanceDuty(int $employeeId, string $role): array
    {
        $policy = new AttendanceDutyPolicyService();
        $duty = $this->attendanceDuty($employeeId, $role);
        if ($duty === null) {
            $message = $policy->requiresManualDuty($role)
                ? 'No duty assignment found for today.'
                : 'Your role is not configured for automatic or manual attendance duty.';
            throw new RuntimeException($message);
        }
        return $duty;
    }

    private function todayRecord(int $employeeId, ?int $shiftId): ?array
    {
        if ($shiftId === null) {
            return $this->queryOne('SELECT * FROM attendance WHERE employee_id = :employee_id AND shift_id IS NULL AND attendance_date = :today ORDER BY id DESC LIMIT 1', ['employee_id' => $employeeId, 'today' => date('Y-m-d')]);
        }
        return $this->queryOne('SELECT * FROM attendance WHERE employee_id = :employee_id AND shift_id = :shift_id AND attendance_date = :today LIMIT 1', ['employee_id' => $employeeId, 'shift_id' => $shiftId, 'today' => date('Y-m-d')]);
    }

    private function storePhoto(?array $file, string $type): string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Camera verification image is required.');
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Camera image could not be uploaded.');
        }
        if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new RuntimeException('Camera image must not exceed 5MB.');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensions[$mime])) {
            throw new RuntimeException('Camera image must be JPG, PNG, or WEBP.');
        }
        $dir = BASE_PATH . '/public/uploads/attendance/' . $type;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Attendance photo directory is not writable.');
        }
        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
        $target = $dir . '/' . $filename;
        if (!move_uploaded_file($tmp, $target)) {
            throw new RuntimeException('Unable to save camera image.');
        }
        // Future AI facial recognition integration: compare this captured image with the employee profile photo.
        return 'uploads/attendance/' . $type . '/' . $filename;
    }

    private function attendanceSelectSql(string $suffix): string
    {
        return "SELECT a.*, e.employee_code, CONCAT(e.first_name, ' ', e.last_name) AS employee_name, d.name AS department_name, COALESCE(a.role, jt.name) AS role_name, s.name AS shift_name FROM attendance a INNER JOIN employees e ON e.id = a.employee_id LEFT JOIN departments d ON d.id = e.department_id LEFT JOIN job_titles jt ON jt.id = e.job_title_id LEFT JOIN shifts s ON s.id = a.shift_id {$suffix}";
    }

    private function mapAttendanceRow(array $row): array
    {
        return [
            'date' => (string) $row['attendance_date'],
            'employee_id' => (string) $row['employee_code'],
            'name' => (string) $row['employee_name'],
            'id' => (int) $row['id'],
            'department' => (string) ($row['department_name'] ?? 'Unassigned'),
            'role' => (string) ($row['role_name'] ?? 'Unassigned'),
            'clock_in' => empty($row['clock_in']) ? '-' : date('h:i A', strtotime((string) $row['clock_in'])),
            'clock_out' => empty($row['clock_out']) ? '-' : date('h:i A', strtotime((string) $row['clock_out'])),
            'shift' => (string) ($row['shift_name'] ?? 'Unassigned'),
            'status' => (string) $row['attendance_status'],
            'remarks' => (string) ($row['remarks'] ?? ''),
            'late' => (int) ($row['lateness_minutes'] ?? 0),
            'overtime' => (int) ($row['overtime_minutes'] ?? 0),
            'verification_status' => (string) ($row['verification_status'] ?? 'Pending'),
            'photo_status' => !empty($row['clock_in_photo']) ? 'Captured' : 'Missing',
        ];
    }

    /** @return array{path: string, mime: string}|null */
    private function resolveAttendancePhoto(mixed $storedPath, string $type): ?array
    {
        $path = str_replace('\\', '/', trim((string) $storedPath));
        if ($path === '' || str_contains($path, "\0") || !in_array($type, ['clock-in', 'clock-out'], true)) {
            return null;
        }
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'public/')) {
            $path = substr($path, 7);
        }
        $pattern = '#^uploads/attendance/' . preg_quote($type, '#') . '/[A-Za-z0-9._-]+\.(?:jpe?g|png|webp)$#i';
        if (preg_match($pattern, $path) !== 1) {
            return null;
        }

        $publicRoot = realpath(BASE_PATH . '/public');
        $expectedDirectory = realpath(BASE_PATH . '/public/uploads/attendance/' . $type);
        if ($publicRoot === false || $expectedDirectory === false) {
            return null;
        }
        $candidate = realpath($publicRoot . '/' . $path);
        $directoryPrefix = rtrim($expectedDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (
            $candidate === false
            || !is_file($candidate)
            || !is_readable($candidate)
            || !str_starts_with($candidate, $directoryPrefix)
        ) {
            return null;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($candidate) ?: '';
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        return ['path' => $candidate, 'mime' => $mime];
    }

    private function addColumnIfMissing(string $table, string $column, string $sql): void
    {
        try {
            $exists = $this->queryOne(
                'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column LIMIT 1',
                ['table' => $table, 'column' => $column]
            );
            if ($exists === null) {
                $this->database()->execute($sql);
            }
        } catch (Throwable $exception) {
            error_log('[Attendance Schema] ' . $exception->getMessage());
        }
    }

    private function logActivity(string $activity, int $employeeId, mixed $oldValue, mixed $newValue): void
    {
        try {
            $request = Request::capture();
            $this->database()->insert('activity_logs', [
                'log_code' => 'ACT-' . date('YmdHis') . '-' . random_int(100, 999),
                'user_id' => Session::get('auth.user_id') !== null ? (int) Session::get('auth.user_id') : null,
                'employee_id' => $employeeId,
                'activity_type' => $activity,
                'module' => 'Attendance',
                'activity' => $activity,
                'entity_type' => 'attendance',
                'entity_id' => $employeeId,
                'old_value' => $oldValue === null ? null : json_encode($oldValue, JSON_THROW_ON_ERROR),
                'new_value' => json_encode($newValue, JSON_THROW_ON_ERROR),
                'status' => 'success',
                'ip_address' => $request->ip(),
                'browser' => $this->browserFromUserAgent($request->userAgent()),
                'notes' => 'Role: ' . (string) Session::get('auth.role', 'Employee'),
            ]);
        } catch (Throwable) {
        }
    }

    private function browserFromUserAgent(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Edg') => 'Edge',
            str_contains($userAgent, 'Firefox') => 'Firefox',
            str_contains($userAgent, 'Chrome') => 'Chrome',
            str_contains($userAgent, 'Safari') => 'Safari',
            default => 'Unknown',
        };
    }
}

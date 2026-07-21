<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Session;
use App\Services\AttendanceDutyPolicyService;
use RuntimeException;
use Throwable;

class DutyManagement extends BaseModel
{
    private const ROSTER_STATUSES = ['Draft', 'Published', 'Archived'];
    private const ASSIGNMENT_STATUSES = ['Assigned', 'Completed', 'Cancelled'];
    private const FUEL_TYPES = ['Petrol', 'Diesel', 'Gas'];

    public function boot(): void
    {
        $this->ensureSchema();
    }

    public function rosterList(array $filters = []): array
    {
        $this->boot();
        $clauses = ['dr.deleted_at IS NULL'];
        $bindings = [];

        if (trim((string) ($filters['search'] ?? '')) !== '') {
            $clauses[] = 'dr.roster_name LIKE :search';
            $bindings['search'] = '%' . trim((string) $filters['search']) . '%';
        }

        if (trim((string) ($filters['status'] ?? '')) !== '') {
            $clauses[] = 'dr.status = :status';
            $bindings['status'] = trim((string) $filters['status']);
        }

        $where = 'WHERE ' . implode(' AND ', $clauses);

        return $this->query(
            "SELECT dr.*, COUNT(da.id) AS total_assignments, COALESCE(u.username, 'System') AS created_by_name
             FROM duty_rosters dr
             LEFT JOIN duty_assignments da ON da.roster_id = dr.id AND da.deleted_at IS NULL
             LEFT JOIN users u ON u.id = dr.created_by
             {$where}
             GROUP BY dr.id
             ORDER BY dr.start_date DESC, dr.id DESC",
            $bindings
        );
    }

    public function assignments(array $filters = []): array
    {
        $this->boot();
        $clauses = ['da.deleted_at IS NULL'];
        $bindings = [];

        foreach (['status' => 'da.status', 'shift_id' => 'da.shift_id', 'pump_id' => 'da.pump_id'] as $key => $column) {
            if (trim((string) ($filters[$key] ?? '')) !== '') {
                $clauses[] = "{$column} = :{$key}";
                $bindings[$key] = $filters[$key];
            }
        }

        if (trim((string) ($filters['fuel_type'] ?? '')) !== '') {
            $clauses[] = 'da.fuel_type = :fuel_type';
            $bindings['fuel_type'] = $this->normalizeFuelType((string) $filters['fuel_type']);
        }

        foreach (['date' => '=', 'start_date' => '>=', 'end_date' => '<='] as $key => $operator) {
            if (trim((string) ($filters[$key] ?? '')) !== '') {
                $bind = $key === 'date' ? 'assignment_date' : $key;
                $clauses[] = 'da.assignment_date ' . $operator . ' :' . $bind;
                $bindings[$bind] = (string) $filters[$key];
            }
        }

        if (trim((string) ($filters['department'] ?? '')) !== '') {
            $clauses[] = 'd.name = :department';
            $bindings['department'] = (string) $filters['department'];
        }

        if (trim((string) ($filters['search'] ?? '')) !== '') {
            $clauses[] = "(CONCAT(e.first_name, ' ', e.last_name) LIKE :search OR e.employee_code LIKE :search OR p.pump_code LIKE :search OR dr.roster_name LIKE :search)";
            $bindings['search'] = '%' . trim((string) $filters['search']) . '%';
        }

        return array_map([$this, 'mapAssignmentRow'], $this->query(
            "SELECT da.*, dr.roster_name, dr.start_date AS roster_start_date, dr.end_date AS roster_end_date,
                    e.employee_code, e.first_name, e.last_name, d.name AS department_name, jt.name AS role_name,
                    p.pump_code, p.pump_name, ft.short_name AS fuel_short_name,
                    s.name AS shift_name, s.start_time, s.end_time,
                    CONCAT(sup.first_name, ' ', sup.last_name) AS supervisor_name
             FROM duty_assignments da
             INNER JOIN duty_rosters dr ON dr.id = da.roster_id AND dr.deleted_at IS NULL
             INNER JOIN employees e ON e.id = da.employee_id AND e.deleted_at IS NULL
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN job_titles jt ON jt.id = e.job_title_id
             INNER JOIN pumps p ON p.id = da.pump_id AND p.deleted_at IS NULL
             INNER JOIN fuel_types ft ON ft.id = p.fuel_type_id
             INNER JOIN shifts s ON s.id = da.shift_id AND s.deleted_at IS NULL
             LEFT JOIN employees sup ON sup.id = e.supervisor_id
             WHERE " . implode(' AND ', $clauses) . "
             ORDER BY da.assignment_date DESC, s.start_time ASC, p.pump_code ASC",
            $bindings
        ));
    }

    public function calendarEvents(array $filters = []): array
    {
        return array_map(static function (array $assignment): array {
            $color = match ($assignment['status']) {
                'Cancelled' => '#64748b',
                'Completed' => '#16a34a',
                default => $assignment['shift'] === 'Evening Shift' ? '#0ea5e9' : '#f68b34',
            };

            return [
                'title' => $assignment['employee'] . ' - ' . $assignment['pump'] . ' - ' . $assignment['shift'],
                'start' => $assignment['date'],
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => $assignment,
            ];
        }, $this->assignments($filters));
    }

    public function formOptions(): array
    {
        $this->boot();

        return [
            'employees' => $this->activePumpAttendants(),
            'pumps' => $this->activePumps(),
            'shifts' => $this->activeShifts(),
            'rosters' => $this->activeRosters(),
            'supervisors' => $this->supervisors(),
            'departments' => array_column($this->query("SELECT name FROM departments WHERE deleted_at IS NULL AND status = 'active' ORDER BY name"), 'name'),
            'roles' => array_column($this->query('SELECT name FROM job_titles WHERE deleted_at IS NULL ORDER BY name'), 'name'),
            'fuel_types' => self::FUEL_TYPES,
            'statuses' => self::ASSIGNMENT_STATUSES,
            'roster_statuses' => self::ROSTER_STATUSES,
        ];
    }

    public function stats(): array
    {
        $this->boot();
        $today = date('Y-m-d');
        [$manualRoleSql, $manualRoleBindings] = $this->manualDutyRoleSql('jt', 'stats_role');

        return [
            'total_rosters' => (int) $this->database()->value('SELECT COUNT(*) FROM duty_rosters WHERE deleted_at IS NULL'),
            'today_assignments' => (int) $this->database()->value('SELECT COUNT(*) FROM duty_assignments WHERE deleted_at IS NULL AND assignment_date = :today', ['today' => $today]),
            'morning_assignments' => (int) $this->database()->value("SELECT COUNT(*) FROM duty_assignments da INNER JOIN shifts s ON s.id = da.shift_id WHERE da.deleted_at IS NULL AND da.assignment_date = :today AND s.name LIKE 'Morning%'", ['today' => $today]),
            'evening_assignments' => (int) $this->database()->value("SELECT COUNT(*) FROM duty_assignments da INNER JOIN shifts s ON s.id = da.shift_id WHERE da.deleted_at IS NULL AND da.assignment_date = :today AND s.name LIKE 'Evening%'", ['today' => $today]),
            'available_employees' => (int) $this->database()->value("SELECT COUNT(*) FROM employees e INNER JOIN job_titles jt ON jt.id = e.job_title_id WHERE e.deleted_at IS NULL AND e.employment_status = 'active' AND {$manualRoleSql}", $manualRoleBindings),
            'available_pumps' => (int) $this->database()->value("SELECT COUNT(*) FROM pumps WHERE deleted_at IS NULL AND status = 'active'"),
            'inactive_pumps' => (int) $this->database()->value("SELECT COUNT(*) FROM pumps WHERE deleted_at IS NULL AND status <> 'active'"),
            'published_rosters' => (int) $this->database()->value("SELECT COUNT(*) FROM duty_rosters WHERE deleted_at IS NULL AND status = 'Published'"),
        ];
    }
    public function saveRoster(array $data, array $context = []): int
    {
        $payload = $this->validateRoster($data);
        $id = (int) ($data['roster_id'] ?? 0);

        return (int) $this->transaction(function (Database $database) use ($payload, $id, $context): int|string {
            if ($id > 0) {
                $existing = $this->findRoster($id);
                if ($existing === null) {
                    throw new RuntimeException('Duty roster record not found.');
                }

                $this->assertRosterNameUnique($payload['roster_name'], $id);
                $payload['updated_by'] = $this->currentUserId();
                $database->update('duty_rosters', $payload, ['id' => $id]);
                $this->logActivity('Roster Updated', 'duty_roster', $id, $existing, $payload, $context);

                return $id;
            }

            $this->assertRosterNameUnique($payload['roster_name']);
            $payload['created_by'] = $this->currentUserId();
            $newId = $database->insert('duty_rosters', $payload);
            $this->logActivity('Roster Created', 'duty_roster', (int) $newId, null, $payload, $context);

            return $newId;
        });
    }

    public function changeRosterStatus(int $id, string $status, array $context = []): void
    {
        if (!in_array($status, self::ROSTER_STATUSES, true)) {
            throw new RuntimeException('Invalid roster status.');
        }

        $existing = $this->findRoster($id);
        if ($existing === null) {
            throw new RuntimeException('Duty roster record not found.');
        }

        $this->database()->update('duty_rosters', ['status' => $status, 'updated_by' => $this->currentUserId()], ['id' => $id]);
        $this->logActivity('Roster ' . $status, 'duty_roster', $id, ['status' => $existing['status']], ['status' => $status], $context);
    }

    public function deleteRoster(int $id, array $context = []): void
    {
        $existing = $this->findRoster($id);
        if ($existing === null) {
            throw new RuntimeException('Duty roster record not found.');
        }

        $this->transaction(function (Database $database) use ($id, $existing, $context): void {
            $database->update('duty_assignments', ['deleted_at' => date('Y-m-d H:i:s')], ['roster_id' => $id]);
            $database->update('duty_rosters', ['deleted_at' => date('Y-m-d H:i:s'), 'updated_by' => $this->currentUserId()], ['id' => $id]);
            $this->logActivity('Roster Deleted', 'duty_roster', $id, $existing, ['deleted_at' => date('Y-m-d H:i:s')], $context);
        });
    }

    public function saveAssignment(array $data, array $context = []): int
    {
        $payload = $this->validateAssignment($data);
        $assignmentId = (int) ($data['assignment_id'] ?? 0);

        return (int) $this->transaction(function (Database $database) use ($payload, $assignmentId, $context): int|string {
            $existing = $assignmentId > 0 ? $this->findAssignment($assignmentId) : null;
            if ($assignmentId > 0 && $existing === null) {
                throw new RuntimeException('Duty assignment record not found.');
            }
            $this->assertAssignmentRules($payload, $assignmentId > 0 ? $assignmentId : null);
            if ($existing !== null) {
                $database->update('duty_assignments', $payload, ['id' => $assignmentId]);
                if (!empty($existing['legacy_roster_assignment_id'])) {
                    $database->update('roster_assignments', [
                        'roster_date' => $payload['assignment_date'], 'employee_id' => $payload['employee_id'],
                        'shift_id' => $payload['shift_id'], 'pump_id' => $payload['pump_id'],
                        'status' => 'scheduled', 'notes' => $payload['remarks'],
                    ], ['id' => (int) $existing['legacy_roster_assignment_id']]);
                }
                $this->logActivity('Duty Assignment Updated', 'duty_assignment', $assignmentId, $existing, $payload, $context);
                return $assignmentId;
            }
            $payload['created_by'] = $this->currentUserId();
            $id = $database->insert('duty_assignments', $payload);
            $this->mirrorRosterAssignment((int) $id, $payload);
            $this->logActivity('Duty Assigned', 'duty_assignment', (int) $id, null, $payload, $context);

            return $id;
        });
    }

    public function cancelAssignment(int $id, array $context = []): void
    {
        $existing = $this->findAssignment($id);
        if ($existing === null) {
            throw new RuntimeException('Duty assignment record not found.');
        }

        $this->database()->update('duty_assignments', ['status' => 'Cancelled'], ['id' => $id]);
        if (!empty($existing['legacy_roster_assignment_id'])) {
            $this->database()->update('roster_assignments', ['status' => 'cancelled'], ['id' => (int) $existing['legacy_roster_assignment_id']]);
        }
        $this->logActivity('Assignment Cancelled', 'duty_assignment', $id, ['status' => $existing['status']], ['status' => 'Cancelled'], $context);
    }

    public function deleteAssignment(int $id, array $context = []): void
    {
        $existing = $this->findAssignment($id);
        if ($existing === null) {
            throw new RuntimeException('Duty assignment record not found.');
        }

        $this->transaction(function (Database $database) use ($id, $existing, $context): void {
            $database->update('duty_assignments', ['deleted_at' => date('Y-m-d H:i:s')], ['id' => $id]);
            if (!empty($existing['legacy_roster_assignment_id'])) {
                $database->update('roster_assignments', ['deleted_at' => date('Y-m-d H:i:s')], ['id' => (int) $existing['legacy_roster_assignment_id']]);
            }
            $this->logActivity('Duty Deleted', 'duty_assignment', $id, $existing, ['deleted_at' => date('Y-m-d H:i:s')], $context);
        });
    }
    private function validateRoster(array $data): array
    {
        $name = trim((string) ($data['roster_name'] ?? ''));
        $start = trim((string) ($data['start_date'] ?? ''));
        $end = trim((string) ($data['end_date'] ?? ''));
        $status = trim((string) ($data['status'] ?? 'Draft'));

        if ($name === '') {
            throw new RuntimeException('Roster name is required.');
        }

        if (!$this->isDate($start) || !$this->isDate($end)) {
            throw new RuntimeException('Start date and end date are required.');
        }

        if ($end < $start) {
            throw new RuntimeException('End date cannot be earlier than start date.');
        }

        if (!in_array($status, self::ROSTER_STATUSES, true)) {
            throw new RuntimeException('Select a valid roster status.');
        }

        return ['roster_name' => $name, 'start_date' => $start, 'end_date' => $end, 'status' => $status];
    }

    private function validateAssignment(array $data): array
    {
        foreach (['roster_id', 'employee_id', 'pump_id', 'shift_id'] as $field) {
            if ((int) ($data[$field] ?? 0) <= 0) {
                throw new RuntimeException(ucwords(str_replace('_', ' ', $field)) . ' is required.');
            }
        }

        $date = trim((string) ($data['assignment_date'] ?? ''));
        if (!$this->isDate($date)) {
            throw new RuntimeException('Assignment date is required.');
        }

        $pump = $this->activePump((int) $data['pump_id']);
        if ($pump === null) {
            throw new RuntimeException('Inactive or invalid pumps cannot be assigned.');
        }

        return [
            'roster_id' => (int) $data['roster_id'],
            'employee_id' => (int) $data['employee_id'],
            'pump_id' => (int) $data['pump_id'],
            'fuel_type' => $this->normalizeFuelType((string) ($pump['fuel_name'] ?? $data['fuel_type'] ?? '')),
            'shift_id' => (int) $data['shift_id'],
            'assignment_date' => $date,
            'remarks' => trim((string) ($data['remarks'] ?? '')),
            'status' => 'Assigned',
        ];
    }

    private function assertAssignmentRules(array $payload, ?int $exceptId = null): void
    {
        $roster = $this->findRoster((int) $payload['roster_id']);
        if ($roster === null || $roster['status'] === 'Archived') {
            throw new RuntimeException('Select an active duty roster.');
        }

        if ($payload['assignment_date'] < $roster['start_date'] || $payload['assignment_date'] > $roster['end_date']) {
            throw new RuntimeException('Assignment date must be within the duty roster period.');
        }

        if ($this->activePumpAttendant((int) $payload['employee_id']) === null) {
            throw new RuntimeException('Inactive employees cannot be assigned.');
        }

        $shift = $this->activeShift((int) $payload['shift_id']);
        if ($shift === null) {
            throw new RuntimeException('Inactive shifts cannot be assigned.');
        }

        $exceptSql = $exceptId === null ? '' : ' AND id <> :except_id';
        $bindings = ['employee_id' => $payload['employee_id'], 'shift_id' => $payload['shift_id'], 'assignment_date' => $payload['assignment_date']];
        if ($exceptId !== null) { $bindings['except_id'] = $exceptId; }
        $employeeConflict = (int) $this->database()->value(
            "SELECT COUNT(*) FROM duty_assignments WHERE deleted_at IS NULL AND status <> 'Cancelled' AND employee_id = :employee_id AND shift_id = :shift_id AND assignment_date = :assignment_date" . $exceptSql,
            $bindings
        );
        if ($employeeConflict > 0) {
            throw new RuntimeException('Employee already has a duty assignment for this shift.');
        }

        $bindings = ['pump_id' => $payload['pump_id'], 'shift_id' => $payload['shift_id'], 'assignment_date' => $payload['assignment_date']];
        if ($exceptId !== null) { $bindings['except_id'] = $exceptId; }
        $pumpConflict = (int) $this->database()->value(
            "SELECT COUNT(*) FROM duty_assignments WHERE deleted_at IS NULL AND status <> 'Cancelled' AND pump_id = :pump_id AND shift_id = :shift_id AND assignment_date = :assignment_date" . $exceptSql,
            $bindings
        );
        if ($pumpConflict > 0) {
            throw new RuntimeException('This pump is already assigned for the selected shift.');
        }

        $bindings = ['shift_id' => $payload['shift_id'], 'assignment_date' => $payload['assignment_date']];
        if ($exceptId !== null) { $bindings['except_id'] = $exceptId; }
        $capacity = (int) $this->database()->value(
            "SELECT COUNT(*) FROM duty_assignments WHERE deleted_at IS NULL AND status <> 'Cancelled' AND shift_id = :shift_id AND assignment_date = :assignment_date" . $exceptSql,
            $bindings
        );
        if ($capacity >= (int) $shift['max_employees']) {
            throw new RuntimeException('Maximum number of employees has been reached for this shift.');
        }
    }

    private function activePumpAttendants(): array
    {
        [$roleSql, $bindings] = $this->manualDutyRoleSql();
        return $this->query("SELECT e.id AS db_id, e.employee_code AS id, CONCAT(e.first_name, ' ', e.last_name) AS name, d.name AS department, jt.name AS role FROM employees e INNER JOIN job_titles jt ON jt.id = e.job_title_id LEFT JOIN departments d ON d.id = e.department_id WHERE e.deleted_at IS NULL AND e.employment_status = 'active' AND {$roleSql} ORDER BY e.first_name, e.last_name", $bindings);
    }

    private function supervisors(): array
    {
        return $this->query("SELECT e.id AS db_id, e.employee_code AS id, CONCAT(e.first_name, ' ', e.last_name) AS name FROM employees e INNER JOIN job_titles jt ON jt.id = e.job_title_id WHERE e.deleted_at IS NULL AND e.employment_status = 'active' AND jt.name IN ('Supervisor', 'Manager') ORDER BY e.first_name, e.last_name");
    }

    private function activePumpAttendant(int $id): ?array
    {
        [$roleSql, $bindings] = $this->manualDutyRoleSql();
        $bindings['id'] = $id;
        return $this->queryOne("SELECT e.id FROM employees e INNER JOIN job_titles jt ON jt.id = e.job_title_id WHERE e.id = :id AND e.deleted_at IS NULL AND e.employment_status = 'active' AND {$roleSql} LIMIT 1", $bindings);
    }

    private function activePumps(): array
    {
        return $this->query("SELECT p.id, p.pump_code, p.pump_name, ft.name AS fuel_name, ft.short_name AS fuel_short_name FROM pumps p INNER JOIN fuel_types ft ON ft.id = p.fuel_type_id WHERE p.deleted_at IS NULL AND p.status = 'active' ORDER BY p.pump_code");
    }

    private function activePump(int $id): ?array
    {
        return $this->queryOne("SELECT p.id, ft.name AS fuel_name, ft.short_name AS fuel_short_name FROM pumps p INNER JOIN fuel_types ft ON ft.id = p.fuel_type_id WHERE p.id = :id AND p.deleted_at IS NULL AND p.status = 'active' LIMIT 1", ['id' => $id]);
    }

    /** @return array{0: string, 1: array<string, string>} */
    private function manualDutyRoleSql(string $alias = 'jt', string $prefix = 'manual_role'): array
    {
        $roles = (new AttendanceDutyPolicyService())->manualDutyRoles();
        if ($roles === []) {
            return ['1 = 0', []];
        }

        $bindings = [];
        $placeholders = [];
        foreach ($roles as $index => $role) {
            $key = $prefix . '_' . $index;
            $placeholders[] = ':' . $key;
            $bindings[$key] = $role;
        }

        return [$alias . '.name IN (' . implode(', ', $placeholders) . ')', $bindings];
    }

    private function activeShifts(): array
    {
        return $this->query("SELECT id, name, start_time, end_time, max_employees FROM shifts WHERE deleted_at IS NULL AND status = 'active' ORDER BY start_time");
    }

    private function activeShift(int $id): ?array
    {
        return $this->queryOne("SELECT id, name, start_time, end_time, max_employees FROM shifts WHERE id = :id AND deleted_at IS NULL AND status = 'active' LIMIT 1", ['id' => $id]);
    }

    private function activeRosters(): array
    {
        return $this->query("SELECT id, roster_name, start_date, end_date, status FROM duty_rosters WHERE deleted_at IS NULL AND status <> 'Archived' ORDER BY start_date DESC, id DESC");
    }

    private function findRoster(int $id): ?array
    {
        return $this->queryOne('SELECT * FROM duty_rosters WHERE id = :id AND deleted_at IS NULL LIMIT 1', ['id' => $id]);
    }

    private function findAssignment(int $id): ?array
    {
        return $this->queryOne('SELECT * FROM duty_assignments WHERE id = :id AND deleted_at IS NULL LIMIT 1', ['id' => $id]);
    }

    private function assertRosterNameUnique(string $name, ?int $exceptId = null): void
    {
        $bindings = ['name' => $name];
        $sql = 'SELECT COUNT(*) FROM duty_rosters WHERE roster_name = :name AND deleted_at IS NULL';
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $bindings['id'] = $exceptId;
        }

        if ((int) $this->database()->value($sql, $bindings) > 0) {
            throw new RuntimeException('Duty roster name already exists.');
        }
    }
    private function mirrorRosterAssignment(int $assignmentId, array $payload): void
    {
        try {
            $shift = $this->queryOne('SELECT start_time, end_time FROM shifts WHERE id = :id LIMIT 1', ['id' => $payload['shift_id']]);
            $legacyId = $this->database()->insert('roster_assignments', [
                'roster_date' => $payload['assignment_date'],
                'employee_id' => $payload['employee_id'],
                'shift_id' => $payload['shift_id'],
                'pump_id' => $payload['pump_id'],
                'supervisor_id' => null,
                'reporting_time' => $shift['start_time'] ?? '00:00:00',
                'closing_time' => $shift['end_time'] ?? '00:00:00',
                'status' => 'scheduled',
                'notes' => $payload['remarks'],
                'created_by' => $this->currentUserId(),
            ]);
            $this->database()->update('duty_assignments', ['legacy_roster_assignment_id' => (int) $legacyId], ['id' => $assignmentId]);
        } catch (Throwable) {
            // Legacy roster mirror supports older screens; duty_assignments remains the source of truth.
        }
    }

    private function mapAssignmentRow(array $row): array
    {
        $fuel = (string) $row['fuel_type'];
        $short = (string) ($row['fuel_short_name'] ?? '');

        return [
            'id' => (int) $row['id'],
            'roster_id' => (int) $row['roster_id'],
            'roster_name' => (string) $row['roster_name'],
            'date' => (string) $row['assignment_date'],
            'employee_id' => (string) $row['employee_code'],
            'employee_db_id' => (int) $row['employee_id'],
            'employee' => trim((string) $row['first_name'] . ' ' . (string) $row['last_name']),
            'department' => (string) ($row['department_name'] ?? 'Unassigned'),
            'role' => (string) ($row['role_name'] ?? 'Unassigned'),
            'shift' => (string) $row['shift_name'],
            'shift_id' => (int) $row['shift_id'],
            'pump' => trim((string) $row['pump_code'] . ' - ' . (string) $row['pump_name'], ' -'),
            'pump_id' => (int) $row['pump_id'],
            'fuel_type' => $short === '' ? $fuel : $fuel . ' (' . $short . ')',
            'fuel_type_key' => $fuel,
            'reporting' => date('h:i A', strtotime((string) $row['start_time'])),
            'closing' => date('h:i A', strtotime((string) $row['end_time'])),
            'supervisor' => trim((string) ($row['supervisor_name'] ?? '')) ?: 'N/A',
            'remarks' => (string) ($row['remarks'] ?? ''),
            'status' => (string) $row['status'],
        ];
    }

    private function normalizeFuelType(string $value): string
    {
        $value = strtolower($value);
        if (str_contains($value, 'petrol') || str_contains($value, 'pms')) {
            return 'Petrol';
        }
        if (str_contains($value, 'diesel') || str_contains($value, 'ago')) {
            return 'Diesel';
        }
        if (str_contains($value, 'gas') || str_contains($value, 'lpg')) {
            return 'Gas';
        }

        throw new RuntimeException('Select a valid fuel type.');
    }

    private function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value;
    }

    private function currentUserId(): ?int
    {
        $userId = Session::get('auth.user_id');
        return $userId === null ? null : (int) $userId;
    }

    private function logActivity(string $activity, string $entityType, int $entityId, mixed $oldValue, mixed $newValue, array $context = []): void
    {
        try {
            $this->database()->insert('activity_logs', [
                'log_code' => 'ACT-' . date('YmdHis') . '-' . random_int(100, 999),
                'user_id' => $this->currentUserId(),
                'employee_id' => null,
                'activity_type' => 'Duty Assignment',
                'module' => 'Duty Roster',
                'activity' => $activity,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'old_value' => $oldValue === null ? null : json_encode($oldValue, JSON_THROW_ON_ERROR),
                'new_value' => json_encode($newValue, JSON_THROW_ON_ERROR),
                'status' => 'success',
            ]);
        } catch (Throwable) {
            // Activity logging must not block duty scheduling operations.
        }
    }

    private function ensureSchema(): void
    {
        $this->database()->execute("CREATE TABLE IF NOT EXISTS duty_rosters (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, roster_name VARCHAR(150) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, status ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft', created_by BIGINT UNSIGNED NULL, updated_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, deleted_at TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (id), KEY idx_duty_rosters_status (status), KEY idx_duty_rosters_dates (start_date, end_date), CONSTRAINT fk_duty_rosters_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE, CONSTRAINT fk_duty_rosters_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->database()->execute("CREATE TABLE IF NOT EXISTS duty_assignments (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, roster_id BIGINT UNSIGNED NOT NULL, employee_id BIGINT UNSIGNED NOT NULL, pump_id BIGINT UNSIGNED NOT NULL, fuel_type ENUM('Petrol','Diesel','Gas') NOT NULL, shift_id BIGINT UNSIGNED NOT NULL, assignment_date DATE NOT NULL, remarks TEXT NULL, status ENUM('Assigned','Completed','Cancelled') NOT NULL DEFAULT 'Assigned', legacy_roster_assignment_id BIGINT UNSIGNED NULL, created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, deleted_at TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (id), KEY idx_duty_assignments_roster (roster_id), KEY idx_duty_assignments_employee_date (employee_id, assignment_date), KEY idx_duty_assignments_pump_date (pump_id, assignment_date), KEY idx_duty_assignments_shift_date (shift_id, assignment_date), KEY idx_duty_assignments_status (status), CONSTRAINT fk_duty_assignments_roster FOREIGN KEY (roster_id) REFERENCES duty_rosters(id) ON DELETE RESTRICT ON UPDATE CASCADE, CONSTRAINT fk_duty_assignments_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT ON UPDATE CASCADE, CONSTRAINT fk_duty_assignments_pump FOREIGN KEY (pump_id) REFERENCES pumps(id) ON DELETE RESTRICT ON UPDATE CASCADE, CONSTRAINT fk_duty_assignments_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE RESTRICT ON UPDATE CASCADE, CONSTRAINT fk_duty_assignments_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->addIndexIfMissing('duty_rosters', 'uq_duty_rosters_name_live', 'CREATE UNIQUE INDEX uq_duty_rosters_name_live ON duty_rosters (roster_name)');
    }

    private function addIndexIfMissing(string $table, string $index, string $sql): void
    {
        try {
            $exists = $this->queryOne('SHOW INDEX FROM ' . $table . ' WHERE Key_name = :index_name', ['index_name' => $index]);
            if ($exists === null) {
                $this->database()->execute($sql);
            }
        } catch (Throwable) {
            // Optional indexes should not block page rendering on restricted MySQL users.
        }
    }
}

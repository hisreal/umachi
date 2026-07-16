<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Session;
use Throwable;

class Shift extends BaseModel
{
    private const STATUS_LABELS = ['active' => 'Active', 'inactive' => 'Inactive'];

    public function boot(): void
    {
        $this->ensureSchema();
        $this->seedDefaults();
    }

    public function paginated(array $filters = []): array
    {
        $this->boot();
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;
        [$whereSql, $bindings] = $this->whereClause($filters);
        [$sort, $direction] = $this->sortClause((string) ($filters['sort'] ?? 'shift_name'), (string) ($filters['direction'] ?? 'asc'));

        $total = (int) $this->database()->value("SELECT COUNT(*) FROM shifts s {$whereSql}", $bindings);
        $rows = $this->query(
            "SELECT s.*, COUNT(ra.id) AS assigned_employees
             FROM shifts s
             LEFT JOIN roster_assignments ra ON ra.shift_id = s.id AND ra.deleted_at IS NULL
             {$whereSql}
             GROUP BY s.id
             ORDER BY {$sort} {$direction}, s.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return [
            'records' => array_map([$this, 'mapRow'], $rows),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => max(1, (int) ceil($total / $perPage)),
                'from' => $total === 0 ? 0 : $offset + 1,
                'to' => min($offset + $perPage, $total),
            ],
        ];
    }

    public function allForExport(array $filters = []): array
    {
        $this->boot();
        [$whereSql, $bindings] = $this->whereClause($filters);
        [$sort, $direction] = $this->sortClause((string) ($filters['sort'] ?? 'shift_name'), (string) ($filters['direction'] ?? 'asc'));

        return array_map([$this, 'mapRow'], $this->query(
            "SELECT s.*, COUNT(ra.id) AS assigned_employees
             FROM shifts s
             LEFT JOIN roster_assignments ra ON ra.shift_id = s.id AND ra.deleted_at IS NULL
             {$whereSql}
             GROUP BY s.id
             ORDER BY {$sort} {$direction}, s.id DESC",
            $bindings
        ));
    }

    public function findForView(int $id): ?array
    {
        $this->boot();
        $row = $this->queryOne(
            "SELECT s.*, COUNT(ra.id) AS assigned_employees
             FROM shifts s
             LEFT JOIN roster_assignments ra ON ra.shift_id = s.id AND ra.deleted_at IS NULL
             WHERE s.id = :id AND s.deleted_at IS NULL
             GROUP BY s.id
             LIMIT 1",
            ['id' => $id]
        );

        return $row === null ? null : $this->mapRow($row);
    }

    public function summary(): array
    {
        $this->boot();
        $counts = ['active' => 0, 'inactive' => 0];
        foreach ($this->query('SELECT status, COUNT(*) AS total FROM shifts WHERE deleted_at IS NULL GROUP BY status') as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }

        $assigned = (int) $this->database()->value('SELECT COUNT(*) FROM roster_assignments WHERE deleted_at IS NULL');
        $morning = (int) $this->database()->value(
            "SELECT COUNT(*) FROM roster_assignments ra INNER JOIN shifts s ON s.id = ra.shift_id WHERE ra.deleted_at IS NULL AND s.deleted_at IS NULL AND s.shift_code = 'MORNING'"
        );
        $evening = (int) $this->database()->value(
            "SELECT COUNT(*) FROM roster_assignments ra INNER JOIN shifts s ON s.id = ra.shift_id WHERE ra.deleted_at IS NULL AND s.deleted_at IS NULL AND s.shift_code = 'EVENING'"
        );

        return [
            'total' => $counts['active'] + $counts['inactive'],
            'active' => $counts['active'],
            'inactive' => $counts['inactive'],
            'assigned' => $assigned,
            'morning' => $morning,
            'evening' => $evening,
        ];
    }

    public function create(array $data, array $context = []): int
    {
        return (int) $this->transaction(function (Database $database) use ($data, $context): int|string {
            $payload = $this->payload($data);
            $payload['created_by'] = $this->currentUserId();
            $id = $database->insert('shifts', $payload);
            $this->logActivity('Shift Created', (int) $id, null, $payload, 'success', $context);

            return $id;
        });
    }

    public function updateShift(int $id, array $data, array $context = []): void
    {
        $existing = $this->findForView($id);
        if ($existing === null) {
            throw new \RuntimeException('Shift record not found.');
        }

        $this->transaction(function (Database $database) use ($id, $data, $existing, $context): void {
            $payload = $this->payload($data);
            $payload['updated_by'] = $this->currentUserId();
            $database->update('shifts', $payload, ['id' => $id]);
            $this->logActivity('Shift Updated', $id, $existing, $payload, 'success', $context);
        });
    }

    public function softDeleteShift(int $id, array $context = []): void
    {
        $existing = $this->findForView($id);
        if ($existing === null) {
            throw new \RuntimeException('Shift record not found.');
        }

        $this->transaction(function (Database $database) use ($id, $existing, $context): void {
            $database->update('shifts', ['deleted_at' => date('Y-m-d H:i:s')], ['id' => $id]);
            $this->logActivity('Shift Deleted', $id, $existing, ['deleted_at' => date('Y-m-d H:i:s')], 'success', $context);
        });
    }

    public function toggleStatus(int $id, array $context = []): void
    {
        $existing = $this->findForView($id);
        if ($existing === null) {
            throw new \RuntimeException('Shift record not found.');
        }

        $next = $existing['status_key'] === 'active' ? 'inactive' : 'active';
        $this->database()->update('shifts', ['status' => $next, 'updated_by' => $this->currentUserId()], ['id' => $id]);
        $this->logActivity($next === 'active' ? 'Shift Activated' : 'Shift Deactivated', $id, ['status' => $existing['status_key']], ['status' => $next], 'success', $context);
    }

    public function valueExists(string $field, string $value, ?int $exceptId = null): bool
    {
        $column = match ($field) {
            'shift_code' => 'shift_code',
            'shift_name' => 'name',
            default => throw new \RuntimeException('Unsupported duplicate validation field.'),
        };
        $bindings = ['value' => trim($value)];
        $sql = "SELECT COUNT(*) FROM shifts WHERE {$column} = :value";
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $bindings['id'] = $exceptId;
        }

        return (int) $this->database()->value($sql, $bindings) > 0;
    }

    public function overlaps(string $start, string $end, ?int $exceptId = null): bool
    {
        $bindings = ['start_time' => $start, 'end_time' => $end];
        $sql = "SELECT COUNT(*) FROM shifts
                WHERE deleted_at IS NULL AND status = 'active'
                AND start_time < :end_time AND end_time > :start_time";
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $bindings['id'] = $exceptId;
        }

        return (int) $this->database()->value($sql, $bindings) > 0;
    }

    private function payload(array $data): array
    {
        return [
            'shift_code' => strtoupper(trim((string) $data['shift_code'])),
            'name' => trim((string) $data['shift_name']),
            'start_time' => (string) $data['reporting_time'],
            'end_time' => (string) $data['closing_time'],
            'max_employees' => (int) $data['maximum_employees'],
            'grace_period' => (int) ($data['grace_period'] ?? 0),
            'status' => strtolower((string) $data['status']),
            'description' => trim((string) ($data['description'] ?? '')),
        ];
    }

    private function whereClause(array $filters): array
    {
        $clauses = ['s.deleted_at IS NULL'];
        $bindings = [];

        if (trim((string) ($filters['search'] ?? '')) !== '') {
            $clauses[] = '(s.name LIKE :search OR s.shift_code LIKE :search)';
            $bindings['search'] = '%' . trim((string) $filters['search']) . '%';
        }

        if (trim((string) ($filters['status'] ?? '')) !== '') {
            $clauses[] = 's.status = :status';
            $bindings['status'] = strtolower((string) $filters['status']);
        }

        if (trim((string) ($filters['reporting_time'] ?? '')) !== '') {
            $clauses[] = 's.start_time = :reporting_time';
            $bindings['reporting_time'] = (string) $filters['reporting_time'];
        }

        if (trim((string) ($filters['closing_time'] ?? '')) !== '') {
            $clauses[] = 's.end_time = :closing_time';
            $bindings['closing_time'] = (string) $filters['closing_time'];
        }

        return [' WHERE ' . implode(' AND ', $clauses), $bindings];
    }

    private function sortClause(string $sort, string $direction): array
    {
        $columns = [
            'shift_name' => 's.name',
            'reporting_time' => 's.start_time',
            'closing_time' => 's.end_time',
            'status' => 's.status',
        ];

        return [$columns[$sort] ?? $columns['shift_name'], strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC'];
    }

    private function mapRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'shift_code' => (string) ($row['shift_code'] ?? ''),
            'shift_name' => (string) $row['name'],
            'name' => (string) $row['name'],
            'reporting_time' => substr((string) $row['start_time'], 0, 5),
            'closing_time' => substr((string) $row['end_time'], 0, 5),
            'reporting' => date('h:i A', strtotime((string) $row['start_time'])),
            'closing' => date('h:i A', strtotime((string) $row['end_time'])),
            'maximum_employees' => (int) ($row['max_employees'] ?? 0),
            'max_employees' => (int) ($row['max_employees'] ?? 0),
            'grace_period' => (int) ($row['grace_period'] ?? 0),
            'status' => self::STATUS_LABELS[(string) $row['status']] ?? 'Inactive',
            'status_key' => (string) $row['status'],
            'description' => (string) ($row['description'] ?? ''),
            'assigned' => (int) ($row['assigned_employees'] ?? 0),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }

    private function ensureSchema(): void
    {
        $columns = array_column($this->query('SHOW COLUMNS FROM shifts'), 'Field');
        $addColumn = function (string $column, string $definition) use (&$columns): void {
            if (!in_array($column, $columns, true)) {
                $this->database()->execute("ALTER TABLE shifts ADD COLUMN {$definition}");
                $columns[] = $column;
            }
        };

        $addColumn('shift_code', "`shift_code` VARCHAR(20) NULL AFTER `id`");
        $addColumn('grace_period', "`grace_period` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `max_employees`");
        $addColumn('description', "`description` TEXT NULL AFTER `status`");
        $addColumn('created_by', "`created_by` BIGINT UNSIGNED NULL AFTER `description`");
        $addColumn('updated_by', "`updated_by` BIGINT UNSIGNED NULL AFTER `created_by`");

        $this->database()->execute("UPDATE shifts SET shift_code = UPPER(REPLACE(name, ' Shift', '')) WHERE shift_code IS NULL OR shift_code = ''");
        $this->addIndexIfMissing('shifts', 'uq_shifts_code', 'CREATE UNIQUE INDEX uq_shifts_code ON shifts (shift_code)');
        $this->addIndexIfMissing('shifts', 'idx_shifts_reporting_time', 'CREATE INDEX idx_shifts_reporting_time ON shifts (start_time)');
    }

    private function seedDefaults(): void
    {
        foreach ([
            ['shift_code' => 'MORNING', 'shift_name' => 'Morning Shift', 'reporting_time' => '06:00', 'closing_time' => '14:00', 'maximum_employees' => 10, 'grace_period' => 0, 'status' => 'Active', 'description' => 'Default morning station shift.'],
            ['shift_code' => 'EVENING', 'shift_name' => 'Evening Shift', 'reporting_time' => '14:00', 'closing_time' => '22:00', 'maximum_employees' => 10, 'grace_period' => 0, 'status' => 'Active', 'description' => 'Default evening station shift.'],
        ] as $shift) {
            if (!$this->valueExists('shift_code', $shift['shift_code'])) {
                $payload = $this->payload($shift);
                $payload['created_by'] = $this->currentUserId();
                $this->insert('shifts', $payload);
            }
        }
    }

    private function addIndexIfMissing(string $table, string $index, string $sql): void
    {
        $exists = $this->database()->value(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index_name',
            ['table' => $table, 'index_name' => $index]
        );

        if ((int) $exists === 0) {
            $this->database()->execute($sql);
        }
    }

    private function currentUserId(): ?int
    {
        $userId = Session::get('auth.user_id');
        if ($userId === null) {
            return null;
        }

        $exists = $this->database()->value('SELECT id FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1', ['id' => (int) $userId]);

        return $exists === null ? null : (int) $userId;
    }

    private function logActivity(string $activity, int $shiftId, mixed $oldValue, mixed $newValue, string $status, array $context = []): void
    {
        try {
            $this->insert('activity_logs', [
                'log_code' => 'ACT-' . date('YmdHis') . '-' . random_int(100, 999),
                'user_id' => $this->currentUserId(),
                'activity_type' => $activity,
                'module' => 'Shift Management',
                'activity' => $activity,
                'entity_type' => 'shift',
                'entity_id' => $shiftId,
                'old_value' => $oldValue === null ? null : json_encode($oldValue, JSON_THROW_ON_ERROR),
                'new_value' => json_encode($newValue, JSON_THROW_ON_ERROR),
                'ip_address' => $context['ip'] ?? null,
                'browser' => substr((string) ($context['user_agent'] ?? ''), 0, 120),
                'status' => $status,
            ]);
        } catch (Throwable) {
            // Audit logging must not block shift management operations.
        }
    }
}

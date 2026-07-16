<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Session;
use Throwable;

class Pump extends BaseModel
{
    private const FUEL_TYPES = [
        'Petrol' => ['name' => 'Petrol', 'short_name' => 'PMS'],
        'Diesel' => ['name' => 'Diesel', 'short_name' => 'AGO'],
        'Gas' => ['name' => 'Gas', 'short_name' => 'LPG'],
    ];

    private const STATUS_LABELS = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'under_maintenance' => 'Under Maintenance',
        'faulty' => 'Faulty',
    ];

    public function paginated(array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;
        [$whereSql, $bindings] = $this->buildListWhere($filters);
        [$sort, $direction] = $this->sortClause((string) ($filters['sort'] ?? 'pump_number'), (string) ($filters['direction'] ?? 'asc'));

        $total = (int) $this->database()->value(
            "SELECT COUNT(*) FROM pumps p INNER JOIN fuel_types ft ON ft.id = p.fuel_type_id {$whereSql}",
            $bindings
        );

        $rows = $this->query(
            "SELECT p.*, ft.name AS fuel_name, ft.short_name AS fuel_short_name
             FROM pumps p
             INNER JOIN fuel_types ft ON ft.id = p.fuel_type_id
             {$whereSql}
             ORDER BY {$sort} {$direction}, p.id DESC
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

    public function exportRows(array $filters = []): array
    {
        [$whereSql, $bindings] = $this->buildListWhere($filters);
        [$sort, $direction] = $this->sortClause((string) ($filters['sort'] ?? 'pump_number'), (string) ($filters['direction'] ?? 'asc'));

        return array_map([$this, 'mapRow'], $this->query(
            "SELECT p.*, ft.name AS fuel_name, ft.short_name AS fuel_short_name
             FROM pumps p
             INNER JOIN fuel_types ft ON ft.id = p.fuel_type_id
             {$whereSql}
             ORDER BY {$sort} {$direction}, p.id DESC",
            $bindings
        ));
    }

    public function summary(): array
    {
        $statusCounts = array_fill_keys(array_keys(self::STATUS_LABELS), 0);
        foreach ($this->query('SELECT status, COUNT(*) AS total FROM pumps WHERE deleted_at IS NULL GROUP BY status') as $row) {
            $statusCounts[(string) $row['status']] = (int) $row['total'];
        }

        $fuelCounts = ['Petrol' => 0, 'Diesel' => 0, 'Gas' => 0];
        foreach ($this->query(
            "SELECT ft.name, COUNT(*) AS total
             FROM pumps p
             INNER JOIN fuel_types ft ON ft.id = p.fuel_type_id
             WHERE p.deleted_at IS NULL
             GROUP BY ft.name"
        ) as $row) {
            $fuelCounts[(string) $row['name']] = (int) $row['total'];
        }

        $total = array_sum($statusCounts);

        return [
            'total' => $total,
            'active' => $statusCounts['active'],
            'inactive' => $statusCounts['inactive'],
            'maintenance' => $statusCounts['under_maintenance'],
            'faulty' => $statusCounts['faulty'],
            'petrol' => $fuelCounts['Petrol'],
            'diesel' => $fuelCounts['Diesel'],
            'gas' => $fuelCounts['Gas'],
        ];
    }

    public function filters(): array
    {
        return [
            'fuel_types' => ['Petrol (PMS)', 'Diesel (AGO)', 'Gas (LPG)'],
            'statuses' => array_values(self::STATUS_LABELS),
            'manufacturers' => array_values(array_filter(array_column($this->query(
                'SELECT DISTINCT manufacturer FROM pumps WHERE deleted_at IS NULL AND manufacturer IS NOT NULL ORDER BY manufacturer'
            ), 'manufacturer'))),
            'years' => array_values(array_filter(array_column($this->query(
                'SELECT DISTINCT YEAR(installation_date) AS year FROM pumps WHERE deleted_at IS NULL AND installation_date IS NOT NULL ORDER BY year DESC'
            ), 'year'))),
        ];
    }

    public function findForView(int $id): ?array
    {
        $row = $this->queryOne(
            "SELECT p.*, ft.name AS fuel_name, ft.short_name AS fuel_short_name
             FROM pumps p
             INNER JOIN fuel_types ft ON ft.id = p.fuel_type_id
             WHERE p.id = :id AND p.deleted_at IS NULL
             LIMIT 1",
            ['id' => $id]
        );

        return $row === null ? null : $this->mapRow($row);
    }

    public function create(array $data, array $context = []): int
    {
        return (int) $this->transaction(function (Database $database) use ($data, $context): int|string {
            $payload = $this->payload($data);
            $payload['created_by'] = $this->columnExists('pumps', 'created_by') ? $this->currentUserId() : null;
            if (!$this->columnExists('pumps', 'created_by')) {
                unset($payload['created_by']);
            }

            $id = $database->insert('pumps', $payload);
            $this->logActivity('Pump Created', (int) $id, null, $payload, 'success', $context);

            return $id;
        });
    }

    public function updatePump(int $id, array $data, array $context = []): void
    {
        $existing = $this->findForView($id);
        if ($existing === null) {
            throw new \RuntimeException('Pump record not found.');
        }

        $this->transaction(function (Database $database) use ($id, $data, $existing, $context): void {
            $payload = $this->payload($data);
            if ($this->columnExists('pumps', 'updated_by')) {
                $payload['updated_by'] = $this->currentUserId();
            }
            $database->update('pumps', $payload, ['id' => $id]);
            $this->logActivity('Pump Updated', $id, $existing, $payload, 'success', $context);
        });
    }

    public function softDeletePump(int $id, array $context = []): void
    {
        $existing = $this->findForView($id);
        if ($existing === null) {
            throw new \RuntimeException('Pump record not found.');
        }

        $this->transaction(function (Database $database) use ($id, $existing, $context): void {
            $database->update('pumps', ['deleted_at' => date('Y-m-d H:i:s')], ['id' => $id]);
            $this->logActivity('Pump Deleted', $id, $existing, ['deleted_at' => date('Y-m-d H:i:s')], 'success', $context);
        });
    }

    public function toggleStatus(int $id, array $context = []): void
    {
        $existing = $this->findForView($id);
        if ($existing === null) {
            throw new \RuntimeException('Pump record not found.');
        }

        $next = $existing['status_key'] === 'active' ? 'inactive' : 'active';
        $this->database()->update('pumps', ['status' => $next], ['id' => $id]);
        $this->logActivity('Pump Status Changed', $id, ['status' => $existing['status_key']], ['status' => $next], 'success', $context);
    }

    public function valueExists(string $field, string $value, ?int $exceptId = null): bool
    {
        $column = match ($field) {
            'pump_number' => 'pump_code',
            'serial_number' => 'serial_number',
            default => throw new \RuntimeException('Unsupported pump duplicate validation field.'),
        };

        $bindings = ['value' => trim($value)];
        $sql = "SELECT COUNT(*) FROM pumps WHERE {$column} = :value";
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $bindings['id'] = $exceptId;
        }

        return (int) $this->database()->value($sql, $bindings) > 0;
    }

    private function payload(array $data): array
    {
        return [
            'pump_code' => trim((string) $data['pump_number']),
            'pump_name' => trim((string) $data['pump_name']),
            'fuel_type_id' => $this->resolveFuelType((string) $data['fuel_type']),
            'current_meter_reading' => (float) $data['meter'],
            'manufacturer' => trim((string) $data['manufacturer']),
            'model' => trim((string) ($data['model'] ?? '')),
            'serial_number' => trim((string) $data['serial_number']),
            'installation_date' => $data['installation_date'],
            'status' => $this->statusKey((string) $data['status']),
            'notes' => trim((string) ($data['notes'] ?? '')),
        ];
    }

    private function resolveFuelType(string $label): int
    {
        $name = $this->fuelName($label);
        $meta = self::FUEL_TYPES[$name] ?? null;
        if ($meta === null) {
            throw new \RuntimeException('Select a valid fuel type.');
        }

        $row = $this->queryOne('SELECT id FROM fuel_types WHERE name = :name AND deleted_at IS NULL LIMIT 1', ['name' => $meta['name']]);
        if ($row !== null) {
            return (int) $row['id'];
        }

        return (int) $this->insert('fuel_types', [
            'name' => $meta['name'],
            'short_name' => $meta['short_name'],
            'unit' => 'litre',
            'status' => 'active',
        ]);
    }

    private function buildListWhere(array $filters): array
    {
        $clauses = ['p.deleted_at IS NULL'];
        $bindings = [];

        if (trim((string) ($filters['search'] ?? '')) !== '') {
            $clauses[] = '(p.pump_code LIKE :search OR p.pump_name LIKE :search OR p.manufacturer LIKE :search OR p.serial_number LIKE :search)';
            $bindings['search'] = '%' . trim((string) $filters['search']) . '%';
        }

        if (trim((string) ($filters['fuel_type'] ?? '')) !== '') {
            $clauses[] = 'ft.name = :fuel_type';
            $bindings['fuel_type'] = $this->fuelName((string) $filters['fuel_type']);
        }

        if (trim((string) ($filters['status'] ?? '')) !== '') {
            $clauses[] = 'p.status = :status';
            $bindings['status'] = $this->statusKey((string) $filters['status']);
        }

        if (trim((string) ($filters['manufacturer'] ?? '')) !== '') {
            $clauses[] = 'p.manufacturer = :manufacturer';
            $bindings['manufacturer'] = trim((string) $filters['manufacturer']);
        }

        if (trim((string) ($filters['year'] ?? '')) !== '') {
            $clauses[] = 'YEAR(p.installation_date) = :year';
            $bindings['year'] = (int) $filters['year'];
        }

        return [' WHERE ' . implode(' AND ', $clauses), $bindings];
    }

    private function sortClause(string $sort, string $direction): array
    {
        $columns = [
            'pump_number' => 'p.pump_code',
            'installation_date' => 'p.installation_date',
            'fuel_type' => 'ft.name',
            'status' => 'p.status',
            'manufacturer' => 'p.manufacturer',
        ];

        return [$columns[$sort] ?? $columns['pump_number'], strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC'];
    }

    private function mapRow(array $row): array
    {
        $fuel = (string) $row['fuel_name'];
        $short = (string) ($row['fuel_short_name'] ?? '');
        $status = (string) $row['status'];

        return [
            'id' => (int) $row['id'],
            'pump_number' => (string) $row['pump_code'],
            'pump_name' => (string) $row['pump_name'],
            'fuel_type' => $short === '' ? $fuel : $fuel . ' (' . $short . ')',
            'fuel_type_name' => $fuel,
            'status' => self::STATUS_LABELS[$status] ?? ucwords(str_replace('_', ' ', $status)),
            'status_key' => $status,
            'meter' => (float) $row['current_meter_reading'],
            'last_updated' => date('Y-m-d h:i A', strtotime((string) $row['updated_at'])),
            'manufacturer' => (string) ($row['manufacturer'] ?? ''),
            'model' => (string) ($row['model'] ?? ''),
            'serial_number' => (string) ($row['serial_number'] ?? ''),
            'installation_date' => (string) ($row['installation_date'] ?? ''),
            'notes' => (string) ($row['notes'] ?? ''),
        ];
    }

    private function fuelName(string $value): string
    {
        $value = trim($value);
        if (str_contains($value, '(')) {
            $value = trim((string) strtok($value, '('));
        }

        return $value;
    }

    private function statusKey(string $value): string
    {
        $normalized = strtolower(str_replace([' ', '-'], '_', trim($value)));
        if ($normalized === 'maintenance') {
            $normalized = 'under_maintenance';
        }

        if (!array_key_exists($normalized, self::STATUS_LABELS)) {
            throw new \RuntimeException('Select a valid pump status.');
        }

        return $normalized;
    }

    private function columnExists(string $table, string $column): bool
    {
        return $this->database()->value(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column',
            ['table' => $table, 'column' => $column]
        ) > 0;
    }

    private function currentUserId(): ?int
    {
        $userId = Session::get('auth.user_id');
        if ($userId === null) {
            return null;
        }

        $exists = $this->database()->value(
            'SELECT id FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            ['id' => (int) $userId]
        );

        return $exists === null ? null : (int) $userId;
    }

    private function logActivity(string $activity, int $pumpId, mixed $oldValue, mixed $newValue, string $status, array $context = []): void
    {
        try {
            $this->insert('activity_logs', [
                'log_code' => 'ACT-' . date('YmdHis') . '-' . random_int(100, 999),
                'user_id' => $this->currentUserId(),
                'activity_type' => $activity,
                'module' => 'Pump Management',
                'activity' => $activity,
                'entity_type' => 'pump',
                'entity_id' => $pumpId,
                'old_value' => $oldValue === null ? null : json_encode($oldValue, JSON_THROW_ON_ERROR),
                'new_value' => json_encode($newValue, JSON_THROW_ON_ERROR),
                'ip_address' => $context['ip'] ?? null,
                'browser' => substr((string) ($context['user_agent'] ?? ''), 0, 120),
                'status' => $status,
            ]);
        } catch (Throwable) {
            // Audit logging must not block pump management operations.
        }
    }
}

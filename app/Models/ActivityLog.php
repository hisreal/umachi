<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ActivityLog
{
    public function __construct(private ?Database $database = null)
    {
        $this->database ??= Database::getInstance();
    }

    public function page(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 20)));
        [$where, $bindings] = $this->where($filters);
        $sort = $this->sortExpression((string) ($filters['sort'] ?? 'date'));
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $from = $this->fromSql();
        $total = (int) $this->database->value("SELECT COUNT(*) {$from} {$where}", $bindings);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;
        $bindings['limit'] = $perPage;
        $bindings['offset'] = $offset;

        $rows = $this->database->select(
            "SELECT al.id, al.log_code, al.user_id, al.employee_id,
                    COALESCE(NULLIF(al.employee_name, ''), NULLIF(TRIM(CONCAT_WS(' ', e.first_name, e.last_name)), ''), u.username, 'System') AS employee_name,
                    COALESCE(e.employee_code, CASE WHEN al.employee_id IS NULL THEN 'N/A' ELSE CAST(al.employee_id AS CHAR) END) AS employee_code,
                    COALESCE(NULLIF(al.role, ''), rr.roles, 'System') AS role,
                    COALESCE(NULLIF(al.action, ''), al.activity_type) AS action,
                    al.module,
                    COALESCE(NULLIF(al.description, ''), al.activity) AS description,
                    al.ip_address, al.browser, al.operating_system, al.device_type,
                    al.request_method, al.request_url, al.old_value, al.new_value,
                    al.status, al.notes, al.created_at
             {$from} {$where}
             ORDER BY {$sort} {$direction}, al.id DESC
             LIMIT :limit OFFSET :offset",
            $bindings
        );

        return [
            'items' => array_map([$this, 'format'], $rows),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $pages,
            'from' => $total === 0 ? 0 : $offset + 1,
            'to' => min($offset + $perPage, $total),
        ];
    }

    public function stats(): array
    {
        $row = $this->database->selectOne(
            "SELECT COUNT(*) AS total,
                    SUM(DATE(created_at) = CURRENT_DATE) AS today,
                    SUM(LOWER(status) = 'failed') AS failed,
                    SUM(LOWER(status) = 'warning') AS warnings,
                    SUM(COALESCE(NULLIF(action, ''), activity_type) = 'Login Successful') AS successful_logins,
                    SUM(COALESCE(NULLIF(action, ''), activity_type) = 'Employee Updated') AS employee_updates
             FROM activity_logs WHERE archived_at IS NULL"
        ) ?? [];

        return [
            ['label' => 'Total Activities', 'value' => number_format((int) ($row['total'] ?? 0)), 'icon' => 'fa-solid fa-list-check', 'tone' => 'primary'],
            ['label' => "Today's Activities", 'value' => number_format((int) ($row['today'] ?? 0)), 'icon' => 'fa-solid fa-calendar-day', 'tone' => 'info'],
            ['label' => 'Failed Activities', 'value' => number_format((int) ($row['failed'] ?? 0)), 'icon' => 'fa-solid fa-triangle-exclamation', 'tone' => 'danger'],
            ['label' => 'Warnings', 'value' => number_format((int) ($row['warnings'] ?? 0)), 'icon' => 'fa-solid fa-shield-halved', 'tone' => 'warning'],
            ['label' => 'Successful Logins', 'value' => number_format((int) ($row['successful_logins'] ?? 0)), 'icon' => 'fa-solid fa-right-to-bracket', 'tone' => 'success'],
            ['label' => 'Employee Updates', 'value' => number_format((int) ($row['employee_updates'] ?? 0)), 'icon' => 'fa-solid fa-user-pen', 'tone' => 'orange'],
        ];
    }

    public function options(): array
    {
        return [
            'employees' => $this->database->select(
                "SELECT DISTINCT COALESCE(NULLIF(al.employee_name, ''), NULLIF(TRIM(CONCAT_WS(' ', e.first_name, e.last_name)), ''), u.username, 'System') AS value
                 {$this->fromSql()} WHERE al.archived_at IS NULL ORDER BY value"
            ),
            'roles' => $this->distinct('COALESCE(NULLIF(al.role, \'\'), rr.roles, \'System\')'),
            'modules' => $this->distinct('al.module'),
            'actions' => $this->distinct('COALESCE(NULLIF(al.action, \'\'), al.activity_type)'),
            'statuses' => $this->distinct('al.status'),
        ];
    }

    private function where(array $filters): array
    {
        $clauses = ['al.archived_at IS NULL'];
        $bindings = [];
        $map = [
            'employee' => "COALESCE(NULLIF(al.employee_name, ''), NULLIF(TRIM(CONCAT_WS(' ', e.first_name, e.last_name)), ''), u.username, 'System')",
            'role' => "COALESCE(NULLIF(al.role, ''), rr.roles, 'System')",
            'module' => 'al.module',
            'action' => "COALESCE(NULLIF(al.action, ''), al.activity_type)",
            'status' => 'LOWER(al.status)',
        ];
        foreach ($map as $key => $expression) {
            $value = trim((string) ($filters[$key] ?? ''));
            if ($value !== '') {
                $clauses[] = "{$expression} = :{$key}";
                $bindings[$key] = $key === 'status' ? strtolower($value) : $value;
            }
        }
        if (($from = trim((string) ($filters['date_from'] ?? ''))) !== '') {
            $clauses[] = 'al.created_at >= :date_from';
            $bindings['date_from'] = $from . ' 00:00:00';
        }
        if (($to = trim((string) ($filters['date_to'] ?? ''))) !== '') {
            $clauses[] = 'al.created_at <= :date_to';
            $bindings['date_to'] = $to . ' 23:59:59';
        }
        if (($search = trim((string) ($filters['search'] ?? ''))) !== '') {
            $clauses[] = "(COALESCE(al.employee_name, CONCAT_WS(' ', e.first_name, e.last_name), u.username, '') LIKE :search_employee
                OR COALESCE(e.employee_code, '') LIKE :search_code
                OR al.module LIKE :search_module
                OR COALESCE(al.action, al.activity_type) LIKE :search_action
                OR COALESCE(al.description, al.activity) LIKE :search_description
                OR COALESCE(al.ip_address, '') LIKE :search_ip)";
            foreach (['employee', 'code', 'module', 'action', 'description', 'ip'] as $field) {
                $bindings['search_' . $field] = '%' . $search . '%';
            }
        }

        return ['WHERE ' . implode(' AND ', $clauses), $bindings];
    }

    private function fromSql(): string
    {
        return "FROM activity_logs al
            LEFT JOIN users u ON u.id = al.user_id
            LEFT JOIN employees e ON e.id = COALESCE(al.employee_id, u.employee_id)
            LEFT JOIN (
                SELECT ur.user_id, GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', ') AS roles
                FROM user_roles ur INNER JOIN roles r ON r.id = ur.role_id
                GROUP BY ur.user_id
            ) rr ON rr.user_id = al.user_id";
    }

    private function distinct(string $expression): array
    {
        return $this->database->select(
            "SELECT DISTINCT {$expression} AS value {$this->fromSql()}
             WHERE al.archived_at IS NULL AND {$expression} IS NOT NULL AND {$expression} <> ''
             ORDER BY value"
        );
    }

    private function sortExpression(string $sort): string
    {
        return match ($sort) {
            'employee' => 'employee_name',
            'role' => 'role',
            'module' => 'al.module',
            'action' => 'action',
            default => 'al.created_at',
        };
    }

    private function format(array $row): array
    {
        $status = ucfirst(strtolower((string) ($row['status'] ?? 'information')));
        return [
            'id' => (string) ($row['log_code'] ?: $row['id']),
            'database_id' => (int) $row['id'],
            'datetime' => date('Y-m-d h:i A', strtotime((string) $row['created_at'])),
            'timestamp' => (string) $row['created_at'],
            'user' => (string) $row['employee_name'],
            'employee_id' => (string) $row['employee_code'],
            'role' => (string) $row['role'],
            'activity' => (string) $row['description'],
            'action' => (string) $row['action'],
            'type' => (string) $row['action'],
            'module' => (string) $row['module'],
            'ip' => (string) ($row['ip_address'] ?: 'N/A'),
            'browser' => (string) ($row['browser'] ?: 'Unknown'),
            'os' => (string) ($row['operating_system'] ?: 'Unknown'),
            'device' => (string) ($row['device_type'] ?: 'Unknown'),
            'method' => (string) ($row['request_method'] ?: 'N/A'),
            'url' => (string) ($row['request_url'] ?: 'N/A'),
            'old_value' => $this->displayJson($row['old_value'] ?? null),
            'new_value' => $this->displayJson($row['new_value'] ?? null),
            'status' => $status,
            'notes' => (string) ($row['notes'] ?: ''),
        ];
    }

    private function displayJson(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }
        $decoded = json_decode((string) $value, true);
        return json_last_error() === JSON_ERROR_NONE
            ? (string) json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : (string) $value;
    }
}

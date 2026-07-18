<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use Throwable;

final class ActivityLogService
{
    public function __construct(private ?Database $database = null)
    {
        $this->database ??= Database::getInstance();
    }

    public function record(
        string $action,
        string $module,
        string $description,
        array $context = [],
        string $status = 'success',
        mixed $oldValue = null,
        mixed $newValue = null,
        ?Request $request = null
    ): ?int {
        try {
            $request ??= Request::capture();
            $identity = $this->identity($context);
            $agent = $this->parseUserAgent($request->userAgent());
            $normalizedStatus = $this->normalizeStatus($status);

            return (int) $this->database->insert('activity_logs', [
                'log_code' => $this->logCode(),
                'user_id' => $identity['user_id'],
                'employee_id' => $identity['employee_id'],
                'employee_name' => $identity['employee_name'],
                'role' => $identity['role'],
                'activity_type' => $action,
                'action' => $action,
                'module' => $module,
                'activity' => $description,
                'description' => $description,
                'entity_type' => $context['entity_type'] ?? null,
                'entity_id' => isset($context['entity_id']) ? (int) $context['entity_id'] : null,
                'old_value' => $this->json($oldValue),
                'new_value' => $this->json($newValue),
                'ip_address' => substr($request->ip(), 0, 45),
                'user_agent' => substr($request->userAgent(), 0, 500),
                'browser' => $agent['browser'],
                'operating_system' => $agent['operating_system'],
                'device_type' => $agent['device_type'],
                'request_method' => substr($request->method(), 0, 10),
                'request_url' => substr((string) ($_SERVER['REQUEST_URI'] ?? $request->route()), 0, 500),
                'status' => $normalizedStatus,
                'notes' => isset($context['notes']) ? (string) $context['notes'] : null,
            ]);
        } catch (Throwable $exception) {
            error_log('[Activity Log] ' . $exception->getMessage());
            return null;
        }
    }

    public function enrichLegacyPayload(array $data, ?Request $request = null): array
    {
        $request ??= Request::capture();
        $identity = $this->identity($data);
        $agent = $this->parseUserAgent($request->userAgent());
        $data['log_code'] = trim((string) ($data['log_code'] ?? '')) !== '' ? $data['log_code'] : $this->logCode();
        $data['user_id'] = isset($data['user_id']) && (int) $data['user_id'] > 0 ? (int) $data['user_id'] : $identity['user_id'];
        $data['employee_id'] = isset($data['employee_id']) && (int) $data['employee_id'] > 0 ? (int) $data['employee_id'] : $identity['employee_id'];
        $data['employee_name'] = trim((string) ($data['employee_name'] ?? '')) !== '' ? $data['employee_name'] : $identity['employee_name'];
        $data['role'] = trim((string) ($data['role'] ?? '')) !== '' ? $data['role'] : $identity['role'];
        $data['activity_type'] = trim((string) ($data['activity_type'] ?? $data['action'] ?? 'System Activity'));
        $data['action'] = trim((string) ($data['action'] ?? $data['activity_type']));
        $data['module'] = trim((string) ($data['module'] ?? 'System'));
        $data['activity'] = trim((string) ($data['activity'] ?? $data['description'] ?? $data['action']));
        $data['description'] = trim((string) ($data['description'] ?? $data['activity']));
        $data['ip_address'] = substr((string) ($data['ip_address'] ?? $request->ip()), 0, 45);
        $data['user_agent'] = substr((string) ($data['user_agent'] ?? $request->userAgent()), 0, 500);
        $data['browser'] = trim((string) ($data['browser'] ?? '')) !== '' ? $data['browser'] : $agent['browser'];
        $data['operating_system'] = trim((string) ($data['operating_system'] ?? '')) !== '' ? $data['operating_system'] : $agent['operating_system'];
        $data['device_type'] = trim((string) ($data['device_type'] ?? '')) !== '' ? $data['device_type'] : $agent['device_type'];
        $data['request_method'] = substr((string) ($data['request_method'] ?? $request->method()), 0, 10);
        $data['request_url'] = substr((string) ($data['request_url'] ?? ($_SERVER['REQUEST_URI'] ?? $request->route())), 0, 500);
        $data['status'] = $this->normalizeStatus((string) ($data['status'] ?? 'information'));

        foreach (['old_value', 'new_value'] as $column) {
            if (isset($data[$column]) && (is_array($data[$column]) || is_object($data[$column]))) {
                $data[$column] = $this->json($data[$column]);
            }
        }

        return $data;
    }

    public function archiveOlderThan(?int $months = null): int
    {
        $months ??= $this->retentionMonths();
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$months} months"));

        return $this->database->execute(
            'UPDATE activity_logs SET archived_at = CURRENT_TIMESTAMP
             WHERE archived_at IS NULL AND created_at < :cutoff',
            ['cutoff' => $cutoff]
        );
    }

    public function purgeArchivedBefore(string $cutoff): int
    {
        return $this->database->execute(
            'DELETE FROM activity_logs WHERE archived_at IS NOT NULL AND archived_at < :cutoff',
            ['cutoff' => $cutoff]
        );
    }

    public function retentionMonths(): int
    {
        $config = CONFIG_PATH . '/activity_log.php';
        $settings = is_file($config) ? require $config : [];

        return max(1, (int) ($settings['retention_months'] ?? 12));
    }

    /** @return array{user_id: ?int, employee_id: ?int, employee_name: string, role: string} */
    private function identity(array $context): array
    {
        $sessionUser = Session::get('auth.user', []);
        $sessionUser = is_array($sessionUser) ? $sessionUser : [];
        $userId = isset($context['user_id'])
            ? (int) $context['user_id']
            : (int) Session::get('auth.user_id', 0);
        $employeeId = isset($context['employee_id'])
            ? (int) $context['employee_id']
            : (int) Session::get('auth.employee_id', 0);

        return [
            'user_id' => $userId > 0 ? $userId : null,
            'employee_id' => $employeeId > 0 ? $employeeId : null,
            'employee_name' => trim((string) ($context['employee_name'] ?? $sessionUser['name'] ?? 'System')),
            'role' => trim((string) ($context['role'] ?? Session::get('auth.role', 'System'))),
        ];
    }

    /** @return array{browser: string, operating_system: string, device_type: string} */
    private function parseUserAgent(string $userAgent): array
    {
        $browser = match (true) {
            preg_match('/Edg\/([\d.]+)/', $userAgent, $match) === 1 => 'Edge ' . $match[1],
            preg_match('/Firefox\/([\d.]+)/', $userAgent, $match) === 1 => 'Firefox ' . $match[1],
            preg_match('/Chrome\/([\d.]+)/', $userAgent, $match) === 1 => 'Chrome ' . $match[1],
            preg_match('/Version\/([\d.]+).*Safari/', $userAgent, $match) === 1 => 'Safari ' . $match[1],
            default => 'Unknown',
        };
        $operatingSystem = match (true) {
            str_contains($userAgent, 'Windows NT 10.0') => 'Windows 10/11',
            preg_match('/Android ([\d.]+)/', $userAgent, $match) === 1 => 'Android ' . $match[1],
            preg_match('/(?:iPhone )?OS ([\d_]+)/', $userAgent, $match) === 1 => 'iOS ' . str_replace('_', '.', $match[1]),
            str_contains($userAgent, 'Mac OS X') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown',
        };
        $device = match (true) {
            preg_match('/iPad|Tablet/i', $userAgent) === 1 => 'Tablet',
            preg_match('/Mobile|Android|iPhone/i', $userAgent) === 1 => 'Mobile',
            default => 'Desktop',
        };

        return ['browser' => $browser, 'operating_system' => $operatingSystem, 'device_type' => $device];
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        return in_array($status, ['success', 'failed', 'warning', 'information'], true)
            ? $status
            : 'information';
    }

    private function json(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function logCode(): string
    {
        return 'ACT-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
    }
}

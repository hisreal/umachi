<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Session;
use Throwable;

class Employee extends BaseModel
{
    public function directory(): array
    {
        $rows = $this->query(
            "SELECT e.*, d.name AS department_name, jt.name AS job_title_name, u.id AS user_id, u.username, u.account_status,
                    ba.bank_name, ba.account_name, ba.account_number,
                    ec.contact_name AS emergency_contact_name, ec.phone AS emergency_contact_phone,
                    CONCAT(s.first_name, ' ', s.last_name) AS supervisor_name
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN job_titles jt ON jt.id = e.job_title_id
             LEFT JOIN users u ON u.employee_id = e.id AND u.deleted_at IS NULL
             LEFT JOIN employee_bank_accounts ba ON ba.employee_id = e.id AND ba.deleted_at IS NULL AND ba.is_primary = 1
             LEFT JOIN employee_emergency_contacts ec ON ec.employee_id = e.id AND ec.deleted_at IS NULL AND ec.is_primary = 1
             LEFT JOIN employees s ON s.id = e.supervisor_id
             ORDER BY e.deleted_at IS NULL DESC, e.created_at DESC, e.id DESC"
        );

        return array_map([$this, 'mapEmployeeRow'], $rows);
    }

    public function findForView(string $employeeCode): ?array
    {
        $row = $this->queryOne(
            "SELECT e.*, d.name AS department_name, jt.name AS job_title_name, u.id AS user_id, u.username, u.account_status,
                    ba.bank_name, ba.account_name, ba.account_number,
                    ec.contact_name AS emergency_contact_name, ec.phone AS emergency_contact_phone,
                    CONCAT(s.first_name, ' ', s.last_name) AS supervisor_name
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN job_titles jt ON jt.id = e.job_title_id
             LEFT JOIN users u ON u.employee_id = e.id AND u.deleted_at IS NULL
             LEFT JOIN employee_bank_accounts ba ON ba.employee_id = e.id AND ba.deleted_at IS NULL AND ba.is_primary = 1
             LEFT JOIN employee_emergency_contacts ec ON ec.employee_id = e.id AND ec.deleted_at IS NULL AND ec.is_primary = 1
             LEFT JOIN employees s ON s.id = e.supervisor_id
             WHERE e.deleted_at IS NULL AND e.employee_code = :employee_code
             LIMIT 1",
            ['employee_code' => $employeeCode]
        );

        return $row === null ? null : $this->mapEmployeeRow($row);
    }

    public function findDatabaseIdByCode(string $employeeCode): ?int
    {
        $id = $this->database()->value(
            'SELECT id FROM employees WHERE employee_code = :employee_code AND deleted_at IS NULL LIMIT 1',
            ['employee_code' => $employeeCode]
        );

        return $id === null ? null : (int) $id;
    }

    public function photoPathByCode(string $employeeCode): ?string
    {
        $path = $this->database()->value(
            'SELECT photo_path FROM employees WHERE employee_code = :employee_code AND deleted_at IS NULL LIMIT 1',
            ['employee_code' => $employeeCode]
        );

        return $path === null || $path === '' ? null : (string) $path;
    }
    public function documentsFor(string $employeeCode): array
    {
        $employeeId = $this->findDatabaseIdByCode($employeeCode);
        if ($employeeId === null) {
            return [];
        }

        return $this->query(
            'SELECT id, document_type, document_title, file_path, expires_on, created_at FROM employee_documents WHERE employee_id = :employee_id AND deleted_at IS NULL ORDER BY created_at DESC',
            ['employee_id' => $employeeId]
        );
    }

    public function options(): array
    {
        return [
            'departments' => array_column($this->query("SELECT name FROM departments WHERE deleted_at IS NULL AND status = 'active' ORDER BY name"), 'name'),
            'roles' => array_column($this->query('SELECT name FROM roles WHERE deleted_at IS NULL ORDER BY name'), 'name'),
            'job_titles' => array_column($this->query("SELECT name FROM job_titles WHERE deleted_at IS NULL AND status = 'active' ORDER BY name"), 'name'),
            'shifts' => array_column($this->query("SELECT name FROM shifts WHERE deleted_at IS NULL AND status = 'active' ORDER BY name"), 'name'),
        ];
    }

    public function nextEmployeeCode(): string
    {
        $last = (int) $this->database()->value(
            "SELECT GREATEST(COALESCE((SELECT MAX(CAST(SUBSTRING(employee_code, 8) AS UNSIGNED)) FROM employees WHERE employee_code REGEXP '^UMACHI-[0-9]+$'), 0),
                             COALESCE((SELECT last_number FROM employee_sequences WHERE sequence_name = 'employee'), 0))"
        );
        return $this->formatEmployeeCode($last + 1);
    }

    public function valueExists(string $field, string $value, ?string $exceptEmployeeCode = null): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        if (in_array($field, ['employee_code', 'email', 'phone'], true)) {
            $bindings = ['value' => $value];
            $sql = "SELECT COUNT(*) FROM employees WHERE {$field} = :value AND deleted_at IS NULL";
            if ($exceptEmployeeCode !== null && $exceptEmployeeCode !== '') {
                $sql .= ' AND employee_code <> :employee_code';
                $bindings['employee_code'] = $exceptEmployeeCode;
            }

            return (int) $this->database()->value($sql, $bindings) > 0;
        }

        if ($field === 'company_email') {
            return (int) $this->database()->value(
                'SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(:value) AND deleted_at IS NULL',
                ['value' => $value]
            ) > 0;
        }

        if ($field === 'username') {
            $bindings = ['value' => $value];
            $sql = 'SELECT COUNT(*) FROM users u LEFT JOIN employees e ON e.id = u.employee_id WHERE u.username = :value AND u.deleted_at IS NULL';
            if ($exceptEmployeeCode !== null && $exceptEmployeeCode !== '') {
                $sql .= ' AND (e.employee_code IS NULL OR e.employee_code <> :employee_code)';
                $bindings['employee_code'] = $exceptEmployeeCode;
            }

            return (int) $this->database()->value($sql, $bindings) > 0;
        }

        throw new \RuntimeException('Unsupported duplicate validation field.');
    }
    public function create(array $data): int
    {
        return (int) $this->transaction(function (Database $database) use ($data): int|string {
            $employeeCode = $this->allocateEmployeeCode($database);
            $departmentId = $this->resolveDepartment((string) $data['department']);
            $jobTitleId = $this->resolveJobTitle((string) $data['role'], $departmentId);
            $supervisorId = $this->resolveSupervisor((string) ($data['supervisor'] ?? ''));

            $employeeId = $database->insert('employees', [
                'employee_code' => $employeeCode,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $this->enum($data['gender']),
                'date_of_birth' => null,
                'marital_status' => null,
                'phone' => null,
                'email' => $data['email'],
                'address' => null,
                'department_id' => $departmentId,
                'job_title_id' => $jobTitleId,
                'supervisor_id' => $supervisorId,
                'employment_type' => $this->enum($data['employment_type']),
                'employment_status' => $this->enum($data['status']),
                'date_joined' => $data['date_joined'],
                'salary' => (float) $data['salary'],
                'allowance' => (float) $data['allowance'],
                'photo_path' => $data['photo_path'] ?? null,
                'profile_completed' => 0,
                'created_by' => $this->currentUserId(),
            ]);

            $this->saveBankAccount((int) $employeeId, $data);
            $userId = $this->createOrUpdateUser((int) $employeeId, $data);
            $this->syncRole($userId, (string) $data['role']);
            $auditData = ['employee_code' => $employeeCode, 'company_email' => $data['company_email'], 'role' => $data['role']];
            $context = is_array($data['_context'] ?? null) ? $data['_context'] : [];
            $this->logActivity('Employee ID Generated', (int) $employeeId, null, $auditData, 'success', $context);
            $this->logActivity('Employee Created', (int) $employeeId, null, $auditData, 'success', $context);
            $this->logActivity('Employee Account Created', (int) $employeeId, null, $auditData, 'success', $context);

            return $employeeId;
        });
    }
    public function employeeCodeById(int $employeeId): string
    {
        $code = $this->database()->value('SELECT employee_code FROM employees WHERE id = :id AND deleted_at IS NULL LIMIT 1', ['id' => $employeeId]);
        if ($code === null || trim((string) $code) === '') {
            throw new RuntimeException('The generated employee ID could not be retrieved.');
        }
        return (string) $code;
    }

    public function recordWelcomeEmailStatus(int $employeeId, string $employeeCode, bool $sent, ?string $error, array $context = []): void
    {
        $this->logActivity(
            $sent ? 'Welcome Email Sent' : 'Welcome Email Failed',
            $employeeId,
            null,
            [
                'employee_id' => $employeeCode,
                'user_id' => $this->currentUserId(),
                'timestamp' => date('Y-m-d H:i:s'),
                'error_message' => $sent ? null : substr((string) $error, 0, 1000),
            ],
            $sent ? 'success' : 'warning',
            $context
        );
    }


    public function updateByCode(string $employeeCode, array $data): void
    {
        $employeeId = $this->findDatabaseIdByCode($employeeCode);
        if ($employeeId === null) {
            throw new \RuntimeException('Employee record not found.');
        }

        $this->transaction(function (Database $database) use ($employeeId, $data): void {
            $before = $database->find('employees', $employeeId);
            $departmentId = $this->resolveDepartment((string) $data['department']);
            $jobTitleId = $this->resolveJobTitle((string) $data['role'], $departmentId);
            $supervisorId = $this->resolveSupervisor((string) ($data['supervisor'] ?? ''));

            $employeeData = [
                'employee_code' => $data['employee_id'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $this->enum($data['gender']),
                'date_of_birth' => $data['dob'],
                'marital_status' => $this->enum($data['marital_status']),
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'],
                'department_id' => $departmentId,
                'job_title_id' => $jobTitleId,
                'supervisor_id' => $supervisorId,
                'employment_type' => $this->enum($data['employment_type']),
                'employment_status' => $this->enum($data['status']),
                'date_joined' => $data['date_joined'],
                'salary' => (float) $data['salary'],
                'allowance' => (float) $data['allowance'],
                'updated_by' => $this->currentUserId(),
            ];

            if (!empty($data['replace_photo'])) {
                $employeeData['photo_path'] = $data['photo_path'];
            }

            $database->update('employees', $employeeData, ['id' => $employeeId]);
            $this->saveEmergencyContact($employeeId, $data);
            $this->saveBankAccount($employeeId, $data);
            $userId = $this->createOrUpdateUser($employeeId, $data);
            $this->syncRole($userId, (string) $data['role']);
            $this->logActivity('Employee Updated', $employeeId, $before, $employeeData, 'success');
        });
    }

    public function toggleAccount(string $employeeCode): void
    {
        $employee = $this->findForView($employeeCode);
        if ($employee === null || empty($employee['user_id'])) {
            throw new \RuntimeException('Employee user account not found.');
        }

        $next = ($employee['account_status'] ?? 'active') === 'active' ? 'inactive' : 'active';
        $this->database()->update('users', ['account_status' => $next], ['id' => (int) $employee['user_id']]);
        $this->database()->update('employees', ['employment_status' => $next === 'active' ? 'active' : 'inactive'], ['id' => (int) $employee['db_id']]);
        $this->logActivity('Employee Account Status Changed', (int) $employee['db_id'], ['status' => $employee['account_status']], ['status' => $next], 'success');
    }

    public function resetPassword(string $employeeCode): string
    {
        $employee = $this->findForView($employeeCode);
        if ($employee === null || empty($employee['user_id'])) {
            throw new \RuntimeException('Employee user account not found.');
        }

        $password = 'FuelOps@' . random_int(1000, 9999);
        $this->database()->update('users', [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'must_change_password' => 1,
            'failed_attempts' => 0,
            'locked_until' => null,
            'last_password_change_at' => date('Y-m-d H:i:s'),
        ], ['id' => (int) $employee['user_id']]);

        $this->logActivity('Employee Password Reset', (int) $employee['db_id'], null, ['must_change_password' => true], 'success');

        return $password;
    }

    public function deleteByCode(string $employeeCode): void
    {
        $employeeId = $this->findDatabaseIdByCode($employeeCode);
        if ($employeeId === null) {
            throw new \RuntimeException('Employee record not found.');
        }

        $this->database()->update('employees', [
            'deleted_at' => date('Y-m-d H:i:s'),
        ], ['id' => $employeeId]);
        $this->database()->update('users', ['deleted_at' => date('Y-m-d H:i:s')], ['employee_id' => $employeeId]);
        $this->logActivity('Employee Deleted', $employeeId, null, ['deleted' => true], 'warning');
    }

    public function uploadDocument(string $employeeCode, array $data, string $filePath): void
    {
        $employeeId = $this->findDatabaseIdByCode($employeeCode);
        if ($employeeId === null) {
            throw new \RuntimeException('Employee record not found.');
        }

        $this->database()->insert('employee_documents', [
            'employee_id' => $employeeId,
            'document_type' => $data['document_type'],
            'document_title' => $data['document_title'],
            'file_path' => $filePath,
            'uploaded_by' => $this->currentUserId(),
            'expires_on' => $data['expires_on'] ?: null,
        ]);
        $this->logActivity('Employee Document Uploaded', $employeeId, null, ['document_type' => $data['document_type']], 'success');
    }


    public function deleteDocument(int $documentId): void
    {
        $document = $this->queryOne(
            'SELECT employee_id FROM employee_documents WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            ['id' => $documentId]
        );

        if ($document === null) {
            throw new \RuntimeException('Employee document not found.');
        }

        $this->database()->update('employee_documents', [
            'deleted_at' => date('Y-m-d H:i:s'),
        ], ['id' => $documentId]);
        $this->logActivity('Employee Document Deleted', (int) $document['employee_id'], null, ['document_id' => $documentId], 'warning');
    }
    public function departmentsWithCounts(): array
    {
        return $this->query(
            "SELECT d.id, d.name, d.description, d.status, COUNT(e.id) AS employee_count
             FROM departments d
             LEFT JOIN employees e ON e.department_id = d.id AND e.deleted_at IS NULL
             WHERE d.deleted_at IS NULL
             GROUP BY d.id, d.name, d.description, d.status
             ORDER BY d.name"
        );
    }

    public function saveDepartment(array $data): void
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new \RuntimeException('Department name is required.');
        }

        $payload = [
            'name' => $name,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'status' => in_array(($data['status'] ?? 'active'), ['active', 'inactive'], true) ? $data['status'] : 'active',
        ];

        if (!empty($data['id'])) {
            $this->database()->update('departments', $payload, ['id' => (int) $data['id']]);
            return;
        }

        $this->database()->insert('departments', $payload);
    }

    public function deactivateDepartment(int $departmentId): void
    {
        $this->database()->update('departments', ['status' => 'inactive'], ['id' => $departmentId]);
    }
    private function createOrUpdateUser(int $employeeId, array $data): int
    {
        $existing = $this->queryOne('SELECT id FROM users WHERE employee_id = :employee_id AND deleted_at IS NULL LIMIT 1', ['employee_id' => $employeeId]);
        $userData = [
            'employee_id' => $employeeId,
            'account_status' => in_array($this->enum($data['status']), ['active', 'probation'], true) ? 'active' : 'inactive',
        ];

        if ($existing === null) {
            $companyEmail = (string) ($data['company_email'] ?? $data['username'] ?? '');
            $userData['username'] = $companyEmail;
            $userData['email'] = $companyEmail;
        } elseif (isset($data['company_email']) && trim((string) $data['company_email']) !== '') {
            $userData['username'] = strtolower(trim((string) $data['company_email']));
            $userData['email'] = strtolower(trim((string) $data['company_email']));
        }

        if (($data['password'] ?? '') !== '') {
            $userData['password_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
            $userData['must_change_password'] = 1;
            $userData['last_password_change_at'] = date('Y-m-d H:i:s');
        }

        if ($existing === null) {
            $userData['password_hash'] ??= password_hash('FuelOps@1234', PASSWORD_DEFAULT);
            $userData['must_change_password'] ??= 1;
            return (int) $this->database()->insert('users', $userData);
        }

        $this->database()->update('users', $userData, ['id' => (int) $existing['id']]);
        return (int) $existing['id'];
    }

    private function saveEmergencyContact(int $employeeId, array $data): void
    {
        $existing = $this->queryOne('SELECT id FROM employee_emergency_contacts WHERE employee_id = :employee_id AND is_primary = 1 AND deleted_at IS NULL LIMIT 1', ['employee_id' => $employeeId]);
        $payload = [
            'employee_id' => $employeeId,
            'contact_name' => $data['emergency_contact_name'],
            'phone' => $data['emergency_contact_phone'],
            'is_primary' => 1,
        ];
        $existing === null ? $this->database()->insert('employee_emergency_contacts', $payload) : $this->database()->update('employee_emergency_contacts', $payload, ['id' => (int) $existing['id']]);
    }

    private function saveBankAccount(int $employeeId, array $data): void
    {
        $existing = $this->queryOne('SELECT id FROM employee_bank_accounts WHERE employee_id = :employee_id AND is_primary = 1 AND deleted_at IS NULL LIMIT 1', ['employee_id' => $employeeId]);
        $payload = [
            'employee_id' => $employeeId,
            'bank_name' => $data['bank_name'],
            'account_name' => $data['account_name'],
            'account_number' => $data['account_number'],
            'is_primary' => 1,
        ];
        $existing === null ? $this->database()->insert('employee_bank_accounts', $payload) : $this->database()->update('employee_bank_accounts', $payload, ['id' => (int) $existing['id']]);
    }

    private function resolveDepartment(string $name): int
    {
        $name = trim($name);
        $row = $this->queryOne('SELECT id FROM departments WHERE name = :name AND deleted_at IS NULL LIMIT 1', ['name' => $name]);
        return $row === null ? (int) $this->database()->insert('departments', ['name' => $name, 'status' => 'active']) : (int) $row['id'];
    }

    private function resolveJobTitle(string $name, int $departmentId): int
    {
        $row = $this->queryOne('SELECT id FROM job_titles WHERE name = :name AND department_id = :department_id AND deleted_at IS NULL LIMIT 1', ['name' => $name, 'department_id' => $departmentId]);
        return $row === null ? (int) $this->database()->insert('job_titles', ['department_id' => $departmentId, 'name' => $name, 'status' => 'active']) : (int) $row['id'];
    }

    private function resolveSupervisor(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $row = $this->queryOne("SELECT id FROM employees WHERE deleted_at IS NULL AND CONCAT(first_name, ' ', last_name) = :name LIMIT 1", ['name' => $name]);
        return $row === null ? null : (int) $row['id'];
    }

    private function syncRole(int $userId, string $roleName): void
    {
        $slug = strtolower(str_replace([' ', '_'], '-', trim($roleName)));
        $role = $this->queryOne('SELECT id FROM roles WHERE slug = :slug AND deleted_at IS NULL LIMIT 1', ['slug' => $slug]);
        $roleId = $role === null ? (int) $this->database()->insert('roles', ['name' => $roleName, 'slug' => $slug]) : (int) $role['id'];
        $this->database()->delete('user_roles', ['user_id' => $userId]);
        $this->database()->insert('user_roles', ['user_id' => $userId, 'role_id' => $roleId, 'assigned_by' => $this->currentUserId()]);
    }

    private function logActivity(string $activity, int $employeeId, mixed $oldValue, mixed $newValue, string $status, array $context = []): void
    {
        try {
            $this->database()->insert('activity_logs', [
                'log_code' => 'ACT-' . date('YmdHis') . '-' . random_int(100, 999),
                'user_id' => $this->currentUserId(),
                'employee_id' => $employeeId,
                'activity_type' => 'Employee Update',
                'module' => 'Employee Management',
                'activity' => $activity,
                'entity_type' => 'employee',
                'entity_id' => $employeeId,
                'old_value' => $oldValue === null ? null : json_encode($oldValue, JSON_THROW_ON_ERROR),
                'new_value' => json_encode($newValue, JSON_THROW_ON_ERROR),
                'ip_address' => $context['ip'] ?? null,
                'browser' => $this->browserFromUserAgent((string) ($context['user_agent'] ?? '')),
                'status' => $status,
            ]);
        } catch (Throwable) {
            // Audit logging must never block the employee management workflow.
        }
    }

    private function allocateEmployeeCode(Database $database): string
    {
        $database->execute("INSERT INTO employee_sequences (sequence_name, last_number) VALUES ('employee', 0) ON DUPLICATE KEY UPDATE sequence_name = VALUES(sequence_name)");
        $row = $database->selectOne("SELECT last_number FROM employee_sequences WHERE sequence_name = 'employee' FOR UPDATE");
        $maximum = (int) $database->value("SELECT COALESCE(MAX(CAST(SUBSTRING(employee_code, 8) AS UNSIGNED)), 0) FROM employees WHERE employee_code REGEXP '^UMACHI-[0-9]+$'");
        $next = max((int) ($row['last_number'] ?? 0), $maximum) + 1;
        $database->update('employee_sequences', ['last_number' => $next], ['sequence_name' => 'employee']);
        return $this->formatEmployeeCode($next);
    }

    private function formatEmployeeCode(int $number): string
    {
        return 'UMACHI-' . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    private function browserFromUserAgent(string $userAgent): ?string
    {
        if ($userAgent === '') {
            return null;
        }

        return match (true) {
            str_contains($userAgent, 'Edg') => 'Edge',
            str_contains($userAgent, 'Firefox') => 'Firefox',
            str_contains($userAgent, 'Chrome') => 'Chrome',
            str_contains($userAgent, 'Safari') => 'Safari',
            default => 'Unknown',
        };
    }

    private function currentUserId(): ?int
    {
        $userId = Session::get('auth.user_id');
        if ($userId === null || (int) $userId <= 0) {
            return null;
        }

        $exists = $this->database()->value(
            'SELECT id FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            ['id' => (int) $userId]
        );

        return $exists === null ? null : (int) $userId;
    }

    private function enum(string $value): string
    {
        return strtolower(str_replace([' ', '-'], '_', trim($value)));
    }

    private function label(?string $value): string
    {
        return ucwords(str_replace('_', ' ', (string) $value));
    }

    private function mapEmployeeRow(array $row): array
    {
        $firstName = (string) $row['first_name'];
        $lastName = (string) $row['last_name'];

        return [
            'db_id' => (int) $row['id'],
            'user_id' => isset($row['user_id']) ? (int) $row['user_id'] : null,
            'id' => (string) $row['employee_code'],
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => trim($firstName . ' ' . $lastName),
            'gender' => $this->label($row['gender'] ?? ''),
            'dob' => (string) ($row['date_of_birth'] ?? ''),
            'marital_status' => $this->label($row['marital_status'] ?? ''),
            'phone' => (string) $row['phone'],
            'email' => (string) ($row['email'] ?? ''),
            'address' => (string) ($row['address'] ?? ''),
            'emergency_contact' => trim((string) ($row['emergency_contact_name'] ?? '') . ' - ' . (string) ($row['emergency_contact_phone'] ?? ''), ' -'),
            'emergency_contact_name' => (string) ($row['emergency_contact_name'] ?? ''),
            'emergency_contact_phone' => (string) ($row['emergency_contact_phone'] ?? ''),
            'department' => (string) ($row['department_name'] ?? 'Unassigned'),
            'role' => (string) ($row['job_title_name'] ?? 'Unassigned'),
            'employment_type' => $this->label($row['employment_type'] ?? ''),
            'status' => !empty($row['deleted_at']) ? 'Deleted' : $this->label($row['employment_status'] ?? ''),
            'is_deleted' => !empty($row['deleted_at']),
            'account_status' => (string) ($row['account_status'] ?? ''),
            'date_joined' => (string) ($row['date_joined'] ?? ''),
            'service_duration' => \App\Services\ServiceDurationFormatter::format((string) ($row['date_joined'] ?? '')),
            'supervisor' => (string) ($row['supervisor_name'] ?? ''),
            'shift' => 'Rotational',
            'salary' => (float) $row['salary'],
            'allowance' => (float) $row['allowance'],
            'bank_name' => (string) ($row['bank_name'] ?? ''),
            'account_name' => (string) ($row['account_name'] ?? ''),
            'account_number' => (string) ($row['account_number'] ?? ''),
            'username' => (string) ($row['username'] ?? ''),
            'photo' => $row['photo_path'] ?: 'images/sample-passport.svg',
            'photo_path' => ($row['photo_path'] ?? '') !== '' ? (string) $row['photo_path'] : null,
        ];
    }
}


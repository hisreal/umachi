<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;
use App\Services\AuthService;

require dirname(__DIR__) . '/bootstrap/app.php';

$config = new Config(CONFIG_PATH);
$database = Database::getInstance();
$auth = new AuthService($database, $config);
$admin = $config->get('installation.default_admin', []);

$username = (string) ($admin['username'] ?? 'admin');
$email = (string) ($admin['email'] ?? 'admin@umachi.com');
$roleName = (string) ($admin['role'] ?? 'Admin');
$fullName = (string) ($admin['full_name'] ?? 'System Administrator');
$defaultPassword = (string) ($admin['password'] ?? 'password1');
$status = strtolower((string) ($admin['status'] ?? 'active'));

// Default administrator account for first-time installation.
// The password is hashed with password_hash(PASSWORD_DEFAULT) and must be changed after first login.
$existingAdmin = $database->selectOne(
    'SELECT id FROM users WHERE username = :username AND deleted_at IS NULL LIMIT 1',
    ['username' => $username]
);

if ($existingAdmin !== null) {
    echo "Default administrator account already exists. No duplicate was created." . PHP_EOL;
    return;
}

$database->transaction(function (Database $database) use ($auth, $username, $email, $roleName, $fullName, $defaultPassword, $status): void {
    $departmentId = ensureRecord($database, 'departments', ['name' => 'Administration'], [
        'name' => 'Administration',
        'description' => 'Administrative users and system operators.',
        'status' => 'active',
    ]);

    $jobTitleId = ensureRecord($database, 'job_titles', ['name' => 'Administrator'], [
        'department_id' => $departmentId,
        'name' => 'Administrator',
        'description' => 'System administrator role for first-time installation.',
        'status' => 'active',
    ]);

    $roleId = ensureRecord($database, 'roles', ['slug' => 'admin'], [
        'name' => $roleName,
        'slug' => 'admin',
        'description' => 'Default administrator role with full system access.',
        'is_system' => 1,
    ]);

    [$firstName, $lastName] = splitFullName($fullName);

    $employeeId = ensureRecord($database, 'employees', ['employee_code' => 'ADM001'], [
        'employee_code' => 'ADM001',
        'first_name' => $firstName,
        'last_name' => $lastName,
        'phone' => '0000000000',
        'email' => $email,
        'department_id' => $departmentId,
        'job_title_id' => $jobTitleId,
        'employment_type' => 'full_time',
        'employment_status' => 'active',
        'date_joined' => date('Y-m-d'),
    ]);

    $userId = (int) $database->insert('users', [
        'employee_id' => $employeeId,
        'username' => $username,
        'email' => $email,
        'password_hash' => $auth->hashPassword($defaultPassword),
        'account_status' => $status === 'active' ? 'active' : 'inactive',
        'must_change_password' => 1,
        'failed_attempts' => 0,
        'locked_until' => null,
        'last_password_change_at' => null,
        'email_verified_at' => date('Y-m-d H:i:s'),
    ]);

    $database->insert('user_roles', [
        'user_id' => $userId,
        'role_id' => $roleId,
        'assigned_by' => null,
    ]);
});

echo "Default administrator account created successfully." . PHP_EOL;

function ensureRecord(Database $database, string $table, array $lookup, array $data): int
{
    [$whereSql, $bindings] = buildWhere($lookup);
    $existing = $database->selectOne("SELECT id FROM `{$table}` WHERE {$whereSql} LIMIT 1", $bindings);

    if ($existing !== null) {
        return (int) $existing['id'];
    }

    return (int) $database->insert($table, $data);
}

function buildWhere(array $lookup): array
{
    $clauses = [];
    $bindings = [];

    foreach ($lookup as $column => $value) {
        $clauses[] = '`' . $column . '` = :' . $column;
        $bindings[(string) $column] = $value;
    }

    return [implode(' AND ', $clauses), $bindings];
}

function splitFullName(string $fullName): array
{
    $parts = preg_split('/\s+/', trim($fullName)) ?: [];
    $firstName = $parts[0] ?? 'System';
    $lastName = trim(implode(' ', array_slice($parts, 1)));

    return [$firstName, $lastName !== '' ? $lastName : 'Administrator'];
}

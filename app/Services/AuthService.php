<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\DatabaseException;
use App\Core\Request;
use App\Core\Session;

class AuthService
{
    private Database $database;
    private Config $config;

    public function __construct(?Database $database = null, ?Config $config = null)
    {
        $this->database = $database ?? Database::getInstance();
        $this->config = $config ?? new Config(CONFIG_PATH);
    }

    public function attempt(string $username, string $password, ?string $selectedRole, Request $request): array
    {
        $username = trim($username);
        $selectedRole = trim((string) $selectedRole);

        if ($username === '' || $password === '') {
            return ['success' => false, 'message' => 'Username and password are required.'];
        }

        try {
            $user = $this->findUserByUsername($username);

            if ($this->isTemporarilyLocked($username, $request->ip(), $user)) {
                $this->recordLoginAttempt($username, $user['id'] ?? null, $request, 'failed', 'Account temporarily locked');
                return ['success' => false, 'message' => 'Too many failed attempts. Please try again later.'];
            }

            if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
                $this->recordLoginAttempt($username, $user['id'] ?? null, $request, 'failed', 'Invalid credentials');
                $this->lockAccountIfNeeded($username, $user);

                return ['success' => false, 'message' => 'Invalid username or password.'];
            }

            if (($user['account_status'] ?? '') !== 'active') {
                $this->recordLoginAttempt($username, (int) $user['id'], $request, 'failed', 'Account not active');
                return ['success' => false, 'message' => 'This account is not active. Please contact the administrator.'];
            }

            $roles = $this->rolesForUser((int) $user['id']);
            if ($selectedRole !== '' && $roles !== [] && !in_array($selectedRole, $roles, true)) {
                $this->recordLoginAttempt($username, (int) $user['id'], $request, 'failed', 'Role mismatch');
                return ['success' => false, 'message' => 'The selected role does not match this account.'];
            }

            $this->loginUser($user, $roles, $selectedRole, $request);
            $this->recordLoginAttempt($username, (int) $user['id'], $request, 'success', null);

            return [
                'success' => true,
                'redirect' => isset($user['profile_completed']) && !(bool) $user['profile_completed']
                    ? $this->profileCompletionRouteFor($roles, $selectedRole)
                    : (!empty($user['must_change_password'])
                    ? $this->passwordChangeRouteFor($roles, $selectedRole)
                    : $this->redirectFor($roles, $selectedRole)),
            ];
        } catch (DatabaseException) {
            return ['success' => false, 'message' => 'Authentication service is currently unavailable.'];
        }
    }

    public function logout(): void
    {
        $sessionId = Session::id();
        $userId = Session::get('auth.user_id');

            (new ActivityLogService($this->database))->record(
                'Logout',
                'Authentication',
                'User signed out successfully.',
                [],
                'success'
            );

        if ($userId !== null) {
            try {
                $this->database->delete('user_sessions', [
                    'user_id' => (int) $userId,
                    'session_token_hash' => $this->sessionHash($sessionId),
                ]);
            } catch (DatabaseException) {
                // Logout should still complete even if session cleanup cannot reach the database.
            }
        }

        Session::destroy();
    }

    public function check(): bool
    {
        return Session::has('auth.user_id');
    }

    public function mustChangePassword(): bool
    {
        return (bool) Session::get('auth.must_change_password', false);
    }

    public function profileCompleted(): bool
    {
        if ((bool) Session::get('auth.profile_completed', true)) {
            return true;
        }

        $userId = (int) Session::get('auth.user_id', 0);
        if ($userId <= 0) {
            return false;
        }

        try {
            $completed = (bool) $this->database->value(
                'SELECT COALESCE(e.profile_completed, 1) FROM users u LEFT JOIN employees e ON e.id = u.employee_id AND e.deleted_at IS NULL WHERE u.id = :user_id AND u.deleted_at IS NULL LIMIT 1',
                ['user_id' => $userId]
            );
            if ($completed) {
                Session::put('auth.profile_completed', true);
            }

            return $completed;
        } catch (DatabaseException) {
            return false;
        }
    }

    public function markProfileCompleted(): void
    {
        Session::put('auth.profile_completed', true);
    }

    public function passwordChangeRoute(): string
    {
        return $this->passwordChangeRouteFor($this->roles(), (string) Session::get('auth.role', ''));
    }

    public function user(): ?array
    {
        $user = Session::get('auth.user');

        return is_array($user) ? $user : null;
    }

    public function roles(): array
    {
        $roles = Session::get('auth.roles', []);

        if (is_array($roles) && $roles !== []) {
            return array_values(array_map(static fn (mixed $role): string => (string) $role, $roles));
        }

        $role = Session::get('auth.role');

        return is_string($role) && $role !== '' ? [$role] : [];
    }

    public function hasRole(string|array $roles): bool
    {
        return array_intersect((array) $roles, $this->roles()) !== [];
    }

    public function enforceSessionTimeout(Request $request): void
    {
        if (!$this->check()) {
            return;
        }

        $lastActivity = (int) Session::get('auth.last_activity', time());
        $timeoutSeconds = max(1, (int) $this->config->get('auth.timeout_minutes', 30)) * 60;

        if ((time() - $lastActivity) > $timeoutSeconds) {
            $this->logout();
            Session::start();
            Session::flash('auth_error', 'Your session expired. Please sign in again.');
            return;
        }

        Session::put('auth.last_activity', time());

        $this->touchActiveSession($request);
    }

    public function csrfToken(): string
    {
        $token = Session::get('_csrf_token');

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::put('_csrf_token', $token);
        }

        return $token;
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function changePassword(string $currentPassword, string $newPassword, string $confirmPassword): array
    {
        $userId = Session::get('auth.user_id');

        if ($userId === null) {
            return ['success' => false, 'message' => 'Please sign in before changing your password.'];
        }

        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'message' => 'The new password confirmation does not match.'];
        }

        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'The new password must be at least 8 characters.'];
        }

        if ($currentPassword === $newPassword) {
            return ['success' => false, 'message' => 'The new password must be different from the current password.'];
        }

        $user = $this->database->selectOne(
            'SELECT id, password_hash FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            ['id' => (int) $userId]
        );

        if ($user === null || !password_verify($currentPassword, (string) $user['password_hash'])) {
            return ['success' => false, 'message' => 'The current password is incorrect.'];
        }

        $this->database->update('users', [
            'password_hash' => $this->hashPassword($newPassword),
            'must_change_password' => 0,
            'last_password_change_at' => date('Y-m-d H:i:s'),
            'failed_attempts' => 0,
            'locked_until' => null,
        ], ['id' => (int) $userId]);

        Session::put('auth.must_change_password', false);

        return ['success' => true, 'message' => 'Password updated successfully.'];
    }

    public function validateCsrf(?string $token): bool
    {
        $sessionToken = Session::get('_csrf_token');

        return is_string($token) && is_string($sessionToken) && hash_equals($sessionToken, $token);
    }

    private function recordProfileActivity(int $userId, string $activity, array $newValue, ?Request $request = null): void
    {
        try {
            $user = $this->database->selectOne('SELECT employee_id FROM users WHERE id = :id LIMIT 1', ['id' => $userId]);
            $this->database->insert('activity_logs', [
                'log_code' => 'ACT-' . date('YmdHis') . '-' . random_int(100, 999),
                'user_id' => $userId,
                'employee_id' => isset($user['employee_id']) ? (int) $user['employee_id'] : null,
                'activity_type' => 'Profile Security',
                'module' => 'Profile Management',
                'activity' => $activity,
                'entity_type' => 'user',
                'entity_id' => $userId,
                'old_value' => null,
                'ip_address' => $request?->ip(),
                'browser' => $request === null ? null : $this->browserFromUserAgent($request->userAgent()),
                'operating_system' => $request === null ? null : $this->osFromUserAgent($request->userAgent()),
                'device_type' => $request === null ? null : $this->deviceFromUserAgent($request->userAgent()),
                'new_value' => json_encode($newValue, JSON_THROW_ON_ERROR),
                'status' => 'Success',
            ]);
        } catch (\Throwable) {
            // Audit logging must never block password changes.
        }
    }
    private function findUserByUsername(string $username): ?array
    {
        return $this->database->selectOne(
            "SELECT u.id, u.employee_id, u.username, u.email, u.password_hash, u.account_status, u.must_change_password,
                    u.failed_attempts, u.locked_until, u.last_login_at, e.profile_completed
             FROM users u
             LEFT JOIN employees e ON e.id = u.employee_id
             WHERE u.deleted_at IS NULL AND (
                 LOWER(u.email) = LOWER(:username)
                 OR UPPER(e.employee_code) = UPPER(:employee_code)
             ) LIMIT 1",
            ['username' => $username, 'employee_code' => $username]
        );
    }

    private function loginUser(array $user, array $roles, string $selectedRole, Request $request): void
    {
        Session::regenerate();

        $displayRole = $selectedRole !== '' ? $selectedRole : ($roles[0] ?? 'User');
        $profile = $this->profileForUser($user, $displayRole);

        Session::put('auth.user_id', (int) $user['id']);
        Session::put('auth.employee_id', $user['employee_id'] ?? null);
        Session::put('auth.roles', $roles);
        Session::put('auth.role', $displayRole);
        Session::put('auth.user', $profile);
        Session::put('auth.last_activity', time());
        Session::put('auth.must_change_password', (bool) ($user['must_change_password'] ?? false));
        Session::put('auth.profile_completed', !isset($user['profile_completed']) || (bool) $user['profile_completed']);

        $now = date('Y-m-d H:i:s');
        $this->database->update('users', ['last_login_at' => $now, 'failed_attempts' => 0, 'locked_until' => null], ['id' => (int) $user['id']]);
        if (empty($user['last_login_at']) && !empty($user['employee_id'])) {
            $this->recordProfileActivity((int) $user['id'], 'First Login', ['employee_id' => $user['employee_id'], 'role' => $displayRole, 'logged_in_at' => $now], $request);
        }
        $this->rememberActiveSession((int) $user['id'], $request);
    }

    private function profileForUser(array $user, string $role): array
    {
        if (!empty($user['employee_id'])) {
            $employee = $this->database->selectOne(
                'SELECT employee_code, first_name, last_name, email, phone, photo_path FROM employees WHERE id = :id LIMIT 1',
                ['id' => (int) $user['employee_id']]
            );

            if ($employee !== null) {
                return [
                    'id' => (int) $user['id'],
                    'employee_id' => $employee['employee_code'] ?? null,
                    'name' => trim((string) ($employee['first_name'] ?? '') . ' ' . (string) ($employee['last_name'] ?? '')),
                    'username' => $user['username'],
                    'email' => $employee['email'] ?? $user['email'],
                    'phone' => $employee['phone'] ?? null,
                    'role' => $role,
                    'avatar' => $employee['photo_path'] ?? null,
                ];
            }
        }

        return [
            'id' => (int) $user['id'],
            'employee_id' => null,
            'name' => (string) $user['username'],
            'username' => $user['username'],
            'email' => $user['email'],
            'phone' => null,
            'role' => $role,
            'avatar' => null,
        ];
    }

    private function rolesForUser(int $userId): array
    {
        $rows = $this->database->select(
            'SELECT r.name FROM roles r INNER JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = :user_id AND r.deleted_at IS NULL',
            ['user_id' => $userId]
        );

        return array_values(array_map(static fn (array $row): string => (string) $row['name'], $rows));
    }

    private function redirectFor(array $roles, string $selectedRole): string
    {
        $role = strtolower($selectedRole !== '' ? $selectedRole : ($roles[0] ?? ''));

        if (in_array($role, ['admin', 'administrator', 'manager', 'supervisor'], true)) {
            return (string) $this->config->get('auth.admin_redirect', 'admin/dashboard');
        }

        return (string) $this->config->get('auth.default_redirect', 'dashboard');
    }

    private function profileCompletionRouteFor(array $roles, string $selectedRole): string
    {
        $role = strtolower($selectedRole !== '' ? $selectedRole : ($roles[0] ?? ''));

        if (in_array($role, ['manager', 'supervisor', 'accountant'], true)) {
            return 'admin/edit-profile';
        }

        return 'profile/complete';
    }

    private function passwordChangeRouteFor(array $roles, string $selectedRole): string
    {
        $role = strtolower($selectedRole !== '' ? $selectedRole : ($roles[0] ?? ''));

        if (in_array($role, ['admin', 'administrator', 'manager', 'supervisor', 'accountant'], true)) {
            return 'admin/change-password';
        }

        return 'profile/change-password';
    }

    private function recordLoginAttempt(string $username, ?int $userId, Request $request, string $status, ?string $reason): void
    {
        $this->database->insert('login_attempts', [
            'username' => $username,
            'user_id' => $userId,
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent(), 0, 500),
            'status' => $status,
            'failure_reason' => $reason,
        ]);

        (new ActivityLogService($this->database))->record(
            $status === 'success' ? 'Login Successful' : 'Login Failed',
            'Authentication',
            $status === 'success' ? 'User signed in successfully.' : 'Login attempt failed: ' . ($reason ?? 'Unknown reason'),
            [
                'user_id' => $userId,
                'employee_name' => $username,
                'notes' => $reason,
            ],
            $status,
            null,
            ['username' => $username, 'reason' => $reason],
            $request
        );
    }

    private function failedAttemptCount(string $username, string $ipAddress): int
    {
        $window = max(1, (int) $this->config->get('auth.failed_login_window_minutes', 15));
        $since = date('Y-m-d H:i:s', time() - ($window * 60));

        return (int) $this->database->value(
            'SELECT COUNT(*) FROM login_attempts WHERE username = :username AND ip_address = :ip AND status = :status AND created_at >= :since',
            ['username' => $username, 'ip' => $ipAddress, 'status' => 'failed', 'since' => $since]
        );
    }

    private function isTemporarilyLocked(string $username, string $ipAddress, ?array $user = null): bool
    {
        if ($user !== null && !empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time()) {
            return true;
        }

        $limit = max(1, (int) $this->config->get('auth.failed_login_limit', 5));

        return $this->failedAttemptCount($username, $ipAddress) >= $limit;
    }

    private function lockAccountIfNeeded(string $username, ?array $user): void
    {
        if ($user === null) {
            return;
        }

        $limit = max(1, (int) $this->config->get('auth.failed_login_limit', 5));
        $count = (int) $this->database->value(
            'SELECT COUNT(*) FROM login_attempts WHERE username = :username AND status = :status AND created_at >= :since',
            [
                'username' => $username,
                'status' => 'failed',
                'since' => date('Y-m-d H:i:s', time() - (max(1, (int) $this->config->get('auth.failed_login_window_minutes', 15)) * 60)),
            ]
        );

        if ($count >= $limit) {
            $lockoutMinutes = max(1, (int) $this->config->get('auth.lockout_minutes', 30));
            $this->database->update('users', [
                'failed_attempts' => $count,
                'locked_until' => date('Y-m-d H:i:s', time() + ($lockoutMinutes * 60)),
            ], ['id' => (int) $user['id']]);
            return;
        }

        $this->database->update('users', ['failed_attempts' => $count], ['id' => (int) $user['id']]);
    }

    private function rememberActiveSession(int $userId, Request $request): void
    {
        $this->database->insert('user_sessions', [
            'user_id' => $userId,
            'session_token_hash' => $this->sessionHash(Session::id()),
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent(), 0, 500),
            'browser' => $this->browserFromUserAgent($request->userAgent()),
            'operating_system' => $this->osFromUserAgent($request->userAgent()),
            'device_type' => $this->deviceFromUserAgent($request->userAgent()),
            'last_activity_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', time() + ((int) $this->config->get('auth.timeout_minutes', 30) * 60)),
        ]);
    }

    private function touchActiveSession(Request $request): void
    {
        $userId = Session::get('auth.user_id');

        if ($userId === null) {
            return;
        }

        try {
            $this->database->update('user_sessions', [
                'last_activity_at' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', time() + ((int) $this->config->get('auth.timeout_minutes', 30) * 60)),
            ], [
                'user_id' => (int) $userId,
                'session_token_hash' => $this->sessionHash(Session::id()),
            ]);
        } catch (DatabaseException) {
            // Active-session tracking should not break page rendering.
        }
    }

    private function sessionHash(string $sessionId): string
    {
        return hash('sha256', $sessionId);
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

    private function osFromUserAgent(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Mac') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown',
        };
    }

    private function deviceFromUserAgent(string $userAgent): string
    {
        return preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent) === 1 ? 'Mobile' : 'Desktop';
    }
}












<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\ServiceDurationFormatter;
use App\Services\ProfilePhotoService;
use RuntimeException;
use Throwable;

class Profile extends BaseModel
{
    public function currentUserProfile(): array
    {
        $userId = (int) Session::get('auth.user_id', 0);
        if ($userId <= 0) {
            throw new RuntimeException('Please sign in before viewing your profile.');
        }

        $row = $this->queryOne(
            "SELECT u.id AS user_id, u.username, u.email AS user_email, u.account_status, u.last_login_at, u.last_password_change_at,
                    e.id AS employee_db_id, e.employee_code, e.first_name, e.last_name, e.gender, e.date_of_birth, e.phone,
                    e.email AS employee_email, e.address, e.state, e.local_government_area, e.nationality,
                    e.marital_status, e.national_id, e.drivers_license, e.employment_status, e.date_joined, e.salary, e.photo_path,
                    e.profile_completed, e.profile_completed_at,
                    d.name AS department_name, jt.name AS job_title_name,
                    ec.contact_name AS emergency_contact_name, ec.phone AS emergency_contact_phone
             FROM users u
             LEFT JOIN employees e ON e.id = u.employee_id AND e.deleted_at IS NULL
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN job_titles jt ON jt.id = e.job_title_id
             LEFT JOIN employee_emergency_contacts ec ON ec.employee_id = e.id AND ec.deleted_at IS NULL AND ec.is_primary = 1
             WHERE u.id = :user_id AND u.deleted_at IS NULL
             LIMIT 1",
            ['user_id' => $userId]
        );

        if ($row === null) {
            throw new RuntimeException('Profile record not found.');
        }

        return $this->mapProfile($row);
    }

    public function updateCurrentUser(array $data, array $files, bool $markComplete = false): void
    {
        $profile = $this->currentUserProfile();
        if (empty($profile['employee_db_id'])) {
            throw new RuntimeException('Your staff profile is not linked to an employee record.');
        }

        $payload = $this->validateProfileData($data, (int) $profile['employee_db_id']);
        $photos = new ProfilePhotoService();
        $oldPhotoPath = (string) ($profile['photo_path'] ?? '');
        $removePhoto = (string) ($data['remove_photo'] ?? '') === '1';
        $photoPath = $removePhoto ? null : $photos->store($files['passport_photo'] ?? null);

        try {
            $this->transaction(function (Database $database) use ($profile, $payload, $photoPath, $removePhoto, $markComplete): void {
                $employeeData = [
                    'gender' => $this->enum($payload['gender']),
                    'date_of_birth' => $payload['dob'],
                    'phone' => $payload['phone'],
                    'email' => $payload['email'],
                    'address' => $payload['address'],
                    'updated_by' => $this->currentUserId(),
                ];

                if ($markComplete) {
                    $employeeData['profile_completed'] = 1;
                    $employeeData['profile_completed_at'] = $profile['profile_completed_at'] ?: date('Y-m-d H:i:s');
                }

                if ($removePhoto || $photoPath !== null) {
                    $employeeData['photo_path'] = $photoPath;
                }

                $database->update('employees', $employeeData, ['id' => (int) $profile['employee_db_id']]);
                $database->update('users', ['email' => $payload['email']], ['employee_id' => (int) $profile['employee_db_id']]);
                $this->saveEmergencyContact((int) $profile['employee_db_id'], $payload['emergency_contact'], $payload['emergency_contact_name']);
                $this->logActivity('Profile Updated', (int) $profile['employee_db_id'], $profile, array_merge($employeeData, ['emergency_contact' => $payload['emergency_contact']]), 'Success');
            });
        } catch (Throwable $exception) {
            $photos->delete($photoPath);
            throw $exception;
        }

        if (($removePhoto || $photoPath !== null) && $oldPhotoPath !== $photoPath) {
            $photos->delete($oldPhotoPath);
        }

        if ($markComplete) {
            Session::put('auth.profile_completed', true);
        }

        $updated = $this->currentUserProfile();
        $authUser = Session::get('auth.user', []);
        if (is_array($authUser)) {
            $authUser['name'] = $updated['name'];
            $authUser['email'] = $updated['email'];
            $authUser['phone'] = $updated['phone'];
            $authUser['avatar'] = $updated['photo_path'] ?: null;
            Session::put('auth.user', $authUser);
        }
    }

    public function completeCurrentUser(array $data, array $files): void
    {
        $existingProfile = $this->currentUserProfile();
        $photo = $files['passport_photo'] ?? null;
        if (empty($existingProfile['photo_path']) && ($photo === null || ($photo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)) {
            throw new RuntimeException('Please upload a passport photograph.');
        }

        $required = ['state' => 'State', 'local_government_area' => 'Local Government Area', 'nationality' => 'Nationality', 'marital_status' => 'Marital status', 'national_id' => 'National ID(NIN)'];
        $extra = [];
        foreach ($required as $field => $label) {
            $extra[$field] = trim((string) ($data[$field] ?? ''));
            if ($extra[$field] === '') {
                throw new RuntimeException($label . ' is required.');
            }
        }
        if (!in_array($extra['marital_status'], ['Single', 'Married', 'Divorced', 'Widowed'], true)) {
            throw new RuntimeException('Select a valid marital status.');
        }
        $extra['drivers_license'] = trim((string) ($data['drivers_license'] ?? '')) ?: null;

        $this->updateCurrentUser($data, $files);
        $profile = $this->currentUserProfile();
        if (empty($profile['employee_db_id'])) {
            throw new RuntimeException('Your staff profile is not linked to an employee record.');
        }

        $this->database()->update('employees', [
            'state' => $extra['state'],
            'local_government_area' => $extra['local_government_area'],
            'nationality' => $extra['nationality'],
            'marital_status' => $this->enum($extra['marital_status']),
            'national_id' => $extra['national_id'],
            'drivers_license' => $extra['drivers_license'],
            'profile_completed' => 1,
            'profile_completed_at' => date('Y-m-d H:i:s'),
        ], ['id' => (int) $profile['employee_db_id']]);
        $this->logActivity('Profile Completed', (int) $profile['employee_db_id'], ['profile_completed' => false], ['profile_completed' => true], 'Success');
        Session::put('auth.profile_completed', true);
    }
    public function profileSummary(): array
    {
        $profile = $this->currentUserProfile();

        return [
            ['label' => 'Employee Id', 'value' => $profile['employee_id'], 'icon' => 'fa-solid fa-id-badge'],
            ['label' => 'Department', 'value' => $profile['department'], 'icon' => 'fa-solid fa-building'],
            ['label' => 'Role', 'value' => $profile['role'], 'icon' => 'fa-solid fa-user-gear'],
            ['label' => 'Duration of Service', 'value' => ServiceDurationFormatter::format($profile['date_joined_raw']), 'icon' => 'fa-solid fa-award'],
            ['label' => 'Current Shift', 'value' => 'Assigned by roster', 'icon' => 'fa-solid fa-business-time'],
            ['label' => 'Assigned Pump', 'value' => 'Assigned by roster', 'icon' => 'fa-solid fa-gas-pump'],
            ['label' => 'Attendance Rate', 'value' => 'Pending report', 'icon' => 'fa-solid fa-chart-line'],
            ['label' => 'Last Clock In', 'value' => 'Pending attendance', 'icon' => 'fa-solid fa-right-to-bracket'],
            ['label' => 'Last Clock Out', 'value' => 'Pending attendance', 'icon' => 'fa-solid fa-arrow-right-from-bracket'],
        ];
    }

    private function validateProfileData(array $data, int $employeeId): array
    {
        $phone = trim((string) ($data['phone'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $address = trim((string) ($data['address'] ?? ''));
        $emergencyContact = trim((string) ($data['emergency_contact'] ?? ''));
        $gender = trim((string) ($data['gender'] ?? ''));
        $dob = trim((string) ($data['dob'] ?? ''));

        if ($phone === '' || !preg_match('/^[0-9+()\-\s]{7,20}$/', $phone)) {
            throw new RuntimeException('Enter a valid phone number.');
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Enter a valid email address.');
        }

        if ($address === '') {
            throw new RuntimeException('Residential address is required.');
        }

        if ($emergencyContact === '' || !preg_match('/^[0-9+()\-\s]{7,80}$/', $emergencyContact)) {
            throw new RuntimeException('Enter a valid emergency contact.');
        }

        if (!in_array($gender, ['Male', 'Female'], true)) {
            throw new RuntimeException('Select a valid gender.');
        }

        if ($dob === '' || strtotime($dob) === false) {
            throw new RuntimeException('Enter a valid date of birth.');
        }

        $duplicate = $this->database()->value(
            'SELECT COUNT(*) FROM employees WHERE email = :email AND id <> :employee_id AND deleted_at IS NULL',
            ['email' => $email, 'employee_id' => $employeeId]
        );

        if ((int) $duplicate > 0) {
            throw new RuntimeException('This email address is already assigned to another employee.');
        }

        $duplicateLogin = $this->database()->value(
            'SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(:email) AND (employee_id IS NULL OR employee_id <> :employee_id) AND deleted_at IS NULL',
            ['email' => $email, 'employee_id' => $employeeId]
        );
        if ((int) $duplicateLogin > 0) {
            throw new RuntimeException('This email address is already assigned to another login account.');
        }

        return [
            'phone' => $phone,
            'email' => $email,
            'address' => $address,
            'emergency_contact' => $emergencyContact,
            'emergency_contact_name' => trim((string) ($data['emergency_name'] ?? '')) ?: 'Primary Emergency Contact',
            'gender' => $gender,
            'dob' => date('Y-m-d', (int) strtotime($dob)),
        ];
    }

    private function saveEmergencyContact(int $employeeId, string $phone, string $name = 'Primary Emergency Contact'): void
    {
        $existing = $this->queryOne(
            'SELECT id FROM employee_emergency_contacts WHERE employee_id = :employee_id AND is_primary = 1 AND deleted_at IS NULL LIMIT 1',
            ['employee_id' => $employeeId]
        );

        $payload = [
            'employee_id' => $employeeId,
            'contact_name' => $name,
            'phone' => $phone,
            'is_primary' => 1,
        ];

        if ($existing === null) {
            $this->database()->insert('employee_emergency_contacts', $payload);
            return;
        }

        $this->database()->update('employee_emergency_contacts', $payload, ['id' => (int) $existing['id']]);
    }

    private function mapProfile(array $row): array
    {
        $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
        if ($name === '') {
            $name = (string) ($row['username'] ?? 'User');
        }

        $dob = $this->formatDate($row['date_of_birth'] ?? null);
        $joined = $this->formatDate($row['date_joined'] ?? null);
        $lastLogin = $this->formatDateTime($row['last_login_at'] ?? null);
        $lastPasswordChange = $this->formatDateTime($row['last_password_change_at'] ?? null);
        $emergencyName = trim((string) ($row['emergency_contact_name'] ?? ''));
        $emergency = trim((string) ($row['emergency_contact_phone'] ?? ''));

        return [
            'user_id' => (int) $row['user_id'],
            'employee_db_id' => isset($row['employee_db_id']) ? (int) $row['employee_db_id'] : null,
            'employee_id' => (string) ($row['employee_code'] ?? 'N/A'),
            'name' => $name,
            'profile_completed' => (bool) ($row['profile_completed'] ?? false),
            'profile_completed_at' => (string) ($row['profile_completed_at'] ?? ''),
            'gender' => $this->label($row['gender'] ?? ''),
            'dob' => $dob,
            'dob_raw' => (string) ($row['date_of_birth'] ?? ''),
            'phone' => (string) ($row['phone'] ?? ''),
            'email' => (string) (($row['employee_email'] ?? '') ?: ($row['user_email'] ?? '')),
            'address' => (string) ($row['address'] ?? ''),
            'state' => (string) ($row['state'] ?? ''),
            'local_government_area' => (string) ($row['local_government_area'] ?? ''),
            'nationality' => (string) ($row['nationality'] ?? ''),
            'marital_status' => $this->label($row['marital_status'] ?? ''),
            'national_id' => (string) ($row['national_id'] ?? ''),
            'drivers_license' => (string) ($row['drivers_license'] ?? ''),
            'emergency_contact_name' => $emergencyName,
            'emergency_contact' => $emergency,
            'department' => (string) ($row['department_name'] ?? 'Unassigned'),
            'role' => (string) ($row['job_title_name'] ?? Session::get('auth.role', 'User')),
            'date_joined' => $joined,
            'date_joined_raw' => (string) ($row['date_joined'] ?? ''),
            'salary' => $this->formatSalary($row['salary'] ?? null),
            'salary_raw' => (string) ($row['salary'] ?? ''),
            'employment_status' => $this->label($row['employment_status'] ?? $row['account_status'] ?? 'active'),
            'username' => (string) ($row['username'] ?? ''),
            'last_login' => $lastLogin,
            'last_password_change' => $lastPasswordChange,
            'account_status' => $this->label($row['account_status'] ?? ''),
            'passport_photo' => (string) (($row['photo_path'] ?? '') ?: ProfilePhotoService::DEFAULT_PHOTO),
            'photo_path' => ($row['photo_path'] ?? '') !== '' ? (string) $row['photo_path'] : null,
        ];
    }

    private function formatDate(mixed $value): string
    {
        $timestamp = strtotime((string) $value);
        return $timestamp === false ? 'N/A' : date('d M Y', $timestamp);
    }

    private function formatDateTime(mixed $value): string
    {
        $timestamp = strtotime((string) $value);
        return $timestamp === false ? 'N/A' : date('d M Y, h:i A', $timestamp);
    }

    private function formatSalary(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        return '?' . number_format((float) $value, 2) . ' / Month';
    }


    private function logActivity(string $activity, int $employeeId, mixed $oldValue, mixed $newValue, string $status): void
    {
        try {
            $request = Request::capture();
            $this->database()->insert('activity_logs', [
                'log_code' => 'ACT-' . date('YmdHis') . '-' . random_int(100, 999),
                'user_id' => $this->currentUserId(),
                'employee_id' => $employeeId,
                'activity_type' => 'Profile Update',
                'module' => 'Profile Management',
                'activity' => $activity,
                'entity_type' => 'employee',
                'entity_id' => $employeeId,
                'old_value' => json_encode($oldValue, JSON_THROW_ON_ERROR),
                'new_value' => json_encode($newValue, JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'browser' => $this->browserFromUserAgent($request->userAgent()),
                'status' => $status,
            ]);
        } catch (Throwable) {
            // Audit logging must never block profile updates.
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

    private function currentUserId(): ?int
    {
        $userId = Session::get('auth.user_id');
        return $userId === null || (int) $userId <= 0 ? null : (int) $userId;
    }

    private function enum(string $value): string
    {
        return strtolower(str_replace([' ', '-'], '_', trim($value)));
    }

    private function label(?string $value): string
    {
        $label = ucwords(str_replace('_', ' ', (string) $value));
        return $label === '' ? 'N/A' : $label;
    }
}

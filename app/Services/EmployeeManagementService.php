<?php

declare(strict_types=1);

namespace App\Services;
use App\Core\ValidationException;

use App\Models\Employee;
use RuntimeException;
use Throwable;

class EmployeeManagementService
{
    private const DOCUMENT_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    public function __construct(private ?Employee $employees = null, private ?ProfilePhotoService $photos = null, private ?MailService $mail = null)
    {
        $this->employees ??= new Employee();
        $this->photos ??= new ProfilePhotoService();
        $this->mail ??= new MailService();
    }

    public function model(): Employee
    {
        return $this->employees;
    }

    public function validateEmployee(array $data, bool $isEdit = false, ?string $currentEmployeeCode = null): array
    {
        if (!$isEdit) {
            return $this->validateNewEmployee($data);
        }

        $errors = [];
        $required = ['employee_id', 'first_name', 'last_name', 'gender', 'dob', 'marital_status', 'phone', 'email', 'address', 'emergency_contact_name', 'emergency_contact_phone', 'department', 'role', 'employment_type', 'status', 'date_joined'];

        foreach ($required as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                $errors[$field] = 'This field is required.';
            }
        }

        if (!filter_var((string) ($data['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }

        foreach (['phone', 'emergency_contact_phone'] as $phoneField) {
            if (!preg_match('/^[+0-9][0-9\s-]{7,}$/', (string) ($data[$phoneField] ?? ''))) {
                $errors[$phoneField] = 'Enter a valid phone number.';
            }
        }

        foreach (['dob' => 'date of birth', 'date_joined' => 'date joined'] as $dateField => $label) {
            if (!$this->isValidDate((string) ($data[$dateField] ?? ''))) {
                $errors[$dateField] = 'Enter a valid ' . $label . '.';
            }
        }

        if (!$isEdit && trim((string) ($data['password'] ?? '')) === '') {
            $errors['password'] = 'Password is required for new employees.';
        }

        if (($data['password'] ?? '') !== '' || ($data['confirm_password'] ?? '') !== '') {
            if ((string) $data['password'] !== (string) ($data['confirm_password'] ?? '')) {
                $errors['confirm_password'] = 'Password confirmation does not match.';
            }

            if (strlen((string) $data['password']) < 8) {
                $errors['password'] = 'Password must be at least 8 characters.';
            }
        }

        foreach (['employee_id' => 'employee_code', 'email' => 'email', 'phone' => 'phone'] as $inputField => $databaseField) {
            if ($this->employees->valueExists($databaseField, (string) ($data[$inputField] ?? ''), $isEdit ? $currentEmployeeCode : null)) {
                $errors[$inputField] = ucfirst(str_replace('_', ' ', $inputField)) . ' already exists.';
            }
        }

        if ($errors !== []) {
            throw new ValidationException('Please correct the highlighted employee fields.', $errors);
        }

        return $data;
    }

    private function validateNewEmployee(array $data): array
    {
        $errors = [];
        $required = ['first_name', 'last_name', 'gender', 'personal_email', 'department', 'role', 'employment_type', 'status', 'date_joined', 'salary', 'allowance', 'bank_name', 'account_name', 'account_number', 'company_email', 'password', 'confirm_password'];
        foreach ($required as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                $errors[$field] = 'This field is required.';
            }
        }

        foreach (['personal_email' => 'personal email address', 'company_email' => 'company email address'] as $field => $label) {
            if (!filter_var((string) ($data[$field] ?? ''), FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = 'Enter a valid ' . $label . '.';
            }
        }
        if (strcasecmp(trim((string) ($data['personal_email'] ?? '')), trim((string) ($data['company_email'] ?? ''))) === 0) {
            $errors['company_email'] = 'Company email must be different from the personal email address.';
        }

        $options = $this->employees->options();
        if (($options['departments'] ?? []) !== [] && !in_array((string) ($data['department'] ?? ''), $options['departments'], true)) {
            $errors['department'] = 'Select a valid department.';
        }
        if (($options['roles'] ?? []) !== [] && !in_array((string) ($data['role'] ?? ''), $options['roles'], true)) {
            $errors['role'] = 'Select a valid role.';
        }
        foreach ([
            'gender' => ['Male', 'Female', 'Other'],
            'employment_type' => ['Full Time', 'Part Time', 'Contract', 'Casual', 'Intern'],
            'status' => ['Active', 'Probation', 'Suspended', 'Resigned'],
        ] as $field => $allowed) {
            if (!in_array((string) ($data[$field] ?? ''), $allowed, true)) {
                $errors[$field] = 'Select a valid ' . str_replace('_', ' ', $field) . '.';
            }
        }

        if (!$this->isValidDate((string) ($data['date_joined'] ?? ''))) {
            $errors['date_joined'] = 'Enter a valid date joined.';
        }
        foreach (['salary', 'allowance'] as $field) {
            if (!is_numeric($data[$field] ?? null) || (float) $data[$field] < 0) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a non-negative number.';
            }
        }
        if (!preg_match('/^[0-9]{10}$/', (string) ($data['account_number'] ?? ''))) {
            $errors['account_number'] = 'Bank account number must contain exactly 10 digits.';
        }

        $password = (string) ($data['password'] ?? '');
        if ($password !== (string) ($data['confirm_password'] ?? '')) {
            $errors['confirm_password'] = 'Password confirmation does not match.';
        }
        if (strlen($password) < 8 || preg_match('/[A-Z]/', $password) !== 1 || preg_match('/[a-z]/', $password) !== 1 || preg_match('/\d/', $password) !== 1 || preg_match('/[^A-Za-z0-9]/', $password) !== 1) {
            $errors['password'] = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
        }
        if ($this->employees->valueExists('email', (string) ($data['personal_email'] ?? ''))) {
            $errors['personal_email'] = 'Personal email address already exists.';
        }
        $companyEmail = strtolower(trim((string) ($data['company_email'] ?? '')));
        if ($this->employees->valueExists('company_email', $companyEmail)
            || $this->employees->valueExists('username', $companyEmail)) {
            $errors['company_email'] = 'Company email address already exists.';
        }
        if ($errors !== []) {
            throw new ValidationException('Please correct the highlighted employee fields.', $errors);
        }

        $data['email'] = strtolower(trim((string) $data['personal_email']));
        $data['company_email'] = strtolower(trim((string) $data['company_email']));
        $data['username'] = $data['company_email'];
        return $data;
    }

    public function store(array $data, array $files, array $context = []): array
    {
        $data = $this->validateEmployee($data);
        $photos = new ProfilePhotoService();
        try {
            $data['photo_path'] = $photos->store($files['passport_photo'] ?? null);
        } catch (RuntimeException $exception) {
            throw new ValidationException('Please correct the passport upload.', ['passport_photo' => $exception->getMessage()]);
        }

        try {
            $data['_context'] = $context;
            $employeeId = $this->employees->create($data);
        } catch (\Throwable $exception) {
            $photos->delete($data['photo_path']);
            throw $exception;
        }

        $employeeCode = $this->employees->employeeCodeById($employeeId);
        $employeeName = trim((string) $data['first_name'] . ' ' . (string) $data['last_name']);
        $subject = 'Welcome to ' . (string) $this->mail->setting('company_name', 'FuelOps') . ' - Your Employee Account Has Been Created';
        $mailSent = false;
        $mailError = null;
        try {
            $loginUrl = trim((string) $this->mail->setting('login_url', ''));
            $this->mail->sendTemplate((string) $data['email'], $employeeName, $subject, 'employee-welcome', [
                'employeeName' => $employeeName,
                'employeeId' => $employeeCode,
                'companyEmail' => (string) $data['company_email'],
                'password' => (string) $data['password'],
                'department' => (string) $data['department'],
                'role' => (string) $data['role'],
                'dateJoined' => date('F j, Y', strtotime((string) $data['date_joined'])),
                'loginUrl' => $loginUrl !== '' ? $loginUrl : route_url('login'),
                'companyLogo' => (string) $this->mail->setting('company_logo', ''),
                'companyName' => (string) $this->mail->setting('company_name', 'FuelOps'),
                'companyAddress' => (string) $this->mail->setting('company_address', ''),
                'companyPhone' => (string) $this->mail->setting('company_phone', ''),
                'companyContactEmail' => (string) $this->mail->setting('company_email', ''),
                'emailSubject' => $subject,
            ]);
            $mailSent = true;
        } catch (Throwable $exception) {
            $mailError = $exception->getMessage();
            error_log('Welcome email failed for ' . $employeeCode . ': ' . $mailError);
        }
        $this->employees->recordWelcomeEmailStatus($employeeId, $employeeCode, $mailSent, $mailError, $context);

        return ['employee_id' => $employeeId, 'employee_code' => $employeeCode, 'mail_sent' => $mailSent];
    }
    public function resetPassword(string $employeeCode, array $context = []): array
    {
        $employee = $this->employees->findForView($employeeCode);
        if ($employee === null || empty($employee['user_id'])) {
            throw new RuntimeException('Employee user account not found.');
        }

        $temporaryPassword = $this->employees->resetPassword($employeeCode);
        $mailSent = false;
        $mailError = null;
        try {
            $loginUrl = trim((string) $this->mail->setting('login_url', ''));
            $this->mail->sendTemplate(
                (string) $employee['email'],
                (string) $employee['name'],
                'Your UMACHI Employee Account Password Has Been Reset',
                'employee-password-reset',
                [
                    'employeeName' => (string) $employee['name'],
                    'employeeId' => (string) $employee['id'],
                    'companyEmail' => (string) $employee['username'],
                    'password' => $temporaryPassword,
                    'department' => (string) $employee['department'],
                    'role' => (string) $employee['role'],
                    'dateJoined' => '',
                    'loginUrl' => $loginUrl !== '' ? $loginUrl : route_url('login'),
                    'companyLogo' => (string) $this->mail->setting('company_logo', ''),
                    'companyName' => (string) $this->mail->setting('company_name', 'FuelOps'),
                    'companyAddress' => (string) $this->mail->setting('company_address', ''),
                    'companyPhone' => (string) $this->mail->setting('company_phone', ''),
                    'companyContactEmail' => (string) $this->mail->setting('company_email', ''),
                    'emailSubject' => 'Your UMACHI Employee Account Password Has Been Reset',
                ]
            );
            $mailSent = true;
        } catch (Throwable $exception) {
            $mailError = $exception->getMessage();
            error_log('Password reset email failed for ' . $employeeCode . ': ' . $mailError);
        } finally {
            unset($temporaryPassword);
        }

        $this->employees->recordPasswordResetEmailStatus((int) $employee['db_id'], (string) $employee['id'], $mailSent, $mailError, $context);
        return ['employee' => (string) $employee['id'], 'mail_sent' => $mailSent];
    }


    public function update(string $employeeCode, array $data, array $files): void
    {
        $existing = $this->employees->findForView($employeeCode);
        if ($existing === null) {
            throw new RuntimeException('Employee record not found.');
        }

        unset($data['password'], $data['confirm_password'], $data['company_email'], $data['work_shift']);

        $data = $this->validateEmployee($data, true, $employeeCode);

        // The edit form intentionally exposes only personal and employment details.
        // Always preserve values managed elsewhere, even if extra fields are injected
        // into the request, so this endpoint cannot change hidden account information.
        foreach (['supervisor', 'salary', 'allowance', 'bank_name', 'account_name', 'account_number', 'username'] as $field) {
            $data[$field] = $existing[$field] ?? '';
        }

        $photos = new ProfilePhotoService();
        $oldPhotoPath = $this->employees->photoPathByCode($employeeCode);
        $removePhoto = (string) ($data['remove_photo'] ?? '') === '1';
        try {
            $photoPath = $removePhoto ? null : $photos->store($files['passport_photo'] ?? null);
        } catch (RuntimeException $exception) {
            throw new ValidationException('Please correct the passport upload.', ['passport_photo' => $exception->getMessage()]);
        }

        if ($removePhoto || $photoPath !== null) {
            $data['photo_path'] = $photoPath;
            $data['replace_photo'] = true;
        }

        try {
            $this->employees->updateByCode($employeeCode, $data);
        } catch (\Throwable $exception) {
            $photos->delete($photoPath);
            throw $exception;
        }

        if (($removePhoto || $photoPath !== null) && $oldPhotoPath !== $photoPath) {
            $photos->delete($oldPhotoPath);
        }

        $this->refreshSessionAvatar($employeeCode, $photoPath, $removePhoto);
    }
    public function uploadDocument(string $employeeCode, array $data, array $files): void
    {
        foreach (['document_type', 'document_title'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new ValidationException('Please complete the document details.', [$field => 'This field is required.']);
            }
        }

        if (!empty($data['expires_on']) && !$this->isValidDate((string) $data['expires_on'])) {
            throw new ValidationException('Please correct the document expiry date.', ['expires_on' => 'Enter a valid document expiry date.']);
        }

        try {
            $path = $this->handleUpload($files['employee_document'] ?? null, 'employees/documents', self::DOCUMENT_TYPES, true);
        } catch (RuntimeException $exception) {
            throw new ValidationException('Please correct the document upload.', ['employee_document' => $exception->getMessage()]);
        }
        $this->employees->uploadDocument($employeeCode, $data, (string) $path);
    }

    private function isValidDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value;
    }

    private function refreshSessionAvatar(string $employeeCode, ?string $photoPath, bool $removed): void
    {
        if (!$removed && $photoPath === null) {
            return;
        }

        $user = \App\Core\Session::get('auth.user', []);
        if (!is_array($user) || (string) ($user['employee_id'] ?? '') !== $employeeCode) {
            return;
        }

        $user['avatar'] = $photoPath;
        \App\Core\Session::put('auth.user', $user);
    }
    private function handleUpload(?array $file, string $folder, array $allowedTypes, bool $required): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                throw new RuntimeException('Please choose a file to upload.');
            }
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The uploaded file could not be processed.');
        }

        if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new RuntimeException('Uploaded files must not exceed 5MB.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpName) ?: '';
        if (!isset($allowedTypes[$mime])) {
            throw new RuntimeException('Unsupported file type.');
        }

        $uploadRoot = BASE_PATH . '/public/uploads/' . $folder;
        if (!is_dir($uploadRoot) && !mkdir($uploadRoot, 0755, true) && !is_dir($uploadRoot)) {
            throw new RuntimeException('Upload directory is not writable.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
        $target = $uploadRoot . '/' . $filename;

        if (!move_uploaded_file($tmpName, $target)) {
            throw new RuntimeException('Unable to save uploaded file.');
        }

        return 'uploads/' . $folder . '/' . $filename;
    }
}


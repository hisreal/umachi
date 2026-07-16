<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Shift;
use RuntimeException;

class ShiftManagementService
{
    public function __construct(private ?Shift $shifts = null, private ?AuthService $auth = null)
    {
        $this->shifts ??= new Shift();
        $this->auth ??= new AuthService();
    }

    public function model(): Shift
    {
        return $this->shifts;
    }

    public function canManage(): bool
    {
        return array_intersect(['Admin', 'Administrator', 'Supervisor'], $this->auth->roles()) !== [];
    }

    public function ensureCanManage(): void
    {
        if (!$this->canManage()) {
            throw new RuntimeException('You do not have permission to manage shifts.');
        }
    }

    public function store(array $data, array $context = []): int
    {
        $this->ensureCanManage();
        $data = $this->validate($data);

        return $this->shifts->create($data, $context);
    }

    public function update(int $id, array $data, array $context = []): void
    {
        $this->ensureCanManage();
        $data = $this->validate($data, $id);
        $this->shifts->updateShift($id, $data, $context);
    }

    public function delete(int $id, array $context = []): void
    {
        $this->ensureCanManage();
        $this->shifts->softDeleteShift($id, $context);
    }

    public function toggle(int $id, array $context = []): void
    {
        $this->ensureCanManage();
        $this->shifts->toggleStatus($id, $context);
    }

    public function validate(array $data, ?int $currentId = null): array
    {
        foreach (['shift_code', 'shift_name', 'reporting_time', 'closing_time', 'maximum_employees', 'status'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new RuntimeException(ucwords(str_replace('_', ' ', $field)) . ' is required.');
            }
        }

        $data['shift_code'] = strtoupper(trim((string) $data['shift_code']));
        if (!preg_match('/^[A-Z0-9_-]{2,20}$/', $data['shift_code'])) {
            throw new RuntimeException('Shift code may contain only letters, numbers, hyphens, and underscores.');
        }

        if (!in_array((string) $data['status'], ['Active', 'Inactive'], true)) {
            throw new RuntimeException('Select a valid shift status.');
        }

        if (!$this->validTime((string) $data['reporting_time']) || !$this->validTime((string) $data['closing_time'])) {
            throw new RuntimeException('Enter valid reporting and closing times.');
        }

        if ((string) $data['reporting_time'] >= (string) $data['closing_time']) {
            throw new RuntimeException('Reporting time must be earlier than closing time.');
        }

        if (filter_var($data['maximum_employees'], FILTER_VALIDATE_INT) === false || (int) $data['maximum_employees'] <= 0) {
            throw new RuntimeException('Maximum employees must be greater than zero.');
        }

        $grace = $data['grace_period'] ?? 0;
        if (filter_var($grace, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 120]]) === false) {
            throw new RuntimeException('Grace period must be between 0 and 120 minutes.');
        }

        if ($this->shifts->valueExists('shift_code', $data['shift_code'], $currentId)) {
            throw new RuntimeException('Shift code already exists.');
        }

        if ($this->shifts->valueExists('shift_name', (string) $data['shift_name'], $currentId)) {
            throw new RuntimeException('Shift name already exists.');
        }

        if ((string) $data['status'] === 'Active' && $this->shifts->overlaps((string) $data['reporting_time'], (string) $data['closing_time'], $currentId)) {
            throw new RuntimeException('Shift time overlaps with an existing active shift.');
        }

        // Future Integration:
        // Validate employee assignment when Duty Roster is implemented.

        return $data;
    }

    private function validTime(string $value): bool
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }
}

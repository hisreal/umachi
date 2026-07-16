<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DutyManagement;
use RuntimeException;

class DutyManagementService
{
    public function __construct(private ?DutyManagement $duties = null, private ?AuthService $auth = null)
    {
        $this->duties ??= new DutyManagement();
        $this->auth ??= new AuthService();
    }

    public function model(): DutyManagement
    {
        return $this->duties;
    }

    public function canManage(): bool
    {
        return array_intersect(['Admin', 'Administrator', 'Manager', 'Supervisor'], $this->auth->roles()) !== [];
    }

    public function canView(): bool
    {
        return array_intersect(['Admin', 'Manager', 'Supervisor', 'Accountant'], $this->auth->roles()) !== [];
    }

    public function ensureCanManage(): void
    {
        if (!$this->canManage()) {
            throw new RuntimeException('You do not have permission to manage duty schedules.');
        }
    }

    public function saveRoster(array $data, array $context = []): int
    {
        $this->ensureCanManage();

        return $this->duties->saveRoster($data, $context);
    }

    public function changeRosterStatus(int $id, string $status, array $context = []): void
    {
        $this->ensureCanManage();
        $this->duties->changeRosterStatus($id, $status, $context);
    }

    public function deleteRoster(int $id, array $context = []): void
    {
        $this->ensureCanManage();
        $this->duties->deleteRoster($id, $context);
    }

    public function saveAssignment(array $data, array $context = []): int
    {
        $this->ensureCanManage();

        return $this->duties->saveAssignment($data, $context);
    }

    public function cancelAssignment(int $id, array $context = []): void
    {
        $this->ensureCanManage();
        $this->duties->cancelAssignment($id, $context);
    }

    public function deleteAssignment(int $id, array $context = []): void
    {
        $this->ensureCanManage();
        $this->duties->deleteAssignment($id, $context);
    }
}

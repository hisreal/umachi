<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Pump;
use RuntimeException;

class PumpManagementService
{
    private const FUEL_TYPES = ['Petrol', 'Diesel', 'Gas', 'Petrol (PMS)', 'Diesel (AGO)', 'Gas (LPG)'];
    private const STATUSES = ['Active', 'Inactive', 'Maintenance', 'Under Maintenance', 'Faulty'];

    public function __construct(private ?Pump $pumps = null, private ?AuthService $auth = null)
    {
        $this->pumps ??= new Pump();
        $this->auth ??= new AuthService();
    }

    public function model(): Pump
    {
        return $this->pumps;
    }

    public function canManage(): bool
    {
        return in_array('Admin', $this->auth->roles(), true);
    }

    public function ensureCanManage(): void
    {
        if (!$this->canManage()) {
            throw new RuntimeException('You do not have permission to manage pumps.');
        }
    }

    public function store(array $data, array $context = []): int
    {
        $this->ensureCanManage();
        $data = $this->validate($data);

        return $this->pumps->create($data, $context);
    }

    public function update(int $id, array $data, array $context = []): void
    {
        $this->ensureCanManage();
        $data = $this->validate($data, $id);
        $this->pumps->updatePump($id, $data, $context);
    }

    public function delete(int $id, array $context = []): void
    {
        $this->ensureCanManage();
        $this->pumps->softDeletePump($id, $context);
    }

    public function toggle(int $id, array $context = []): void
    {
        $this->ensureCanManage();
        $this->pumps->toggleStatus($id, $context);
    }

    public function validate(array $data, ?int $currentId = null): array
    {
        $required = ['pump_number', 'pump_name', 'fuel_type', 'manufacturer', 'serial_number', 'installation_date', 'meter', 'status'];
        foreach ($required as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new RuntimeException(ucwords(str_replace('_', ' ', $field)) . ' is required.');
            }
        }

        if (!in_array((string) $data['fuel_type'], self::FUEL_TYPES, true)) {
            throw new RuntimeException('Select a valid fuel type.');
        }

        if (!in_array((string) $data['status'], self::STATUSES, true)) {
            throw new RuntimeException('Select a valid pump status.');
        }

        if (!$this->validDate((string) $data['installation_date'])) {
            throw new RuntimeException('Enter a valid installation date.');
        }

        if (!is_numeric($data['meter']) || (float) $data['meter'] < 0) {
            throw new RuntimeException('Current meter reading must be a number greater than or equal to zero.');
        }

        if ($this->pumps->valueExists('pump_number', (string) $data['pump_number'], $currentId)) {
            throw new RuntimeException('Pump number already exists.');
        }

        if ($this->pumps->valueExists('serial_number', (string) $data['serial_number'], $currentId)) {
            throw new RuntimeException('Serial number already exists.');
        }

        return $data;
    }

    private function validDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value;
    }
}

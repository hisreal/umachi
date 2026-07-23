<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

final class AttendanceDutyPolicyService
{
    private Config $config;

    public function __construct(?Config $config = null)
    {
        $this->config = $config ?? new Config(CONFIG_PATH);
    }

    /** @return string[] */
    public function automaticDutyRoles(): array
    {
        return $this->configuredRoles('attendance.automatic_duty_roles');
    }

    /** @return string[] */
    public function manualDutyRoles(): array
    {
        return $this->configuredRoles('attendance.manual_duty_roles');
    }

    public function isAutomaticDutyRole(string $role): bool
    {
        return in_array($this->normalize($role), $this->normalized($this->automaticDutyRoles()), true);
    }

    public function requiresManualDuty(string $role): bool
    {
        return in_array($this->normalize($role), $this->normalized($this->manualDutyRoles()), true);
    }

    /** @return string[] */
    public function clockOutSelfieExemptRoles(): array
    {
        return $this->configuredRoles('attendance.clock_out_selfie_exempt_roles');
    }

    public function requiresClockOutSelfie(string $role): bool
    {
        return !$this->requiresManualDuty($role)
            && !in_array($this->normalize($role), $this->normalized($this->clockOutSelfieExemptRoles()), true);
    }

    /** @return array<string, mixed> */
    public function virtualDutyContext(string $role): array
    {
        return [
            'duty_assignment_id' => null,
            'shift_id' => null,
            'pump_id' => null,
            'fuel_type_id' => null,
            'pump' => null,
            'shift_name' => 'Automatically Assigned',
            'shift_label' => 'Automatically Assigned',
            'reporting' => 'Automatic',
            'start_time' => null,
            'end_time' => null,
            'grace_period' => 0,
            'assignment_date' => date('Y-m-d'),
            'role' => $role,
            'is_automatic' => true,
        ];
    }

    /** @return string[] */
    private function configuredRoles(string $key): array
    {
        $roles = $this->config->get($key, []);

        return is_array($roles)
            ? array_values(array_unique(array_filter(array_map(static fn (mixed $role): string => trim((string) $role), $roles))))
            : [];
    }

    /** @param string[] $roles @return string[] */
    private function normalized(array $roles): array
    {
        return array_map([$this, 'normalize'], $roles);
    }

    private function normalize(string $role): string
    {
        return strtolower(trim($role));
    }
}

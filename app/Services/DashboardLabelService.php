<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;

final class DashboardLabelService
{
    /** @var array<string, string> */
    private const ROLE_LABELS = [
        'admin' => 'Admin',
        'administrator' => 'Admin',
        'manager' => 'Manager',
        'supervisor' => 'Supervisor',
        'accountant' => 'Accountant',
        'pump attendant' => 'Pump Attendant',
        'cashier' => 'Cashier',
        'security' => 'Security',
        'driver' => 'Driver',
    ];

    public static function forCurrentUser(): string
    {
        return self::forRole(self::currentRole());
    }

    public static function forRole(string $role): string
    {
        $normalized = strtolower(trim(str_replace(['_', '-'], ' ', $role)));
        $roleLabel = self::ROLE_LABELS[$normalized] ?? self::titleCaseRole($normalized);

        return ($roleLabel !== '' ? $roleLabel : 'Staff') . ' Dashboard';
    }

    private static function currentRole(): string
    {
        $activeRole = trim((string) Session::get('auth.role', ''));
        if ($activeRole !== '') {
            return $activeRole;
        }

        $roles = Session::get('auth.roles', []);
        return is_array($roles) ? trim((string) ($roles[0] ?? '')) : '';
    }

    private static function titleCaseRole(string $role): string
    {
        return implode(' ', array_map(
            static fn (string $part): string => ucfirst($part),
            array_filter(explode(' ', $role))
        ));
    }
}

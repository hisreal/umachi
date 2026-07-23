<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Session;
use Throwable;

class SettingsModel extends BaseModel
{
    private const FUEL_TYPES = [
        'petrol' => ['name' => 'Petrol', 'short_name' => 'Petrol', 'label' => 'Petrol (Petrol)', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'primary'],
        'ago' => ['name' => 'Diesel', 'short_name' => 'AGO', 'label' => 'Diesel (AGO)', 'icon' => 'fa-solid fa-oil-can', 'tone' => 'warning'],
        'lpg' => ['name' => 'Gas', 'short_name' => 'LPG', 'label' => 'Gas (LPG)', 'icon' => 'fa-solid fa-fire-flame-simple', 'tone' => 'info'],
    ];

    public function currentFuelPrices(): array
    {
        $this->ensureFuelTypes();
        $rows = $this->query(
            "SELECT ft.id AS fuel_type_id, ft.name, ft.short_name, fp.price_per_litre, fp.effective_from, fp.created_at,
                    COALESCE(CONCAT(e.first_name, ' ', e.last_name), u.username, 'System') AS updated_by
             FROM fuel_types ft
             LEFT JOIN fuel_prices fp ON fp.id = (
                 SELECT fp2.id FROM fuel_prices fp2
                 WHERE fp2.fuel_type_id = ft.id AND fp2.status = 'active'
                 ORDER BY fp2.effective_from DESC, fp2.id DESC LIMIT 1
             )
             LEFT JOIN users u ON u.id = fp.created_by
             LEFT JOIN employees e ON e.id = u.employee_id
             WHERE ft.short_name IN ('Petrol', 'AGO', 'LPG')
             ORDER BY FIELD(ft.short_name, 'Petrol', 'AGO', 'LPG')"
        );

        $prices = [];
        foreach ($rows as $row) {
            $key = strtolower((string) $row['short_name']);
            $meta = self::FUEL_TYPES[$key] ?? [];
            $prices[$key] = [
                'fuel_type_id' => (int) $row['fuel_type_id'],
                'fuel' => $meta['label'] ?? ((string) $row['name'] . ' (' . (string) $row['short_name'] . ')'),
                'short_name' => (string) $row['short_name'],
                'price' => (float) ($row['price_per_litre'] ?? 0),
                'updated_by' => (string) ($row['updated_by'] ?? 'System'),
                'effective_date' => $row['effective_from'] ? date('Y-m-d', strtotime((string) $row['effective_from'])) : date('Y-m-d'),
                'effective_time' => $row['effective_from'] ? date('H:i', strtotime((string) $row['effective_from'])) : date('H:i'),
                'effective_from' => (string) ($row['effective_from'] ?? ''),
                'icon' => $meta['icon'] ?? 'fa-solid fa-gas-pump',
                'tone' => $meta['tone'] ?? 'primary',
            ];
        }

        return $prices;
    }

    public function fuelPriceHistory(int $limit = 50): array
    {
        $this->ensureFuelTypes();
        return array_map(static function (array $row): array {
            return [
                'id' => 'FPH-' . str_pad((string) $row['id'], 4, '0', STR_PAD_LEFT),
                'date' => date('Y-m-d h:i A', strtotime((string) $row['created_at'])),
                'fuel_type' => (string) $row['fuel_label'],
                'old_price' => $row['old_price'] === null ? 0.0 : (float) $row['old_price'],
                'new_price' => (float) $row['new_price'],
                'updated_by' => (string) ($row['updated_by'] ?: 'System'),
                'effective_date' => date('Y-m-d', strtotime((string) $row['effective_from'])),
            ];
        }, $this->query(
            "SELECT fph.id, fph.old_price, fph.new_price, fph.effective_from, fph.created_at,
                    CONCAT(ft.name, ' (', ft.short_name, ')') AS fuel_label,
                    COALESCE(CONCAT(e.first_name, ' ', e.last_name), u.username, 'System') AS updated_by
             FROM fuel_price_history fph
             INNER JOIN fuel_types ft ON ft.id = fph.fuel_type_id
             LEFT JOIN users u ON u.id = fph.changed_by
             LEFT JOIN employees e ON e.id = u.employee_id
             ORDER BY fph.created_at DESC, fph.id DESC
             LIMIT {$limit}"
        ));
    }

    public function saveFuelPrices(array $prices, string $effectiveFrom, ?string $remarks = null): void
    {
        $fuelTypes = $this->ensureFuelTypes();
        $userId = $this->currentUserId();

        $this->transaction(function (Database $database) use ($prices, $effectiveFrom, $fuelTypes, $userId, $remarks): void {
            foreach (self::FUEL_TYPES as $key => $meta) {
                $newPrice = (float) ($prices[$key] ?? 0);
                if ($newPrice <= 0) {
                    throw new \RuntimeException($meta['label'] . ' price must be greater than zero.');
                }

                $fuelTypeId = $fuelTypes[$key];
                $current = $database->selectOne(
                    "SELECT id, price_per_litre FROM fuel_prices WHERE fuel_type_id = :fuel_type_id AND status = 'active' ORDER BY effective_from DESC, id DESC LIMIT 1",
                    ['fuel_type_id' => $fuelTypeId]
                );
                $oldPrice = $current === null ? null : (float) $current['price_per_litre'];

                if ($oldPrice !== null && abs($oldPrice - $newPrice) < 0.01) {
                    continue;
                }

                $database->execute(
                    "UPDATE fuel_prices SET status = 'expired', effective_to = :effective_to WHERE fuel_type_id = :fuel_type_id AND status = 'active'",
                    ['effective_to' => $effectiveFrom, 'fuel_type_id' => $fuelTypeId]
                );
                $database->insert('fuel_prices', [
                    'fuel_type_id' => $fuelTypeId,
                    'price_per_litre' => $newPrice,
                    'effective_from' => $effectiveFrom,
                    'effective_to' => null,
                    'status' => 'active',
                    'created_by' => $userId,
                ]);
                $database->insert('fuel_price_history', [
                    'fuel_type_id' => $fuelTypeId,
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                    'effective_from' => $effectiveFrom,
                    'changed_by' => $userId,
                ]);

                $this->logActivity('Updated ' . $meta['label'] . ' Price', 'Fuel Price Update', 'Fuel Pricing', $oldPrice, $newPrice, 'success', $remarks);
            }
        });
    }

    public function settings(string $group): array
    {
        $rows = $this->query('SELECT setting_key, setting_value, value_type FROM system_settings WHERE setting_group = :setting_group', ['setting_group' => $group]);
        $settings = [];
        foreach ($rows as $row) {
            $settings[(string) $row['setting_key']] = $this->decodeSetting($row['setting_value'], (string) $row['value_type']);
        }

        return $settings;
    }

    public function saveSettings(string $group, array $settings, bool $isPublic = false): void
    {
        $oldSettings = [];
        foreach ($settings as $key => $value) {
            $type = $this->settingType($value);
            $payload = [
                'setting_group' => $group,
                'setting_key' => (string) $key,
                'setting_value' => json_encode($value, JSON_THROW_ON_ERROR),
                'value_type' => $type,
                'is_public' => $isPublic ? 1 : 0,
                'updated_by' => $this->currentUserId(),
            ];
            $existing = $this->queryOne('SELECT id, setting_value, value_type FROM system_settings WHERE setting_group = :setting_group AND setting_key = :setting_key LIMIT 1', [
                'setting_group' => $group,
                'setting_key' => (string) $key,
            ]);
            if ($existing !== null) {
                $oldSettings[(string) $key] = $this->decodeSetting($existing['setting_value'] ?? null, (string) ($existing['value_type'] ?? 'string'));
            }
            $existing === null ? $this->insert('system_settings', $payload) : $this->update('system_settings', $payload, ['id' => (int) $existing['id']]);
        }
        if ($settings !== []) {
            $label = ucfirst($group) . ' Settings Updated';
            $this->logActivity(
                $label,
                $label,
                'Settings',
                $oldSettings,
                $settings,
                'success'
            );
        }
    }

    public function ensureFuelTypes(): array
    {
        $ids = [];
        foreach (self::FUEL_TYPES as $key => $fuel) {
            $row = $this->queryOne(
                'SELECT id, name, short_name FROM fuel_types
                 WHERE short_name = :short_name OR name = :name
                 ORDER BY CASE WHEN short_name = :preferred_short_name THEN 0 ELSE 1 END, id
                 LIMIT 1',
                [
                    'short_name' => $fuel['short_name'],
                    'name' => $fuel['name'],
                    'preferred_short_name' => $fuel['short_name'],
                ]
            );
            if ($row === null) {
                $ids[$key] = (int) $this->insert('fuel_types', [
                    'name' => $fuel['name'],
                    'short_name' => $fuel['short_name'],
                    'unit' => 'litre',
                    'status' => 'active',
                ]);
                continue;
            }
            if ((string) $row['name'] !== $fuel['name'] || (string) $row['short_name'] !== $fuel['short_name']) {
                $this->update('fuel_types', [
                    'name' => $fuel['name'],
                    'short_name' => $fuel['short_name'],
                    'unit' => 'litre',
                    'status' => 'active',
                ], ['id' => (int) $row['id']]);
            }
            $ids[$key] = (int) $row['id'];
        }

        return $ids;
    }

    private function decodeSetting(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }
        $decoded = json_decode((string) $value, true);
        return match ($type) {
            'number' => (float) $decoded,
            'boolean' => (bool) $decoded,
            default => $decoded,
        };
    }

    private function settingType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value), is_float($value) => 'number',
            is_array($value) => 'json',
            default => 'string',
        };
    }

    private function currentUserId(): ?int
    {
        $userId = Session::get('auth.user_id');
        if ($userId === null) {
            return null;
        }

        $exists = $this->database()->value(
            'SELECT id FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            ['id' => (int) $userId]
        );

        return $exists === null ? null : (int) $userId;
    }

    private function logActivity(string $activity, string $type, string $module, mixed $oldValue, mixed $newValue, string $status, ?string $notes = null): void
    {
        try {
            $this->insert('activity_logs', [
                'log_code' => 'ACT-' . date('YmdHis') . '-' . random_int(100, 999),
                'user_id' => $this->currentUserId(),
                'activity_type' => $type,
                'module' => $module,
                'activity' => $activity,
                'entity_type' => 'setting',
                'old_value' => $oldValue === null ? null : json_encode($oldValue, JSON_THROW_ON_ERROR),
                'new_value' => json_encode($newValue, JSON_THROW_ON_ERROR),
                'status' => $status,
                'notes' => $notes,
            ]);
        } catch (Throwable) {
            // Settings updates must not fail because audit logging is unavailable.
        }
    }
}

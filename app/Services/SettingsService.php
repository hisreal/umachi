<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SettingsModel;
use RuntimeException;

class SettingsService
{
    public function __construct(private ?SettingsModel $settings = null)
    {
        $this->settings ??= new SettingsModel();
    }

    public function model(): SettingsModel
    {
        return $this->settings;
    }

    public function saveFuelPrices(array $data): void
    {
        $prices = [
            'pms' => $this->number($data['pms_price'] ?? null, 'Petrol (PMS)'),
            'ago' => $this->number($data['ago_price'] ?? null, 'Diesel (AGO)'),
            'lpg' => $this->number($data['lpg_price'] ?? null, 'Gas (LPG)'),
        ];

        $date = trim((string) ($data['effective_date'] ?? ''));
        $time = trim((string) ($data['effective_time'] ?? ''));
        if (!$this->validDate($date) || !$this->validTime($time)) {
            throw new RuntimeException('Select a valid effective date and time.');
        }

        $this->settings->saveFuelPrices($prices, $date . ' ' . $time . ':00', trim((string) ($data['remarks'] ?? '')) ?: null);
    }

    public function saveCompanyInformation(array $data): void
    {
        $required = ['company_name', 'email', 'phone', 'address'];
        foreach ($required as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new RuntimeException('Company name, email, phone, and address are required.');
            }
        }
        if (!filter_var((string) $data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Enter a valid company email address.');
        }

        $this->settings->saveSettings('company', [
            'company_name' => trim((string) $data['company_name']),
            'email' => trim((string) $data['email']),
            'phone' => trim((string) $data['phone']),
            'address' => trim((string) $data['address']),
            'website' => trim((string) ($data['website'] ?? '')),
        ], true);
    }

    public function saveSystemSettings(array $data): void
    {
        $this->settings->saveSettings('system', [
            'timezone' => trim((string) ($data['timezone'] ?? 'Africa/Lagos')),
            'currency' => trim((string) ($data['currency'] ?? 'NGN')),
            'date_format' => trim((string) ($data['date_format'] ?? 'd M Y')),
            'maintenance_mode' => isset($data['maintenance_mode']),
        ]);
    }

    private function number(mixed $value, string $label): float
    {
        $number = (float) str_replace(',', '', (string) $value);
        if ($number <= 0) {
            throw new RuntimeException($label . ' price must be greater than zero.');
        }

        return $number;
    }

    private function validDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value;
    }

    private function validTime(string $value): bool
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }
}

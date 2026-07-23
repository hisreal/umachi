<?php

declare(strict_types=1);


try {
    $settingsModel = new \App\Models\SettingsModel();
    $fuelPrices = $settingsModel->currentFuelPrices();
    $priceHistory = $settingsModel->fuelPriceHistory();
} catch (Throwable) {
    $fuelPrices = [
        'Petrol' => ['fuel' => 'Petrol (Petrol)', 'price' => 0.00, 'updated_by' => 'System', 'effective_date' => date('Y-m-d'), 'effective_time' => date('H:i'), 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'primary'],
        'ago' => ['fuel' => 'Diesel (AGO)', 'price' => 0.00, 'updated_by' => 'System', 'effective_date' => date('Y-m-d'), 'effective_time' => date('H:i'), 'icon' => 'fa-solid fa-oil-can', 'tone' => 'warning'],
        'lpg' => ['fuel' => 'Gas (LPG)', 'price' => 0.00, 'updated_by' => 'System', 'effective_date' => date('Y-m-d'), 'effective_time' => date('H:i'), 'icon' => 'fa-solid fa-fire-flame-simple', 'tone' => 'info'],
    ];
    $priceHistory = [];
}

$priceCards = [];
foreach ($fuelPrices as $price) {
    $priceCards[] = [
        'label' => $price['fuel'] . ' Price',
        'value' => 'NGN ' . number_format((float) $price['price'], 2) . '/Litre',
        'icon' => $price['icon'],
        'tone' => $price['tone'],
    ];
}
$lastUpdated = $priceHistory[0]['date'] ?? 'N/A';
$priceCards[] = ['label' => 'Last Updated', 'value' => $lastUpdated, 'icon' => 'fa-solid fa-clock-rotate-left', 'tone' => 'success'];
$activityStatusClasses = [
    'Success' => 'settings-status--success',
    'Failed' => 'settings-status--failed',
    'Warning' => 'settings-status--warning',
    'Information' => 'settings-status--info',
];


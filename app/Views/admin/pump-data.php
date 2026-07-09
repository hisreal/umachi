<?php

declare(strict_types=1);

// ===========================================
// DATABASE PLACEHOLDER
// Retrieve pumps from MySQL.
// ===========================================
$pumps = [
    ['id' => 1, 'pump_number' => 'Pump 1', 'pump_name' => 'Main Forecourt Pump', 'fuel_type' => 'Petrol (PMS)', 'status' => 'Active', 'meter' => 125000, 'last_updated' => '2026-07-08 02:15 PM', 'manufacturer' => 'Gilbarco', 'model' => 'Encore 700S', 'serial_number' => 'GLB-700S-001', 'installation_date' => '2022-01-15', 'notes' => 'Primary PMS dispenser on the main forecourt.'],
    ['id' => 2, 'pump_number' => 'Pump 2', 'pump_name' => 'Diesel Pump', 'fuel_type' => 'Diesel (AGO)', 'status' => 'Under Maintenance', 'meter' => 84500, 'last_updated' => '2026-07-08 01:50 PM', 'manufacturer' => 'Wayne', 'model' => 'Helix 6000', 'serial_number' => 'WYN-HLX-002', 'installation_date' => '2022-03-10', 'notes' => 'Scheduled nozzle and calibration inspection.'],
    ['id' => 3, 'pump_number' => 'Pump 3', 'pump_name' => 'Side Lane Pump', 'fuel_type' => 'Petrol (PMS)', 'status' => 'Active', 'meter' => 98590, 'last_updated' => '2026-07-08 02:05 PM', 'manufacturer' => 'Tokheim', 'model' => 'Quantium 510', 'serial_number' => 'TKH-Q510-003', 'installation_date' => '2023-06-18', 'notes' => 'Serves the side lane during peak traffic.'],
    ['id' => 4, 'pump_number' => 'Pump 4', 'pump_name' => 'Gas Pump', 'fuel_type' => 'Gas (LPG)', 'status' => 'Inactive', 'meter' => 45220, 'last_updated' => '2026-07-07 10:20 PM', 'manufacturer' => 'Tatsuno', 'model' => 'Sunny GL', 'serial_number' => 'TAT-SGL-004', 'installation_date' => '2024-02-02', 'notes' => 'Inactive pending final safety clearance.'],
];

$fuelTypes = ['Petrol (PMS)', 'Diesel (AGO)', 'Gas (LPG)'];
$pumpStatuses = ['Active', 'Inactive', 'Under Maintenance', 'Faulty'];

$selectedPump = $pumps[0];
$requestedPumpId = (int) ($_GET['pump'] ?? 0);
foreach ($pumps as $pumpRecord) {
    if ($pumpRecord['id'] === $requestedPumpId) {
        $selectedPump = $pumpRecord;
        break;
    }
}

$pumpStatusClasses = [
    'Active' => 'pump-status--active',
    'Inactive' => 'pump-status--inactive',
    'Under Maintenance' => 'pump-status--maintenance',
    'Faulty' => 'pump-status--faulty',
];

$pumpStats = [
    ['label' => 'Total Pumps', 'value' => count($pumps) . ' Pumps', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'primary'],
    ['label' => 'Active Pumps', 'value' => count(array_filter($pumps, static fn (array $pump): bool => $pump['status'] === 'Active')) . ' Active', 'icon' => 'fa-solid fa-circle-check', 'tone' => 'success'],
    ['label' => 'Inactive Pumps', 'value' => count(array_filter($pumps, static fn (array $pump): bool => $pump['status'] === 'Inactive')) . ' Inactive', 'icon' => 'fa-solid fa-circle-pause', 'tone' => 'danger'],
    ['label' => 'Pumps Under Maintenance', 'value' => count(array_filter($pumps, static fn (array $pump): bool => $pump['status'] === 'Under Maintenance')) . ' Pump', 'icon' => 'fa-solid fa-screwdriver-wrench', 'tone' => 'warning'],
    ['label' => 'Fuel Distribution', 'value' => '2 Petrol / 1 Diesel / 1 Gas', 'icon' => 'fa-solid fa-chart-pie', 'tone' => 'info'],
];

<?php

declare(strict_types=1);

// ===========================================
// DATABASE PLACEHOLDER
// Retrieve fuel sales from MySQL.
// ===========================================
$fuelSales = [
    ['transaction_id' => 'FS001', 'date' => '2026-07-08', 'pump' => 'Pump 1', 'fuel_type' => 'Petrol (PMS)', 'attendant' => 'John Doe', 'shift' => 'Morning', 'opening_meter' => 125000, 'closing_meter' => 125450, 'liters_sold' => 450, 'amount' => 567000, 'status' => 'Pending', 'submitted_time' => '02:12 PM'],
    ['transaction_id' => 'FS002', 'date' => '2026-07-08', 'pump' => 'Pump 2', 'fuel_type' => 'Diesel (AGO)', 'attendant' => 'Mary Johnson', 'shift' => 'Evening', 'opening_meter' => 84500, 'closing_meter' => 84810, 'liters_sold' => 310, 'amount' => 620000, 'status' => 'Verified', 'submitted_time' => '10:08 PM'],
    ['transaction_id' => 'FS003', 'date' => '2026-07-08', 'pump' => 'Pump 3', 'fuel_type' => 'Petrol (PMS)', 'attendant' => 'Daniel James', 'shift' => 'Morning', 'opening_meter' => 98040, 'closing_meter' => 98590, 'liters_sold' => 550, 'amount' => 693000, 'status' => 'Verified', 'submitted_time' => '02:03 PM'],
    ['transaction_id' => 'FS004', 'date' => '2026-07-07', 'pump' => 'Pump 4', 'fuel_type' => 'Gas (LPG)', 'attendant' => 'Esther Grace', 'shift' => 'Evening', 'opening_meter' => 45000, 'closing_meter' => 45220, 'liters_sold' => 220, 'amount' => 242000, 'status' => 'Rejected', 'submitted_time' => '10:20 PM'],
    ['transaction_id' => 'FS005', 'date' => '2026-07-07', 'pump' => 'Pump 1', 'fuel_type' => 'Petrol (PMS)', 'attendant' => 'Samuel Peters', 'shift' => 'Morning', 'opening_meter' => 124520, 'closing_meter' => 125000, 'liters_sold' => 480, 'amount' => 604800, 'status' => 'Verified', 'submitted_time' => '02:00 PM'],
    ['transaction_id' => 'FS006', 'date' => '2026-07-06', 'pump' => 'Pump 2', 'fuel_type' => 'Diesel (AGO)', 'attendant' => 'Aisha Bello', 'shift' => 'Evening', 'opening_meter' => 84120, 'closing_meter' => 84500, 'liters_sold' => 380, 'amount' => 760000, 'status' => 'Pending', 'submitted_time' => '10:14 PM'],
];

$fuelTypes = ['Petrol (PMS)', 'Diesel (AGO)', 'Gas (LPG)'];
$pumps = ['Pump 1', 'Pump 2', 'Pump 3', 'Pump 4'];
$shifts = ['Morning', 'Evening'];
$salesStatuses = ['Pending', 'Verified', 'Rejected'];
$attendants = array_values(array_unique(array_column($fuelSales, 'attendant')));

$fuelSalesSummary = [
    ['label' => "Today's Total Sales", 'value' => 'NGN ' . number_format(1880000), 'icon' => 'fa-solid fa-money-bill-trend-up', 'tone' => 'primary'],
    ['label' => 'Total Liters Sold', 'value' => number_format(1530) . ' L', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'success'],
    ['label' => 'Total Transactions', 'value' => count($fuelSales), 'icon' => 'fa-solid fa-receipt', 'tone' => 'info'],
    ['label' => 'Pending Verification', 'value' => '2 Pending', 'icon' => 'fa-solid fa-hourglass-half', 'tone' => 'warning'],
    ['label' => 'Verified Sales', 'value' => '3 Verified', 'icon' => 'fa-solid fa-circle-check', 'tone' => 'success'],
    ['label' => 'Active Pumps', 'value' => '4 / 4 Active', 'icon' => 'fa-solid fa-oil-can', 'tone' => 'secondary'],
];

$salesStatusClasses = [
    'Pending' => 'fuel-status--pending',
    'Verified' => 'fuel-status--verified',
    'Rejected' => 'fuel-status--rejected',
];

$selectedSale = $fuelSales[0];
$requestedTransactionId = (string) ($_GET['transaction'] ?? '');
foreach ($fuelSales as $saleRecord) {
    if ($saleRecord['transaction_id'] === $requestedTransactionId) {
        $selectedSale = $saleRecord;
        break;
    }
}

$pumpMeterReadings = array_map(static function (array $sale): array {
    return [
        'date' => $sale['date'],
        'pump' => $sale['pump'],
        'fuel_type' => $sale['fuel_type'],
        'opening_meter' => $sale['opening_meter'],
        'closing_meter' => $sale['closing_meter'],
        'difference' => $sale['closing_meter'] - $sale['opening_meter'],
        'shift' => $sale['shift'],
        'attendant' => $sale['attendant'],
        'status' => $sale['status'],
    ];
}, $fuelSales);

// ===========================================
// DATABASE PLACEHOLDER
// Load reports and analytics.
// ===========================================
$fuelReportKpis = [
    ['label' => 'Total Revenue', 'value' => 'NGN ' . number_format(array_sum(array_column($fuelSales, 'amount'))), 'icon' => 'fa-solid fa-naira-sign', 'tone' => 'primary'],
    ['label' => 'Total Liters Sold', 'value' => number_format(array_sum(array_column($fuelSales, 'liters_sold'))) . ' L', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'success'],
    ['label' => 'Average Daily Sales', 'value' => 'NGN ' . number_format(1164500), 'icon' => 'fa-solid fa-chart-line', 'tone' => 'info'],
    ['label' => 'Best Performing Pump', 'value' => 'Pump 1', 'icon' => 'fa-solid fa-trophy', 'tone' => 'warning'],
    ['label' => 'Best Selling Fuel Type', 'value' => 'Petrol (PMS)', 'icon' => 'fa-solid fa-fire-flame-curved', 'tone' => 'secondary'],
];

$fuelChartData = [
    'daily' => ['labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], 'values' => [880000, 920000, 1250000, 1180000, 1440000, 1680000, 1320000]],
    'weekly' => ['labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4'], 'values' => [6200000, 7100000, 6840000, 7900000]],
    'monthly' => ['labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], 'values' => [22000000, 24500000, 23800000, 26000000, 27500000, 29100000]],
    'fuelDistribution' => ['labels' => ['Petrol (PMS)', 'Diesel (AGO)', 'Gas (LPG)'], 'values' => [58, 30, 12]],
    'pumpPerformance' => ['labels' => ['Pump 1', 'Pump 2', 'Pump 3', 'Pump 4'], 'values' => [930, 690, 550, 220]],
];

$pumpMeterSummary = [
    ['label' => 'Total Pumps', 'value' => '4 Pumps', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'primary'],
    ['label' => 'Total Liters Dispensed Today', 'value' => '1,530 L', 'icon' => 'fa-solid fa-droplet', 'tone' => 'success'],
    ['label' => 'Highest Dispensing Pump', 'value' => 'Pump 1', 'icon' => 'fa-solid fa-arrow-trend-up', 'tone' => 'warning'],
    ['label' => 'Lowest Dispensing Pump', 'value' => 'Pump 4', 'icon' => 'fa-solid fa-arrow-trend-down', 'tone' => 'danger'],
];

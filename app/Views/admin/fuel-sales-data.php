<?php

declare(strict_types=1);

use App\Models\FuelSale;

try {
    $fuelSalesData = (new FuelSale())->dashboardData($_GET ?? []);
} catch (Throwable $exception) {
    $fuelSalesData = [
        'fuelSales' => [],
        'fuelSalesSummary' => [
            ['label' => "Today's Total Sales", 'value' => 'NGN 0.00', 'icon' => 'fa-solid fa-money-bill-trend-up', 'tone' => 'primary'],
            ['label' => 'Total Liters Sold', 'value' => '0.00 L', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'success'],
            ['label' => 'Total Transactions', 'value' => '0', 'icon' => 'fa-solid fa-receipt', 'tone' => 'info'],
            ['label' => 'Pending Verification', 'value' => '0 Pending', 'icon' => 'fa-solid fa-hourglass-half', 'tone' => 'warning'],
            ['label' => 'Verified Sales', 'value' => '0 Verified', 'icon' => 'fa-solid fa-circle-check', 'tone' => 'success'],
            ['label' => 'Active Pumps', 'value' => '0 / 0 Active', 'icon' => 'fa-solid fa-oil-can', 'tone' => 'secondary'],
        ],
        'fuelTypes' => [],
        'pumps' => [],
        'shifts' => [],
        'salesStatuses' => ['Pending', 'Verified', 'Rejected', 'Correction Requested'],
        'attendants' => [],
        'salesStatusClasses' => [
            'Pending' => 'fuel-status--pending',
            'Verified' => 'fuel-status--verified',
            'Rejected' => 'fuel-status--rejected',
            'Correction Requested' => 'fuel-status--pending',
            'Cancelled' => 'fuel-status--rejected',
        ],
        'selectedSale' => [
            'transaction_id' => 'N/A', 'date' => date('Y-m-d'), 'pump' => 'N/A', 'fuel_type' => 'N/A', 'attendant' => 'N/A',
            'shift' => 'N/A', 'opening_meter' => 0, 'closing_meter' => 0, 'liters_sold' => 0, 'amount' => 0,
            'amount_collected' => 0, 'expected_amount' => 0, 'variance' => 0, 'status' => 'Pending', 'submitted_time' => '-',
        ],
        'pumpMeterReadings' => [],
        'fuelReportKpis' => [],
        'fuelChartData' => ['daily' => ['labels' => [], 'values' => []], 'weekly' => ['labels' => [], 'values' => []], 'monthly' => ['labels' => [], 'values' => []], 'fuelDistribution' => ['labels' => [], 'values' => []], 'pumpPerformance' => ['labels' => [], 'values' => []]],
        'pumpMeterSummary' => [],
    ];
    $fuelSalesError = $exception->getMessage();
}

$fuelSales = $fuelSalesData['fuelSales'];
$fuelSalesSummary = $fuelSalesData['fuelSalesSummary'];
$fuelTypes = $fuelSalesData['fuelTypes'];
$pumps = $fuelSalesData['pumps'];
$shifts = $fuelSalesData['shifts'];
$salesStatuses = $fuelSalesData['salesStatuses'];
$attendants = $fuelSalesData['attendants'];
$salesStatusClasses = $fuelSalesData['salesStatusClasses'];
$selectedSale = $fuelSalesData['selectedSale'];
$pumpMeterReadings = $fuelSalesData['pumpMeterReadings'];
$fuelReportKpis = $fuelSalesData['fuelReportKpis'];
$fuelChartData = $fuelSalesData['fuelChartData'];
$pumpMeterSummary = $fuelSalesData['pumpMeterSummary'];

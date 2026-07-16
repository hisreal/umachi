<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Session;
use App\Models\Pump;
use App\Services\PumpManagementService;

$pumpModel = new Pump();
$canManagePumps = (new PumpManagementService($pumpModel))->canManage();
$request = Request::capture();
$pumpFilters = [
    'search' => (string) $request->query('search', ''),
    'fuel_type' => (string) $request->query('fuel_type', ''),
    'status' => (string) $request->query('status', ''),
    'manufacturer' => (string) $request->query('manufacturer', ''),
    'year' => (string) $request->query('year', ''),
    'sort' => (string) $request->query('sort', 'pump_number'),
    'direction' => (string) $request->query('direction', 'asc'),
    'page' => (int) $request->query('page', 1),
    'per_page' => 20,
];

$pumpResult = $pumpModel->paginated($pumpFilters);
$pumps = $pumpResult['records'];
$pagination = $pumpResult['pagination'];
$pumpOptions = $pumpModel->filters();
$fuelTypes = $pumpOptions['fuel_types'];
$pumpStatuses = $pumpOptions['statuses'];
$pumpManufacturers = $pumpOptions['manufacturers'];
$installationYears = $pumpOptions['years'];
$pumpSuccess = Session::pullFlash('pump_success');
$pumpError = Session::pullFlash('pump_error');

$selectedPump = null;
$requestedPumpId = (int) $request->query('pump', 0);
if ($requestedPumpId > 0) {
    $selectedPump = $pumpModel->findForView($requestedPumpId);
}

if ($selectedPump === null) {
    $selectedPump = [
        'id' => 0,
        'pump_number' => '',
        'pump_name' => '',
        'fuel_type' => '',
        'status' => 'Active',
        'manufacturer' => '',
        'model' => '',
        'serial_number' => '',
        'installation_date' => date('Y-m-d'),
        'meter' => '',
        'notes' => '',
    ];
}

$pumpStatusClasses = [
    'Active' => 'pump-status--active',
    'Inactive' => 'pump-status--inactive',
    'Under Maintenance' => 'pump-status--maintenance',
    'Faulty' => 'pump-status--faulty',
];

$summary = $pumpModel->summary();
$pumpStats = [
    ['label' => 'Total Pumps', 'value' => $summary['total'] . ' Pumps', 'icon' => 'fa-solid fa-gas-pump', 'tone' => 'primary'],
    ['label' => 'Active Pumps', 'value' => $summary['active'] . ' Active', 'icon' => 'fa-solid fa-circle-check', 'tone' => 'success'],
    ['label' => 'Inactive Pumps', 'value' => $summary['inactive'] . ' Inactive', 'icon' => 'fa-solid fa-circle-pause', 'tone' => 'danger'],
    ['label' => 'Pumps Under Maintenance', 'value' => $summary['maintenance'] . ' Pump', 'icon' => 'fa-solid fa-screwdriver-wrench', 'tone' => 'warning'],
    ['label' => 'Faulty Pumps', 'value' => $summary['faulty'] . ' Faulty', 'icon' => 'fa-solid fa-triangle-exclamation', 'tone' => 'danger'],
    ['label' => 'Fuel Distribution', 'value' => $summary['petrol'] . ' Petrol / ' . $summary['diesel'] . ' Diesel / ' . $summary['gas'] . ' Gas', 'icon' => 'fa-solid fa-chart-pie', 'tone' => 'info'],
];

$exportQuery = http_build_query(array_filter($pumpFilters, static fn (mixed $value): bool => $value !== '' && $value !== null && $value !== 1 && $value !== 20));
<?php

declare(strict_types=1);

$pageTitle = 'Add New Pump | FuelOps Admin Dashboard';
$pageHeading = 'Add New Pump';
$currentRoute = 'admin/add-pump';
require __DIR__ . '/pump-page-setup.php';
$formMode = 'add';
$formPump = ['id' => 0, 'pump_number' => '', 'pump_name' => '', 'fuel_type' => '', 'status' => 'Active', 'manufacturer' => '', 'model' => '', 'serial_number' => '', 'installation_date' => date('Y-m-d'), 'meter' => '', 'notes' => ''];
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page pump-module-page"><section class="clock-hero pump-hero"><div class="container-fluid"><nav class="pump-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Pump Management</span><i class="fa-solid fa-chevron-right"></i><span>Add Pump</span></nav><div class="clock-hero__content pump-hero-card"><div><span class="eyebrow">Pump Registration</span><h1>Add New Pump</h1><p>Register a new pump and assign its fuel type, meter reading, and operating status.</p></div><span class="pump-hero-icon"><i class="fa-solid fa-plus"></i></span></div></div></section><section class="container-fluid clock-workspace"><?php require __DIR__ . '/pump-form.php'; ?></section></main><?php require __DIR__ . '/../includes/footer.php'; ?>

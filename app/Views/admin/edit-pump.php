<?php

declare(strict_types=1);

$pageTitle = 'Edit Pump | FuelOps Admin Dashboard';
$pageHeading = 'Edit Pump';
$currentRoute = 'admin/edit-pump';
require __DIR__ . '/pump-page-setup.php';
$formMode = 'edit';
$formPump = $selectedPump;
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page pump-module-page"><section class="clock-hero pump-hero"><div class="container-fluid"><nav class="pump-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Pump Management</span><i class="fa-solid fa-chevron-right"></i><span>Edit Pump</span></nav><div class="clock-hero__content pump-hero-card"><div><span class="eyebrow">Pump Record</span><h1>Edit <?php echo e($selectedPump['pump_number']); ?></h1><p>Update pump configuration and meter information.</p></div><span class="pump-hero-icon"><i class="fa-solid fa-pen-to-square"></i></span></div></div></section><section class="container-fluid clock-workspace"><?php require __DIR__ . '/pump-form.php'; ?></section></main><?php require __DIR__ . '/../includes/footer.php'; ?>

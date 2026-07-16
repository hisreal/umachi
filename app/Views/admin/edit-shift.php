<?php

declare(strict_types=1);

$pageTitle = 'Edit Shift | FuelOps Admin Dashboard';
$pageHeading = 'Edit Shift';
$currentRoute = 'admin/edit-shift';
require __DIR__ . '/duty-roster-setup.php';
$formMode = 'edit';
$formShift = $selectedShift;
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page duty-module-page"><section class="clock-hero duty-hero"><div class="container-fluid"><nav class="duty-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Duty Roster</span><i class="fa-solid fa-chevron-right"></i><span>Edit Shift</span></nav><div class="clock-hero__content duty-hero-card"><div><span class="eyebrow">Shift Configuration</span><h1>Edit <?php echo e($selectedShift['shift_name'] ?: 'Shift'); ?></h1><p>Update working hours, grace period, employee capacity, and status.</p></div><span class="duty-hero-icon"><i class="fa-solid fa-pen-to-square"></i></span></div></div></section><section class="container-fluid clock-workspace"><?php require __DIR__ . '/shift-form.php'; ?></section></main><?php require __DIR__ . '/../includes/footer.php'; ?>

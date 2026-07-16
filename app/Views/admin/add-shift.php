<?php

declare(strict_types=1);

$pageTitle = 'Add Shift | FuelOps Admin Dashboard';
$pageHeading = 'Add Shift';
$currentRoute = 'admin/add-shift';
require __DIR__ . '/duty-roster-setup.php';
$formMode = 'add';
$formShift = $selectedShift;
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page duty-module-page"><section class="clock-hero duty-hero"><div class="container-fluid"><nav class="duty-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Duty Roster</span><i class="fa-solid fa-chevron-right"></i><span>Add Shift</span></nav><div class="clock-hero__content duty-hero-card"><div><span class="eyebrow">Shift Configuration</span><h1>Add Shift</h1><p>Create a reusable work shift for attendance and duty roster assignments.</p></div><span class="duty-hero-icon"><i class="fa-solid fa-plus"></i></span></div></div></section><section class="container-fluid clock-workspace"><?php require __DIR__ . '/shift-form.php'; ?></section></main><?php require __DIR__ . '/../includes/footer.php'; ?>

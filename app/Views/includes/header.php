<?php

declare(strict_types=1);

require_once __DIR__ . '/view-helpers.php';

$pageTitle = $pageTitle ?? 'FuelOps Staff Dashboard';
$extraStyles = $extraStyles ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Filling station staff and activity management dashboard.">
    <title><?php echo e($pageTitle); ?></title>

    <link rel="shortcut icon" href="<?php echo e(asset_url('images/favicon.png')); ?>">
    <link rel="apple-touch-icon" href="<?php echo e(asset_url('images/apple-icon.png')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset_url('vendor/bootstrap/css/bootstrap.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset_url('vendor/fontawesome/css/all.min.css')); ?>">

    <?php foreach ($extraStyles as $stylePath): ?>
        <link rel="stylesheet" href="<?php echo e(asset_url($stylePath)); ?>">
    <?php endforeach; ?>
</head>
<body class="attendant-dashboard-layout">
    <input type="checkbox" id="attendantSidebarControl" class="attendant-sidebar-control" aria-hidden="true">
    <div class="attendant-shell">
        <?php require __DIR__ . '/sidebar.php'; ?>

        <label class="attendant-sidebar-backdrop" for="attendantSidebarControl" aria-label="Close navigation"></label>

        <div class="attendant-main">
            <?php require __DIR__ . '/topbar.php'; ?>

            <div class="attendant-content">

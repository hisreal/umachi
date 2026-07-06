<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Staff Page | FuelOps Staff Dashboard';
$pageHeading = $pageHeading ?? 'Staff Page';
$topbarSubtitle = $topbarSubtitle ?? 'Pump Attendant Dashboard';
$pageIntro = $pageIntro ?? 'This section is prepared for future backend integration.';
$pageIcon = $pageIcon ?? 'fa-solid fa-gas-pump';
$extraStyles = $extraStyles ?? ['css/clock-in.css'];
$summaryCards = $summaryCards ?? [];
$tableColumns = $tableColumns ?? [];
$tableRows = $tableRows ?? [];
$quickActions = $quickActions ?? [];
$announcements = $announcements ?? [];
$emptyMessage = $emptyMessage ?? 'No records available yet.';
$employee = $employee ?? [
    'name' => 'Chinedu Okafor',
    'role' => 'Pump Attendant',
];
$attendantName = $employee['name'] ?? 'Station Staff';
$attendantRole = $employee['role'] ?? 'Pump Attendant';

require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page dashboard-page">
    <section class="clock-hero">
        <div class="container-fluid">
            <div class="clock-hero__content">
                <div>
                    <h1><?php echo e($pageHeading); ?></h1>
                    <p><?php echo e($pageIntro); ?></p>
                </div>
                <span class="employee-avatar" aria-hidden="true">
                    <i class="<?php echo e($pageIcon); ?>"></i>
                </span>
            </div>
        </div>
    </section>

    <section class="container-fluid clock-workspace">
        <?php if ($quickActions !== []): ?>
            <div class="dashboard-section dashboard-quick-actions">
                <div class="row g-4">
                    <?php foreach ($quickActions as $action): ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <a class="dashboard-action-card app-card card" href="<?php echo e(route_url($action['route'])); ?>">
                                <span class="dashboard-action-icon" aria-hidden="true"><i class="<?php echo e($action['icon']); ?>"></i></span>
                                <div>
                                    <h2><?php echo e($action['title']); ?></h2>
                                    <p><?php echo e($action['description']); ?></p>
                                </div>
                                <span class="dashboard-action-link">
                                    View
                                    <i class="fa-solid fa-arrow-right"></i>
                                </span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($announcements !== []): ?>
            <section class="dashboard-section dashboard-announcements" aria-labelledby="dashboardAnnouncementsTitle">
                <div class="history-toolbar dashboard-section-header">
                    <div>
                        <span class="eyebrow">Latest Notices</span>
                    </div>
                </div>
                <div class="row g-4">
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="col-12 col-lg-4">
                            <article class="app-card card dashboard-announcement-card">
                                <div class="dashboard-announcement-icon" aria-hidden="true">
                                    <i class="<?php echo e($announcement['icon'] ?? 'fa-solid fa-bullhorn'); ?>"></i>
                                </div>
                                <div>
                                    <h3><?php echo e($announcement['title']); ?></h3>
                                    <p><?php echo e($announcement['message']); ?></p>
                                    <time datetime="<?php echo e($announcement['date']); ?>">
                                        <i class="fa-solid fa-calendar-day"></i>
                                        <?php echo e(date('d M Y', strtotime($announcement['date']))); ?>
                                    </time>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

     
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

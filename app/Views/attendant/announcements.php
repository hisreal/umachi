<?php

declare(strict_types=1);

$pageTitle = 'Announcements | FuelOps Staff Dashboard';
$pageHeading = 'Announcements';
$topbarSubtitle = 'Staff Dashboard';
$currentRoute = $currentRoute ?? 'announcements';
$extraStyles = ['css/clock-in.css', 'css/dashboard.css'];
$employee = $employee ?? [];
$announcements = is_array($announcements ?? null) ? $announcements : [];
$attendantName = $employee['name'] ?? 'Station Staff';
$attendantRole = $employee['role'] ?? 'Employee';

require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page attendant-dashboard-page">
    <section class="clock-hero">
        <div class="container-fluid">
            <div class="clock-hero__content">
                <div>
                    <h1><?php echo e($pageHeading); ?></h1>
                    <p>Read the latest station notices and employee updates.</p>
                </div>
                <span class="employee-avatar" aria-hidden="true"><i class="fa-solid fa-bullhorn"></i></span>
            </div>
        </div>
    </section>

    <section class="container-fluid clock-workspace">
        <article class="app-card card">
            <div class="app-card__header">
                <div><span class="eyebrow">Station Updates</span><h2>Latest Announcements</h2></div>
            </div>
            <?php if ($announcements === []): ?>
                <div class="alert alert-info mb-0">There are no active announcements for your role.</div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="col-12 col-lg-6">
                            <article class="border rounded-3 p-3 h-100">
                                <div class="d-flex justify-content-between gap-3 align-items-start mb-2">
                                    <h3 class="h5 mb-0"><?php echo e((string) ($announcement['title'] ?? 'Station Notice')); ?></h3>
                                    <?php if (!empty($announcement['priority'])): ?><span class="badge text-bg-warning"><?php echo e(ucfirst((string) $announcement['priority'])); ?></span><?php endif; ?>
                                </div>
                                <p class="mb-2"><?php echo nl2br(e((string) ($announcement['message'] ?? $announcement['content'] ?? ''))); ?></p>
                                <?php if (!empty($announcement['date'])): ?><small class="text-muted"><?php echo e(date('d M Y', strtotime((string) $announcement['date']))); ?></small><?php endif; ?>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

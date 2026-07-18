<?php

declare(strict_types=1);

$pageTitle = 'Announcement Details | FuelOps Admin Dashboard';
$pageHeading = 'Announcement Details';
$currentRoute = 'admin/announcements';
$topbarSubtitle = 'Admin Dashboard';
$extraStyles = ['css/clock-in.css', 'css/admin-dashboard.css', 'css/announcement-management.css'];
$extraScripts = ['js/admin-dashboard.js', 'js/announcement-management.js'];
$sidebarVariant = 'admin-sidebar';
$sidebarHomeRoute = 'admin/dashboard';
$sidebarBrandTitle = 'FuelOps';
$sidebarBrandSubtitle = 'Admin Panel';
$navItems = require __DIR__ . '/../includes/admin-nav.php';
$employee = ['name' => 'Administrator', 'role' => 'System Administrator'];
$attendantName = $employee['name'];
$attendantRole = $employee['role'];
require __DIR__ . '/announcement-data.php';
$announcementRoles = (new \App\Services\AuthService())->roles();
$activeAnnouncementRole = trim((string) \App\Core\Session::get('auth.role', ''));
if (in_array(strtolower($activeAnnouncementRole), ['manager', 'supervisor', 'accountant'], true)) {
    $announcementRoles = [$activeAnnouncementRole];
}
$canManageAnnouncements = (new \App\Services\RbacService())->canAccess(
    'admin/announcements/store',
    $announcementRoles
);
$announcement = $selectedAnnouncement;
require __DIR__ . '/../includes/header.php';
?>
<?php if (!$canManageAnnouncements): ?><style>a[href*="edit-announcement"],[data-announcement-action="archive"],[data-announcement-action="delete"]{display:none!important}</style><?php endif; ?>
<main class="clock-in-page announcement-page"><section class="clock-hero announcement-hero"><div class="container-fluid"><nav class="announcement-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><a href="<?php echo e(route_url('admin/announcements')); ?>">Announcements</a><i class="fa-solid fa-chevron-right"></i><span>Details</span></nav><div class="clock-hero__content announcement-hero-card"><div><span class="eyebrow">Read Only View</span><h1><?php echo e($announcement['title']); ?></h1><p>Review announcement content, publication schedule, audience, and engagement statistics.</p></div><div class="announcement-hero-actions"><a class="btn btn-light" href="<?php echo e(route_url('admin/edit-announcement')); ?>&id=<?php echo e($announcement['id']); ?>"><i class="fa-solid fa-pen"></i>Edit</a><button class="btn btn-outline-light" data-announcement-action="print" data-title="<?php echo e($announcement['title']); ?>" type="button"><i class="fa-solid fa-print"></i>Print</button></div></div></div></section>
<section class="container-fluid clock-workspace"><?php if (!empty($announcementSuccess)): ?><div class="alert alert-success" role="alert"><?php echo e($announcementSuccess); ?></div><?php endif; ?><?php if (!empty($announcementError)): ?><div class="alert alert-danger" role="alert"><?php echo e($announcementError); ?></div><?php endif; ?><div class="row g-4 align-items-start"><div class="col-12 col-xl-8"><article class="app-card card announcement-detail-card"><div class="announcement-detail-header"><div><span class="table-badge <?php echo e(announcement_badge_class('priority', $announcement['priority'])); ?>"><?php echo e($announcement['priority']); ?></span><span class="table-badge <?php echo e(announcement_badge_class('status', $announcement['status'])); ?>"><?php echo e($announcement['status']); ?></span></div><h2><?php echo e($announcement['title']); ?></h2><dl><div><dt>Category</dt><dd><?php echo e($announcement['category']); ?></dd></div><div><dt>Publish Date</dt><dd><?php echo e(format_date($announcement['publish_date'])); ?> at <?php echo e(format_date($announcement['publish_time'], 'h:i A')); ?></dd></div><div><dt>Expiry Date</dt><dd><?php echo e(format_date($announcement['expiry_date'])); ?></dd></div><div><dt>Created By</dt><dd><?php echo e($announcement['created_by']); ?></dd></div></dl></div><div class="announcement-body"><?php echo $announcementContent; ?></div></article></div><div class="col-12 col-xl-4"><article class="app-card card announcement-detail-card"><div class="app-card__header"><div><span class="eyebrow">Audience</span><h2>Recipients</h2></div></div><div class="audience-chip-list"><?php foreach (['Everyone', 'Managers', 'Supervisors', 'Pump Attendants'] as $audience): ?><span><?php echo e($audience); ?></span><?php endforeach; ?></div></article><div class="announcement-stat-grid mt-4"><article><small>Views</small><strong><?php echo e((string) $announcementStats['views']); ?></strong></article><article><small>Acknowledged</small><strong><?php echo e((string) $announcementStats['acknowledged']); ?></strong></article><article><small>Unread</small><strong><?php echo e((string) $announcementStats['unread']); ?></strong></article><article><small>Comments</small><strong><?php echo e((string) $announcementStats['comments']); ?></strong></article></div><article class="app-card card announcement-detail-card mt-4"><div class="announcement-action-stack"><a class="btn btn-primary" href="<?php echo e(route_url('admin/edit-announcement')); ?>&id=<?php echo e($announcement['id']); ?>"><i class="fa-solid fa-pen"></i>Edit</a><button class="btn btn-outline-brand" data-announcement-action="archive" data-announcement-id="<?php echo e((string) $announcement['db_id']); ?>" data-title="<?php echo e($announcement['title']); ?>" type="button"><i class="fa-solid fa-box-archive"></i>Archive</button><button class="btn btn-outline-danger" data-announcement-action="delete" data-announcement-id="<?php echo e((string) $announcement['db_id']); ?>" data-title="<?php echo e($announcement['title']); ?>" type="button"><i class="fa-solid fa-trash"></i>Delete</button></div></article></div></div><article class="app-card card announcement-preview-card mt-4"><div class="app-card__header"><div><span class="eyebrow">Employee Dashboard</span><h2>Dashboard Preview Card</h2></div></div><div class="announcement-preview announcement-preview--large"><span><i class="fa-solid fa-bullhorn"></i> STAFF NOTICE</span><h3><?php echo e($announcement['title']); ?></h3><p><?php echo e($announcement['message']); ?></p></div></article></section></main>
<form id="announcementActionForm" class="d-none" method="post" action="<?php echo e(route_url('admin/announcements/action')); ?>">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="announcement_id" id="announcementActionId">
    <input type="hidden" name="action" id="announcementActionValue">
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>




<?php

declare(strict_types=1);

$pageTitle = 'Announcement Management | FuelOps Admin Dashboard';
$pageHeading = 'Announcement Management';
$currentRoute = 'admin/announcements';
$topbarSubtitle = 'Admin Dashboard';
$extraStyles = ['css/clock-in.css', 'css/admin-dashboard.css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', 'css/announcement-management.css'];
$extraScripts = ['https://cdn.jsdelivr.net/npm/flatpickr', 'js/admin-dashboard.js', 'js/announcement-management.js'];
$sidebarVariant = 'admin-sidebar';
$sidebarHomeRoute = 'admin/dashboard';
$sidebarBrandTitle = 'FuelOps';
$sidebarBrandSubtitle = 'Admin Panel';
$navItems = require __DIR__ . '/../includes/admin-nav.php';
$employee = ['name' => 'Administrator', 'role' => 'System Administrator'];
$attendantName = $employee['name'];
$attendantRole = $employee['role'];
require __DIR__ . '/announcement-data.php';
$canManageAnnouncements = (new \App\Services\RbacService())->canAccess(
    'admin/announcements/store',
    (new \App\Services\AuthService())->roles()
);
require __DIR__ . '/../includes/header.php';
?>
<?php if (!$canManageAnnouncements): ?><style>a[href*="add-announcement"],a[href*="edit-announcement"],[data-announcement-action]{display:none!important}</style><?php endif; ?>
<main class="clock-in-page announcement-page">
    <section class="clock-hero announcement-hero"><div class="container-fluid"><nav class="announcement-breadcrumb" aria-label="Breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Announcements</span></nav><div class="clock-hero__content announcement-hero-card"><div><span class="eyebrow">Staff Communication</span><h1>Announcement Management</h1><p>Create, publish, schedule, archive, and monitor announcements visible to employees after login.</p></div><a class="btn btn-light" href="<?php echo e(route_url('admin/add-announcement')); ?>"><i class="fa-solid fa-plus"></i>Create Announcement</a></div></div></section>
    <section class="container-fluid clock-workspace"><?php if (!empty($announcementSuccess)): ?><div class="alert alert-success" role="alert"><?php echo e($announcementSuccess); ?></div><?php endif; ?><?php if (!empty($announcementError)): ?><div class="alert alert-danger" role="alert"><?php echo e($announcementError); ?></div><?php endif; ?>
        <div class="announcement-summary-grid"><?php foreach ($summaryCards as $card): ?><article class="announcement-summary-card announcement-summary-card--<?php echo e($card['tone']); ?>"><span><i class="<?php echo e($card['icon']); ?>"></i></span><div><small><?php echo e($card['label']); ?></small><strong><?php echo e((string) $card['value']); ?></strong></div></article><?php endforeach; ?></div>
        <article class="app-card card announcement-table-card mt-4">
            <div class="announcement-toolbar"><div><span class="eyebrow">Announcement Records</span><h2>All Announcements</h2></div><div class="announcement-toolbar-actions"><a class="btn btn-primary" href="<?php echo e(route_url('admin/add-announcement')); ?>"><i class="fa-solid fa-plus"></i>Create Announcement</a><div class="dropdown"><button class="btn btn-outline-brand dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fa-solid fa-download"></i>Export</button><ul class="dropdown-menu dropdown-menu-end"><li><button class="dropdown-item" data-announcement-export="PDF" type="button">Export PDF</button></li><li><button class="dropdown-item" data-announcement-export="Excel" type="button">Export Excel</button></li><li><button class="dropdown-item" data-announcement-export="CSV" type="button">Export CSV</button></li></ul></div></div></div>
            <div class="announcement-filter-grid"><div class="filter-control filter-control--wide"><i class="fa-solid fa-magnifying-glass"></i><input id="announcementSearch" type="search" placeholder="Search title or posted by"></div><select class="form-select" id="announcementStatusFilter"><option value="">All statuses</option><?php foreach ($statuses as $status): ?><option value="<?php echo e($status); ?>"><?php echo e($status); ?></option><?php endforeach; ?></select><select class="form-select" id="announcementPriorityFilter"><option value="">All priorities</option><?php foreach ($priorities as $priority): ?><option value="<?php echo e($priority); ?>"><?php echo e($priority); ?></option><?php endforeach; ?></select><select class="form-select" id="announcementAudienceFilter"><option value="">All audiences</option><?php foreach ($audienceGroups as $audience): ?><option value="<?php echo e($audience); ?>"><?php echo e($audience); ?></option><?php endforeach; ?></select><input class="form-control js-date-picker" id="announcementStartDate" type="text" placeholder="Start date"><input class="form-control js-date-picker" id="announcementEndDate" type="text" placeholder="End date"></div>
            <div class="table-responsive"><table class="table attendance-table announcement-table align-middle"><thead><tr><th>Title</th><th>Category</th><th>Audience</th><th>Priority</th><th>Publish Date</th><th>Expiry Date</th><th>Status</th><th>Created By</th><th>Actions</th></tr></thead><tbody id="announcementTableBody"><?php foreach ($announcements as $announcement): ?><tr data-announcement-row data-search="<?php echo e(strtolower($announcement['title'] . ' ' . $announcement['created_by'])); ?>" data-status="<?php echo e($announcement['status']); ?>" data-priority="<?php echo e($announcement['priority']); ?>" data-audience="<?php echo e($announcement['audience']); ?>" data-date="<?php echo e($announcement['publish_date']); ?>"><td><strong><?php echo e($announcement['title']); ?></strong><?php echo $announcement['pinned'] ? '<span class="pin-label"><i class="fa-solid fa-thumbtack"></i>Pinned</span>' : ''; ?></td><td><?php echo e($announcement['category']); ?></td><td><?php echo e($announcement['audience']); ?></td><td><span class="table-badge <?php echo e(announcement_badge_class('priority', $announcement['priority'])); ?>"><?php echo e($announcement['priority']); ?></span></td><td><?php echo e(format_date($announcement['publish_date'])); ?></td><td><?php echo e(format_date($announcement['expiry_date'])); ?></td><td><span class="table-badge <?php echo e(announcement_badge_class('status', $announcement['status'])); ?>"><?php echo e($announcement['status']); ?></span></td><td><?php echo e($announcement['created_by']); ?></td><td><div class="announcement-actions"><a class="btn btn-sm btn-light" href="<?php echo e(route_url('admin/announcement-details')); ?>&id=<?php echo e($announcement['id']); ?>" title="View"><i class="fa-solid fa-eye"></i></a><a class="btn btn-sm btn-light" href="<?php echo e(route_url('admin/edit-announcement')); ?>&id=<?php echo e($announcement['id']); ?>" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a><button class="btn btn-sm btn-light" data-announcement-action="publish" data-announcement-id="<?php echo e((string) $announcement['db_id']); ?>" data-title="<?php echo e($announcement['title']); ?>" type="button" title="Publish"><i class="fa-solid fa-paper-plane"></i></button><button class="btn btn-sm btn-light" data-announcement-action="archive" data-announcement-id="<?php echo e((string) $announcement['db_id']); ?>" data-title="<?php echo e($announcement['title']); ?>" type="button" title="Archive"><i class="fa-solid fa-box-archive"></i></button><button class="btn btn-sm btn-light" data-announcement-action="delete" data-announcement-id="<?php echo e((string) $announcement['db_id']); ?>" data-title="<?php echo e($announcement['title']); ?>" type="button" title="Delete"><i class="fa-solid fa-trash"></i></button></div></td></tr><?php endforeach; ?></tbody></table></div>
            <div class="announcement-pagination"><span id="announcementPageSummary">Showing announcements</span><div><button class="btn btn-outline-brand btn-sm" id="prevAnnouncementPage" type="button"><i class="fa-solid fa-chevron-left"></i></button><button class="btn btn-outline-brand btn-sm" id="nextAnnouncementPage" type="button"><i class="fa-solid fa-chevron-right"></i></button></div></div>
        </article>
    </section>
</main>
<form id="announcementActionForm" class="d-none" method="post" action="<?php echo e(route_url('admin/announcements/action')); ?>">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="announcement_id" id="announcementActionId">
    <input type="hidden" name="action" id="announcementActionValue">
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>


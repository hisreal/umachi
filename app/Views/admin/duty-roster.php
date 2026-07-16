<?php

declare(strict_types=1);

$pageTitle = 'Duty Roster Management | FuelOps Admin Dashboard';
$pageHeading = 'Duty Roster Management';
$currentRoute = 'admin/duty-roster';
require __DIR__ . '/duty-roster-setup.php';
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page duty-module-page">
    <section class="clock-hero duty-hero">
        <div class="container-fluid">
            <nav class="duty-breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Duty Roster</span>
            </nav>
            <div class="clock-hero__content duty-hero-card">
                <div><span class="eyebrow">Workforce Scheduling</span><h1>Duty Roster Management</h1><p>Create, publish, archive, and monitor staff duty roster periods.</p></div>
                <a class="btn btn-light" href="<?php echo e(route_url('admin/pump-allocation')); ?>"><i class="fa-solid fa-plus"></i>Assign Duty</a>
            </div>
        </div>
    </section>

    <section class="container-fluid clock-workspace">
        <?php if ($dutySuccess): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?php echo e($dutySuccess); ?></div><?php endif; ?>
        <?php if ($dutyError): ?><div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo e($dutyError); ?></div><?php endif; ?>

        <div class="duty-summary-grid">
            <?php foreach ($dutyStats as $card): ?>
                <article class="duty-summary-card duty-summary-card--<?php echo e($card['tone']); ?>"><span><i class="<?php echo e($card['icon']); ?>"></i></span><div><small><?php echo e($card['label']); ?></small><strong><?php echo e($card['value']); ?></strong></div></article>
            <?php endforeach; ?>
        </div>

        <?php if ($canManageDuties): ?>
            <article class="app-card card duty-form-card duty-form mt-4">
                <div class="duty-section-heading"><span><i class="fa-solid fa-calendar-plus"></i></span><div><small>Roster Period</small><h2>Create Duty Roster</h2></div></div>
                <form method="post" action="<?php echo e(route_url('admin/duty-rosters/save')); ?>" class="needs-validation" novalidate>
                    <?php echo csrf_field(); ?>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label" for="rosterName">Roster Name</label><input class="form-control" id="rosterName" name="roster_name" required placeholder="July Week 2 Roster"></div>
                        <div class="col-md-3"><label class="form-label" for="rosterStart">Start Date</label><input class="form-control" id="rosterStart" name="start_date" type="date" required></div>
                        <div class="col-md-3"><label class="form-label" for="rosterEnd">End Date</label><input class="form-control" id="rosterEnd" name="end_date" type="date" required></div>
                        <div class="col-md-2"><label class="form-label" for="rosterStatus">Status</label><select class="form-select" id="rosterStatus" name="status" required><?php foreach ($options['roster_statuses'] as $status): ?><option value="<?php echo e($status); ?>"><?php echo e($status); ?></option><?php endforeach; ?></select></div>
                    </div>
                    <div class="duty-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i>Save Roster</button></div>
                </form>
            </article>
        <?php endif; ?>

        <article class="app-card card duty-table-card mt-4">
            <div class="duty-toolbar"><div><span class="eyebrow">Roster Records</span><h2>Duty Rosters</h2></div><a class="btn btn-primary" href="<?php echo e(route_url('admin/pump-allocation')); ?>"><i class="fa-solid fa-user-check"></i>Assign Duty</a></div>
            <div class="duty-filter-grid duty-filter-grid--roster">
                <form method="get" class="filter-control filter-control--wide"><input type="hidden" name="route" value="admin/duty-roster"><i class="fa-solid fa-magnifying-glass"></i><input name="search" type="search" value="<?php echo e($dutyFilters['search']); ?>" placeholder="Search roster name"></form>
                <select class="form-select" onchange="if(this.value){location.href='<?php echo e(route_url('admin/duty-roster')); ?>&status='+encodeURIComponent(this.value)}else{location.href='<?php echo e(route_url('admin/duty-roster')); ?>'}"><option value="">All statuses</option><?php foreach ($options['roster_statuses'] as $status): ?><option value="<?php echo e($status); ?>" <?php echo $dutyFilters['status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option><?php endforeach; ?></select>
            </div>
            <div class="table-responsive">
                <table class="table attendance-table duty-table align-middle">
                    <thead><tr><th>Roster Name</th><th>Start Date</th><th>End Date</th><th>Total Assignments</th><th>Status</th><th>Created By</th><th>Actions</th></tr></thead>
                    <tbody id="dutyRosterBody">
                        <?php if ($rosters === []): ?><tr><td colspan="7" class="text-center py-4">No duty rosters found.</td></tr><?php endif; ?>
                        <?php foreach ($rosters as $roster): ?>
                            <tr data-duty-row data-search="<?php echo e(strtolower($roster['roster_name'])); ?>" data-status="<?php echo e($roster['status']); ?>">
                                <td><strong><?php echo e($roster['roster_name']); ?></strong></td>
                                <td><?php echo e(format_date($roster['start_date'])); ?></td>
                                <td><?php echo e(format_date($roster['end_date'])); ?></td>
                                <td><?php echo e((string) $roster['total_assignments']); ?></td>
                                <td><span class="table-badge <?php echo e($dutyStatusClasses[$roster['status']] ?? 'duty-status--scheduled'); ?>"><?php echo e($roster['status']); ?></span></td>
                                <td><?php echo e($roster['created_by_name']); ?></td>
                                <td><div class="duty-actions">
                                    <a class="btn btn-sm btn-light" href="<?php echo e(route_url('admin/pump-allocation')); ?>&roster_id=<?php echo e((string) $roster['id']); ?>" title="View"><i class="fa-solid fa-eye"></i></a>
                                    <?php if ($canManageDuties): ?>
                                        <form method="post" action="<?php echo e(route_url('admin/duty-rosters/publish')); ?>" class="d-inline"><?php echo csrf_field(); ?><input type="hidden" name="roster_id" value="<?php echo e((string) $roster['id']); ?>"><button class="btn btn-sm btn-light" data-duty-action="publish" data-duty-name="<?php echo e($roster['roster_name']); ?>" title="Publish"><i class="fa-solid fa-paper-plane"></i></button></form>
                                        <form method="post" action="<?php echo e(route_url('admin/duty-rosters/archive')); ?>" class="d-inline"><?php echo csrf_field(); ?><input type="hidden" name="roster_id" value="<?php echo e((string) $roster['id']); ?>"><button class="btn btn-sm btn-light" data-duty-action="archive" data-duty-name="<?php echo e($roster['roster_name']); ?>" title="Archive"><i class="fa-solid fa-box-archive"></i></button></form>
                                        <form method="post" action="<?php echo e(route_url('admin/duty-rosters/delete')); ?>" class="d-inline"><?php echo csrf_field(); ?><input type="hidden" name="roster_id" value="<?php echo e((string) $roster['id']); ?>"><button class="btn btn-sm btn-light duty-action-danger" data-duty-action="delete" data-duty-name="<?php echo e($roster['roster_name']); ?>" title="Delete"><i class="fa-solid fa-trash"></i></button></form>
                                    <?php endif; ?>
                                </div></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="duty-pagination"><span id="dutyRosterSummary">Showing live roster records</span><div><button class="btn btn-outline-brand btn-sm" id="prevDutyPage" type="button"><i class="fa-solid fa-chevron-left"></i></button><button class="btn btn-outline-brand btn-sm" id="nextDutyPage" type="button"><i class="fa-solid fa-chevron-right"></i></button></div></div>
        </article>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
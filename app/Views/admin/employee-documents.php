<?php

declare(strict_types=1);

$pageTitle = 'Employee Documents | FuelOps Admin Dashboard';
$pageHeading = 'Employee Documents';
$topbarSubtitle = 'Admin Dashboard';
$currentRoute = 'admin/employee-documents';
$extraStyles = ['css/clock-in.css', 'css/admin-dashboard.css', 'css/employee-management.css'];
$extraScripts = ['js/admin-dashboard.js', 'js/employee-management.js'];
$sidebarVariant = 'admin-sidebar';
$sidebarHomeRoute = 'admin/dashboard';
$sidebarBrandTitle = 'FuelOps';
$sidebarBrandSubtitle = 'Admin Panel';
$navItems = require __DIR__ . '/../includes/admin-nav.php';
$adminUser = ['name' => 'Administrator', 'role' => 'System Administrator'];
$employee = ['name' => $adminUser['name'], 'role' => $adminUser['role']];
$attendantName = $adminUser['name'];
$attendantRole = $adminUser['role'];

require __DIR__ . '/employee-data.php';

// ============================================
// DATABASE PLACEHOLDER
// Upload and retrieve employee documents.
// ============================================
$documentStats = [
    ['label' => 'Total Documents', 'value' => 5, 'icon' => 'fa-solid fa-folder-open', 'tone' => 'primary'],
    ['label' => 'Uploaded Documents', 'value' => 3, 'icon' => 'fa-solid fa-cloud-arrow-up', 'tone' => 'success'],
    ['label' => 'Missing Documents', 'value' => 2, 'icon' => 'fa-solid fa-triangle-exclamation', 'tone' => 'danger'],
    ['label' => 'Expiring Documents', 'value' => 1, 'icon' => 'fa-solid fa-calendar-xmark', 'tone' => 'warning'],
];
$documents = [
    ['type' => 'Passport Photograph', 'status' => 'Uploaded', 'upload_date' => '2026-07-01', 'icon' => 'fa-solid fa-image'],
    ['type' => 'National ID', 'status' => 'Uploaded', 'upload_date' => '2026-07-02', 'icon' => 'fa-solid fa-id-card'],
    ['type' => "Driver's License", 'status' => 'Missing', 'upload_date' => '', 'icon' => 'fa-solid fa-car'],
    ['type' => 'Employment Letter', 'status' => 'Uploaded', 'upload_date' => '2026-07-03', 'icon' => 'fa-solid fa-file-signature'],
    ['type' => 'Other Documents', 'status' => 'Missing', 'upload_date' => '', 'icon' => 'fa-solid fa-file-lines'],
];
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page employee-module-page">
    <section class="clock-hero employee-hero"><div class="container-fluid"><nav class="employee-breadcrumb" aria-label="Breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><a href="<?php echo e(route_url('admin/employees')); ?>">Employee Management</a><i class="fa-solid fa-chevron-right"></i><span>Documents</span></nav><div class="clock-hero__content employee-hero-card"><div><span class="eyebrow">Document Center</span><h1><?php echo e($selectedEmployee['name']); ?> Documents</h1><p>Manage employee document uploads in frontend-only demo mode.</p></div><a class="btn btn-light" href="<?php echo e(route_url('admin/employee-profile')); ?>&employee=<?php echo e($selectedEmployee['id']); ?>"><i class="fa-solid fa-arrow-left"></i>Back to Profile</a></div></div></section>
    <section class="container-fluid clock-workspace">
        <div class="employee-summary-grid"><?php foreach ($documentStats as $stat): ?><article class="employee-summary-card employee-summary-card--<?php echo e($stat['tone']); ?>"><span><i class="<?php echo e($stat['icon']); ?>"></i></span><div><small><?php echo e($stat['label']); ?></small><strong><?php echo e((string) $stat['value']); ?></strong></div></article><?php endforeach; ?></div>
        <article class="app-card card employee-list-card mt-4">
            <div class="employee-toolbar"><div><span class="eyebrow">Document Files</span><h2>Document Management</h2></div></div>
            <div class="employee-filter-grid employee-filter-grid--documents"><select class="form-select" id="documentTypeFilter"><option value="">All document types</option><?php foreach ($documents as $document): ?><option value="<?php echo e($document['type']); ?>"><?php echo e($document['type']); ?></option><?php endforeach; ?></select><input class="form-control" type="date" id="documentDateFilter" aria-label="Filter by upload date"></div>
            <div class="row g-4" id="documentGrid">
                <?php foreach ($documents as $document): ?>
                    <div class="col-12 col-md-6 col-xl-4" data-document-card data-type="<?php echo e($document['type']); ?>" data-date="<?php echo e($document['upload_date']); ?>">
                        <article class="document-card">
                            <span class="document-card-icon"><i class="<?php echo e($document['icon']); ?>"></i></span>
                            <div>
                                <h3><?php echo e($document['type']); ?></h3>
                                <p><?php echo $document['upload_date'] !== '' ? 'Uploaded on ' . e(date('d M Y', strtotime($document['upload_date']))) : 'No file uploaded yet'; ?></p>
                                <span class="table-badge <?php echo $document['status'] === 'Uploaded' ? 'employee-status--active' : 'employee-status--inactive'; ?>"><?php echo e($document['status']); ?></span>
                            </div>
                            <input class="form-control" type="file" accept="image/*,.pdf,.doc,.docx" data-document-upload>
                            <div class="document-actions">
                                <button class="btn btn-sm btn-outline-brand" type="button" data-document-action="preview" data-document="<?php echo e($document['type']); ?>">Preview</button>
                                <button class="btn btn-sm btn-light" type="button" data-document-action="download" data-document="<?php echo e($document['type']); ?>">Download</button>
                                <button class="btn btn-sm btn-outline-danger" type="button" data-document-action="delete" data-document="<?php echo e($document['type']); ?>">Delete</button>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

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

try {
    $documents = (new \App\Models\Employee())->documentsFor((string) $selectedEmployee['id']);
} catch (Throwable) {
    $documents = [];
}

$documentTypes = ['Passport Photograph', 'National ID(NIN)', "Driver's License", 'Employment Letter', 'Other Documents'];
$employeeCsrf = (new \App\Services\AuthService())->csrfToken();
$employeeSuccess = \App\Core\Session::pullFlash('employee_success');
$employeeError = \App\Core\Session::pullFlash('employee_error');
$documentStats = [
    ['label' => 'Total Documents', 'value' => count($documents), 'icon' => 'fa-solid fa-folder-open', 'tone' => 'primary'],
    ['label' => 'Uploaded Documents', 'value' => count($documents), 'icon' => 'fa-solid fa-cloud-arrow-up', 'tone' => 'success'],
    ['label' => 'Missing Documents', 'value' => max(0, count($documentTypes) - count($documents)), 'icon' => 'fa-solid fa-triangle-exclamation', 'tone' => 'danger'],
    ['label' => 'Expiring Documents', 'value' => count(array_filter($documents, static fn (array $document): bool => !empty($document['expires_on']) && ($expiresAt = strtotime((string) $document['expires_on'])) !== false && $expiresAt <= strtotime('+30 days'))), 'icon' => 'fa-solid fa-calendar-xmark', 'tone' => 'warning'],
];

require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page employee-module-page">
    <section class="clock-hero employee-hero"><div class="container-fluid"><nav class="employee-breadcrumb" aria-label="Breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><a href="<?php echo e(route_url('admin/employees')); ?>">Employee Management</a><i class="fa-solid fa-chevron-right"></i><span>Documents</span></nav><div class="clock-hero__content employee-hero-card"><div><span class="eyebrow">Document Center</span><h1><?php echo e($selectedEmployee['name']); ?> Documents</h1><p>Upload and manage employee documents using secure server-side validation.</p></div><a class="btn btn-light" href="<?php echo e(route_url('admin/employee-profile')); ?>&employee=<?php echo e($selectedEmployee['id']); ?>"><i class="fa-solid fa-arrow-left"></i>Back to Profile</a></div></div></section>
    <section class="container-fluid clock-workspace">
        <?php if (is_string($employeeSuccess) && $employeeSuccess !== ''): ?><div class="alert alert-success"><?php echo e($employeeSuccess); ?></div><?php endif; ?>
        <?php if (is_string($employeeError) && $employeeError !== ''): ?><div class="alert alert-danger"><?php echo e($employeeError); ?></div><?php endif; ?>
        <div class="employee-summary-grid"><?php foreach ($documentStats as $stat): ?><article class="employee-summary-card employee-summary-card--<?php echo e($stat['tone']); ?>"><span><i class="<?php echo e($stat['icon']); ?>"></i></span><div><small><?php echo e($stat['label']); ?></small><strong><?php echo e((string) $stat['value']); ?></strong></div></article><?php endforeach; ?></div>
        <article class="app-card card employee-list-card mt-4">
            <div class="employee-toolbar"><div><span class="eyebrow">Document Files</span><h2>Document Management</h2></div></div>
            <form class="employee-filter-grid employee-filter-grid--documents" method="post" action="<?php echo e(route_url('admin/employees/upload-document')); ?>" enctype="multipart/form-data" data-employee-document-upload novalidate>
                <input type="hidden" name="_csrf_token" value="<?php echo e($employeeCsrf); ?>">
                <input type="hidden" name="employee" value="<?php echo e($selectedEmployee['id']); ?>">
                <select class="form-select" name="document_type" required><option value="">Select document type</option><?php foreach ($documentTypes as $documentType): ?><option value="<?php echo e($documentType); ?>"><?php echo e($documentType); ?></option><?php endforeach; ?></select>
                <input class="form-control" name="document_title" placeholder="Document title" required>
                <input class="form-control" name="expires_on" type="date" aria-label="Expiry date">
                <input class="form-control" name="employee_document" type="file" accept="image/*,.pdf,.doc,.docx" data-image-crop data-crop-ratio="free" data-crop-ratio-source="[name='document_type']" data-compress-type="document" data-compress-type-source="[name='document_type']" required>
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-upload"></i>Upload Document</button>
            </form>
            <div class="row g-4" id="documentGrid">
                <?php if ($documents === []): ?>
                    <div class="col-12"><div class="alert alert-info mb-0">No documents have been uploaded for this employee yet.</div></div>
                <?php endif; ?>
                <?php foreach ($documents as $document): ?>
                    <div class="col-12 col-md-6 col-xl-4" data-document-card data-type="<?php echo e($document['document_type']); ?>" data-date="<?php echo e((string) $document['created_at']); ?>">
                        <article class="document-card">
                            <span class="document-card-icon"><i class="fa-solid fa-file-lines"></i></span>
                            <div>
                                <h3><?php echo e($document['document_title']); ?></h3>
                                <p><?php echo e($document['document_type']); ?> â€¢ Uploaded on <?php echo e(format_date($document['created_at'] ?? null)); ?></p>
                                <span class="table-badge employee-status--active">Uploaded</span>
                            </div>
                            <div class="document-actions">
                                <?php $isImageDocument = in_array(strtolower(pathinfo((string) $document['file_path'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true); ?>
                                <?php if ($isImageDocument): ?>
                                    <button class="btn btn-sm btn-outline-brand" type="button" data-image-view="<?php echo e(asset_url($document['file_path'])); ?>" data-image-title="<?php echo e($document['document_title']); ?>" data-download-name="<?php echo e(basename((string) $document['file_path'])); ?>"><i class="fa-solid fa-eye me-1"></i>Preview</button>
                                <?php endif; ?>
                                <a class="btn btn-sm btn-light" href="<?php echo e(asset_url($document['file_path'])); ?>" download>Download</a>
                                <form method="post" action="<?php echo e(route_url('admin/employees/delete-document')); ?>" data-confirm-submit="Delete this document?">
                                    <input type="hidden" name="_csrf_token" value="<?php echo e($employeeCsrf); ?>">
                                    <input type="hidden" name="employee" value="<?php echo e($selectedEmployee['id']); ?>">
                                    <input type="hidden" name="document_id" value="<?php echo e((string) $document['id']); ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

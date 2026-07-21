<?php

declare(strict_types=1);

$pageTitle = 'Leave Approval Settings | FuelOps Admin Dashboard';
$pageHeading = 'Leave Approval Settings';
$currentRoute = 'admin/leave-approval-settings';
require __DIR__ . '/leave-management-setup.php';
require __DIR__ . '/../includes/header.php';
?>
<main class="clock-in-page leave-module-page">
    <section class="clock-hero leave-hero"><div class="container-fluid"><nav class="leave-breadcrumb"><a href="<?php echo e(route_url('admin/dashboard')); ?>">Dashboard</a><i class="fa-solid fa-chevron-right"></i><span>Leave Management</span><i class="fa-solid fa-chevron-right"></i><span>Approval Settings</span></nav><div class="clock-hero__content leave-hero-card"><div><span class="eyebrow">Workflow Configuration</span><h1>Leave Approval Settings</h1><p>Choose who approves leave requests and configure additional leave policy rules.</p></div><span class="leave-hero-icon"><i class="fa-solid fa-user-shield"></i></span></div></div></section>
    <section class="container-fluid clock-workspace"><?php if (!empty($leaveSuccess)): ?><div class="alert alert-success" role="alert"><?php echo e($leaveSuccess); ?></div><?php endif; ?><?php if (!empty($leaveError)): ?><div class="alert alert-danger" role="alert"><?php echo e($leaveError); ?></div><?php endif; ?>
        <form class="leave-settings-form needs-validation" id="leaveApprovalSettingsForm" method="post" action="<?php echo e(route_url('admin/leave-approval-settings/save')); ?>" novalidate><?php echo csrf_field(); ?>
            <div class="row g-4">
                <div class="col-xl-7">
                    <article class="app-card card leave-form-card h-100">
                        <div class="leave-section-heading"><span><i class="fa-solid fa-diagram-project"></i></span><div><small>Leave Approval Workflow</small><h2>Select Active Workflow</h2></div></div>
                        <div class="approval-option-grid">
                            <?php foreach ($approvalWorkflows as $key => $workflow): ?>
                                <label class="approval-option">
                                    <input type="radio" name="approvalWorkflow" value="<?php echo e($key); ?>" <?php echo $key === $activeApprovalWorkflow ? 'checked' : ''; ?>>
                                    <span><strong><?php echo e($workflow['label']); ?></strong><small><?php echo e(implode(' -> ', $workflow['steps'])); ?></small></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </div>
                <div class="col-xl-5">
                    <article class="app-card card leave-form-card h-100">
                        <div class="leave-section-heading"><span><i class="fa-solid fa-sliders"></i></span><div><small>Additional Settings</small><h2>Policy Controls</h2></div></div>
                        <div class="leave-settings-grid">
                            <div class="form-check form-switch leave-switch"><input class="form-check-input" type="checkbox" id="allowCancellation" name="allow_cancellation" value="1" <?php echo $leavePolicySettings['allow_cancellation'] ? 'checked' : ''; ?>><label class="form-check-label" for="allowCancellation">Allow Leave Cancellation</label></div>
                            <div class="form-check form-switch leave-switch"><input class="form-check-input" type="checkbox" id="requireDocuments" name="require_documents" value="1" <?php echo $leavePolicySettings['require_documents'] ? 'checked' : ''; ?>><label class="form-check-label" for="requireDocuments">Require Supporting Documents</label></div>
                            <div class="form-check form-switch leave-switch"><input class="form-check-input" type="checkbox" id="notifyApprovers" name="notify_approvers" value="1" <?php echo $leavePolicySettings['notify_approvers'] ? 'checked' : ''; ?>><label class="form-check-label" for="notifyApprovers">Notify Approvers</label></div>
                            <div class="form-check form-switch leave-switch"><input class="form-check-input" type="checkbox" id="autoApproveEmergency" name="auto_approve_emergency" value="1" <?php echo $leavePolicySettings['auto_approve_emergency'] ? 'checked' : ''; ?>><label class="form-check-label" for="autoApproveEmergency">Auto Approve Emergency Leave</label></div>
                            <div class="form-check form-switch leave-switch"><input class="form-check-input" type="checkbox" id="allowHalfDay" name="allow_half_day" value="1" <?php echo $leavePolicySettings['allow_half_day'] ? 'checked' : ''; ?>><label class="form-check-label" for="allowHalfDay">Allow Half-Day Leave</label></div>
                            <div><label class="form-label" for="maxRequests">Maximum Leave Requests Per Year</label><input class="form-control" id="maxRequests" name="max_requests_per_year" type="number" min="1" value="<?php echo e((string)$leavePolicySettings['max_requests_per_year']); ?>" required></div>
                            <div><label class="form-label" for="approvalDeadline">Approval Deadline (Hours)</label><input class="form-control" id="approvalDeadline" name="approval_deadline_hours" type="number" min="1" value="<?php echo e((string)$leavePolicySettings['approval_deadline_hours']); ?>" required></div>
                        </div>
                    </article>
                </div>
            </div>
            <article class="app-card card leave-workflow-preview mt-4">
                <div class="leave-section-heading"><span><i class="fa-solid fa-eye"></i></span><div><small>Approval Flow Preview</small><h2>Current Workflow</h2></div></div>
                <div class="workflow-preview" id="leaveWorkflowPreview"></div>
            </article>
            <div class="leave-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i>Save Settings</button><button class="btn btn-outline-brand" type="reset"><i class="fa-solid fa-rotate-left"></i>Reset</button></div>
        </form>
    </section>
</main>
<script>window.leaveApprovalWorkflows = <?php echo json_encode($approvalWorkflows, JSON_THROW_ON_ERROR); ?>;</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>


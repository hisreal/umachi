(function () {
    'use strict';

    const alertBox = (title, text, icon = 'success') => {
        if (window.Swal) {
            window.Swal.fire({ title, text, icon, confirmButtonColor: '#f68b34' });
            return;
        }
        window.alert(`${title}\n${text}`);
    };

    const withinDateRange = (date, from, to) => (!from || date >= from) && (!to || date <= to);

    const filterRows = (rows, config) => {
        rows.forEach((row) => {
            const matchesSearch = !config.search || (row.dataset.search || '').includes(config.search);
            const matchesSelects = Object.entries(config.selects).every(([key, value]) => !value || row.dataset[key] === value);
            const matchesDate = withinDateRange(row.dataset.start || '', config.from || '', config.to || '');
            row.classList.toggle('is-hidden-by-leave-filter', !(matchesSearch && matchesSelects && matchesDate));
        });
    };

    const historyRows = Array.from(document.querySelectorAll('[data-leave-history-row]'));
    const historyState = { page: 1, perPage: 5 };

    const paginateHistory = () => {
        if (!historyRows.length) return;
        const visible = historyRows.filter((row) => !row.classList.contains('is-hidden-by-leave-filter'));
        const pages = Math.max(1, Math.ceil(visible.length / historyState.perPage));
        historyState.page = Math.min(historyState.page, pages);
        const start = (historyState.page - 1) * historyState.perPage;
        const end = start + historyState.perPage;
        historyRows.forEach((row) => row.classList.add('is-hidden-by-leave-page'));
        visible.slice(start, end).forEach((row) => row.classList.remove('is-hidden-by-leave-page'));
        const summary = document.getElementById('leaveHistorySummary');
        if (summary) summary.textContent = visible.length ? `Showing ${start + 1}-${Math.min(end, visible.length)} of ${visible.length} records` : 'No leave history matches the filters';
        const prev = document.getElementById('prevLeaveHistoryPage');
        const next = document.getElementById('nextLeaveHistoryPage');
        if (prev) prev.disabled = historyState.page <= 1;
        if (next) next.disabled = historyState.page >= pages;
    };

    const applyHistoryFilters = () => {
        filterRows(historyRows, {
            search: (document.getElementById('leaveHistorySearch')?.value || '').trim().toLowerCase(),
            from: document.getElementById('leaveHistoryFromFilter')?.value || '',
            to: document.getElementById('leaveHistoryToFilter')?.value || '',
            selects: {
                department: document.getElementById('leaveHistoryDepartmentFilter')?.value || '',
                type: document.getElementById('leaveHistoryTypeFilter')?.value || '',
                status: document.getElementById('leaveHistoryStatusFilter')?.value || '',
            },
        });
        historyState.page = 1;
        paginateHistory();
    };

    ['leaveHistorySearch', 'leaveHistoryFromFilter', 'leaveHistoryToFilter', 'leaveHistoryDepartmentFilter', 'leaveHistoryTypeFilter', 'leaveHistoryStatusFilter'].forEach((id) => {
        document.getElementById(id)?.addEventListener('input', applyHistoryFilters);
        document.getElementById(id)?.addEventListener('change', applyHistoryFilters);
    });
    document.getElementById('prevLeaveHistoryPage')?.addEventListener('click', () => { historyState.page -= 1; paginateHistory(); });
    document.getElementById('nextLeaveHistoryPage')?.addEventListener('click', () => { historyState.page += 1; paginateHistory(); });

    const requestRows = Array.from(document.querySelectorAll('[data-leave-request-row]'));
    const applyRequestFilters = () => {
        filterRows(requestRows, {
            search: (document.getElementById('leaveRequestSearch')?.value || '').trim().toLowerCase(),
            from: document.getElementById('leaveRequestFromFilter')?.value || '',
            to: document.getElementById('leaveRequestToFilter')?.value || '',
            selects: {
                type: document.getElementById('leaveRequestTypeFilter')?.value || '',
                department: document.getElementById('leaveRequestDepartmentFilter')?.value || '',
                role: document.getElementById('leaveRequestRoleFilter')?.value || '',
                status: document.getElementById('leaveRequestStatusFilter')?.value || '',
            },
        });
    };
    ['leaveRequestSearch', 'leaveRequestTypeFilter', 'leaveRequestDepartmentFilter', 'leaveRequestRoleFilter', 'leaveRequestStatusFilter', 'leaveRequestFromFilter', 'leaveRequestToFilter'].forEach((id) => {
        document.getElementById(id)?.addEventListener('input', applyRequestFilters);
        document.getElementById(id)?.addEventListener('change', applyRequestFilters);
    });

    const submitLeaveAction = (requestId, action, notes = '') => {
        const form = document.getElementById('leaveActionForm');
        if (!form || !requestId || !action || action === 'note') return;
        document.getElementById('leaveActionRequestId').value = requestId;
        document.getElementById('leaveActionValue').value = action;
        document.getElementById('leaveActionNotes').value = notes;
        form.submit();
    };

    document.querySelectorAll('[data-leave-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const action = button.dataset.leaveAction || '';
            const requestId = button.dataset.leaveId || document.getElementById('leaveDetailsModal')?.dataset.requestId || '';
            const name = button.dataset.leaveName || 'this request';
            const notes = document.getElementById('approvalNotes')?.value || '';

            if (action === 'note') {
                alertBox('Approval Notes', 'Choose Approve, Reject, or Forward to save notes with an approval action.', 'info');
                return;
            }

            const title = action.charAt(0).toUpperCase() + action.slice(1);
            if (window.Swal) {
                window.Swal.fire({
                    title: `${title} leave request?`,
                    text: `This will update ${name}'s leave request.`,
                    icon: action === 'reject' ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#f68b34',
                }).then((result) => {
                    if (result.isConfirmed) submitLeaveAction(requestId, action, notes);
                });
                return;
            }

            if (window.confirm(`${title} leave request for ${name}?`)) {
                submitLeaveAction(requestId, action, notes);
            }
        });
    });

    document.querySelectorAll('[data-leave-view]').forEach((button) => {
        button.addEventListener('click', () => {
            const request = (window.leaveRequests || []).find((item) => String(item.id) === String(button.dataset.leaveView));
            if (!request) return;
            const history = (request.history || []).map((item) => {
                if (typeof item === 'string') return `<li>${item}</li>`;
                return `<li><strong>${item.stage || 'Action'}</strong> - ${item.actor || 'System'} (${item.status || ''}) ${item.comment ? `: ${item.comment}` : ''}</li>`;
            }).join('');
            const content = document.getElementById('leaveDetailsContent');
            if (content) {
                content.innerHTML = `<div class="leave-details-grid"><div class="leave-detail-item"><small>Employee</small><strong>${request.employee}</strong></div><div class="leave-detail-item"><small>Department</small><strong>${request.department}</strong></div><div class="leave-detail-item"><small>Role</small><strong>${request.role}</strong></div><div class="leave-detail-item"><small>Leave Type</small><strong>${request.type}</strong></div><div class="leave-detail-item"><small>Start Date</small><strong>${request.start}</strong></div><div class="leave-detail-item"><small>End Date</small><strong>${request.end}</strong></div><div class="leave-detail-item"><small>Number of Days</small><strong>${request.days}</strong></div><div class="leave-detail-item"><small>Supporting Documents</small><strong>${request.documents}</strong></div></div><div class="leave-detail-item mt-3"><small>Reason</small><strong>${request.reason}</strong></div><div class="leave-detail-item mt-3"><small>Approval History</small><ul class="mb-0">${history}</ul></div>`;
            }
            const modalElement = document.getElementById('leaveDetailsModal');
            if (modalElement) modalElement.dataset.requestId = String(request.id);
            const noteButton = document.querySelector('[data-leave-action="note"]');
            if (noteButton) noteButton.dataset.leaveId = String(request.id);
            const modal = window.bootstrap ? new window.bootstrap.Modal(modalElement) : null;
            if (modal) modal.show();
        });
    });

    document.getElementById('leaveTypeForm')?.addEventListener('submit', (event) => {
        const form = event.currentTarget;
        if (!form.checkValidity()) {
            event.preventDefault();
            form.classList.add('was-validated');
            alertBox('Missing Leave Type Details', 'Please complete all required leave type fields.', 'warning');
        }
    });

    document.querySelectorAll('[data-leave-type-edit]').forEach((button) => button.addEventListener('click', () => alertBox('Edit Leave Type', `${button.dataset.leaveTypeEdit} can be edited in a future enhancement.`, 'info')));
    document.querySelectorAll('[data-leave-type-delete]').forEach((button) => {
        button.addEventListener('click', () => {
            const form = document.getElementById('leaveTypeToggleForm');
            const input = document.getElementById('leaveTypeToggleId');
            if (!form || !input) return;
            input.value = button.dataset.leaveTypeId || '';
            form.submit();
        });
    });
    document.querySelectorAll('[data-leave-export]').forEach((button) => button.addEventListener('click', () => alertBox('Export Started', `${button.dataset.leaveExport} export will be added when reporting is enabled.`, 'info')));

    const renderWorkflow = () => {
        const preview = document.getElementById('leaveWorkflowPreview');
        const selected = document.querySelector('input[name="approvalWorkflow"]:checked');
        if (!preview || !selected) return;
        const workflow = window.leaveApprovalWorkflows?.[selected.value];
        const steps = workflow?.steps || [];
        preview.innerHTML = steps.map((step, index) => `<span class="workflow-step"><i class="fa-solid fa-circle-check"></i>${step}</span>${index < steps.length - 1 ? '<span class="workflow-arrow"><i class="fa-solid fa-arrow-right"></i></span>' : ''}`).join('');
    };
    document.querySelectorAll('input[name="approvalWorkflow"]').forEach((input) => input.addEventListener('change', renderWorkflow));

    document.getElementById('leaveApprovalSettingsForm')?.addEventListener('submit', (event) => {
        const form = event.currentTarget;
        if (!form.checkValidity()) {
            event.preventDefault();
            form.classList.add('was-validated');
            alertBox('Missing Approval Settings', 'Please complete the numeric policy settings.', 'warning');
        }
    });

    const initCharts = () => {
        if (!window.Chart || !window.leaveChartData) return;
        const colors = ['#f68b34', '#ed3237', '#16a34a', '#0ea5e9', '#f59e0b', '#64748b', '#7c3aed', '#14b8a6'];
        const monthly = document.getElementById('monthlyLeaveChart');
        if (monthly) new window.Chart(monthly, { type: 'line', data: { labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], datasets: [{ label: 'Requests', data: window.leaveChartData.monthly, borderColor: '#f68b34', backgroundColor: 'rgba(246,139,52,.14)', fill: true, tension: .38 }] }, options: { responsive: true, plugins: { legend: { display: false } } } });
        const typeChart = document.getElementById('leaveTypeChart');
        if (typeChart) new window.Chart(typeChart, { type: 'pie', data: { labels: window.leaveChartData.typeLabels, datasets: [{ data: window.leaveChartData.types, backgroundColor: colors }] }, options: { responsive: true, plugins: { legend: { position: 'bottom' } } } });
        const statusChart = document.getElementById('approvalStatusChart');
        if (statusChart) new window.Chart(statusChart, { type: 'doughnut', data: { labels: ['Pending', 'Approved', 'Rejected', 'Forwarded'], datasets: [{ data: window.leaveChartData.statuses, backgroundColor: ['#f59e0b', '#16a34a', '#ed3237', '#0ea5e9'] }] }, options: { responsive: true, plugins: { legend: { position: 'bottom' } } } });
    };

    document.addEventListener('DOMContentLoaded', () => {
        applyRequestFilters();
        applyHistoryFilters();
        renderWorkflow();
        initCharts();
    });
}());

(function () {
    'use strict';

    const alertBox = (title, text, icon = 'success') => {
        if (window.Swal) {
            window.Swal.fire({ title, text, icon, confirmButtonColor: '#f68b34' });
            return;
        }
        window.alert(`${title}\n${text}`);
    };

    const numericPrice = (value) => Number(String(value).replace(/,/g, '').trim());

    document.getElementById('fuelPricingForm')?.addEventListener('submit', (event) => {
        const form = event.currentTarget;
        const prices = Array.from(form.querySelectorAll('.settings-price-input'));
        const invalidPrice = prices.find((input) => !Number.isFinite(numericPrice(input.value)) || numericPrice(input.value) <= 0);

        if (!form.checkValidity() || invalidPrice) {
            event.preventDefault();
            event.stopPropagation();
            form.classList.add('was-validated');
            alertBox('Invalid Fuel Price', 'Please enter numeric fuel prices greater than zero and select an effective date/time.', 'warning');
            invalidPrice?.focus();
        }
    });

    document.querySelector('[data-settings-reset]')?.addEventListener('click', (event) => {
        event.preventDefault();
        const form = event.currentTarget.closest('form');
        if (window.Swal) {
            window.Swal.fire({ title: 'Reset Pricing Form?', text: 'Sample values will be restored.', icon: 'question', showCancelButton: true, confirmButtonColor: '#f68b34' }).then((result) => {
                if (result.isConfirmed) form?.reset();
            });
            return;
        }
        form?.reset();
    });

    document.querySelectorAll('[data-price-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const action = button.dataset.priceAction;
            const id = button.dataset.priceId;
            alertBox(`${action.charAt(0).toUpperCase() + action.slice(1)} Price History`, `${id} would be processed in demo mode.`, action === 'delete' ? 'warning' : 'info');
        });
    });

    const activityRows = Array.from(document.querySelectorAll('[data-activity-row]'));
    const activityState = { page: 1, perPage: 5 };

    const applyActivityFilters = () => {
        if (!activityRows.length) return;
        const search = (document.getElementById('activitySearch')?.value || '').trim().toLowerCase();
        const from = document.getElementById('activityFromFilter')?.value || '';
        const to = document.getElementById('activityToFilter')?.value || '';
        const filters = {
            role: document.getElementById('activityRoleFilter')?.value || '',
            type: document.getElementById('activityTypeFilter')?.value || '',
            status: document.getElementById('activityStatusFilter')?.value || '',
        };

        activityRows.forEach((row) => {
            const matchesSearch = !search || (row.dataset.search || '').includes(search);
            const matchesFilters = Object.entries(filters).every(([key, value]) => !value || row.dataset[key] === value);
            const date = row.dataset.date || '';
            const matchesDate = (!from || date >= from) && (!to || date <= to);
            row.classList.toggle('is-hidden-by-settings-filter', !(matchesSearch && matchesFilters && matchesDate));
        });

        activityState.page = 1;
        paginateActivities();
    };

    const paginateActivities = () => {
        if (!activityRows.length) return;
        const visible = activityRows.filter((row) => !row.classList.contains('is-hidden-by-settings-filter'));
        const pages = Math.max(1, Math.ceil(visible.length / activityState.perPage));
        activityState.page = Math.min(activityState.page, pages);
        const start = (activityState.page - 1) * activityState.perPage;
        const end = start + activityState.perPage;

        activityRows.forEach((row) => row.classList.add('is-hidden-by-settings-page'));
        visible.slice(start, end).forEach((row) => row.classList.remove('is-hidden-by-settings-page'));

        const summary = document.getElementById('activitySummary');
        if (summary) summary.textContent = visible.length ? `Showing ${start + 1}-${Math.min(end, visible.length)} of ${visible.length} sample records` : 'No activity records match the filters';
        const prev = document.getElementById('prevActivityPage');
        const next = document.getElementById('nextActivityPage');
        if (prev) prev.disabled = activityState.page <= 1;
        if (next) next.disabled = activityState.page >= pages;
    };

    ['activitySearch', 'activityFromFilter', 'activityToFilter', 'activityRoleFilter', 'activityTypeFilter', 'activityStatusFilter'].forEach((id) => {
        document.getElementById(id)?.addEventListener('input', applyActivityFilters);
        document.getElementById(id)?.addEventListener('change', applyActivityFilters);
    });

    document.getElementById('prevActivityPage')?.addEventListener('click', () => { activityState.page -= 1; paginateActivities(); });
    document.getElementById('nextActivityPage')?.addEventListener('click', () => { activityState.page += 1; paginateActivities(); });

    document.querySelectorAll('[data-activity-view]').forEach((button) => {
        button.addEventListener('click', () => {
            const log = (window.activityLogs || []).find((item) => item.id === button.dataset.activityView);
            if (!log) return;
            const content = document.getElementById('activityDetailsContent');
            if (content) {
                content.innerHTML = `<div class="settings-details-grid"><div class="settings-detail-item"><small>Activity ID</small><strong>${log.id}</strong></div><div class="settings-detail-item"><small>User</small><strong>${log.user}</strong></div><div class="settings-detail-item"><small>Employee ID</small><strong>${log.employee_id}</strong></div><div class="settings-detail-item"><small>Role</small><strong>${log.role}</strong></div><div class="settings-detail-item"><small>Module</small><strong>${log.module}</strong></div><div class="settings-detail-item"><small>Activity Description</small><strong>${log.activity}</strong></div><div class="settings-detail-item"><small>Date & Time</small><strong>${log.datetime}</strong></div><div class="settings-detail-item"><small>IP Address</small><strong>${log.ip}</strong></div><div class="settings-detail-item"><small>Browser</small><strong>${log.browser}</strong></div><div class="settings-detail-item"><small>Operating System</small><strong>${log.os}</strong></div><div class="settings-detail-item"><small>Device Type</small><strong>${log.device}</strong></div><div class="settings-detail-item"><small>Status</small><strong>${log.status}</strong></div><div class="settings-detail-item"><small>Old Value</small><strong>${log.old_value}</strong></div><div class="settings-detail-item"><small>New Value</small><strong>${log.new_value}</strong></div></div><div class="settings-detail-item mt-3"><small>Additional Notes</small><strong>${log.notes}</strong></div>`;
            }
            if (window.bootstrap) new window.bootstrap.Modal(document.getElementById('activityDetailsModal')).show();
        });
    });

    document.querySelectorAll('[data-settings-export]').forEach((button) => {
        button.addEventListener('click', () => alertBox('Export Started (Demo Mode)', `${button.dataset.settingsExport} export would be generated here.`, 'info'));
    });

    document.addEventListener('DOMContentLoaded', () => {
        applyActivityFilters();
    });
}());


(function () {
    'use strict';

    const showAlert = (icon, title, text) => {
        if (window.Swal) {
            window.Swal.fire({ icon, title, text, confirmButtonColor: '#f68b34' });
            return;
        }
        window.alert(`${title}\n${text}`);
    };
    const normalize = (value) => String(value || '').trim().toLowerCase();
    const rows = Array.from(document.querySelectorAll('[data-pump-row]'));
    const search = document.getElementById('pumpSearch');
    const fuelFilter = document.getElementById('pumpFuelFilter');
    const statusFilter = document.getElementById('pumpStatusFilter');
    const pageSummary = document.getElementById('pumpPageSummary');
    const prev = document.getElementById('prevPumpPage');
    const next = document.getElementById('nextPumpPage');
    const perPage = 5;
    let page = 1;

    const filteredRows = () => rows.filter((row) => (!search || !search.value || normalize(row.dataset.search).includes(normalize(search.value))) && (!fuelFilter || !fuelFilter.value || row.dataset.fuel === fuelFilter.value) && (!statusFilter || !statusFilter.value || row.dataset.status === statusFilter.value));
    const renderRows = () => {
        if (rows.length === 0) { return; }
        const visible = filteredRows();
        const pages = Math.max(1, Math.ceil(visible.length / perPage));
        page = Math.min(page, pages);
        const start = (page - 1) * perPage;
        const end = start + perPage;
        rows.forEach((row) => { row.hidden = true; });
        visible.slice(start, end).forEach((row) => { row.hidden = false; });
        if (pageSummary) { pageSummary.textContent = `Showing ${visible.length === 0 ? 0 : start + 1}-${Math.min(end, visible.length)} of ${visible.length} pumps`; }
        if (prev) { prev.disabled = page <= 1; }
        if (next) { next.disabled = page >= pages; }
    };
    [search, fuelFilter, statusFilter].forEach((field) => { if (!field) { return; } field.addEventListener('input', () => { page = 1; renderRows(); }); field.addEventListener('change', () => { page = 1; renderRows(); }); });
    if (prev) { prev.addEventListener('click', () => { page -= 1; renderRows(); }); }
    if (next) { next.addEventListener('click', () => { page += 1; renderRows(); }); }

    document.addEventListener('click', (event) => {
        const exportButton = event.target.closest('[data-pump-export]');
        if (exportButton) { showAlert('info', `${exportButton.dataset.pumpExport} Export (Demo Mode)`, 'Pump records export will be connected during backend integration.'); return; }
        const actionButton = event.target.closest('[data-pump-action]');
        if (!actionButton) { return; }
        const pump = actionButton.dataset.pump || 'this pump';
        const action = actionButton.dataset.pumpAction;
        const titles = { view: 'Pump Details (Demo Mode)', toggle: 'Update Pump Status (Demo Mode)', delete: 'Delete Pump (Demo Mode)' };
        showAlert(action === 'delete' ? 'warning' : 'info', titles[action] || 'Pump Action (Demo Mode)', `${pump} ${action} action will be connected to MySQL later.`);
    });

    const form = document.getElementById('pumpForm');
    if (form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            form.classList.add('was-validated');
            if (!form.checkValidity()) { showAlert('warning', 'Review Pump Form', 'Please complete all required pump fields.'); return; }
            // ===========================================
            // DATABASE PLACEHOLDER
            // Save or update pump information.
            // ===========================================
            const isEdit = document.body.textContent.includes('Update Pump');
            showAlert('success', isEdit ? 'Pump Updated (Demo Mode)' : 'Pump Saved (Demo Mode)', 'Pump information will be saved to MySQL during backend integration.');
        });
        form.addEventListener('reset', () => { window.setTimeout(() => form.classList.remove('was-validated'), 0); });
    }
    renderRows();
}());

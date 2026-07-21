(function () {
    'use strict';

    const alertBox = (icon, title, text) => {
        if (window.Swal) {
            window.Swal.fire({ icon, title, text, confirmButtonColor: '#f68b34' });
            return;
        }
        window.alert(`${title}\n${text}`);
    };
    const normalize = (value) => String(value || '').trim().toLowerCase();
    const tableRows = () => Array.from(document.querySelectorAll('.fuel-table tbody tr')).filter((row) => !row.hidden);
    const tableHeaders = () => Array.from(document.querySelectorAll('.fuel-table thead th')).map((cell) => cell.textContent.trim());
    const tableData = () => tableRows().map((row) => Array.from(row.children).map((cell) => cell.textContent.replace(/\s+/g, ' ').trim()));
    const downloadFile = (filename, mimeType, content) => {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = filename;
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();
        URL.revokeObjectURL(url);
    };
    const csvEscape = (value) => `"${String(value).replace(/"/g, '""')}"`;
    const exportTable = (type) => {
        const headers = tableHeaders();
        const data = tableData();
        if (headers.length === 0 || data.length === 0) {
            alertBox('warning', 'No Records', 'There are no visible fuel sales records to export.');
            return;
        }
        const normalizedType = normalize(type);
        if (normalizedType === 'pdf') {
            window.print();
            return;
        }
        if (normalizedType === 'excel') {
            const html = `<table><thead><tr>${headers.map((header) => `<th>${header}</th>`).join('')}</tr></thead><tbody>${data.map((row) => `<tr>${row.map((cell) => `<td>${cell}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
            downloadFile(`fuel-sales-${Date.now()}.xls`, 'application/vnd.ms-excel', html);
            return;
        }
        const csv = [headers, ...data].map((row) => row.map(csvEscape).join(',')).join('\n');
        downloadFile(`fuel-sales-${Date.now()}.csv`, 'text/csv;charset=utf-8', csv);
    };

    const rows = () => Array.from(document.querySelectorAll('[data-fuel-row]'));
    const fields = {
        search: document.getElementById('fuelSearch'),
        date: document.getElementById('fuelDateFilter'),
        shift: document.getElementById('fuelShiftFilter'),
        pump: document.getElementById('fuelPumpFilter'),
        fuel: document.getElementById('fuelTypeFilter'),
        status: document.getElementById('fuelStatusFilter'),
        attendant: document.getElementById('fuelAttendantFilter'),
    };
    const pageSummary = document.getElementById('fuelPageSummary');
    const prev = document.getElementById('prevFuelPage');
    const next = document.getElementById('nextFuelPage');
    const perPage = 5;
    let page = 1;

    const filteredRows = () => rows().filter((row) => (!fields.search || !fields.search.value || normalize(row.dataset.search).includes(normalize(fields.search.value)))
        && (!fields.date || !fields.date.value || row.dataset.date === fields.date.value)
        && (!fields.shift || !fields.shift.value || row.dataset.shift === fields.shift.value)
        && (!fields.pump || !fields.pump.value || row.dataset.pump === fields.pump.value)
        && (!fields.fuel || !fields.fuel.value || row.dataset.fuel === fields.fuel.value)
        && (!fields.status || !fields.status.value || row.dataset.status === fields.status.value)
        && (!fields.attendant || !fields.attendant.value || row.dataset.attendant === fields.attendant.value));

    const renderRows = () => {
        if (rows().length === 0) { return; }
        const visible = filteredRows();
        const pages = Math.max(1, Math.ceil(visible.length / perPage));
        page = Math.min(page, pages);
        const start = (page - 1) * perPage;
        const end = start + perPage;
        rows().forEach((row) => { row.hidden = true; });
        visible.slice(start, end).forEach((row) => { row.hidden = false; });
        if (pageSummary) { pageSummary.textContent = `Showing ${visible.length === 0 ? 0 : start + 1}-${Math.min(end, visible.length)} of ${visible.length} fuel sales records`; }
        if (prev) { prev.disabled = page <= 1; }
        if (next) { next.disabled = page >= pages; }
    };
    Object.values(fields).forEach((field) => {
        if (!field) { return; }
        field.addEventListener('input', () => { page = 1; renderRows(); });
        field.addEventListener('change', () => { page = 1; renderRows(); });
    });
    if (prev) { prev.addEventListener('click', () => { page -= 1; renderRows(); }); }
    if (next) { next.addEventListener('click', () => { page += 1; renderRows(); }); }
    renderRows();

    document.addEventListener('click', (event) => {
        const exportButton = event.target.closest('[data-fuel-export]');
        if (exportButton) {
            exportTable(exportButton.dataset.fuelExport || 'CSV');
            return;
        }
        const actionButton = event.target.closest('[data-fuel-action]');
        if (actionButton) {
            const action = actionButton.dataset.fuelAction;
            const tx = actionButton.dataset.transaction || 'report';
            alertBox('info', `${action.replace('-', ' ')}`, `${tx} action is available from the verification/details page.`);
            return;
        }

    });

    const chartRoot = document.querySelector('[data-fuel-chart-data]');
    if (chartRoot && window.Chart) {
        let chartData = null;
        try { chartData = JSON.parse(chartRoot.dataset.fuelChartData || '{}'); } catch (error) { chartData = null; }
        const makeChart = (id, type, key, label, options = {}) => {
            const canvas = document.getElementById(id);
            if (!canvas || !chartData || !chartData[key]) { return; }
            const data = chartData[key];
            new window.Chart(canvas, {
                type,
                data: { labels: data.labels, datasets: [{ label, data: data.values, backgroundColor: options.colors || ['#f68b34', '#ed3237', '#0ea5e9', '#16a34a'], borderColor: '#f68b34', borderWidth: 2, fill: options.fill || false, tension: 0.35 }] },
                options: { responsive: true, maintainAspectRatio: false, indexAxis: options.indexAxis || 'x', plugins: { legend: { display: type === 'pie' || type === 'doughnut' } }, scales: type === 'pie' || type === 'doughnut' ? {} : { y: { beginAtZero: true } } },
            });
        };
        makeChart('fuelDailyChart', 'line', 'daily', 'Daily Sales', { fill: true });
        makeChart('fuelWeeklyChart', 'bar', 'weekly', 'Weekly Sales');
        makeChart('fuelMonthlyChart', 'line', 'monthly', 'Monthly Revenue', { fill: true });
        makeChart('fuelDistributionChart', 'doughnut', 'fuelDistribution', 'Fuel Type Distribution');
        makeChart('fuelPumpPerformanceChart', 'bar', 'pumpPerformance', 'Pump Performance', { indexAxis: 'y' });
    }
    window.renderFuelRows = renderRows;
}());




    document.addEventListener('submit', async (event) => {
        const form = event.target;
        const route = new URL(form.action, window.location.href).searchParams.get('route') || '';
        if (route !== 'admin/fuel-sales/verify') return;
        event.preventDefault(); event.stopImmediatePropagation();
        const button = event.submitter || form.querySelector('[type="submit"]');
        const action = button?.value || '';
        if (!['verify', 'reject', 'correction'].includes(action)) return;
        const title = action === 'verify' ? 'Verify fuel sale?' : action === 'reject' ? 'Reject fuel sale?' : 'Request correction?';
        const confirmed = !window.Swal ? window.confirm(title) : (await window.Swal.fire({
            icon: action === 'verify' ? 'question' : 'warning', title,
            text: 'The transaction status and live reports will update immediately.',
            showCancelButton: true, confirmButtonColor: action === 'verify' ? '#16a34a' : '#ed3237',
        })).isConfirmed;
        if (!confirmed) return;
        let actionInput = form.querySelector('input[name="action"]');
        if (!actionInput) { actionInput = document.createElement('input'); actionInput.type = 'hidden'; actionInput.name = 'action'; form.append(actionInput); }
        actionInput.value = action;
        try {
            await window.FuelOpsAjax.submitForm(form, { button, refresh: '.clock-workspace', redirect: false, loadingText: 'Updating sale...' });
        } catch (error) {
            // Shared AJAX handling preserves notes and displays validation/service errors.
        }
    }, true);

    let liveRefreshRunning = false;
    const refreshLiveFuelData = async () => {
        if (liveRefreshRunning || document.hidden || document.querySelector('form[action*="fuel-sales%2Fverify"], form[action*="fuel-sales/verify"]')) return;
        const selectors = [];
        if (document.querySelector('.fuel-summary-grid')) selectors.push('.fuel-summary-grid');
        if (document.getElementById('fuelSalesBody')) selectors.push('#fuelSalesBody');
        else if (document.querySelector('.fuel-table tbody')) selectors.push('.fuel-table tbody');
        if (!selectors.length) return;
        liveRefreshRunning = true;
        try {
            await window.FuelOpsAjax.refresh(selectors, window.location.href);
            window.renderFuelRows?.();
        } catch (error) {
            // Background refresh remains silent; the next interval retries.
        } finally {
            liveRefreshRunning = false;
        }
    };

    if (document.querySelector('.fuel-module-page')) {
        window.setInterval(refreshLiveFuelData, 30000);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshLiveFuelData(); });
    }



(function () {
    'use strict';
    const moneyFormatter = new Intl.NumberFormat('en-NG', { maximumFractionDigits: 2, minimumFractionDigits: 0 });
    const showAlert = (icon, title, text) => {
        if (window.Swal) { window.Swal.fire({ icon, title, text, confirmButtonColor: '#f68b34' }); return; }
        window.alert(`${title}\n${text}`);
    };
    const normalize = (value) => String(value || '').trim().toLowerCase();
    const form = document.getElementById('fuelDeliveryForm');
    const quantityInput = document.getElementById('quantityDelivered');
    const costInput = document.getElementById('costPerLiter');
    const totalCostInput = document.getElementById('totalCost');
    const calculateTotalCost = () => {
        if (!quantityInput || !costInput || !totalCostInput) { return; }
        totalCostInput.value = `NGN ${moneyFormatter.format(Number(quantityInput.value || 0) * Number(costInput.value || 0))}`;
    };
    [quantityInput, costInput].forEach((input) => { if (input) { input.addEventListener('input', calculateTotalCost); } });
    if (form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!form.checkValidity()) { form.classList.add('was-validated'); showAlert('error', 'Incomplete Delivery Form', 'Please complete all required delivery fields before saving.'); return; }
            // DATABASE PLACEHOLDER: Save fuel delivery.
            showAlert('success', 'Fuel Delivery Saved (Demo Mode)', 'This frontend sample is ready for future database persistence.');
            form.reset(); form.classList.remove('was-validated'); calculateTotalCost();
        });
        form.addEventListener('reset', () => { window.setTimeout(calculateTotalCost, 0); });
    }
    const rows = Array.from(document.querySelectorAll('[data-inventory-row]'));
    const searchInput = document.getElementById('inventorySearch');
    const fuelFilter = document.getElementById('inventoryFuelFilter');
    const pageSummary = document.getElementById('inventoryPageSummary');
    const prevButton = document.getElementById('prevInventoryPage');
    const nextButton = document.getElementById('nextInventoryPage');
    const perPage = 4;
    let page = 1;
    const filteredRows = () => rows.filter((row) => (!searchInput || !searchInput.value || normalize(row.dataset.search).includes(normalize(searchInput.value))) && (!fuelFilter || !fuelFilter.value || row.dataset.fuel === fuelFilter.value));
    const renderRows = () => {
        if (rows.length === 0) { return; }
        const visible = filteredRows();
        const pages = Math.max(1, Math.ceil(visible.length / perPage));
        page = Math.min(page, pages);
        const start = (page - 1) * perPage;
        rows.forEach((row) => { row.hidden = true; });
        visible.slice(start, start + perPage).forEach((row) => { row.hidden = false; });
        if (pageSummary) { pageSummary.textContent = `Showing ${visible.length === 0 ? 0 : start + 1}-${Math.min(start + perPage, visible.length)} of ${visible.length} delivery records`; }
        if (prevButton) { prevButton.disabled = page <= 1; }
        if (nextButton) { nextButton.disabled = page >= pages; }
    };
    [searchInput, fuelFilter].forEach((field) => { if (field) { field.addEventListener('input', () => { page = 1; renderRows(); }); field.addEventListener('change', () => { page = 1; renderRows(); }); } });
    if (prevButton) { prevButton.addEventListener('click', () => { page -= 1; renderRows(); }); }
    if (nextButton) { nextButton.addEventListener('click', () => { page += 1; renderRows(); }); }
    renderRows();
    document.addEventListener('click', (event) => {
        const cancelButton = event.target.closest('[data-inventory-action="cancel-form"]');
        if (cancelButton && form) { form.reset(); form.classList.remove('was-validated'); calculateTotalCost(); return; }
        const exportButton = event.target.closest('[data-inventory-export]');
        if (exportButton) { showAlert('info', `Export ${exportButton.dataset.inventoryExport} (Demo Mode)`, 'Inventory export will be generated after backend reporting is connected.'); return; }
        const actionButton = event.target.closest('[data-inventory-action]');
        if (!actionButton) { return; }
        const action = actionButton.dataset.inventoryAction;
        const delivery = actionButton.dataset.delivery || 'delivery record';
        if (action === 'delete') {
            if (window.Swal) { window.Swal.fire({ icon: 'warning', title: 'Delete delivery record?', text: `${delivery} will only be removed after backend integration.`, showCancelButton: true, confirmButtonText: 'Delete (Demo)', cancelButtonText: 'Cancel', confirmButtonColor: '#ed3237', cancelButtonColor: '#667085' }).then((result) => { if (result.isConfirmed) { showAlert('success', 'Delete Confirmed (Demo Mode)', 'Database deletion will be connected later.'); } }); return; }
            window.alert('Delete confirmation will be connected later.'); return;
        }
        showAlert('info', `${action.replace('-', ' ')} Delivery (Demo Mode)`, `${delivery} will open in a future backend workflow.`);
    });
    const chartRoot = document.querySelector('[data-inventory-chart-data]');
    if (chartRoot && window.Chart) {
        let chartData = null;
        try { chartData = JSON.parse(chartRoot.dataset.inventoryChartData || '{}'); } catch (error) { chartData = null; }
        const colors = ['#f68b34', '#0ea5e9', '#16a34a'];
        const makeDataset = (label, data, color, fill) => ({ label, data, backgroundColor: fill ? `${color}33` : color, borderColor: color, borderWidth: 2, tension: 0.35, fill });
        if (chartData && document.getElementById('inventoryDeliveriesChart')) { new window.Chart(document.getElementById('inventoryDeliveriesChart'), { type: 'bar', data: { labels: chartData.monthlyDeliveries.labels, datasets: [makeDataset('Petrol', chartData.monthlyDeliveries.petrol, colors[0], false), makeDataset('Diesel', chartData.monthlyDeliveries.diesel, colors[1], false), makeDataset('Gas', chartData.monthlyDeliveries.gas, colors[2], false)] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } } }); }
        if (chartData && document.getElementById('inventoryConsumptionChart')) { new window.Chart(document.getElementById('inventoryConsumptionChart'), { type: 'line', data: { labels: chartData.consumptionTrend.labels, datasets: [makeDataset('Petrol', chartData.consumptionTrend.petrol, '#ed3237', true), makeDataset('Diesel', chartData.consumptionTrend.diesel, colors[1], true), makeDataset('Gas', chartData.consumptionTrend.gas, colors[2], true)] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } } }); }
        if (chartData && document.getElementById('inventoryDistributionChart')) { new window.Chart(document.getElementById('inventoryDistributionChart'), { type: 'pie', data: { labels: chartData.distribution.labels, datasets: [{ data: chartData.distribution.values, backgroundColor: colors, borderColor: '#fff', borderWidth: 3 }] }, options: { responsive: true, maintainAspectRatio: false } }); }
    }
}());
(function () {
    'use strict';
    const moneyFormatter = new Intl.NumberFormat('en-NG', { maximumFractionDigits: 2, minimumFractionDigits: 0 });
    const showAlert = (icon, title, text) => {
        if (window.Swal) { window.Swal.fire({ icon, title, text, confirmButtonColor: '#f68b34' }); return; }
        window.alert(`${title}\n${text}`);
    };
    const normalize = (value) => String(value || '').trim().toLowerCase();
    const tableRows = () => Array.from(document.querySelectorAll('.inventory-table tbody tr')).filter((row) => !row.hidden);
    const tableHeaders = () => Array.from(document.querySelectorAll('.inventory-table thead th')).map((cell) => cell.textContent.trim());
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
    const exportInventory = (type) => {
        const headers = tableHeaders();
        const data = tableData();
        if (headers.length === 0 || data.length === 0) { showAlert('warning', 'No Records', 'There are no visible inventory records to export.'); return; }
        const normalizedType = normalize(type);
        if (normalizedType === 'pdf') { window.print(); return; }
        if (normalizedType === 'excel') {
            const html = `<table><thead><tr>${headers.map((header) => `<th>${header}</th>`).join('')}</tr></thead><tbody>${data.map((row) => `<tr>${row.map((cell) => `<td>${cell}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
            downloadFile(`fuel-inventory-${Date.now()}.xls`, 'application/vnd.ms-excel', html);
            return;
        }
        const csv = [headers, ...data].map((row) => row.map(csvEscape).join(',')).join('\n');
        downloadFile(`fuel-inventory-${Date.now()}.csv`, 'text/csv;charset=utf-8', csv);
    };
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
            if (!form.checkValidity()) { event.preventDefault(); form.classList.add('was-validated'); showAlert('error', 'Incomplete Delivery Form', 'Please complete all required delivery fields before saving.'); }
        });
        form.addEventListener('reset', () => { window.setTimeout(calculateTotalCost, 0); });
    }
    const rows = () => Array.from(document.querySelectorAll('[data-inventory-row]'));
    const searchInput = document.getElementById('inventorySearch');
    const fuelFilter = document.getElementById('inventoryFuelFilter');
    const pageSummary = document.getElementById('inventoryPageSummary');
    const prevButton = document.getElementById('prevInventoryPage');
    const nextButton = document.getElementById('nextInventoryPage');
    const perPage = 4;
    let page = 1;
    const filteredRows = () => rows().filter((row) => (!searchInput || !searchInput.value || normalize(row.dataset.search).includes(normalize(searchInput.value))) && (!fuelFilter || !fuelFilter.value || row.dataset.fuel === fuelFilter.value));
    const renderRows = () => {
        if (rows().length === 0) { return; }
        const visible = filteredRows();
        const pages = Math.max(1, Math.ceil(visible.length / perPage));
        page = Math.min(page, pages);
        const start = (page - 1) * perPage;
        rows().forEach((row) => { row.hidden = true; });
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
        if (exportButton) { exportInventory(exportButton.dataset.inventoryExport || 'CSV'); return; }
        const actionButton = event.target.closest('[data-inventory-action]');
        if (!actionButton) { return; }
        const action = actionButton.dataset.inventoryAction;
        const delivery = actionButton.dataset.delivery || 'delivery record';
        if (action === 'delete') {
            showAlert('info', 'Delete Not Available', 'Use a controlled inventory adjustment to correct stock records.');
            return;
        }
        showAlert('info', `${action.replace('-', ' ')} Delivery`, `${delivery} is recorded in the inventory audit trail.`);
    });    const chartRoot = document.querySelector('[data-inventory-chart-data]');
    if (chartRoot && window.Chart) {
        let chartData = null;
        try { chartData = JSON.parse(chartRoot.dataset.inventoryChartData || '{}'); } catch (error) { chartData = null; }
        const colors = ['#f68b34', '#0ea5e9', '#16a34a'];
        const makeDataset = (label, data, color, fill) => ({ label, data, backgroundColor: fill ? `${color}33` : color, borderColor: color, borderWidth: 2, tension: 0.35, fill });
        if (chartData && document.getElementById('inventoryDeliveriesChart')) { new window.Chart(document.getElementById('inventoryDeliveriesChart'), { type: 'bar', data: { labels: chartData.monthlyDeliveries.labels, datasets: [makeDataset('Petrol', chartData.monthlyDeliveries.petrol, colors[0], false), makeDataset('Diesel', chartData.monthlyDeliveries.diesel, colors[1], false), makeDataset('Gas', chartData.monthlyDeliveries.gas, colors[2], false)] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } } }); }
        if (chartData && document.getElementById('inventoryConsumptionChart')) { new window.Chart(document.getElementById('inventoryConsumptionChart'), { type: 'line', data: { labels: chartData.consumptionTrend.labels, datasets: [makeDataset('Petrol', chartData.consumptionTrend.petrol, '#ed3237', true), makeDataset('Diesel', chartData.consumptionTrend.diesel, colors[1], true), makeDataset('Gas', chartData.consumptionTrend.gas, colors[2], true)] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } } }); }
        if (chartData && document.getElementById('inventoryDistributionChart')) { new window.Chart(document.getElementById('inventoryDistributionChart'), { type: 'pie', data: { labels: chartData.distribution.labels, datasets: [{ data: chartData.distribution.values, backgroundColor: colors, borderColor: '#fff', borderWidth: 3 }] }, options: { responsive: true, maintainAspectRatio: false } }); }
    }
    window.renderInventoryRows = renderRows;
    window.calculateInventoryTotal = calculateTotalCost;
}());


(function () {
    'use strict';
    const routeUrl = (route) => { const url = new URL(window.location.href); url.search = ''; url.searchParams.set('route', route); return url.toString(); };
    const refreshInventory = async () => {
        await window.FuelOpsAjax.refresh(['.inventory-summary-grid', '.inventory-fuel-grid', '.row.g-4.mt-1.align-items-start', '#inventoryDeliveryBody'], window.location.href);
        window.renderInventoryRows?.();
        installAdjustmentButton();
        const html = await fetch(window.location.href, { credentials: 'same-origin' }).then((response) => response.text());
        const match = html.match(/window\.inventoryDeliveries\s*=\s*(\[[\s\S]*?\]);<\/script>/);
        if (match) window.inventoryDeliveries = JSON.parse(match[1]);
    };
    const ensureDeliveryId = (form) => {
        let input = form.querySelector('[name="delivery_id"]');
        if (!input) { input = document.createElement('input'); input.type = 'hidden'; input.name = 'delivery_id'; form.append(input); }
        return input;
    };
    const installAdjustmentButton = () => {
        const header = document.querySelector('#fuelDeliveryForm')?.previousElementSibling;
        if (!header || header.querySelector('[data-inventory-adjust]')) return;
        const button = document.createElement('button');
        button.type = 'button'; button.className = 'btn btn-outline-brand btn-sm'; button.dataset.inventoryAdjust = 'true';
        button.innerHTML = '<i class="fa-solid fa-sliders"></i> Stock Adjustment';
        header.append(button);
    };
    document.addEventListener('input', (event) => {
        if (event.target.matches('#quantityDelivered, #costPerLiter')) window.calculateInventoryTotal?.();
    }, true);
    document.addEventListener('submit', async (event) => {
        const form = event.target;
        const route = new URL(form.action, window.location.href).searchParams.get('route') || '';
        if (route !== 'admin/fuel-inventory/delivery') return;
        event.preventDefault(); event.stopImmediatePropagation();
        if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
        try {
            await window.FuelOpsAjax.submitForm(form, { button: event.submitter, redirect: false, notify: true, loadingText: 'Saving delivery...' });
            await refreshInventory();
        } catch (error) { /* shared helper preserves all delivery values */ }
    }, true);
    document.addEventListener('click', async (event) => {
        const cancel = event.target.closest('[data-inventory-action="cancel-form"]');
        if (cancel) {
            event.preventDefault(); event.stopImmediatePropagation();
            const currentForm = document.getElementById('fuelDeliveryForm');
            if (currentForm) { currentForm.reset(); currentForm.classList.remove('was-validated'); window.calculateInventoryTotal?.(); }
            return;
        }

        const adjust = event.target.closest('[data-inventory-adjust]');
        if (adjust) {
            event.preventDefault(); event.stopImmediatePropagation();
            const options = Array.from(document.querySelectorAll('#deliveryFuelType option')).filter((option) => option.value).map((option) => `<option value="${option.value}">${option.textContent}</option>`).join('');
            if (!window.Swal) { window.FuelOpsAjax.notify('info', 'Stock adjustment requires the dialog component.'); return; }
            const result = await window.Swal.fire({

                title: 'Stock Adjustment',
                html: `<select id="stockAdjustmentFuel" class="form-select mb-3"><option value="">Select fuel type</option>${options}</select><input id="stockAdjustmentQuantity" class="form-control mb-3" type="number" step="0.01" placeholder="Use negative value to reduce stock"><textarea id="stockAdjustmentReason" class="form-control" rows="3" placeholder="Reason for adjustment"></textarea>`,
                showCancelButton: true, confirmButtonText: 'Apply Adjustment', confirmButtonColor: '#f68b34',
                preConfirm: () => {
                    const fuel = document.getElementById('stockAdjustmentFuel').value;
                    const quantity = document.getElementById('stockAdjustmentQuantity').value;
                    const reason = document.getElementById('stockAdjustmentReason').value.trim();
                    if (!fuel || !quantity || Number(quantity) === 0 || !reason) { window.Swal.showValidationMessage('Fuel type, non-zero quantity, and reason are required.'); return false; }
                    return { fuel, quantity, reason };
                },
            });
            if (!result.isConfirmed) return;
            const data = new FormData(); data.append('fuel_type_id', result.value.fuel); data.append('adjustment_quantity', result.value.quantity); data.append('reason', result.value.reason); data.append('_csrf_token', window.FuelOpsAjax.csrfToken());
            window.FuelOpsAjax.loading.start(adjust, 'Adjusting...');
            try { const payload = await window.FuelOpsAjax.request(routeUrl('admin/fuel-inventory/adjust'), { method: 'POST', body: data }); await window.FuelOpsAjax.notify('success', payload.message); await refreshInventory(); }
            catch (error) { await window.FuelOpsAjax.notify('error', error.message); }
            finally { window.FuelOpsAjax.loading.stop(adjust); }
            return;
        }
        const button = event.target.closest('[data-inventory-action="edit"], [data-inventory-action="delete"]');
        if (!button) return;
        event.preventDefault(); event.stopImmediatePropagation();
        const delivery = (window.inventoryDeliveries || []).find((item) => item.invoice === button.dataset.delivery);
        if (!delivery) return;
        if (button.dataset.inventoryAction === 'edit') {
            const form = document.getElementById('fuelDeliveryForm'); if (!form) return;
            ensureDeliveryId(form).value = delivery.id;
            const values = { fuel_type_id: delivery.fuel_type_id, delivery_date: delivery.date, delivery_time: delivery.time.slice(0,5), supplier_name: delivery.supplier, tanker_number: delivery.tanker, invoice_number: delivery.invoice, quantity_litres: delivery.quantity, cost_per_litre: delivery.cost_per_liter, received_by: delivery.received_by_id, remarks: delivery.remarks };
            Object.entries(values).forEach(([name,value]) => { if (form.elements[name]) form.elements[name].value = value ?? ''; });
            const submit = form.querySelector('[type="submit"]'); if (submit) submit.innerHTML = '<i class="fa-solid fa-floppy-disk"></i>Update Delivery';
            window.calculateInventoryTotal?.(); form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        const confirmed = !window.Swal ? window.confirm(`Delete delivery ${delivery.invoice}?`) : (await window.Swal.fire({ icon: 'warning', title: 'Delete fuel delivery?', text: 'Its quantity will be reversed from current stock. This is blocked if it would make stock negative.', showCancelButton: true, confirmButtonColor: '#ed3237' })).isConfirmed;
        if (!confirmed) return;
        const data = new FormData(); data.append('delivery_id', delivery.id); data.append('_csrf_token', window.FuelOpsAjax.csrfToken());
        window.FuelOpsAjax.loading.start(button, 'Deleting...');
        try { const payload = await window.FuelOpsAjax.request(routeUrl('admin/fuel-inventory/delete-delivery'), { method: 'POST', body: data }); await window.FuelOpsAjax.notify('success', payload.message); await refreshInventory(); }
        catch (error) { await window.FuelOpsAjax.notify('error', error.message); }
        finally { window.FuelOpsAjax.loading.stop(button); }
    }, true);
    document.addEventListener('reset', (event) => { if (event.target.id === 'fuelDeliveryForm') { ensureDeliveryId(event.target).value = ''; const submit = event.target.querySelector('[type="submit"]'); if (submit) submit.innerHTML = '<i class="fa-solid fa-floppy-disk"></i>Save Delivery'; } });
    document.addEventListener('DOMContentLoaded', installAdjustmentButton);
    let refreshing = false;
    window.setInterval(async () => { if (refreshing || document.hidden || document.activeElement?.closest('#fuelDeliveryForm')) return; refreshing = true; try { await refreshInventory(); } catch (error) {} finally { refreshing = false; } }, 30000);
}());

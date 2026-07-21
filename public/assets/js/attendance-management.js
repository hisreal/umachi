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

    const setupCanvas = (canvas) => {
        if (!canvas) { return null; }
        const ratio = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        const width = rect.width || canvas.parentElement.clientWidth || 320;
        const height = Number(canvas.getAttribute('height')) || 230;
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        canvas.style.height = `${height}px`;
        const context = canvas.getContext('2d');
        context.scale(ratio, ratio);
        return { context, width, height };
    };
    const drawText = (context, text, x, y, options = {}) => { context.fillStyle = options.color || '#667085'; context.font = options.font || '700 11px Arial'; context.textAlign = options.align || 'center'; context.fillText(text, x, y); };
    const roundedRect = (context, x, y, width, height, radius) => { const r = Math.min(radius, width / 2, height / 2); context.beginPath(); context.moveTo(x + r, y); context.lineTo(x + width - r, y); context.quadraticCurveTo(x + width, y, x + width, y + r); context.lineTo(x + width, y + height - r); context.quadraticCurveTo(x + width, y + height, x + width - r, y + height); context.lineTo(x + r, y + height); context.quadraticCurveTo(x, y + height, x, y + height - r); context.lineTo(x, y + r); context.quadraticCurveTo(x, y, x + r, y); context.closePath(); };
    const chartData = (() => {
        const page = document.querySelector('[data-attendance-chart-data]');
        if (!page) { return null; }
        try { return JSON.parse(page.dataset.attendanceChartData || '{}'); } catch (error) { return null; }
    })();
    const drawLineChart = (canvas, data) => {
        const state = setupCanvas(canvas); if (!state || !data) { return; }
        const { context, width, height } = state; const padding = 34; const max = 100; const min = 80;
        const points = data.values.map((value, index) => ({ x: padding + index * ((width - padding * 2) / (data.values.length - 1)), y: padding + ((max - value) / (max - min)) * (height - padding * 2), value, label: data.labels[index] }));
        context.clearRect(0, 0, width, height); context.strokeStyle = '#e5e7eb'; context.lineWidth = 1;
        for (let i = 0; i < 4; i += 1) { const y = padding + i * ((height - padding * 2) / 3); context.beginPath(); context.moveTo(padding, y); context.lineTo(width - padding, y); context.stroke(); }
        context.strokeStyle = '#f68b34'; context.lineWidth = 3; context.beginPath(); points.forEach((p, i) => { if (i === 0) { context.moveTo(p.x, p.y); } else { context.lineTo(p.x, p.y); } }); context.stroke();
        points.forEach((p) => { context.beginPath(); context.fillStyle = '#fff'; context.arc(p.x, p.y, 6, 0, Math.PI * 2); context.fill(); context.strokeStyle = '#ed3237'; context.lineWidth = 3; context.stroke(); drawText(context, `${p.value}%`, p.x, p.y - 12, { color: '#101828', font: '800 11px Arial' }); drawText(context, p.label, p.x, height - 10); });
    };
    const drawBarChart = (canvas, data) => {
        const state = setupCanvas(canvas); if (!state || !data) { return; }
        const { context, width, height } = state; const padding = 34; const max = Math.max(...data.values); const area = width - padding * 2; const barWidth = Math.max(18, area / data.values.length - 14);
        context.clearRect(0, 0, width, height); data.values.forEach((value, index) => { const x = padding + index * (area / data.values.length) + 7; const barHeight = (value / max) * (height - padding * 2); const y = height - padding - barHeight; const gradient = context.createLinearGradient(0, y, 0, height - padding); gradient.addColorStop(0, '#f68b34'); gradient.addColorStop(1, '#ed3237'); context.fillStyle = gradient; roundedRect(context, x, y, barWidth, barHeight, 8); context.fill(); drawText(context, data.labels[index].slice(0, 3), x + barWidth / 2, height - 10); });
    };
    const drawDoughnutChart = (canvas, data) => {
        const state = setupCanvas(canvas); if (!state || !data) { return; }
        const { context, width, height } = state; const colors = ['#16a34a', '#ed3237', '#f68b34', '#0ea5e9']; const total = data.values.reduce((sum, value) => sum + value, 0); const cx = width / 2; const cy = height / 2 - 6; const radius = Math.min(width, height) / 3.25; let start = -Math.PI / 2;
        context.clearRect(0, 0, width, height); data.values.forEach((value, index) => { const angle = (value / total) * Math.PI * 2; context.beginPath(); context.moveTo(cx, cy); context.fillStyle = colors[index % colors.length]; context.arc(cx, cy, radius, start, start + angle); context.closePath(); context.fill(); start += angle; });
        context.beginPath(); context.fillStyle = '#fff'; context.arc(cx, cy, radius * .55, 0, Math.PI * 2); context.fill(); drawText(context, 'Status', cx, cy - 2, { color: '#101828', font: '900 16px Arial' }); drawText(context, 'Today', cx, cy + 16, { font: '800 12px Arial' });
    };
    const drawCharts = () => { if (!chartData) { return; } drawLineChart(document.getElementById('attendanceMonthlyChart'), chartData.monthly); drawBarChart(document.getElementById('attendanceDailyChart'), chartData.daily); drawDoughnutChart(document.getElementById('attendanceStatusChart'), chartData.status); };
    drawCharts(); window.addEventListener('resize', drawCharts);

    const rows = () => Array.from(document.querySelectorAll('[data-attendance-row]'));
    const fields = { search: document.getElementById('attendanceSearch'), date: document.getElementById('attendanceDateFilter'), department: document.getElementById('attendanceDepartmentFilter'), role: document.getElementById('attendanceRoleFilter'), employee: document.getElementById('attendanceEmployeeFilter'), shift: document.getElementById('attendanceShiftFilter'), status: document.getElementById('attendanceStatusFilter') };
    const pageSummary = document.getElementById('attendancePageSummary'); const prev = document.getElementById('prevAttendancePage'); const next = document.getElementById('nextAttendancePage'); const perPage = 5; let page = 1;
    const renderRows = () => { const visible = rows(); if (visible.length === 0) { if (pageSummary) pageSummary.textContent = 'Showing 0 attendance records'; return; } const pages = Math.max(1, Math.ceil(visible.length / perPage)); page = Math.min(page, pages); const start = (page - 1) * perPage; const end = start + perPage; visible.forEach((row) => { row.hidden = true; }); visible.slice(start, end).forEach((row) => { row.hidden = false; }); if (pageSummary) pageSummary.textContent = `Showing ${start + 1}-${Math.min(end, visible.length)} of ${visible.length} attendance records`; if (prev) prev.disabled = page <= 1; if (next) next.disabled = page >= pages; };
    const filterUrl = () => {
        const url = new URL(window.location.href);
        Object.entries(fields).forEach(([key, field]) => {
            const value = field?.value?.trim() || '';
            if (value) url.searchParams.set(key, value); else url.searchParams.delete(key);
        });
        return url;
    };
    let filterTimer;
    const loadFilteredAttendance = () => {
        window.clearTimeout(filterTimer);
        filterTimer = window.setTimeout(async () => {
            const url = filterUrl(); page = 1;
            try {
                await window.FuelOpsAjax.refresh(['.attendance-stats-grid', '#attendanceHistoryBody'], url.toString());
                window.history.replaceState({}, '', url.toString());
                renderRows();
            } catch (error) { window.FuelOpsAjax.notify('error', error.message); }
        }, 250);
    };
    Object.values(fields).forEach((field) => { if (!field) return; field.addEventListener('input', loadFilteredAttendance); field.addEventListener('change', loadFilteredAttendance); });
    if (prev) prev.addEventListener('click', () => { page -= 1; renderRows(); });
    if (next) next.addEventListener('click', () => { page += 1; renderRows(); });
    renderRows();

    document.addEventListener('click', (event) => {
        const exportButton = event.target.closest('[data-attendance-export-type], [data-attendance-export="true"]');
        if (!exportButton) { return; }
        event.preventDefault();
        const type = exportButton.dataset.attendanceExportType || 'Attendance';
        showAlert('info', `Export ${type} (Demo Mode)`, 'Attendance export will be connected during backend integration.');
    });

    const settingsForm = document.getElementById('attendanceSettingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', async (event) => {
            event.preventDefault(); settingsForm.classList.add('was-validated');
            if (!settingsForm.checkValidity()) { showAlert('warning', 'Review Settings', 'Please complete all attendance configuration fields.'); return; }
            await window.FuelOpsAjax.submitForm(settingsForm, { button: event.submitter, redirect: false }).catch(() => {});
        });
        settingsForm.addEventListener('reset', () => { window.setTimeout(() => settingsForm.classList.remove('was-validated'), 0); });
    }
}());

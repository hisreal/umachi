(() => {
    'use strict';

    const modalElement = document.getElementById('attendanceSelfieModal');
    const content = document.getElementById('attendanceSelfieModalContent');
    const endpoint = window.attendanceSelfieDetailsUrl || '';
    if (!modalElement || !content || !endpoint) return;

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const detail = (label, value) => `<div class="attendance-selfie-detail"><small>${escapeHtml(label)}</small><strong>${escapeHtml(value || 'N/A')}</strong></div>`;
    const evidence = (label, status, url, employeeName, kind = 'Selfie') => {
        let body = `<div class="attendance-selfie-empty">No ${escapeHtml(label)} ${escapeHtml(kind)} Available.</div>`;
        if (status === 'missing') {
            body = '<div class="attendance-selfie-empty">Image not available.</div>';
        } else if (status === 'available' && url) {
            body = `<button class="btn p-0 border-0 w-100" type="button" data-image-view="${escapeHtml(url)}" data-image-title="${escapeHtml(label)} ${escapeHtml(kind)}" title="View image">
                <img src="${escapeHtml(url)}" loading="lazy" alt="${escapeHtml(label)} ${escapeHtml(kind)} for ${escapeHtml(employeeName)}" data-attendance-selfie-image>
            </button>`;
        }
        return `<article class="attendance-selfie-card"><div class="attendance-selfie-card__header"><i class="fa-solid fa-camera"></i><h6>${escapeHtml(label)} ${escapeHtml(kind)}</h6></div>${body}</article>`;
    };

    const modal = window.bootstrap ? new window.bootstrap.Modal(modalElement) : null;
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-attendance-view]');
        if (!button) return;

        const recordId = button.dataset.attendanceView;
        content.innerHTML = '<div class="attendance-selfie-loading"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Loading attendance details...</div>';
        modal?.show();

        try {
            const payload = await window.FuelOpsAjax.request(`${endpoint}&id=${encodeURIComponent(recordId)}`);
            if (!payload.data?.record) {
                throw new Error(payload.message || 'Attendance record not found.');
            }
            const record = payload.data.record;
            content.innerHTML = `
                <div class="attendance-selfie-details-grid">
                    ${detail('Employee Name: ', record.employee_name)}
                    ${detail('Employee ID: ', record.employee_id)}
                    ${detail('Department: ', record.department)}
                    ${detail('Role: ', record.role)}
                    ${detail('Attendance Date: ', record.attendance_date)}
                    ${detail('Shift: ', record.shift)}
                    ${detail('Attendance Status: ', record.status)}
                    ${detail('Clock-In Time: ', record.clock_in_time)}
                    ${detail('Clock-Out Time: ', record.clock_out_time)}
                    ${detail('Lateness: ', record.lateness)}
                    ${detail('Overtime: ', record.overtime)}
                    ${detail('Attendance Remarks: ', record.remarks)}
                </div>
                <div class="attendance-selfie-image-grid">
                    ${evidence('Clock-In', record.clock_in_selfie_status, record.clock_in_selfie_url, record.employee_name)}
                    ${evidence('Clock-Out', record.clock_out_selfie_status, record.clock_out_selfie_url, record.employee_name)}
                    ${evidence('Opening Meter', record.opening_meter_image_status, record.opening_meter_image_url, record.employee_name, 'Photo')}
                    ${evidence('Closing Meter', record.closing_meter_image_status, record.closing_meter_image_url, record.employee_name, 'Photo')}
                </div>`;

            content.querySelectorAll('[data-attendance-selfie-image]').forEach((image) => {
                image.addEventListener('error', () => {
                    const card = image.closest('.attendance-selfie-card');
                    if (card) {
                        card.querySelector('[data-image-view]')?.remove();
                        card.insertAdjacentHTML('beforeend', '<div class="attendance-selfie-empty">Image not available.</div>');
                    }
                }, { once: true });
            });
        } catch (error) {
            content.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(error.message || 'Attendance details could not be loaded.')}</div>`;
        }
    });
})();
(() => {
    'use strict';
    const endpoint = window.attendanceSelfieDetailsUrl || '';

    const adjustmentElement = document.getElementById('attendanceAdjustmentModal');
    const adjustmentForm = document.getElementById('attendanceAdjustmentForm');
    const adjustmentModal = adjustmentElement && window.bootstrap ? new window.bootstrap.Modal(adjustmentElement) : null;
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-attendance-adjust]');
        if (!button || !adjustmentForm) return;
        const recordId = button.dataset.attendanceAdjust;
        const summary = document.getElementById('attendanceAdjustmentRecord');
        adjustmentForm.reset();
        adjustmentForm.elements.attendance_id.value = recordId;
        if (summary) summary.textContent = 'Loading attendance record...';
        adjustmentModal?.show();
        try {
            const payload = await window.FuelOpsAjax.request(`${endpoint}&id=${encodeURIComponent(recordId)}`);
            const record = payload.data?.record;
            if (!record) throw new Error('Attendance record not found.');
            adjustmentForm.elements.clock_in.value = record.clock_in_value || '';
            adjustmentForm.elements.clock_out.value = record.clock_out_value || '';
            adjustmentForm.elements.status.value = record.status || 'Present';
            adjustmentForm.elements.remarks.value = record.remarks === 'No attendance remarks.' ? '' : record.remarks;
            if (summary) summary.textContent = `${record.employee_name} (${record.employee_id}) ? ${record.attendance_date}`;
        } catch (error) {
            if (summary) summary.textContent = error.message || 'Attendance record could not be loaded.';
        }
    });

    adjustmentForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        adjustmentForm.classList.add('was-validated');
        if (!adjustmentForm.checkValidity()) return;
        await window.FuelOpsAjax.submitForm(adjustmentForm, {
            button: event.submitter,
            redirect: false,
            refresh: ['.attendance-stats-grid', '#attendanceHistoryBody'],
            refreshUrl: window.location.href
        }).then(() => {
            adjustmentModal?.hide();
            adjustmentForm.classList.remove('was-validated');
            document.dispatchEvent(new CustomEvent('attendance:updated'));
        }).catch(() => {});
    });
})();

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
    const selfie = (label, status, url, employeeName) => {
        let body = `<div class="attendance-selfie-empty">No ${escapeHtml(label)} Selfie Available.</div>`;
        if (status === 'missing') {
            body = '<div class="attendance-selfie-empty">Image not available.</div>';
        } else if (status === 'available' && url) {
            body = `<a href="${escapeHtml(url)}" target="_blank" rel="noopener" title="Open full-size image">
                <img src="${escapeHtml(url)}" loading="lazy" alt="${escapeHtml(label)} selfie for ${escapeHtml(employeeName)}" data-attendance-selfie-image>
            </a>`;
        }
        return `<article class="attendance-selfie-card"><div class="attendance-selfie-card__header"><i class="fa-solid fa-camera"></i><h6>${escapeHtml(label)} Selfie</h6></div>${body}</article>`;
    };

    const modal = window.bootstrap ? new window.bootstrap.Modal(modalElement) : null;
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-attendance-view]');
        if (!button) return;

        const recordId = button.dataset.attendanceView;
        content.innerHTML = '<div class="attendance-selfie-loading"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Loading attendance details...</div>';
        modal?.show();

        try {
            const response = await fetch(`${endpoint}&id=${encodeURIComponent(recordId)}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const payload = await response.json();
            if (!response.ok || !payload.success || !payload.record) {
                throw new Error(payload.message || 'Attendance record not found.');
            }

            const record = payload.record;
            content.innerHTML = `
                <div class="attendance-selfie-details-grid">
                    ${detail('Employee Name', record.employee_name)}
                    ${detail('Employee ID', record.employee_id)}
                    ${detail('Department', record.department)}
                    ${detail('Role', record.role)}
                    ${detail('Attendance Date', record.attendance_date)}
                    ${detail('Shift', record.shift)}
                    ${detail('Attendance Status', record.status)}
                    ${detail('Clock-In Time', record.clock_in_time)}
                    ${detail('Clock-Out Time', record.clock_out_time)}
                    ${detail('Lateness', record.lateness)}
                    ${detail('Overtime', record.overtime)}
                    ${detail('Attendance Remarks', record.remarks)}
                </div>
                <div class="attendance-selfie-image-grid">
                    ${selfie('Clock-In', record.clock_in_selfie_status, record.clock_in_selfie_url, record.employee_name)}
                    ${selfie('Clock-Out', record.clock_out_selfie_status, record.clock_out_selfie_url, record.employee_name)}
                </div>`;

            content.querySelectorAll('[data-attendance-selfie-image]').forEach((image) => {
                image.addEventListener('error', () => {
                    const card = image.closest('.attendance-selfie-card');
                    if (card) {
                        card.querySelector('a')?.remove();
                        card.insertAdjacentHTML('beforeend', '<div class="attendance-selfie-empty">Image not available.</div>');
                    }
                }, { once: true });
            });
        } catch (error) {
            content.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(error.message || 'Attendance details could not be loaded.')}</div>`;
        }
    });
})();

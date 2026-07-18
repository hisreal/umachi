(() => {
    'use strict';

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const detail = (label, value) => `<div class="settings-detail-item"><small>${escapeHtml(label)}</small><strong>${escapeHtml(value || 'N/A')}</strong></div>`;

    document.querySelectorAll('[data-activity-view]').forEach((button) => {
        button.addEventListener('click', () => {
            const log = (window.activityLogs || []).find((item) => String(item.id) === String(button.dataset.activityView));
            if (!log) return;

            const content = document.getElementById('activityDetailsContent');
            if (content) {
                content.innerHTML = `<div class="settings-details-grid">
                    ${detail('Activity ID', log.id)}
                    ${detail('Employee', log.user)}
                    ${detail('Employee ID', log.employee_id)}
                    ${detail('Role', log.role)}
                    ${detail('Module', log.module)}
                    ${detail('Action', log.action)}
                    ${detail('Description', log.activity)}
                    ${detail('Date & Time', log.timestamp)}
                    ${detail('IP Address', log.ip)}
                    ${detail('Browser', log.browser)}
                    ${detail('Operating System', log.os)}
                    ${detail('Device Type', log.device)}
                    ${detail('HTTP Method', log.method)}
                    ${detail('Request URL', log.url)}
                    ${detail('Status', log.status)}
                </div>
                <div class="settings-detail-item mt-3"><small>Old Value</small><pre class="mb-0">${escapeHtml(log.old_value)}</pre></div>
                <div class="settings-detail-item mt-3"><small>New Value</small><pre class="mb-0">${escapeHtml(log.new_value)}</pre></div>
                <div class="settings-detail-item mt-3"><small>Additional Notes</small><strong>${escapeHtml(log.notes || 'N/A')}</strong></div>`;
            }

            if (window.bootstrap) {
                new window.bootstrap.Modal(document.getElementById('activityDetailsModal')).show();
            }
        });
    });
})();

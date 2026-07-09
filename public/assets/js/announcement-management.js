(function () {
    'use strict';
    const showAlert = (icon, title, text) => { if (window.Swal) { window.Swal.fire({ icon, title, text, confirmButtonColor: '#f68b34' }); return; } window.alert(`${title}\n${text}`); };
    const normalize = (value) => String(value || '').trim().toLowerCase();
    if (window.flatpickr) { window.flatpickr('.js-date-picker', { dateFormat: 'Y-m-d', allowInput: true }); }
    const rows = Array.from(document.querySelectorAll('[data-announcement-row]'));
    const fields = { search: document.getElementById('announcementSearch'), status: document.getElementById('announcementStatusFilter'), priority: document.getElementById('announcementPriorityFilter'), audience: document.getElementById('announcementAudienceFilter'), start: document.getElementById('announcementStartDate'), end: document.getElementById('announcementEndDate') };
    const summary = document.getElementById('announcementPageSummary');
    const prev = document.getElementById('prevAnnouncementPage');
    const next = document.getElementById('nextAnnouncementPage');
    const perPage = 4;
    let page = 1;
    const inDateRange = (row) => (!fields.start || !fields.start.value || row.dataset.date >= fields.start.value) && (!fields.end || !fields.end.value || row.dataset.date <= fields.end.value);
    const filteredRows = () => rows.filter((row) => (!fields.search || !fields.search.value || normalize(row.dataset.search).includes(normalize(fields.search.value))) && (!fields.status || !fields.status.value || row.dataset.status === fields.status.value) && (!fields.priority || !fields.priority.value || row.dataset.priority === fields.priority.value) && (!fields.audience || !fields.audience.value || row.dataset.audience === fields.audience.value) && inDateRange(row));
    const renderRows = () => { if (rows.length === 0) { return; } const visible = filteredRows(); const pages = Math.max(1, Math.ceil(visible.length / perPage)); page = Math.min(page, pages); const start = (page - 1) * perPage; rows.forEach((row) => { row.hidden = true; }); visible.slice(start, start + perPage).forEach((row) => { row.hidden = false; }); if (summary) { summary.textContent = `Showing ${visible.length === 0 ? 0 : start + 1}-${Math.min(start + perPage, visible.length)} of ${visible.length} announcements`; } if (prev) { prev.disabled = page <= 1; } if (next) { next.disabled = page >= pages; } };
    Object.values(fields).forEach((field) => { if (!field) { return; } field.addEventListener('input', () => { page = 1; renderRows(); }); field.addEventListener('change', () => { page = 1; renderRows(); }); });
    if (prev) { prev.addEventListener('click', () => { page -= 1; renderRows(); }); }
    if (next) { next.addEventListener('click', () => { page += 1; renderRows(); }); }
    renderRows();
    const form = document.getElementById('announcementForm');
    const titleInput = document.getElementById('announcementTitle');
    const contentInput = document.getElementById('announcementContent');
    const priorityInput = document.getElementById('announcementPriority');
    const audienceInput = document.getElementById('announcementAudience');
    const charCount = document.getElementById('announcementCharCount');
    const previewTitle = document.getElementById('previewTitle');
    const previewContent = document.getElementById('previewContent');
    const previewMeta = document.getElementById('previewMeta');
    const updatePreview = () => { if (charCount && contentInput) { charCount.textContent = String(contentInput.value.length); } if (previewTitle && titleInput) { previewTitle.textContent = titleInput.value || 'Announcement title'; } if (previewContent && contentInput) { previewContent.textContent = contentInput.value || 'Announcement content preview will appear here as you type.'; } if (previewMeta) { const selectedAudience = audienceInput ? Array.from(audienceInput.selectedOptions).map((option) => option.value).join(', ') : ''; previewMeta.textContent = `${selectedAudience || 'Audience'} | ${(priorityInput && priorityInput.value) || 'Priority'}`; } };
    [titleInput, contentInput, priorityInput, audienceInput].forEach((field) => { if (field) { field.addEventListener('input', updatePreview); field.addEventListener('change', updatePreview); } });
    updatePreview();
    document.addEventListener('click', (event) => { const editorButton = event.target.closest('[data-editor-command]'); if (editorButton) { showAlert('info', 'Editor Tool (Demo Mode)', `${editorButton.dataset.editorCommand} formatting will be connected to a rich text editor later.`); } });
    if (form) { form.addEventListener('submit', (event) => { event.preventDefault(); const submitter = event.submitter; if (!form.checkValidity()) { form.classList.add('was-validated'); showAlert('error', 'Incomplete Announcement', 'Please complete all required announcement fields.'); return; } const action = submitter ? submitter.dataset.announcementSubmit : 'save'; const labels = { draft: 'Draft Saved', publish: 'Announcement Published', update: 'Announcement Updated' }; showAlert('success', `${labels[action] || 'Announcement Saved'} (Demo Mode)`, 'Database persistence and employee notifications will be connected later.'); }); }
    document.addEventListener('click', (event) => { const exportButton = event.target.closest('[data-announcement-export]'); if (exportButton) { showAlert('info', `Export ${exportButton.dataset.announcementExport} (Demo Mode)`, 'Announcement export will be connected during backend reporting.'); return; } const actionButton = event.target.closest('[data-announcement-action]'); if (!actionButton) { return; } const action = actionButton.dataset.announcementAction; const title = actionButton.dataset.title || 'announcement'; if (action === 'delete') { if (window.Swal) { window.Swal.fire({ icon: 'warning', title: 'Delete announcement?', text: `${title} will be deleted after backend integration.`, showCancelButton: true, confirmButtonText: 'Delete (Demo)', cancelButtonText: 'Cancel', confirmButtonColor: '#ed3237', cancelButtonColor: '#667085' }).then((result) => { if (result.isConfirmed) { showAlert('success', 'Delete Confirmed (Demo Mode)', 'The delete workflow will be connected later.'); } }); return; } } if (action === 'publish') { showAlert('success', 'Publish Confirmed (Demo Mode)', `${title} would now be visible to selected employees.`); return; } if (action === 'archive') { showAlert('success', 'Archive Confirmed (Demo Mode)', `${title} would be archived after backend integration.`); return; } if (action === 'print') { window.print(); return; } showAlert('info', `${action.replace('-', ' ')} (Demo Mode)`, `${title} action will be connected later.`); });
}());
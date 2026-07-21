(function () {
    'use strict';
    const rows = () => Array.from(document.querySelectorAll('[data-announcement-row]'));
    const fields = { search: document.getElementById('announcementSearch'), status: document.getElementById('announcementStatusFilter'), priority: document.getElementById('announcementPriorityFilter'), audience: document.getElementById('announcementAudienceFilter'), start: document.getElementById('announcementStartDate'), end: document.getElementById('announcementEndDate') };
    const summary = document.getElementById('announcementPageSummary');
    const prev = document.getElementById('prevAnnouncementPage');
    const next = document.getElementById('nextAnnouncementPage');
    let page = 1;
    const perPage = 5;
    const showAlert = (icon, title, text) => { if (window.Swal) { window.Swal.fire({ icon, title, text, confirmButtonColor: '#f68b34' }); return; } window.alert(`${title}\n${text}`); };
    const filteredRows = () => { const search = (fields.search?.value || '').trim().toLowerCase(); return rows().filter((row) => { const matchesSearch = !search || (row.dataset.search || '').includes(search); const matchesStatus = !fields.status?.value || row.dataset.status === fields.status.value; const matchesPriority = !fields.priority?.value || row.dataset.priority === fields.priority.value; const matchesAudience = !fields.audience?.value || row.dataset.audience === fields.audience.value; const matchesStart = !fields.start?.value || row.dataset.date >= fields.start.value; const matchesEnd = !fields.end?.value || row.dataset.date <= fields.end.value; return matchesSearch && matchesStatus && matchesPriority && matchesAudience && matchesStart && matchesEnd; }); };
    const renderRows = () => { if (rows().length === 0) { return; } const visible = filteredRows(); const pages = Math.max(1, Math.ceil(visible.length / perPage)); page = Math.min(page, pages); const start = (page - 1) * perPage; rows().forEach((row) => { row.hidden = true; }); visible.slice(start, start + perPage).forEach((row) => { row.hidden = false; }); if (summary) { summary.textContent = `Showing ${visible.length === 0 ? 0 : start + 1}-${Math.min(start + perPage, visible.length)} of ${visible.length} announcements`; } if (prev) { prev.disabled = page <= 1; } if (next) { next.disabled = page >= pages; } };
    Object.values(fields).forEach((field) => { field?.addEventListener('input', () => { page = 1; renderRows(); }); field?.addEventListener('change', () => { page = 1; renderRows(); }); });
    prev?.addEventListener('click', () => { page = Math.max(1, page - 1); renderRows(); });
    next?.addEventListener('click', () => { page += 1; renderRows(); });
    if (window.flatpickr) { document.querySelectorAll('.js-date-picker').forEach((field) => window.flatpickr(field, { dateFormat: 'Y-m-d' })); }

    const form = document.getElementById('announcementForm');
    const titleInput = document.getElementById('announcementTitle');
    const contentInput = document.getElementById('announcementContent');
    const priorityInput = document.getElementById('announcementPriority');
    const audienceInput = document.getElementById('announcementAudience');
    const charCount = document.getElementById('announcementCharCount');
    const previewTitle = document.getElementById('previewTitle');
    const previewContent = document.getElementById('previewContent');
    const previewMeta = document.getElementById('previewMeta');
    const updatePreview = () => { if (charCount && contentInput) charCount.textContent = String(contentInput.value.length); if (previewTitle && titleInput) previewTitle.textContent = titleInput.value || 'Announcement title'; if (previewContent && contentInput) previewContent.textContent = contentInput.value || 'Announcement content preview will appear here as you type.'; if (previewMeta) { const selected = Array.from(audienceInput?.selectedOptions || []).map((option) => option.textContent.trim()).join(', ') || 'Audience'; previewMeta.textContent = `${selected} | ${priorityInput?.value || 'Priority'}`; } };
    [titleInput, contentInput, priorityInput, audienceInput].forEach((field) => { field?.addEventListener('input', updatePreview); field?.addEventListener('change', updatePreview); });
    document.addEventListener('click', (event) => { const editorButton = event.target.closest('[data-editor-command]'); if (editorButton) { showAlert('info', 'Editor Tool', 'Rich text formatting can be connected to an editor later.'); } });
    if (form) { form.addEventListener('submit', (event) => { if (!form.checkValidity()) { event.preventDefault(); form.classList.add('was-validated'); showAlert('error', 'Incomplete Announcement', 'Please complete all required announcement fields.'); } }); }
    document.addEventListener('click', (event) => { const exportButton = event.target.closest('[data-announcement-export]'); if (exportButton) { showAlert('info', `Export ${exportButton.dataset.announcementExport}`, 'Announcement export will be added to reporting later.'); return; } const actionButton = event.target.closest('[data-announcement-action]'); if (!actionButton) { return; } const action = actionButton.dataset.announcementAction; if (action === 'print') { window.print(); return; } const id = actionButton.dataset.announcementId || ''; const form = document.getElementById('announcementActionForm'); const idInput = document.getElementById('announcementActionId'); const actionInput = document.getElementById('announcementActionValue'); if (!form || !idInput || !actionInput || !id) { return; } const submit = () => { idInput.value = id; actionInput.value = action; form.submit(); }; if (window.Swal && (action === 'delete' || action === 'archive')) { window.Swal.fire({ icon: 'warning', title: action === 'delete' ? 'Archive announcement?' : 'Archive announcement?', text: 'This keeps the record but hides it from users.', showCancelButton: true, confirmButtonText: 'Continue', confirmButtonColor: '#ed3237', cancelButtonColor: '#667085' }).then((result) => { if (result.isConfirmed) submit(); }); return; } submit(); });
    updatePreview();
    renderRows();
    window.renderAnnouncementRows = renderRows;
}());


(function () {
    'use strict';
    const enhanceUnpublishActions = () => {
        document.querySelectorAll('[data-announcement-row]').forEach((row) => {
            if (!['Published', 'Scheduled'].includes(row.dataset.status) || row.querySelector('[data-announcement-action="unpublish"]')) return;
            const publish = row.querySelector('[data-announcement-action="publish"]');
            if (!publish) return;
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'btn btn-sm btn-light'; button.title = 'Unpublish';
            button.dataset.announcementAction = 'unpublish'; button.dataset.announcementId = publish.dataset.announcementId;
            button.dataset.title = publish.dataset.title; button.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
            publish.insertAdjacentElement('afterend', button);
        });
    };
    const refreshAnnouncements = async () => {
        await window.FuelOpsAjax.refresh(['.announcement-summary-grid', '#announcementTableBody'], window.location.href);
        enhanceUnpublishActions(); window.renderAnnouncementRows?.();
    };
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-announcement-action]');
        if (!button) return;
        const action = button.dataset.announcementAction || '';
        if (action === 'print') return;
        event.preventDefault(); event.stopImmediatePropagation();
        const id = button.dataset.announcementId || '';
        const form = document.getElementById('announcementActionForm');
        if (!form || !id) return;
        const destructive = ['delete', 'archive', 'unpublish'].includes(action);
        const title = `${action.charAt(0).toUpperCase() + action.slice(1)} announcement?`;
        const confirmed = !window.Swal ? window.confirm(title) : (await window.Swal.fire({
            icon: destructive ? 'warning' : 'question', title,
            text: action === 'delete' ? 'This permanently removes the announcement and its audience/read records.' : 'The announcement table will update immediately.',
            showCancelButton: true, confirmButtonText: 'Continue', confirmButtonColor: destructive ? '#ed3237' : '#f68b34',
        })).isConfirmed;
        if (!confirmed) return;
        form.elements.announcement_id.value = id; form.elements.action.value = action;
        try {
            await window.FuelOpsAjax.submitForm(form, { button, refresh: ['.announcement-summary-grid', '#announcementTableBody'], redirect: false, loadingText: 'Updating...' });
            enhanceUnpublishActions(); window.renderAnnouncementRows?.();
        } catch (error) { /* shared helper reports the error */ }
    }, true);
    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (form.id !== 'announcementForm') return;
        event.preventDefault(); event.stopImmediatePropagation();
        if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
        const button = event.submitter || form.querySelector('[type="submit"]');
        if (button?.name === 'status' && form.elements.status) form.elements.status.value = button.value;
        try {
            await window.FuelOpsAjax.submitForm(form, { button, loadingText: form.elements.announcement_id ? 'Updating announcement...' : 'Creating announcement...' });
        } catch (error) {
            // Validation failures preserve all entered announcement fields.
        }
    }, true);
    document.addEventListener('DOMContentLoaded', enhanceUnpublishActions);
    document.addEventListener('fuelops:refreshed', (event) => {
        if ((event.detail?.selectors || []).includes('#announcementTableBody')) enhanceUnpublishActions();
    });
    let refreshing = false;
    if (document.querySelector('.announcement-page')) window.setInterval(async () => {
        if (refreshing || document.hidden) return;
        refreshing = true; try { await refreshAnnouncements(); } catch (error) {} finally { refreshing = false; }
    }, 30000);
}());

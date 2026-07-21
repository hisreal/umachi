(function () {
    'use strict';

    const showAlert = (title, text, icon = 'success') => {
        if (window.Swal) {
            window.Swal.fire({ title, text, icon, confirmButtonColor: '#f68b34' });
            return;
        }
        window.alert(`${title}\n${text}`);
    };

    const rosterRows = Array.from(document.querySelectorAll('[data-duty-row]'));
    const rosterState = { page: 1, perPage: 5 };

    const applyRosterFilters = () => {
        if (!rosterRows.length) {
            return;
        }

        const search = (document.getElementById('dutyRosterSearch')?.value || '').trim().toLowerCase();
        const filters = {
            date: document.getElementById('dutyDateFilter')?.value || '',
            shift: document.getElementById('dutyShiftFilter')?.value || '',
            department: document.getElementById('dutyDepartmentFilter')?.value || '',
            role: document.getElementById('dutyRoleFilter')?.value || '',
            pump: document.getElementById('dutyPumpFilter')?.value || '',
            fuel: document.getElementById('dutyFuelFilter')?.value || '',
        };

        rosterRows.forEach((row) => {
            const matchesSearch = !search || (row.dataset.search || '').includes(search);
            const matchesFilters = Object.entries(filters).every(([key, value]) => !value || row.dataset[key] === value);
            row.classList.toggle('is-hidden-by-duty-filter', !(matchesSearch && matchesFilters));
        });

        rosterState.page = 1;
        paginateRoster();
    };

    const paginateRoster = () => {
        if (!rosterRows.length) {
            return;
        }

        const visibleRows = rosterRows.filter((row) => !row.classList.contains('is-hidden-by-duty-filter'));
        const totalPages = Math.max(1, Math.ceil(visibleRows.length / rosterState.perPage));
        rosterState.page = Math.min(rosterState.page, totalPages);
        const start = (rosterState.page - 1) * rosterState.perPage;
        const end = start + rosterState.perPage;

        rosterRows.forEach((row) => row.classList.add('is-hidden-by-duty-page'));
        visibleRows.slice(start, end).forEach((row) => row.classList.remove('is-hidden-by-duty-page'));

        const summary = document.getElementById('dutyRosterSummary');
        if (summary) {
            summary.textContent = visibleRows.length ? `Showing ${start + 1}-${Math.min(end, visibleRows.length)} of ${visibleRows.length} live records` : 'No live records match the filters';
        }

        const prev = document.getElementById('prevDutyPage');
        const next = document.getElementById('nextDutyPage');
        if (prev) prev.disabled = rosterState.page <= 1;
        if (next) next.disabled = rosterState.page >= totalPages;
    };

    ['dutyRosterSearch', 'dutyDateFilter', 'dutyShiftFilter', 'dutyDepartmentFilter', 'dutyRoleFilter', 'dutyPumpFilter', 'dutyFuelFilter'].forEach((id) => {
        document.getElementById(id)?.addEventListener('input', applyRosterFilters);
        document.getElementById(id)?.addEventListener('change', applyRosterFilters);
    });

    document.getElementById('prevDutyPage')?.addEventListener('click', () => {
        rosterState.page -= 1;
        paginateRoster();
    });

    document.getElementById('nextDutyPage')?.addEventListener('click', () => {
        rosterState.page += 1;
        paginateRoster();
    });

    document.querySelectorAll('[data-duty-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const action = button.dataset.dutyAction || 'view';
            const name = button.dataset.dutyName || 'this record';
            const title = action === 'delete' ? 'Delete Duty Record?' : `${action.charAt(0).toUpperCase() + action.slice(1)} Duty Record`;
            const text = action === 'delete' ? `${name} would be removed in backend mode.` : `${name}'s duty record is ready for ${action} in backend mode.`;
            showAlert(title, text, action === 'delete' ? 'warning' : 'info');
        });
    });

    document.getElementById('shiftConfigForm')?.addEventListener('submit', (event) => {
        const form = event.currentTarget;
        form.classList.add('was-validated');
        if (!form.checkValidity()) {
            event.preventDefault();
            showAlert('Missing Shift Details', 'Please complete all required shift configuration fields.', 'warning');
            return;
        }

        const reporting = form.querySelector('[name="reporting_time"]');
        const closing = form.querySelector('[name="closing_time"]');
        if (reporting && closing && reporting.value >= closing.value) {
            event.preventDefault();
            showAlert('Invalid Time Range', 'Reporting time must be earlier than closing time.', 'warning');
        }
    });

    document.addEventListener('click', async (event) => {
        const shiftButton = event.target.closest('[data-shift-action]');
        if (!shiftButton) { return; }

        const action = shiftButton.dataset.shiftAction;
        const name = shiftButton.dataset.name || 'this shift';
        if (action === 'view') {
            event.preventDefault();
            showAlert(name, `${shiftButton.dataset.code || ''}\n${shiftButton.dataset.time || ''}\n${shiftButton.dataset.status || ''}`, 'info');
            return;
        }

        if (action === 'delete' || action === 'toggle') {
            event.preventDefault();
            const confirmed = window.Swal
                ? (await window.Swal.fire({ icon: 'warning', title: action === 'delete' ? 'Delete Shift' : 'Update Shift Status', text: `${name} will be ${action === 'delete' ? 'soft deleted' : 'updated'}.`, showCancelButton: true, confirmButtonColor: '#ed3237', confirmButtonText: 'Yes, continue' })).isConfirmed
                : window.confirm(`${name} will be ${action === 'delete' ? 'soft deleted' : 'updated'}.`);
            if (confirmed) {
                shiftButton.closest('form')?.submit();
            }
        }
    });

    const employeeSelect = document.getElementById('allocationEmployee');
    employeeSelect?.addEventListener('change', () => {
        const selected = employeeSelect.selectedOptions[0];
        document.getElementById('allocationDepartment').value = selected?.dataset.department || '';
        document.getElementById('allocationRole').value = selected?.dataset.role || '';
    });

    const pumpSelect = document.getElementById('allocationPump');
    pumpSelect?.addEventListener('change', () => {
        document.getElementById('allocationFuel').value = pumpSelect.selectedOptions[0]?.dataset.fuel || '';
    });

    const shiftSelect = document.getElementById('allocationShift');
    shiftSelect?.addEventListener('change', () => {
        const selected = shiftSelect.selectedOptions[0];
        document.getElementById('allocationReporting').value = selected?.dataset.reporting || '';
        document.getElementById('allocationClosing').value = selected?.dataset.closing || '';
    });

    document.getElementById('pumpAllocationForm')?.addEventListener('submit', (event) => {
        const form = event.currentTarget;
        form.classList.add('was-validated');
        if (!form.checkValidity()) {
            event.preventDefault();
            showAlert('Missing Assignment Details', 'Please complete all required allocation fields.', 'warning');
        }
    });
    const initDutyCalendar = () => {
        const calendarEl = document.getElementById('dutyCalendar');
        if (!calendarEl || !window.FullCalendar) {
            return;
        }

        let baseEvents = window.dutyRosterEvents || [];
        const calendar = new window.FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            initialDate: new Date().toISOString().slice(0, 10),
            height: 'auto',
            events: baseEvents,
            eventClick(info) {
                const data = info.event.extendedProps || {};
                showAlert(
                    data.employee || info.event.title,
                    `${data.department || 'Department'}\n${data.shift || 'Shift'} | ${data.pump || 'Pump'} | ${data.fuel_type || 'Fuel'}\n${data.reporting || ''} - ${data.closing || ''}`,
                    'info'
                );
            },
        });

        const applyCalendarFilters = () => {
            const filters = {
                employee: document.getElementById('calendarEmployeeFilter')?.value || '',
                department: document.getElementById('calendarDepartmentFilter')?.value || '',
                shift: document.getElementById('calendarShiftFilter')?.value || '',
                pump: document.getElementById('calendarPumpFilter')?.value || '',
                fuel_type: document.getElementById('calendarFuelFilter')?.value || '',
            };

            calendar.removeAllEvents();
            baseEvents.filter((event) => Object.entries(filters).every(([key, value]) => !value || event.extendedProps?.[key] === value)).forEach((event) => calendar.addEvent(event));
        };

        ['calendarEmployeeFilter', 'calendarDepartmentFilter', 'calendarShiftFilter', 'calendarPumpFilter', 'calendarFuelFilter'].forEach((id) => {
            document.getElementById(id)?.addEventListener('change', applyCalendarFilters);
        });

        calendar.render();
        window.refreshDutyCalendar = async () => {
            const html = await fetch(window.location.href, { credentials: 'same-origin' }).then((response) => response.text());
            const match = html.match(/window\.dutyRosterEvents\s*=\s*(\[[\s\S]*?\]);/);
            if (match) { baseEvents = JSON.parse(match[1]); applyCalendarFilters(); }
        };
    };

    document.addEventListener('DOMContentLoaded', () => {
        applyRosterFilters();
        initDutyCalendar();
    });

    const refreshSelector = () => document.getElementById('pumpAllocationWorkspace') ? '#pumpAllocationWorkspace'
        : document.getElementById('dutyRosterWorkspace') ? '#dutyRosterWorkspace'
            : document.querySelector('.clock-workspace') ? '.clock-workspace' : '';

    const confirmAction = async (form, button) => {
        const action = button?.dataset.dutyAction || button?.dataset.shiftAction || '';
        if (!['delete', 'cancel', 'publish', 'archive', 'toggle'].includes(action)) return true;
        const name = button?.dataset.dutyName || button?.dataset.name || 'this record';
        const destructive = action === 'delete' || action === 'cancel';
        if (!window.Swal) return window.confirm(`${action.charAt(0).toUpperCase() + action.slice(1)} ${name}?`);
        return (await window.Swal.fire({
            icon: 'warning',
            title: `${action.charAt(0).toUpperCase() + action.slice(1)} ${name}?`,
            text: 'This change will be applied immediately.',
            showCancelButton: true,
            confirmButtonColor: destructive ? '#ed3237' : '#f68b34',
            confirmButtonText: 'Yes, continue',
        })).isConfirmed;
    };

    const loadAllocationData = async () => {
        if (!document.getElementById('pumpAllocationWorkspace')) return;
        const html = await fetch(window.location.href, { credentials: 'same-origin' }).then((response) => response.text());
        const match = html.match(/window\.existingPumpAllocations\s*=\s*(\[[\s\S]*?\]);<\/script>/);
        if (match) window.existingPumpAllocations = JSON.parse(match[1]);
    };

    const enhanceAllocationRows = () => {
        const allocations = window.existingPumpAllocations || [];
        document.querySelectorAll('#pumpAllocationBody tr[data-allocation-row]').forEach((row) => {
            const assignmentId = row.querySelector('[name="assignment_id"]')?.value;
            const allocation = allocations.find((item) => String(item.id) === String(assignmentId));
            const actions = row.querySelector('.duty-actions');
            if (!allocation || !actions || actions.querySelector('[data-duty-action="edit"]')) return;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-light';
            button.title = 'Edit';
            button.dataset.dutyAction = 'edit';
            button.dataset.assignmentId = String(allocation.id);
            button.innerHTML = '<i class="fa-solid fa-pen-to-square"></i>';
            actions.querySelector('form')?.insertAdjacentElement('beforebegin', button) || actions.append(button);
        });
    };

    document.addEventListener('click', (event) => {
        const actionButton = event.target.closest('[data-duty-action], [data-shift-action]');
        if (!actionButton) return;
        const action = actionButton.dataset.dutyAction || actionButton.dataset.shiftAction;
        const actionForm = actionButton.closest('form');
        if (actionForm && action !== 'edit') {
            event.preventDefault();
            event.stopImmediatePropagation();
            actionForm.requestSubmit(actionButton);
            return;
        }
        const button = action === 'edit' ? actionButton : null;
        if (!button) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        const allocation = (window.existingPumpAllocations || []).find((item) => String(item.id) === button.dataset.assignmentId);
        const form = document.getElementById('pumpAllocationForm');
        if (!allocation || !form) return;
        const values = {
            assignment_id: allocation.id, roster_id: allocation.roster_id,
            employee_id: allocation.employee_db_id, pump_id: allocation.pump_id,
            assignment_date: allocation.date, shift_id: allocation.shift_id, remarks: allocation.remarks,
        };
        Object.entries(values).forEach(([name, value]) => { if (form.elements[name]) form.elements[name].value = value ?? ''; });
        ['allocationEmployee', 'allocationPump', 'allocationShift'].forEach((id) => document.getElementById(id)?.dispatchEvent(new Event('change')));
        const submit = form.querySelector('[type="submit"]');
        if (submit) submit.innerHTML = '<i class="fa-solid fa-check"></i>Update Assignment';
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, true);

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        const action = form.action || '';
        if (!action.includes('/shifts/') && !action.includes('/duty-rosters/') && !action.includes('/duty-assignments/')) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
        if (!(await confirmAction(form, event.submitter))) return;
        const selector = refreshSelector();
        try {
            await window.FuelOpsAjax.submitForm(form, {
                button: event.submitter || form.querySelector('[type="submit"]'),
                refresh: selector,
                redirect: false,
            });
            if (selector === '#pumpAllocationWorkspace') {
                await loadAllocationData();
                enhanceAllocationRows();
            }
        } catch (error) { /* shared helper rendered the error */ }
    }, true);

    document.addEventListener('fuelops:refreshed', enhanceAllocationRows);
    document.addEventListener('DOMContentLoaded', enhanceAllocationRows);
    if (document.getElementById('dutyCalendar')) window.setInterval(() => window.refreshDutyCalendar?.().catch(() => {}), 60000);
}());
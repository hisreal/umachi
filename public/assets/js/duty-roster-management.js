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
            summary.textContent = visibleRows.length ? `Showing ${start + 1}-${Math.min(end, visibleRows.length)} of ${visibleRows.length} sample records` : 'No sample records match the filters';
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
            const text = action === 'delete' ? `${name} would be removed in demo mode.` : `${name}'s duty record is ready for ${action} in demo mode.`;
            showAlert(title, text, action === 'delete' ? 'warning' : 'info');
        });
    });

    document.getElementById('shiftConfigForm')?.addEventListener('submit', (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            showAlert('Missing Shift Details', 'Please complete all required shift configuration fields.', 'warning');
            return;
        }

        // ===============================================
        // DATABASE PLACEHOLDER
        // Save shift configuration changes to MySQL.
        // ===============================================
        showAlert('Shift Settings Saved (Demo Mode)', 'The shift configuration has been validated on the frontend only.');
    });

    const employeeSelect = document.getElementById('allocationEmployee');
    employeeSelect?.addEventListener('change', () => {
        const selected = employeeSelect.selectedOptions[0];
        document.getElementById('allocationDepartment').value = selected?.dataset.department || '';
        document.getElementById('allocationRole').value = selected?.dataset.role || '';
    });

    document.getElementById('pumpAllocationForm')?.addEventListener('submit', (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const employee = document.getElementById('allocationEmployee');
        const pump = document.getElementById('allocationPump');
        const date = document.getElementById('allocationDate');
        const shift = document.getElementById('allocationShift');
        const reporting = document.getElementById('allocationReporting');
        const closing = document.getElementById('allocationClosing');

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            showAlert('Missing Assignment Details', 'Please complete all required allocation fields.', 'warning');
            return;
        }

        if (reporting.value >= closing.value) {
            showAlert('Invalid Time Range', 'Reporting time must be earlier than closing time.', 'warning');
            return;
        }

        const allocations = window.existingPumpAllocations || [];
        const duplicateEmployee = allocations.some((item) => item.employee_id === employee.value && item.date === date.value && item.shift === shift.value);
        const duplicatePump = allocations.some((item) => item.pump === pump.value && item.date === date.value && item.shift === shift.value);

        if (duplicateEmployee) {
            showAlert('Duplicate Employee Assignment', 'This employee is already assigned during the selected date and shift.', 'warning');
            return;
        }

        if (duplicatePump) {
            showAlert('Duplicate Pump Assignment', 'This pump is already assigned during the selected date and shift.', 'warning');
            return;
        }

        // ===============================================
        // DATABASE PLACEHOLDER
        // Save shift assignments and pump allocations to MySQL.
        // ===============================================
        showAlert('Pump Allocation Saved (Demo Mode)', 'The assignment passed frontend validation and is ready for future database integration.');
        form.reset();
        document.getElementById('allocationDepartment').value = '';
        document.getElementById('allocationRole').value = '';
        form.classList.remove('was-validated');
    });

    const initDutyCalendar = () => {
        const calendarEl = document.getElementById('dutyCalendar');
        if (!calendarEl || !window.FullCalendar) {
            return;
        }

        const baseEvents = window.dutyRosterEvents || [];
        const calendar = new window.FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            initialDate: '2026-07-08',
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
    };

    document.addEventListener('DOMContentLoaded', () => {
        applyRosterFilters();
        initDutyCalendar();
    });
}());
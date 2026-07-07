(function () {
    'use strict';

    const form = document.getElementById('assignDutyForm');
    const employeeField = document.getElementById('employeeName');
    const dateField = document.getElementById('dutyDate');
    const shiftField = document.getElementById('shiftName');
    const pumpField = document.getElementById('pumpName');
    const fuelField = document.getElementById('fuelType');
    const reportingField = document.getElementById('reportingTime');
    const closingField = document.getElementById('closingTime');
    const supervisorField = document.getElementById('supervisorName');
    const rows = Array.from(document.querySelectorAll('[data-duty-row]'));
    const searchField = document.getElementById('dutySearch');
    const filterFields = {
        date: document.getElementById('dateFilter'),
        shift: document.getElementById('shiftFilter'),
        pump: document.getElementById('pumpFilter'),
        fuel: document.getElementById('fuelFilter'),
        supervisor: document.getElementById('supervisorFilter'),
        status: document.getElementById('statusFilter'),
    };
    const pageSummary = document.getElementById('dutyPageSummary');
    const prevButton = document.getElementById('prevDutyPage');
    const nextButton = document.getElementById('nextDutyPage');
    const rowsPerPage = 5;
    let currentPage = 1;

    const showAlert = (icon, title, text) => {
        if (window.Swal) {
            window.Swal.fire({
                icon,
                title,
                text,
                confirmButtonColor: '#f68b34',
            });
            return;
        }

        window.alert(`${title}\n${text}`);
    };

    const normalize = (value) => String(value || '').trim().toLowerCase();

    const activeRows = () => rows.filter((row) => row.dataset.status !== 'Cancelled');

    const resetCustomValidity = () => {
        [employeeField, dateField, shiftField, pumpField, fuelField, reportingField, closingField, supervisorField].forEach((field) => {
            if (field) {
                field.setCustomValidity('');
            }
        });
    };

    const hasEmployeeConflict = () => activeRows().some((row) => row.dataset.date === dateField.value
        && row.dataset.shift === shiftField.value
        && row.dataset.employee === employeeField.value);

    const hasPumpConflict = () => activeRows().some((row) => row.dataset.date === dateField.value
        && row.dataset.shift === shiftField.value
        && row.dataset.pump === pumpField.value);

    const validateAssignment = () => {
        resetCustomValidity();

        if (reportingField.value && closingField.value && reportingField.value >= closingField.value) {
            closingField.setCustomValidity('Closing time must be after reporting time.');
        }

        if (employeeField.value && dateField.value && shiftField.value && hasEmployeeConflict()) {
            employeeField.setCustomValidity('Employee is already assigned for this date and shift.');
        }

        if (pumpField.value && dateField.value && shiftField.value && hasPumpConflict()) {
            pumpField.setCustomValidity('Pump is already assigned for this date and shift.');
        }
    };

    const filteredRows = () => {
        const searchValue = normalize(searchField ? searchField.value : '');

        return rows.filter((row) => {
            const matchesSearch = !searchValue || normalize(row.dataset.employee).includes(searchValue);
            const matchesDate = !filterFields.date.value || row.dataset.date === filterFields.date.value;
            const matchesShift = !filterFields.shift.value || row.dataset.shift === filterFields.shift.value;
            const matchesPump = !filterFields.pump.value || row.dataset.pump === filterFields.pump.value;
            const matchesFuel = !filterFields.fuel.value || row.dataset.fuel === filterFields.fuel.value;
            const matchesSupervisor = !filterFields.supervisor.value || row.dataset.supervisor === filterFields.supervisor.value;
            const matchesStatus = !filterFields.status.value || row.dataset.status === filterFields.status.value;

            return matchesSearch && matchesDate && matchesShift && matchesPump && matchesFuel && matchesSupervisor && matchesStatus;
        });
    };

    const renderRows = () => {
        const visibleRows = filteredRows();
        const totalPages = Math.max(1, Math.ceil(visibleRows.length / rowsPerPage));
        currentPage = Math.min(currentPage, totalPages);
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        rows.forEach((row) => {
            row.hidden = true;
        });

        visibleRows.slice(start, end).forEach((row) => {
            row.hidden = false;
        });

        if (pageSummary) {
            const firstRecord = visibleRows.length === 0 ? 0 : start + 1;
            const lastRecord = Math.min(end, visibleRows.length);
            pageSummary.textContent = `Showing ${firstRecord}-${lastRecord} of ${visibleRows.length} sample records`;
        }

        if (prevButton) {
            prevButton.disabled = currentPage <= 1;
        }

        if (nextButton) {
            nextButton.disabled = currentPage >= totalPages;
        }
    };

    if (form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            validateAssignment();
            form.classList.add('was-validated');

            if (!form.checkValidity()) {
                showAlert('warning', 'Review Duty Assignment', 'Please complete all required fields and resolve any assignment conflicts.');
                return;
            }

            // =======================================
            // DATABASE PLACEHOLDER
            // Save duty assignments into MySQL.
            // =======================================
            showAlert('success', 'Duty Assigned Successfully (Demo Mode)', 'This sample assignment is ready to be connected to the backend later.');
        });

        form.addEventListener('reset', () => {
            window.setTimeout(() => {
                resetCustomValidity();
                form.classList.remove('was-validated');
            }, 0);
        });

        [employeeField, dateField, shiftField, pumpField, fuelField, reportingField, closingField, supervisorField].forEach((field) => {
            if (field) {
                field.addEventListener('change', validateAssignment);
                field.addEventListener('input', validateAssignment);
            }
        });
    }

    [searchField, ...Object.values(filterFields)].forEach((field) => {
        if (field) {
            field.addEventListener('input', () => {
                currentPage = 1;
                renderRows();
            });
            field.addEventListener('change', () => {
                currentPage = 1;
                renderRows();
            });
        }
    });

    if (prevButton) {
        prevButton.addEventListener('click', () => {
            currentPage -= 1;
            renderRows();
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', () => {
            currentPage += 1;
            renderRows();
        });
    }

    document.addEventListener('click', (event) => {
        const actionButton = event.target.closest('[data-duty-action]');
        if (!actionButton) {
            return;
        }

        const row = actionButton.closest('[data-duty-row]');
        const employeeName = row ? row.dataset.employee : 'this employee';
        const action = actionButton.dataset.dutyAction;

        if (action === 'delete') {
            showAlert('info', 'Delete Action (Demo Mode)', `${employeeName}'s duty assignment would be deleted after backend integration.`);
            return;
        }

        const title = action === 'edit' ? 'Edit Action (Demo Mode)' : 'Duty Assignment Details';
        const text = action === 'edit'
            ? `${employeeName}'s duty assignment would open in edit mode after backend integration.`
            : `${employeeName} is assigned to ${row.dataset.pump} for the ${row.dataset.shift} shift.`;

        showAlert('info', title, text);
    });

    renderRows();
}());

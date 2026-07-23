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

    const employeeRows = () => Array.from(document.querySelectorAll('[data-employee-row]'));
    const employeeSearch = document.getElementById('employeeSearch');
    const filters = {
        department: document.getElementById('departmentFilter'),
        role: document.getElementById('roleFilter'),
        status: document.getElementById('statusFilter'),
        gender: document.getElementById('genderFilter'),
    };
    const pageSummary = document.getElementById('employeePageSummary');
    const prevPage = document.getElementById('prevEmployeePage');
    const nextPage = document.getElementById('nextEmployeePage');
    const rowsPerPage = Number.POSITIVE_INFINITY;
    let currentPage = 1;

    const tableDataForExport = () => {
        const table = document.querySelector('.employee-table');
        if (!table) { return { headers: [], rows: [] }; }

        const headers = Array.from(table.querySelectorAll('thead th'))
            .map((cell) => cell.textContent.trim())
            .filter((header) => header !== 'Actions');
        const rows = filteredEmployees().map((row) => Array.from(row.children)
            .slice(0, headers.length)
            .map((cell) => cell.textContent.replace(/\s+/g, ' ').trim()));

        return { headers, rows };
    };

    const downloadFile = (filename, content, type) => {
        const blob = new Blob([content], { type });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        URL.revokeObjectURL(link.href);
        link.remove();
    };

    const exportEmployees = (type) => {
        const { headers, rows } = tableDataForExport();
        if (headers.length === 0) {
            showAlert('warning', 'Export Unavailable', 'No employee table was found to export.');
            return;
        }

        const filename = `employees-${new Date().toISOString().slice(0, 10)}`;
        const csv = [headers, ...rows]
            .map((row) => row.map((value) => `"${String(value).replace(/"/g, '""')}"`).join(','))
            .join('\n');

        if (type === 'CSV') {
            downloadFile(`${filename}.csv`, csv, 'text/csv;charset=utf-8');
            return;
        }

        if (type === 'Excel') {
            const htmlRows = [headers, ...rows]
                .map((row) => `<tr>${row.map((value) => `<td>${value}</td>`).join('')}</tr>`)
                .join('');
            downloadFile(`${filename}.xls`, `<table>${htmlRows}</table>`, 'application/vnd.ms-excel');
            return;
        }

        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            showAlert('warning', 'Popup Blocked', 'Please allow popups to print the employee export.');
            return;
        }
        printWindow.document.write(`<title>Employee Export</title><h1>Employee Export</h1><table border="1" cellspacing="0" cellpadding="6"><thead><tr>${headers.map((header) => `<th>${header}</th>`).join('')}</tr></thead><tbody>${rows.map((row) => `<tr>${row.map((value) => `<td>${value}</td>`).join('')}</tr>`).join('')}</tbody></table>`);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    };
    const filteredEmployees = () => employeeRows().filter((row) => {
        const query = normalize(employeeSearch ? employeeSearch.value : '');
        return (!query || normalize(row.dataset.search).includes(query))
            && (!filters.department || !filters.department.value || row.dataset.department === filters.department.value)
            && (!filters.role || !filters.role.value || row.dataset.role === filters.role.value)
            && (!filters.status || !filters.status.value || row.dataset.status === filters.status.value)
            && (!filters.gender || !filters.gender.value || row.dataset.gender === filters.gender.value);
    });

    const renderEmployees = () => {
        if (employeeRows().length === 0) {
            return;
        }
        const visible = filteredEmployees();
        employeeRows().forEach((row) => { row.hidden = !visible.includes(row); });
        if (pageSummary) {
            pageSummary.textContent = `Showing all ${visible.length} employees`;
        }
        if (prevPage) { prevPage.disabled = true; }
        if (nextPage) { nextPage.disabled = true; }
    };

    [employeeSearch, ...Object.values(filters)].forEach((field) => {
        if (!field) { return; }
        field.addEventListener('input', () => { currentPage = 1; renderEmployees(); });
        field.addEventListener('change', () => { currentPage = 1; renderEmployees(); });
    });
    if (prevPage) { prevPage.addEventListener('click', () => { currentPage -= 1; renderEmployees(); }); }
    if (nextPage) { nextPage.addEventListener('click', () => { currentPage += 1; renderEmployees(); }); }

    document.addEventListener('click', (event) => {
        const exportButton = event.target.closest('[data-export-type]');
        if (exportButton) {
            exportEmployees(exportButton.dataset.exportType || 'CSV');
            return;
        }

        const profileAction = event.target.closest('[data-profile-action="print"]');
        if (profileAction) {
            window.print();
        }
    });

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-confirm-submit]');
        if (form && !window.confirm(form.dataset.confirmSubmit || 'Are you sure?')) {
            event.preventDefault();
        }
    });

    document.querySelectorAll('[data-toggle-password]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.querySelector(button.dataset.togglePassword);
            if (!input) { return; }
            input.type = input.type === 'password' ? 'text' : 'password';
            const icon = button.querySelector('i');
            if (icon) {
                icon.className = input.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
            }
        });
    });

    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    const passwordStrength = document.getElementById('passwordStrength');
    const updatePasswordStrength = () => {
        if (!password || !passwordStrength) { return; }
        const value = password.value;
        passwordStrength.classList.remove('is-weak', 'is-medium', 'is-strong');
        if (!value) { return; }
        let score = 0;
        if (value.length >= 8) { score += 1; }
        if (/[A-Z]/.test(value) && /[a-z]/.test(value)) { score += 1; }
        if (/\d/.test(value) || /[^A-Za-z0-9]/.test(value)) { score += 1; }
        passwordStrength.classList.add(score >= 3 ? 'is-strong' : score === 2 ? 'is-medium' : 'is-weak');
    };
    if (password) { password.addEventListener('input', updatePasswordStrength); }

    const employeeForm = document.getElementById('employeeForm');
    if (employeeForm) {
        employeeForm.addEventListener('submit', (event) => {
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match.');
            } else if (confirmPassword) {
                confirmPassword.setCustomValidity('');
            }

            employeeForm.classList.add('was-validated');

            if (!employeeForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                showAlert('warning', 'Review Employee Form', 'Please complete all required fields correctly.');
            }
        });
        employeeForm.addEventListener('reset', () => {
            window.setTimeout(() => {
                employeeForm.classList.remove('was-validated');
                if (passwordStrength) { passwordStrength.classList.remove('is-weak', 'is-medium', 'is-strong'); }
            }, 0);
        });
    }

    document.querySelectorAll('[data-image-preview]').forEach((input) => {
        input.addEventListener('change', () => {
            const target = document.querySelector(input.dataset.imagePreview);
            const file = input.files && input.files[0];
            if (!target || !file) { return; }
            target.src = URL.createObjectURL(file);
        });
    });

    document.querySelectorAll('[data-document-upload]').forEach((input) => {
        input.addEventListener('change', () => {
            if (input.files && input.files[0]) {
                showAlert('success', 'Document Selected', `${input.files[0].name} is ready for upload.`);
            }
        });
    });

    const documentTypeFilter = document.getElementById('documentTypeFilter');
    const documentDateFilter = document.getElementById('documentDateFilter');
    const documentCards = () => Array.from(document.querySelectorAll('[data-document-card]'));
    const renderDocuments = () => {
        documentCards().forEach((card) => {
            const matchesType = !documentTypeFilter || !documentTypeFilter.value || card.dataset.type === documentTypeFilter.value;
            const matchesDate = !documentDateFilter || !documentDateFilter.value || card.dataset.date === documentDateFilter.value;
            card.hidden = !(matchesType && matchesDate);
        });
    };
    [documentTypeFilter, documentDateFilter].forEach((field) => {
        if (field) { field.addEventListener('change', renderDocuments); }
    });

    renderEmployees();
}());


    document.addEventListener('submit', async (event) => {
        if (event.defaultPrevented) return;
        const form = event.target;
        if (!window.FuelOpsAjax || !(form instanceof HTMLFormElement)) return;

        if (form.matches('[data-employee-ajax-form]')) {
            event.preventDefault();
            form.classList.add('was-validated');
            if (!form.checkValidity()) return;
            const action = form.action;
            const actionRoute = new URL(action, window.location.href).searchParams.get('route');
            await window.FuelOpsAjax.submitForm(form, {
                button: event.submitter,
                redirect: false
            }).then((payload) => {
                if (actionRoute === 'admin/employees/store') {
                    const employeeListUrl = payload.meta?.redirect;
                    if (employeeListUrl) window.location.assign(employeeListUrl);
                    return;
                } else if (payload.data?.employee) {
                    const updatedAction = new URL(form.action, window.location.href);
                    updatedAction.searchParams.set('employee', payload.data.employee);
                    form.action = updatedAction.toString();
                    const heading = document.querySelector('.employee-hero h1');
                    if (heading) heading.textContent = `Edit ${form.elements.first_name.value} ${form.elements.last_name.value}`.trim();
                }
            }).catch(() => {});
            return;
        }

        if (form.matches('[data-employee-document-upload]')) {
            event.preventDefault();
            form.classList.add('was-validated');
            if (!form.checkValidity()) return;
            await window.FuelOpsAjax.submitForm(form, {
                button: event.submitter,
                redirect: false,
                refresh: ['.employee-summary-grid', '#documentGrid']
            }).then(() => {
                form.reset();
                form.classList.remove('was-validated');
            }).catch(() => {});
            return;
        }

        if (!form.matches('[data-confirm-submit]')) return;
        event.preventDefault();
        const isDocumentAction = form.action.includes('delete-document');
        const isPasswordReset = form.action.includes('reset-password');
        await window.FuelOpsAjax.submitForm(form, {
            button: event.submitter,
            redirect: false,
            notify: !isPasswordReset,
            refresh: isDocumentAction ? ['.employee-summary-grid', '#documentGrid'] : (isPasswordReset ? undefined : ['.employee-summary-grid', '#employeeTableBody'])
        }).then((payload) => {
            if (isPasswordReset) {
                const password = payload.data?.temporary_password || '';
                window.FuelOpsAjax.notify('success', `Temporary password: ${password}`, 'Password Reset Successful');
            }
            renderEmployees();
        }).catch(() => {});
    });


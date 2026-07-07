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

    const employeeRows = Array.from(document.querySelectorAll('[data-employee-row]'));
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
    const rowsPerPage = 5;
    let currentPage = 1;

    const filteredEmployees = () => employeeRows.filter((row) => {
        const query = normalize(employeeSearch ? employeeSearch.value : '');
        return (!query || normalize(row.dataset.search).includes(query))
            && (!filters.department || !filters.department.value || row.dataset.department === filters.department.value)
            && (!filters.role || !filters.role.value || row.dataset.role === filters.role.value)
            && (!filters.status || !filters.status.value || row.dataset.status === filters.status.value)
            && (!filters.gender || !filters.gender.value || row.dataset.gender === filters.gender.value);
    });

    const renderEmployees = () => {
        if (employeeRows.length === 0) {
            return;
        }
        const visible = filteredEmployees();
        const totalPages = Math.max(1, Math.ceil(visible.length / rowsPerPage));
        currentPage = Math.min(currentPage, totalPages);
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        employeeRows.forEach((row) => { row.hidden = true; });
        visible.slice(start, end).forEach((row) => { row.hidden = false; });
        if (pageSummary) {
            const first = visible.length === 0 ? 0 : start + 1;
            const last = Math.min(end, visible.length);
            pageSummary.textContent = `Showing ${first}-${last} of ${visible.length} employees`;
        }
        if (prevPage) { prevPage.disabled = currentPage <= 1; }
        if (nextPage) { nextPage.disabled = currentPage >= totalPages; }
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
            showAlert('info', `Export ${exportButton.dataset.exportType} (Demo Mode)`, 'Employee export will be connected during backend integration.');
            return;
        }

        const actionButton = event.target.closest('[data-employee-action]');
        if (actionButton) {
            const employee = actionButton.dataset.employee || 'this employee';
            const action = actionButton.dataset.employeeAction;
            const title = action === 'reset' ? 'Reset Password (Demo Mode)' : 'Update Account Status (Demo Mode)';
            const text = action === 'reset'
                ? `${employee}'s password reset workflow will be connected later.`
                : `${employee}'s activation status will be updated after backend integration.`;
            showAlert('info', title, text);
            return;
        }

        const documentAction = event.target.closest('[data-document-action]');
        if (documentAction) {
            const action = documentAction.dataset.documentAction;
            const documentName = documentAction.dataset.document;
            showAlert('info', `${action.charAt(0).toUpperCase() + action.slice(1)} Document (Demo Mode)`, `${documentName} ${action} action will be connected later.`);
            return;
        }

        const profileAction = event.target.closest('[data-profile-action="print"]');
        if (profileAction) {
            showAlert('info', 'Print Profile (Demo Mode)', 'A printable employee profile will be generated during backend integration.');
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
            event.preventDefault();
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match.');
            } else if (confirmPassword) {
                confirmPassword.setCustomValidity('');
            }
            employeeForm.classList.add('was-validated');
            if (!employeeForm.checkValidity()) {
                showAlert('warning', 'Review Employee Form', 'Please complete all required fields correctly.');
                return;
            }
            // ============================================
            // DATABASE PLACEHOLDER
            // Save employee information.
            // ============================================
            const isEdit = document.body.textContent.includes('Update Employee');
            showAlert('success', isEdit ? 'Employee Updated (Demo Mode)' : 'Employee Saved (Demo Mode)', 'Employee data will be saved to MySQL during backend integration.');
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
                showAlert('success', 'Document Selected (Demo Mode)', `${input.files[0].name} is ready for upload when backend storage is connected.`);
            }
        });
    });

    const documentTypeFilter = document.getElementById('documentTypeFilter');
    const documentDateFilter = document.getElementById('documentDateFilter');
    const documentCards = Array.from(document.querySelectorAll('[data-document-card]'));
    const renderDocuments = () => {
        documentCards.forEach((card) => {
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

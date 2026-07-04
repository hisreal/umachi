(function () {
    'use strict';

    const form = document.getElementById('leaveApplicationForm');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const searchInput = document.getElementById('leaveSearch');
    const typeFilter = document.getElementById('filterLeaveType');
    const statusFilter = document.getElementById('filterStatus');
    const yearFilter = document.getElementById('filterYear');
    const rows = Array.from(document.querySelectorAll('[data-leave-row]'));
    const countLabel = document.getElementById('leaveHistoryCount');
    const prevButton = document.getElementById('leavePrevPage');
    const nextButton = document.getElementById('leaveNextPage');
    const pageSize = 5;
    let currentPage = 1;

    const showAlert = (icon, title, text) => {
        if (window.Swal) {
            window.Swal.fire({
                icon,
                title,
                text,
                confirmButtonColor: '#F68B34',
            });
            return;
        }

        window.alert(`${title}\n\n${text}`);
    };

    const datesAreValid = () => {
        if (!startDate.value || !endDate.value) {
            endDate.setCustomValidity('');
            return true;
        }

        const valid = new Date(endDate.value) >= new Date(startDate.value);
        endDate.setCustomValidity(valid ? '' : 'End date cannot be before start date.');
        return valid;
    };

    const normalizedText = (value) => String(value || '').trim().toLowerCase();

    const getFilteredRows = () => {
        const search = normalizedText(searchInput.value);
        const leaveType = typeFilter.value;
        const status = statusFilter.value;
        const year = yearFilter.value;

        return rows.filter((row) => {
            const rowText = normalizedText(row.textContent);
            const matchesSearch = !search || rowText.includes(search);
            const matchesType = !leaveType || row.dataset.leaveType === leaveType;
            const matchesStatus = !status || row.dataset.status === status;
            const matchesYear = !year || row.dataset.year === year;

            return matchesSearch && matchesType && matchesStatus && matchesYear;
        });
    };

    const renderRows = () => {
        const filteredRows = getFilteredRows();
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
        currentPage = Math.min(currentPage, totalPages);
        const start = (currentPage - 1) * pageSize;
        const visibleRows = filteredRows.slice(start, start + pageSize);

        rows.forEach((row) => {
            row.hidden = !visibleRows.includes(row);
        });

        if (countLabel) {
            const shownFrom = filteredRows.length === 0 ? 0 : start + 1;
            const shownTo = Math.min(start + pageSize, filteredRows.length);
            countLabel.textContent = `Showing ${shownFrom}-${shownTo} of ${filteredRows.length} leave requests`;
        }

        if (prevButton) {
            prevButton.disabled = currentPage <= 1;
        }

        if (nextButton) {
            nextButton.disabled = currentPage >= totalPages;
        }
    };

    const resetFiltersToFirstPage = () => {
        currentPage = 1;
        renderRows();
    };

    if (form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            event.stopPropagation();

            datesAreValid();
            form.classList.add('was-validated');

            if (!form.checkValidity()) {
                showAlert('warning', 'Check Leave Form', 'Please complete all required leave details before submitting.');
                return;
            }

            // =======================================
            // DATABASE PLACEHOLDER
            // Save leave request to the database.
            // =======================================
            showAlert('success', 'Leave request submitted successfully (Demo Mode).', 'No database changes were made.');
        });

        form.addEventListener('reset', () => {
            window.setTimeout(() => {
                form.classList.remove('was-validated');
                endDate.setCustomValidity('');
            }, 0);
        });
    }

    [startDate, endDate].forEach((field) => {
        if (field) {
            field.addEventListener('change', datesAreValid);
        }
    });

    [searchInput, typeFilter, statusFilter, yearFilter].forEach((field) => {
        if (field) {
            field.addEventListener('input', resetFiltersToFirstPage);
            field.addEventListener('change', resetFiltersToFirstPage);
        }
    });

    if (prevButton) {
        prevButton.addEventListener('click', () => {
            currentPage = Math.max(1, currentPage - 1);
            renderRows();
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', () => {
            currentPage += 1;
            renderRows();
        });
    }

    renderRows();
})();

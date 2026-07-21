(function () {
    'use strict';

    const form = document.getElementById('leaveApplicationForm');
    const leaveType = document.getElementById('leaveType');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const numberOfDays = document.getElementById('numberOfDays');
    const supportingDocument = document.getElementById('supportingDocument');
    const supportingDocumentHelp = document.getElementById('supportingDocumentHelp');
    const searchInput = document.getElementById('leaveSearch');
    const typeFilter = document.getElementById('filterLeaveType');
    const statusFilter = document.getElementById('filterStatus');
    const yearFilter = document.getElementById('filterYear');
    const rows = Array.from(document.querySelectorAll('[data-leave-row]'));
    const countLabel = document.getElementById('leaveHistoryCount');
    const prevButton = document.getElementById('leavePrevPage');
    const nextButton = document.getElementById('leaveNextPage');
    const pageSize = 1000;
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

    const calculateDays = () => {
        if (!startDate || !endDate || !numberOfDays || !startDate.value || !endDate.value) {
            if (numberOfDays) {
                numberOfDays.value = '';
            }
            return 0;
        }

        const start = new Date(`${startDate.value}T00:00:00`);
        const end = new Date(`${endDate.value}T00:00:00`);
        if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) {
            numberOfDays.value = '';
            return 0;
        }

        const days = Math.floor((end - start) / 86400000) + 1;
        numberOfDays.value = String(days);
        return days;
    };

    const datesAreValid = () => {
        if (!startDate || !endDate) {
            return true;
        }

        if (!startDate.value || !endDate.value) {
            endDate.setCustomValidity('');
            calculateDays();
            return true;
        }

        const valid = new Date(`${endDate.value}T00:00:00`) >= new Date(`${startDate.value}T00:00:00`);
        endDate.setCustomValidity(valid ? '' : 'End date cannot be before start date.');
        calculateDays();
        return valid;
    };

    const updateAttachmentRequirement = () => {
        if (!leaveType || !supportingDocument) {
            return;
        }

        const selected = leaveType.options[leaveType.selectedIndex];
        const required = selected && selected.dataset.requiresAttachment === '1';
        supportingDocument.required = Boolean(required);

        if (supportingDocumentHelp) {
            supportingDocumentHelp.textContent = required
                ? 'A supporting document is required for this leave type. Accepted formats: PDF, JPG, PNG, DOC, DOCX. Maximum size: 5MB.'
                : 'Accepted formats: PDF, JPG, PNG, DOC, DOCX. Maximum size: 5MB.';
        }
    };

    const normalizedText = (value) => String(value || '').trim().toLowerCase();

    const getFilteredRows = () => {
        const search = normalizedText(searchInput ? searchInput.value : '');
        const selectedType = typeFilter ? typeFilter.value : '';
        const status = statusFilter ? statusFilter.value : '';
        const year = yearFilter ? yearFilter.value : '';

        return rows.filter((row) => {
            const rowText = normalizedText(row.textContent);
            const matchesSearch = !search || rowText.includes(search);
            const matchesType = !selectedType || row.dataset.leaveType === selectedType;
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
            datesAreValid();
            updateAttachmentRequirement();
            form.classList.add('was-validated');

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                showAlert('warning', 'Check Leave Form', 'Please complete all required leave details before submitting.');
            }
        });

        form.addEventListener('reset', () => {
            window.setTimeout(() => {
                form.classList.remove('was-validated');
                if (endDate) {
                    endDate.setCustomValidity('');
                }
                if (numberOfDays) {
                    numberOfDays.value = '';
                }
                updateAttachmentRequirement();
            }, 0);
        });
    }

    [startDate, endDate].forEach((field) => {
        if (field) {
            field.addEventListener('change', datesAreValid);
            field.addEventListener('input', datesAreValid);
        }
    });

    if (leaveType) {
        leaveType.addEventListener('change', updateAttachmentRequirement);
    }


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

    updateAttachmentRequirement();
    datesAreValid();
    document.addEventListener('submit', async (event) => {
        const submittedForm = event.target;
        const route = new URL(submittedForm.action, window.location.href).searchParams.get('route') || '';
        if (submittedForm.id !== 'leaveApplicationForm' && route !== 'leave-requests/cancel') return;
        event.preventDefault(); event.stopImmediatePropagation();
        if (submittedForm.id === 'leaveApplicationForm') {
            datesAreValid(); updateAttachmentRequirement(); submittedForm.classList.add('was-validated');
            if (!submittedForm.checkValidity()) return;
        } else {
            const confirmed = !window.Swal ? window.confirm('Cancel this leave request?') : (await window.Swal.fire({ icon: 'warning', title: 'Cancel leave request?', text: 'The request status will be updated immediately.', showCancelButton: true, confirmButtonColor: '#ed3237' })).isConfirmed;
            if (!confirmed) return;
        }
        try {
            await window.FuelOpsAjax.submitForm(submittedForm, {
                button: event.submitter || submittedForm.querySelector('[type="submit"]'),
                refresh: '.clock-workspace',
                redirect: false,
                loadingText: submittedForm.id === 'leaveApplicationForm' ? 'Submitting request...' : 'Cancelling...',
            });
        } catch (error) {
            // The shared helper keeps entered values and renders validation errors.
        }
    }, true);

})();
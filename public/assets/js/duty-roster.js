(function () {
    'use strict';

    const searchInput = document.getElementById('rosterSearch');
    const shiftFilter = document.getElementById('shiftFilter');
    const statusFilter = document.getElementById('statusFilter');
    const rows = Array.from(document.querySelectorAll('[data-roster-row]'));
    const countLabel = document.getElementById('rosterCount');
    const prevButton = document.getElementById('rosterPrevPage');
    const nextButton = document.getElementById('rosterNextPage');
    const calendarDays = document.querySelectorAll('[data-calendar-day]');
    const pageSize = 1000;
    let currentPage = 1;

    const showAlert = (title, text) => {
        if (window.Swal) {
            window.Swal.fire({
                icon: 'info',
                title,
                text,
                confirmButtonColor: '#F68B34',
            });
            return;
        }

        window.alert(`${title}\n\n${text}`);
    };

    const normalize = (value) => String(value || '').trim().toLowerCase();

    const getFilteredRows = () => {
        const search = normalize(searchInput.value);
        const shift = shiftFilter.value;
        const status = statusFilter ? statusFilter.value : '';

        return rows.filter((row) => {
            const matchesSearch = !search || normalize(row.textContent).includes(search) || normalize(row.dataset.date).includes(search);
            const matchesShift = !shift || row.dataset.shift === shift;
            const matchesStatus = !status || row.dataset.status === status;

            return matchesSearch && matchesShift && matchesStatus;
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
            countLabel.textContent = `Showing ${shownFrom}-${shownTo} of ${filteredRows.length} roster records`;
        }

        if (prevButton) {
            prevButton.disabled = currentPage <= 1;
        }

        if (nextButton) {
            nextButton.disabled = currentPage >= totalPages;
        }
    };

    const resetToFirstPage = () => {
        currentPage = 1;
        renderRows();
    };

    [searchInput, shiftFilter, statusFilter].forEach((field) => {
        if (field) {
            field.addEventListener('input', resetToFirstPage);
            field.addEventListener('change', resetToFirstPage);
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

    calendarDays.forEach((day) => {
        day.addEventListener('click', () => {
            const label = `Duty Assignment - Day ${day.dataset.calendarDay}`;
            showAlert(label, day.dataset.calendarDetails || 'No duty assigned.');
        });
    });

    renderRows();
})();

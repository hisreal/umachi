(function () {
    'use strict';

    const searchInput = document.getElementById('attendanceSearch');
    const monthFilter = document.getElementById('monthFilter');
    const yearFilter = document.getElementById('yearFilter');
    const statusFilter = document.getElementById('statusFilter');
    const rows = Array.from(document.querySelectorAll('[data-attendance-row]'));
    const countLabel = document.getElementById('attendanceHistoryCount');
    const prevButton = document.getElementById('attendancePrevPage');
    const nextButton = document.getElementById('attendanceNextPage');
    const calendarDays = document.querySelectorAll('[data-attendance-day]');
    const pageSize = 6;
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
        const month = monthFilter.value;
        const year = yearFilter.value;
        const status = statusFilter.value;

        return rows.filter((row) => {
            const matchesSearch = !search || normalize(row.textContent).includes(search) || normalize(row.dataset.date).includes(search);
            const matchesMonth = !month || row.dataset.month === month;
            const matchesYear = !year || row.dataset.year === year;
            const matchesStatus = !status || row.dataset.status === status;

            return matchesSearch && matchesMonth && matchesYear && matchesStatus;
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
            countLabel.textContent = `Showing ${shownFrom}-${shownTo} of ${filteredRows.length} attendance records`;
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

    [searchInput, monthFilter, yearFilter, statusFilter].forEach((field) => {
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
            showAlert(`July ${day.dataset.attendanceDay}, 2026`, `Attendance status: ${day.dataset.attendanceStatus}`);
        });
    });

    renderRows();
})();

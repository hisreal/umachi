(function () {
    'use strict';

    const searchInput = document.getElementById('salesSearch');
    const shiftFilter = document.getElementById('shiftFilter');
    const pumpFilter = document.getElementById('pumpFilter');
    const fuelFilter = document.getElementById('fuelFilter');
    const monthFilter = document.getElementById('monthFilter');
    const yearFilter = document.getElementById('yearFilter');
    const statusFilter = document.getElementById('statusFilter');
    const rows = Array.from(document.querySelectorAll('[data-sales-row]'));
    const countLabel = document.getElementById('fuelSalesCount');
    const prevButton = document.getElementById('salesPrevPage');
    const nextButton = document.getElementById('salesNextPage');
    const chartPlaceholder = document.querySelector('.sales-chart-placeholder');
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
        const shift = shiftFilter.value;
        const pump = pumpFilter.value;
        const fuel = fuelFilter.value;
        const month = monthFilter.value;
        const year = yearFilter.value;
        const status = statusFilter.value;

        return rows.filter((row) => {
            const matchesSearch = !search || normalize(row.textContent).includes(search) || normalize(row.dataset.date).includes(search);
            const matchesShift = !shift || row.dataset.shift === shift;
            const matchesPump = !pump || row.dataset.pump === pump;
            const matchesFuel = !fuel || row.dataset.fuel === fuel;
            const matchesMonth = !month || row.dataset.month === month;
            const matchesYear = !year || row.dataset.year === year;
            const matchesStatus = !status || row.dataset.status === status;

            return matchesSearch && matchesShift && matchesPump && matchesFuel && matchesMonth && matchesYear && matchesStatus;
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
            countLabel.textContent = `Showing ${shownFrom}-${shownTo} of ${filteredRows.length} fuel sales records`;
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

    [searchInput, shiftFilter, pumpFilter, fuelFilter, monthFilter, yearFilter, statusFilter].forEach((field) => {
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

    if (chartPlaceholder) {
        chartPlaceholder.addEventListener('click', () => {
            showAlert('Future Sales Chart', 'Charts will be connected when backend reporting data is available.');
        });
    }

    renderRows();
})();

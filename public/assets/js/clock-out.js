(function () {
    'use strict';

    const byId = (id) => document.getElementById(id);

    const currentDate = byId('currentDate');
    const liveClock = byId('liveClock');
    const form = byId('clockOutForm');
    const pumpSelection = byId('pumpSelection');
    const fuelType = byId('fuelType');
    const openingMeter = byId('openingMeter');
    const closingMeter = byId('closingMeter');
    const litersSold = byId('litersSold');
    const amountCollected = byId('amountCollected');
    const unitPrice = Number(form ? form.dataset.unitPrice : 0) || 0;
    const clockOutPhoto = byId('clockOutPhoto');

    const summaryPump = byId('summaryPump');
    const summaryFuelType = byId('summaryFuelType');
    const summaryOpeningMeter = byId('summaryOpeningMeter');
    const summaryClosingMeter = byId('summaryClosingMeter');
    const summaryLitersSold = byId('summaryLitersSold');
    const summaryAmountCollected = byId('summaryAmountCollected');
    const hasFuelSalesFields = Boolean(pumpSelection && fuelType && openingMeter && closingMeter && litersSold && amountCollected);

    const formatNumber = (value) => {
        const number = Number(value);

        if (!Number.isFinite(number)) {
            return '0.00';
        }

        return number.toLocaleString('en-NG', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    };

    const parseCurrency = (value) => {
        const normalized = String(value || '').replace(/[^\d.]/g, '');
        const amount = Number(normalized);

        return Number.isFinite(amount) ? amount : 0;
    };

    const formatCurrency = (value) => {
        const amount = Number(value) || 0;

        return `\u20a6${amount.toLocaleString('en-NG', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })}`;
    };

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

    const updateClock = () => {
        if (!currentDate || !liveClock) {
            return;
        }

        const now = new Date();

        currentDate.textContent = now.toLocaleDateString('en-NG', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });

        liveClock.textContent = now.toLocaleTimeString('en-NG', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    };

    const syncSummary = () => {
        if (!form || !hasFuelSalesFields) {
            return;
        }

        const openingValue = Number(openingMeter.value) || 0;
        const closingValue = Number(closingMeter.value) || 0;
        const calculatedLiters = Math.max(0, closingValue - openingValue);
        const calculatedAmount = calculatedLiters * unitPrice;
        litersSold.value = formatNumber(calculatedLiters).replace(/,/g, '');
        amountCollected.value = unitPrice > 0 ? formatCurrency(calculatedAmount) : 'Fuel price not set';

        summaryPump.textContent = pumpSelection.value || 'Pending';
        summaryFuelType.textContent = fuelType.value || 'Pending';
        summaryOpeningMeter.textContent = formatNumber(openingMeter.value);
        summaryClosingMeter.textContent = formatNumber(closingMeter.value);
        summaryLitersSold.textContent = formatNumber(litersSold.value);
        summaryAmountCollected.textContent = amountCollected.value || formatCurrency(0);
    };

    const validateField = (field) => {
        const isValid = Boolean(field && String(field.value).trim());
        if (field) {
            field.classList.toggle('is-invalid', !isValid);
        }

        return isValid;
    };

    const validateForm = () => {
        if (!validateField(clockOutPhoto)) {
            showAlert('warning', 'Clock-Out Selfie Required', 'Please capture your clock-out selfie before submitting.');
            return false;
        }

        if (!hasFuelSalesFields) {
            return true;
        }

        const requiredFields = [
            pumpSelection,
            fuelType,
            openingMeter,
            closingMeter,
            amountCollected,
        ];

        const fieldsAreComplete = requiredFields.every(validateField);
        const openingValue = Number(openingMeter.value);
        const closingValue = Number(closingMeter.value);

        if (!fieldsAreComplete) {
            showAlert('warning', 'Check Required Fields', 'Please complete all required shift sales fields before clocking out.');
            return false;
        }

        if (closingValue < openingValue) {
            closingMeter.classList.add('is-invalid');
            showAlert('warning', 'Invalid Meter Reading', 'Closing meter reading must be greater than or equal to the opening meter reading.');
            return false;
        }

        if (unitPrice <= 0) {
            amountCollected.classList.add('is-invalid');
            showAlert('warning', 'Fuel Price Not Configured', 'Current fuel price is not configured for your assigned fuel type. Please contact your manager.');
            return false;
        }

        if (Number(litersSold.value) < 0) {
            showAlert('warning', 'Invalid Litres Sold', 'Litres sold cannot be negative.');
            return false;
        }

        return true;
    };

    const handleFormSubmit = (event) => {
        if (!validateForm()) {
            event.preventDefault();
        }
    };

    if (form && hasFuelSalesFields) {
        [pumpSelection, fuelType, openingMeter, closingMeter, litersSold, amountCollected].filter(Boolean).forEach((field) => {
            field.addEventListener('input', syncSummary);
            field.addEventListener('change', syncSummary);
            field.addEventListener('input', () => field.classList.remove('is-invalid'));
        });

        form.addEventListener('submit', handleFormSubmit);
    }

    if (form && !hasFuelSalesFields) {
        form.addEventListener('submit', handleFormSubmit);
    }

    updateClock();
    syncSummary();
    window.setInterval(updateClock, 1000);
})();







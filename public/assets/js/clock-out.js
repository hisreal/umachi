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

    const summaryPump = byId('summaryPump');
    const summaryFuelType = byId('summaryFuelType');
    const summaryOpeningMeter = byId('summaryOpeningMeter');
    const summaryClosingMeter = byId('summaryClosingMeter');
    const summaryLitersSold = byId('summaryLitersSold');
    const summaryAmountCollected = byId('summaryAmountCollected');

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
        const amount = parseCurrency(value);

        return `\u20a6${amount.toLocaleString('en-NG', {
            maximumFractionDigits: 0,
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
        if (!form) {
            return;
        }

        summaryPump.textContent = pumpSelection.value || 'Pending';
        summaryFuelType.textContent = fuelType.value || 'Pending';
        summaryOpeningMeter.textContent = formatNumber(openingMeter.value);
        summaryClosingMeter.textContent = formatNumber(closingMeter.value);
        summaryLitersSold.textContent = formatNumber(litersSold.value);
        summaryAmountCollected.textContent = formatCurrency(amountCollected.value);
    };

    const validateField = (field) => {
        const isValid = Boolean(field && String(field.value).trim());
        if (field) {
            field.classList.toggle('is-invalid', !isValid);
        }

        return isValid;
    };

    const validateForm = () => {
        const requiredFields = [
            pumpSelection,
            fuelType,
            openingMeter,
            closingMeter,
            litersSold,
            amountCollected,
        ];

        const fieldsAreComplete = requiredFields.every(validateField);
        const openingValue = Number(openingMeter.value);
        const closingValue = Number(closingMeter.value);
        const amountValue = parseCurrency(amountCollected.value);

        if (!fieldsAreComplete) {
            showAlert('warning', 'Check Required Fields', 'Please complete all required shift sales fields before clocking out.');
            return false;
        }

        if (closingValue < openingValue) {
            closingMeter.classList.add('is-invalid');
            showAlert('warning', 'Invalid Meter Reading', 'Closing meter reading must be greater than or equal to the opening meter reading.');
            return false;
        }

        if (amountValue <= 0) {
            amountCollected.classList.add('is-invalid');
            showAlert('warning', 'Invalid Amount', 'Please enter the total amount collected for this shift.');
            return false;
        }

        return true;
    };

    const handleAmountBlur = () => {
        if (amountCollected.value.trim()) {
            amountCollected.value = formatCurrency(amountCollected.value);
        }

        syncSummary();
    };

    const handleFormSubmit = (event) => {
        event.preventDefault();

        if (!validateForm()) {
            return;
        }

        // DATABASE PLACEHOLDER
        // Save clock-out information and fuel sales records to the database.
        showAlert(
            'success',
            'Clock Out Successful (Demo Mode)',
            'Your shift summary and fuel sales record have been submitted for this prototype.'
        );
    };

    if (form) {
        [pumpSelection, fuelType, openingMeter, closingMeter, litersSold, amountCollected].forEach((field) => {
            field.addEventListener('input', syncSummary);
            field.addEventListener('change', syncSummary);
            field.addEventListener('input', () => field.classList.remove('is-invalid'));
        });

        amountCollected.addEventListener('blur', handleAmountBlur);
        form.addEventListener('submit', handleFormSubmit);
    }

    updateClock();
    syncSummary();
    window.setInterval(updateClock, 1000);
})();



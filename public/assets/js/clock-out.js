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
    const cashReceived = byId('cashReceived');
    const posReceived = byId('posReceived');
    const bankTransferReceived = byId('bankTransferReceived');
    const paymentRemark = byId('paymentRemark');
    const unitPrice = Number(form ? form.dataset.unitPrice : 0) || 0;
    const clockOutPhoto = byId('clockOutPhoto');

    const summaryPump = byId('summaryPump');
    const summaryFuelType = byId('summaryFuelType');
    const summaryOpeningMeter = byId('summaryOpeningMeter');
    const summaryClosingMeter = byId('summaryClosingMeter');
    const summaryLitersSold = byId('summaryLitersSold');
    const summaryAmountCollected = byId('summaryAmountCollected');
    const summaryExpectedAmount = byId('summaryExpectedAmount');
    const summaryCashReceived = byId('summaryCashReceived');
    const summaryPosReceived = byId('summaryPosReceived');
    const summaryBankTransferReceived = byId('summaryBankTransferReceived');
    const summaryDifference = byId('summaryDifference');
    const paymentStatus = byId('paymentStatus');
    const summaryPaymentStatus = byId('summaryPaymentStatus');
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
        const expectedAmount = calculatedLiters * unitPrice;
        const cash = Number(cashReceived.value) || 0;
        const pos = Number(posReceived.value) || 0;
        const transfer = Number(bankTransferReceived.value) || 0;
        const totalReceived = cash + pos + transfer;
        const difference = expectedAmount - totalReceived;
        const balanced = Math.abs(difference) < 0.01;
        const status = balanced ? 'Balanced' : (difference > 0 ? `Shortage ${formatCurrency(difference)}` : `Overpaid ${formatCurrency(Math.abs(difference))}`);
        const badgeClass = balanced ? 'bg-success' : (difference > 0 ? 'bg-danger' : 'bg-warning text-dark');
        paymentRemark.required = !balanced;
        litersSold.value = formatNumber(calculatedLiters).replace(/,/g, '');
        amountCollected.value = unitPrice > 0 ? formatCurrency(totalReceived) : 'Fuel price not set';
        [paymentStatus, summaryPaymentStatus].forEach((badge) => {
            badge.className = `badge ${badgeClass}`;
            badge.textContent = status;
        });

        summaryPump.textContent = pumpSelection.value || 'Pending';
        summaryFuelType.textContent = fuelType.value || 'Pending';
        summaryOpeningMeter.textContent = formatNumber(openingMeter.value);
        summaryClosingMeter.textContent = formatNumber(closingMeter.value);
        summaryLitersSold.textContent = formatNumber(litersSold.value);
        summaryAmountCollected.textContent = amountCollected.value || formatCurrency(0);
        summaryExpectedAmount.textContent = formatCurrency(expectedAmount);
        summaryCashReceived.textContent = formatCurrency(cash);
        summaryPosReceived.textContent = formatCurrency(pos);
        summaryBankTransferReceived.textContent = formatCurrency(transfer);
        summaryDifference.textContent = formatCurrency(difference);
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
            cashReceived,
            posReceived,
            bankTransferReceived,
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

        const paymentFields = [cashReceived, posReceived, bankTransferReceived];
        if (paymentFields.some((field) => Number(field.value) < 0 || !Number.isFinite(Number(field.value)))) {
            paymentFields.forEach((field) => field.classList.toggle('is-invalid', Number(field.value) < 0 || !Number.isFinite(Number(field.value))));
            showAlert('warning', 'Invalid Payment Amount', 'Cash, POS / Card, and bank transfer values must be valid non-negative amounts.');
            return false;
        }

        const expectedAmount = Number(litersSold.value) * unitPrice;
        const totalReceived = paymentFields.reduce((total, field) => total + Number(field.value), 0);
        if (Math.abs(expectedAmount - totalReceived) >= 0.01 && !paymentRemark.value.trim()) {
            paymentRemark.classList.add('is-invalid');
            showAlert('warning', 'Payment Explanation Required', 'Explain the shortage or overpayment before clocking out.');
            return false;
        }

        return true;
    };

    const handleFormSubmit = async (event) => {
        if (!validateForm()) {
            event.preventDefault();
            return;
        }
        event.preventDefault();
        await window.FuelOpsAjax.submitForm(form, {
            button: event.submitter,
            onProgress: () => {}
        }).catch(() => {});
    };

    if (form && hasFuelSalesFields) {
        [pumpSelection, fuelType, openingMeter, closingMeter, litersSold, amountCollected, cashReceived, posReceived, bankTransferReceived, paymentRemark].filter(Boolean).forEach((field) => {
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







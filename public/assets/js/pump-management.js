(function () {
    'use strict';

    const showAlert = (icon, title, text) => {
        if (window.Swal) {
            window.Swal.fire({ icon, title, text, confirmButtonColor: '#f68b34' });
            return;
        }
        window.alert(`${title}\n${text}`);
    };

    const confirmAction = async (title, text) => {
        if (!window.Swal) {
            return window.confirm(`${title}\n${text}`);
        }

        const result = await window.Swal.fire({
            icon: 'warning',
            title,
            text,
            showCancelButton: true,
            confirmButtonColor: '#ed3237',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, continue',
        });

        return result.isConfirmed;
    };

    document.addEventListener('click', async (event) => {
        const actionButton = event.target.closest('[data-pump-action]');
        if (!actionButton) { return; }

        const action = actionButton.dataset.pumpAction;
        const pump = actionButton.dataset.pump || 'this pump';

        if (action === 'view') {
            event.preventDefault();
            showAlert(
                'info',
                pump,
                [
                    actionButton.dataset.name,
                    `Fuel Type: ${actionButton.dataset.fuel || 'N/A'}`,
                    `Status: ${actionButton.dataset.status || 'N/A'}`,
                    `Meter: ${actionButton.dataset.meter || '0.00'}`,
                    `Manufacturer: ${actionButton.dataset.manufacturer || 'N/A'}`,
                    `Serial: ${actionButton.dataset.serial || 'N/A'}`,
                ].filter(Boolean).join('\n')
            );
            return;
        }

        if (action === 'delete') {
            event.preventDefault();
            if (await confirmAction('Delete Pump', `${pump} will be soft deleted and hidden from normal listings.`)) {
                actionButton.closest('form')?.submit();
            }
            return;
        }

        if (action === 'toggle') {
            event.preventDefault();
            if (await confirmAction('Update Pump Status', `${pump} status will be changed.`)) {
                actionButton.closest('form')?.submit();
            }
        }
    });

    const form = document.getElementById('pumpForm');
    if (form) {
        form.addEventListener('submit', (event) => {
            form.classList.add('was-validated');
            if (!form.checkValidity()) {
                event.preventDefault();
                showAlert('warning', 'Review Pump Form', 'Please complete all required pump fields.');
            }
        });
        form.addEventListener('reset', () => { window.setTimeout(() => form.classList.remove('was-validated'), 0); });
    }
}());
(function () {
    'use strict';

    const form = document.getElementById('editProfileForm');
    const cancelButton = document.getElementById('cancelEditProfileBtn');
    const demoButtons = document.querySelectorAll('[data-edit-profile-demo-action]');

    const showAlert = (icon, title, text) => {
        if (window.Swal) {
            return window.Swal.fire({
                icon,
                title,
                text,
                confirmButtonColor: '#F68B34',
            });
        }

        window.alert(`${title}\n\n${text}`);
        return Promise.resolve({ isConfirmed: true });
    };

    demoButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const action = button.dataset.editProfileDemoAction;
            const title = action === 'remove-photo' ? 'Remove Photo (Demo)' : 'Change Photo (Demo)';
            const text = action === 'remove-photo'
                ? 'Photo removal will be connected during backend integration.'
                : 'Photo upload and preview will be connected during backend integration.';

            showAlert('info', title, text);
        });
    });

    if (form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            event.stopPropagation();

            form.classList.add('was-validated');

            if (!form.checkValidity()) {
                showAlert('warning', 'Check Required Fields', 'Please complete all required profile fields before saving.');
                return;
            }

            // =======================================
            // DATABASE PLACEHOLDER
            // Save updated employee information to the database.
            // =======================================
            showAlert('success', 'Profile updated successfully (Demo Mode)', 'No database changes were made.');
        });
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', (event) => {
            if (!window.Swal) {
                return;
            }

            event.preventDefault();

            window.Swal.fire({
                icon: 'question',
                title: 'Cancel Edit?',
                text: 'Your demo changes will be discarded.',
                showCancelButton: true,
                confirmButtonText: 'Yes, cancel',
                cancelButtonText: 'Keep editing',
                confirmButtonColor: '#F68B34',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = cancelButton.href;
                }
            });
        });
    }
})();

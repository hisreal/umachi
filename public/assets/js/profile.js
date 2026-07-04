(function () {
    'use strict';

    const demoButtons = document.querySelectorAll('[data-profile-demo-action]');

    const showDemoMessage = (action) => {
        const title = action === 'remove-photo' ? 'Remove Photo (Demo)' : 'Change Photo (Demo)';
        const text = action === 'remove-photo'
            ? 'Photo removal will be connected during backend integration.'
            : 'Photo upload and preview will be connected during backend integration.';

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

    demoButtons.forEach((button) => {
        button.addEventListener('click', () => {
            showDemoMessage(button.dataset.profileDemoAction || 'change-photo');
        });
    });
})();

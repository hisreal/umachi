(function () {
    'use strict';

    const editButtons = document.querySelectorAll('[data-profile-edit-action]');
    const editLink = document.querySelector('.profile-edit-btn');

    editButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (editLink && editLink.href) {
                window.location.href = editLink.href;
            }
        });
    });
})();
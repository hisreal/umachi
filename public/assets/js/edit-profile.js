(function () {
    'use strict';

    const form = document.getElementById('editProfileForm');
    const cancelButton = document.getElementById('cancelEditProfileBtn');
    const photoInput = document.getElementById('passportPhoto');
    const photoPreview = document.querySelector('.profile-passport');
    const removePhotoInput = document.getElementById('removePhoto');
    const changePhotoButton = document.querySelector('[data-edit-profile-action="change-photo"]');
    const clearPhotoButton = document.querySelector('[data-edit-profile-action="clear-photo"]');
   const originalPhoto = photoPreview ? photoPreview.src : '';

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

    if (changePhotoButton && photoInput) {
        changePhotoButton.addEventListener('click', () => photoInput.click());
    }

    if (clearPhotoButton && photoInput && photoPreview) {
        clearPhotoButton.addEventListener('click', () => {
            photoInput.value = '';
            if (removePhotoInput) removePhotoInput.value = '1';
            photoPreview.src = window.defaultProfilePhoto || originalPhoto;
        });
    }

    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', () => {
            if (removePhotoInput) removePhotoInput.value = '0';
            const file = photoInput.files && photoInput.files[0] ? photoInput.files[0] : null;
            if (!file) {
                photoPreview.src = originalPhoto;
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                photoInput.value = '';
                showAlert('warning', 'Invalid Image', 'Please choose a JPG, PNG, or WEBP profile photo.');
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                photoInput.value = '';
                showAlert('warning', 'Image Too Large', 'Profile photo must not be larger than 5 MB.');
                return;
            }

            photoPreview.src = URL.createObjectURL(file);
        });
    }

    if (form) {
        form.addEventListener('submit', (event) => {
            form.classList.add('was-validated');

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                showAlert('warning', 'Check Required Fields', 'Please complete all required profile fields before saving.');
            }
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
                text: 'Your unsaved profile changes will be discarded.',
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
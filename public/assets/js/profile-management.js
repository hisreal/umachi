(function () {
    'use strict';

    const alertBox = (title, text, icon = 'success') => {
        if (window.Swal) {
            window.Swal.fire({ title, text, icon, confirmButtonColor: '#f68b34' });
            return;
        }
        window.alert(`${title}\n${text}`);
    };

    const photoInput = document.getElementById('profilePhotoInput');
    const photoPreview = document.getElementById('profilePhotoPreview');

    document.querySelector('[data-profile-photo-trigger]')?.addEventListener('click', () => photoInput?.click());

    photoInput?.addEventListener('change', () => {
        const file = photoInput.files?.[0];
        if (!file) return;
        if (!['image/jpeg', 'image/jpg', 'image/png'].includes(file.type)) {
            photoInput.value = '';
            alertBox('Invalid Image Type', 'Please select a JPG, JPEG, or PNG image.', 'warning');
            return;
        }
        photoPreview.src = URL.createObjectURL(file);
    });

    document.querySelector('[data-profile-photo-remove]')?.addEventListener('click', () => {
        photoPreview.src = window.defaultProfilePhoto || photoPreview.src;
        if (photoInput) photoInput.value = '';
        alertBox('Photo Removed (Demo Mode)', 'The profile photo preview has been reset.');
    });

    document.getElementById('adminProfileForm')?.addEventListener('submit', (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const phonePattern = /^[+\d][\d\s-]{7,}$/;
        const phone = document.getElementById('phone')?.value || '';
        const emergencyPhone = document.getElementById('emergencyPhone')?.value || '';

        if (!form.checkValidity() || !phonePattern.test(phone) || !phonePattern.test(emergencyPhone)) {
            form.classList.add('was-validated');
            alertBox('Invalid Profile Details', 'Please complete all required fields with a valid email and phone number.', 'warning');
            return;
        }

        // ===========================================
        // DATABASE PLACEHOLDER
        // Save updated administrator profile to MySQL.
        // ===========================================
        alertBox('Profile Saved (Demo Mode)', 'Administrator profile changes were validated on the frontend only.');
    });

    document.querySelectorAll('[data-profile-reset]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            const form = button.closest('form');
            if (window.Swal) {
                window.Swal.fire({ title: 'Reset Form?', text: 'Demo form values will be restored.', icon: 'question', showCancelButton: true, confirmButtonColor: '#f68b34' }).then((result) => {
                    if (result.isConfirmed) form?.reset();
                });
                return;
            }
            form?.reset();
        });
    });

    document.querySelectorAll('[data-profile-cancel]').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (!window.Swal) return;
            event.preventDefault();
            window.Swal.fire({ title: 'Leave This Page?', text: 'Unsaved demo changes will be discarded.', icon: 'question', showCancelButton: true, confirmButtonColor: '#f68b34' }).then((result) => {
                if (result.isConfirmed) window.location.href = link.href;
            });
        });
    });

    document.querySelectorAll('[data-toggle-password]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.togglePassword);
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            button.querySelector('i')?.classList.toggle('fa-eye-slash');
        });
    });

    const passwordRules = {
        length: (value) => value.length >= 8,
        upper: (value) => /[A-Z]/.test(value),
        lower: (value) => /[a-z]/.test(value),
        number: (value) => /\d/.test(value),
        special: (value) => /[^A-Za-z0-9]/.test(value),
    };

    const updateStrength = () => {
        const value = document.getElementById('newPassword')?.value || '';
        const results = Object.entries(passwordRules).map(([key, test]) => [key, test(value)]);
        const score = results.filter(([, passed]) => passed).length;
        const labels = ['Very Weak', 'Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];
        const colors = ['#ed3237', '#ed3237', '#f59e0b', '#f68b34', '#16a34a', '#15803d'];
        const bar = document.getElementById('passwordStrengthBar');
        const label = document.getElementById('passwordStrengthLabel');
        if (bar) {
            bar.style.width = `${(score / 5) * 100}%`;
            bar.style.background = colors[score];
        }
        if (label) label.textContent = labels[score];
        results.forEach(([key, passed]) => {
            const item = document.querySelector(`[data-password-rule="${key}"]`);
            item?.classList.toggle('is-met', passed);
            const icon = item?.querySelector('i');
            if (icon) icon.className = passed ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle';
        });
        return score;
    };

    document.getElementById('newPassword')?.addEventListener('input', updateStrength);

    document.getElementById('passwordForm')?.addEventListener('submit', (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const current = document.getElementById('currentPassword')?.value || '';
        const next = document.getElementById('newPassword')?.value || '';
        const confirm = document.getElementById('confirmPassword')?.value || '';
        const score = updateStrength();

        if (!form.checkValidity() || score < 5 || next !== confirm || current === next) {
            form.classList.add('was-validated');
            alertBox('Password Not Updated', 'Check password requirements, confirmation match, and ensure the new password is different.', 'warning');
            return;
        }

        // ===========================================
        // DATABASE PLACEHOLDER
        // Update administrator password in MySQL.
        // Store password change history.
        // ===========================================
        alertBox('Password Updated (Demo Mode)', 'The password passed frontend validation and is ready for backend integration.');
        form.reset();
        updateStrength();
    });

    document.addEventListener('DOMContentLoaded', updateStrength);
}());
(function () {
    'use strict';

    const form = document.getElementById('loginForm');
    const loginButton = document.getElementById('loginButton');
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const forgotPasswordLink = document.getElementById('forgotPasswordLink');
    const backButton = document.getElementById('authBackButton');

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

    if (window.authLoginMessage) {
        showAlert('warning', 'Authentication Notice', window.authLoginMessage);
    }

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            togglePassword.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            togglePassword.innerHTML = `<i class="fa-solid ${isPassword ? 'fa-eye-slash' : 'fa-eye'}"></i>`;
        });
    }

    if (form) {
        form.addEventListener('submit', (event) => {
            form.classList.add('was-validated');

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                showAlert('warning', 'Check Login Details', 'Please enter your username, select a role, and provide your password.');
                return;
            }

            if (loginButton) {
                loginButton.classList.add('is-loading');
                loginButton.disabled = true;
            }
        });
    }

    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener('click', (event) => {
            event.preventDefault();
            showAlert('info', 'Forgot Password', 'Please contact the administrator to reset your password.');
        });
    }

    if (backButton) {
        backButton.addEventListener('click', (event) => {
            if (window.history.length > 1) {
                event.preventDefault();
                window.history.back();
            }
        });
    }
})();

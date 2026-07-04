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
            event.preventDefault();
            event.stopPropagation();

            form.classList.add('was-validated');

            if (!form.checkValidity()) {
                showAlert('warning', 'Check Login Details', 'Please enter your username, select a role, and provide your password.');
                return;
            }

            loginButton.classList.add('is-loading');
            loginButton.disabled = true;

            window.setTimeout(() => {
                loginButton.classList.remove('is-loading');
                loginButton.disabled = false;

                // =======================================
                // DATABASE PLACEHOLDER
                // Validate username, role, and password
                // against the MySQL database.
                // =======================================
                showAlert('success', 'Login Successful (Demo Mode)', 'Authentication will be connected during backend integration.');
            }, 650);
        });
    }

    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener('click', (event) => {
            event.preventDefault();
            showAlert('info', 'Forgot Password (Demo)', 'Password recovery will be connected during backend integration.');
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

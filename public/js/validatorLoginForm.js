document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    const submitBtn = document.getElementById('login');
    const emailField = document.getElementById('username');
    const passwordField = document.getElementById('password');

    submitBtn.addEventListener('click', function (event) {
        event.preventDefault();

        // Nettoyer les anciennes erreurs
        [emailField, passwordField].forEach(field => {
            field.classList.remove('field-error');
            const oldError = field.parentElement.querySelector('.error-message');
            if (oldError) oldError.remove();
        });

        let isValid = true;

        const emailRegex = /^[^@]+@[^@]+\.[a-z]{2,}$/i;
        if (!emailRegex.test(emailField.value)) {
            showError(emailField, "Invalid email format.");
            isValid = false;
        }

        if (passwordField.value.length < 8) {
            showError(passwordField, "Password must be at least 8 characters.");
            isValid = false;
        }

        if (isValid) {
            form.submit();
        }
    });

    function showError(field, message) {
        field.classList.add('field-error');
        const error = document.createElement('div');
        error.classList.add('error-message');
        error.textContent = message;
        field.parentElement.appendChild(error);
    }
});

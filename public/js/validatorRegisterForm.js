document.addEventListener('DOMContentLoaded', () => {
    const joinBtn = document.getElementById('join');
    const form = document.querySelector('form');

    if (!joinBtn || !form) return; // Sécurité : pas de bouton ou pas de form, on sort

    joinBtn.addEventListener('click', function(event) {
        event.preventDefault();
        if (validateForm()) {
            form.submit();
        }
    });

    function validateForm() {
        const nameField = document.getElementById('name');
        const surnameField = document.getElementById('surname');
        const emailField = document.getElementById('email');
        const confirmEmailField = document.getElementById('confirmEmail');
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirmPass');
        const birthdateField = document.getElementById('birthdate');
        const roleSelect = document.getElementById('role'); // peut ne pas exister !

        // Tableau de tous les champs à nettoyer (erreurs)
        const fields = [nameField, surnameField, emailField, confirmEmailField, passwordField, confirmPasswordField, birthdateField];
        if (roleSelect) fields.push(roleSelect);

        // Nettoyage des erreurs précédentes
        fields.forEach(field => {
            if (!field) return;
            field.classList.remove('field-error');
            const error = field.parentElement.querySelector('.error-message');
            if (error) error.remove();
        });

        let isValid = true;

        // Validation du champ rôle uniquement s'il existe (form admin)
        if (roleSelect && roleSelect.value === "") {
            fail("Please select a role (User or Admin).", roleSelect);
            isValid = false;
        }

        const nameRegex = /^\p{L}+$/u;
        const emailRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/;

        if (!nameField || !nameRegex.test(nameField.value)) {
            fail("Name must only contain alphabetical letters.", nameField);
            isValid = false;
        }

        if (!surnameField || !nameRegex.test(surnameField.value)) {
            fail("Surname must only contain alphabetical letters.", surnameField);
            isValid = false;
        }

        if (!emailField || !emailRegex.test(emailField.value)) {
            fail("Invalid email format.", emailField);
            isValid = false;
        }

        if (!confirmEmailField || emailField.value !== confirmEmailField.value) {
            fail("Email confirmation does not match.", confirmEmailField);
            isValid = false;
        }

        if (!passwordField || passwordField.value.length < 8) {
            fail("Password must be at least 8 characters long.", passwordField);
            isValid = false;
        } else if (!passwordRegex.test(passwordField.value)) {
            fail("Password must contain uppercase, lowercase, digit, and special character.", passwordField);
            isValid = false;
        }

        if (!confirmPasswordField || passwordField.value !== confirmPasswordField.value) {
            fail("Password confirmation does not match.", confirmPasswordField);
            isValid = false;
        }

        if (!birthdateField || !birthdateField.value) {
            fail("Please enter your birthdate.", birthdateField);
            isValid = false;
        } else {
            const birthDate = new Date(birthdateField.value);
            const today = new Date();
            const age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            const dayDiff = today.getDate() - birthDate.getDate();
            const is18 = age > 18 || (age === 18 && (monthDiff > 0 || (monthDiff === 0 && dayDiff >= 0)));

            if (!is18) {
                fail("You must be 18 years or older.", birthdateField);
                isValid = false;
            }
        }

        return isValid;
    }

    function fail(message, field) {
        if (!field) return;
        field.classList.add('field-error');
        const error = document.createElement('div');
        error.classList.add('error-message');
        error.textContent = message;
        error.style.color = '#D84315';
        field.parentElement.appendChild(error);
    }
});

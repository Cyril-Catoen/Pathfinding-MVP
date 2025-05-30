/*Event submit*/
const testFormBeforeSubmit = document.getElementById(`submitBtn`)
const form = document.querySelector(`form`)
testFormBeforeSubmit.addEventListener("click", function(event) {
    event.preventDefault(); // Empêche l'envoi du formulaire si la validation échoue
    if (ValidateForm()) {
        form.submit() // Envoie le formulaire après validation réussie
    }
})


/*functions*/

/*Check that all the form is completed and true*/
function ValidateForm() {
    const nameField = document.getElementById('name');
    const surnameField = document.getElementById('surname');
    const emailField = document.getElementById('email');
    const phoneField = document.getElementById('phone');
    const countryField = document.getElementById('country');
    const majorityCheckbox = document.getElementById('declarationOfMajority'); // Peut être null

    // Nettoyer les anciennes erreurs
    [nameField, surnameField, emailField, phoneField, countryField].forEach(field => {
        field.classList.remove('field-error');
        const existingError = field.parentElement.querySelector('.error-message');
        if (existingError) existingError.remove();
    });

    let isValid = ValidateInput(surnameField, nameField, emailField, phoneField, countryField);

    // Vérification spécifique pour la création uniquement
    if (majorityCheckbox && !majorityCheckbox.checked) {
        const label = document.querySelector('label[for="declarationOfMajority"]');
        if (label && !label.nextElementSibling?.classList.contains('error-message')) {
            const error = document.createElement('div');
            error.classList.add('error-message');
            error.textContent = "You must confirm that the contact is of legal age.";
            error.style.color = '#D84315';
            label.insertAdjacentElement('afterend', error);
        }
        isValid = false;
    }

    return isValid;
}

/* Check all the inputs */
function ValidateInput(surnameField, nameField, emailField, phoneField, countryField) {
    let isValid = true;

    const nameRegex = /^\p{L}+$/u;
    const emailRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
    const phoneRegex = /^\+[\d\s\-()]{10,}$/;

    if (!nameRegex.test(surnameField.value)) {
        fail("Surname must only contain alphabetical letters.", surnameField);
        isValid = false;
    }

    if (!nameRegex.test(nameField.value)) {
        fail("Name must only contain alphabetical letters.", nameField);
        isValid = false;
    }

    if (!emailRegex.test(emailField.value) || emailField.value.length <= 4) {
        fail("Email must contain more than 4 characters, at least one dot (.) and one @.", emailField);
        isValid = false;
    }

    if (!phoneRegex.test(phoneField.value) || phoneField.value.length <= 10) {
        fail("Phone number must contain more than 10 numbers and at least one + with the country indicator.", phoneField);
        isValid = false;
    }

    if (countryField.value === "" || countryField.value === "default") {
        fail("Please, select a country.", countryField);
        isValid = false;
    }

    return isValid;
}

/* Centralize errors */
function fail(message, field) {
    console.log("Validation failed: " + message);
    field.classList.add('field-error');
     // Supprimer un ancien message s’il existe
    const existingError = field.parentElement.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }

    // Créer et afficher le nouveau message
    const error = document.createElement('div');
    error.classList.add('error-message');
    error.textContent = message;
    field.parentElement.appendChild(error);

    return false;
}
const testFormBeforeSubmit = document.getElementById('submitBtn');
const form = document.querySelector('form');

testFormBeforeSubmit.addEventListener("click", function (event) {
    event.preventDefault();
    if (ValidateAdventureForm()) {
        form.submit();
    }
});

function ValidateAdventureForm() {
    const titleField = document.getElementById('title');
    const startField = document.getElementById('start_date');
    const endField = document.getElementById('end_date');
    const safetyToggle = document.getElementById('toggleButtonSafety');
    const durationField = document.getElementById('duration-input');

    const fields = [titleField, startField, endField];
    fields.forEach(field => {
        field.classList.remove('field-error');
        const existingError = field.parentElement.querySelector('.error-message');
        if (existingError) existingError.remove();
    });

    let isValid = true;

    // Titre non vide
    if (!titleField.value.trim()) {
        fail("Le titre est obligatoire.", titleField);
        isValid = false;
    }

    // Dates valides
    const startDate = new Date(startField.value);
    const endDate = new Date(endField.value);

    if (!startField.value || !endField.value || startDate >= endDate) {
        fail("La date de début doit être antérieure à la date de fin.", endField);
        isValid = false;
    }

    // Safety toggle + durée
    if (safetyToggle.checked) {
        if (!durationField.value || durationField.value === '00:00:00') {
            fail("Vous devez définir une durée si Safety Alert est activée.", durationField);
            isValid = false;
        }
    }

    return isValid;
}

function fail(message, field) {
    field.classList.add('field-error');

    // Supprimer les anciens messages
    const existingError = field.parentElement.querySelector('.error-message');
    if (existingError) existingError.remove();

    // Créer et insérer le nouveau message
    const error = document.createElement('div');
    error.classList.add('error-message');
    error.textContent = message;
    error.style.color = '#D84315';
    error.style.marginTop = '0.25rem';
    field.parentElement.appendChild(error);
}

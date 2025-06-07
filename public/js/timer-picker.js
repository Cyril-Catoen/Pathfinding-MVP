const durationInput = document.getElementById('duration-input');
const durationPicker = document.getElementById('duration-picker');

// Toggle affichage du picker
durationInput.addEventListener('click', (e) => {
  e.stopPropagation(); // Évite la fermeture immédiate par l'autre script
  durationPicker.style.display = durationPicker.style.display === 'flex' ? 'none' : 'flex';
});

// Sélection des heures, minutes, secondes
document.querySelectorAll('.picker-option').forEach(el => {
  el.addEventListener('click', () => {
    const type = el.getAttribute('data-type');
    const val = el.textContent;
    const parts = durationInput.value.split(':');

    // Ensure all three parts (h, m, s) are always present
    while (parts.length < 3) parts.push('00');

    if (type === 'h') parts[0] = val;
    if (type === 'm') parts[1] = val;
    if (type === 's') parts[2] = val;

    if (parseInt(parts[0],10) > 72) parts[0] = "72";
    durationInput.value = parts.join(':');
    document.getElementById('duration-picker').style.display = 'none';
  });
});

// Fermeture si clic à l'extérieur (en ignorant les popups externes comme le sélecteur d'aventures)
document.addEventListener('click', (e) => {
  const clickedInsidePicker = durationPicker.contains(e.target);
  const clickedOnInput = durationInput.contains(e.target);
  const clickedInAdventurePopup = e.target.closest('#adventure-popup') !== null;
  const clickedOnAdventureInput = e.target.closest('#adventure-input') !== null;

  if (!clickedInsidePicker && !clickedOnInput && !clickedInAdventurePopup && !clickedOnAdventureInput) {
    durationPicker.style.display = 'none';
  }
});

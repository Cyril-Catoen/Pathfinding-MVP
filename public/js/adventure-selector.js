const adventureInput = document.getElementById('adventure-input');
const adventurePopup = document.getElementById('adventure-popup');
const checkboxes = adventurePopup?.querySelectorAll('input[type="checkbox"]') || [];

// Toggle l'affichage
adventureInput?.addEventListener('click', (e) => {
  e.stopPropagation();
  adventurePopup?.classList.toggle('visible');
});

// Ferme si clic à l'extérieur
document.addEventListener('click', (e) => {
  const isClickInsidePopup = adventurePopup?.contains(e.target);
  const isClickOnInput = e.target === adventureInput;
  const isClickInsideTimer = e.target.closest('#duration-picker') !== null;

  if (!isClickInsidePopup && !isClickOnInput && !isClickInsideTimer) {
    adventurePopup?.classList.remove('visible');
    updateInputValue();
  }
});

function updateInputValue() {
  if (!adventureInput) return;

  const selected = [...checkboxes]
    .filter(cb => cb.checked)
    .map(cb => cb.value);
  adventureInput.value = selected.join(', ');
}

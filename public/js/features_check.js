document.querySelectorAll('.toggle-btn input[type="checkbox"]').forEach(toggle => {
  const slider = toggle.nextElementSibling;
  const btnText = slider.querySelector('.btn-text');

  // Fonction pour mettre à jour le texte
  function updateText() {
      if (toggle.checked) {
          btnText.textContent = "Yes";
      } else {
          btnText.textContent = "No";
      }
  }

  // On initialise au chargement
  updateText();

  // On met à jour au changement
  toggle.addEventListener('change', updateText);
});

document.getElementById('myForm').addEventListener('submit', function(event) {
  event.preventDefault();
  console.log('Safety Alert:', document.getElementById('toggleButtonSafety').checked);
  console.log('Live Track:', document.getElementById('toggleButtonLive').checked);
});

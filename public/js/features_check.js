document.querySelectorAll('.toggle-btn input[type="checkbox"]').forEach(toggle => {
  const slider = toggle.nextElementSibling;
  const btnText = slider?.querySelector('.btn-text');

  function updateText() {
    if (btnText) {
      btnText.textContent = toggle.checked ? "Yes" : "No";
    }
  }

  updateText();
  toggle.addEventListener('change', updateText);
});

// // Formulaire : on vérifie qu'il existe avant de manipuler
// const form = document.querySelector('form');
// if (form) {
//   form.addEventListener('submit', function (event) {
//     const safetyToggle = document.getElementById('toggleButtonSafety');
//     const liveToggle = document.getElementById('toggleButtonLive');

//     console.log('Safety Alert:', safetyToggle?.checked);
//     console.log('Live Track:', liveToggle?.checked);
//   });
// }

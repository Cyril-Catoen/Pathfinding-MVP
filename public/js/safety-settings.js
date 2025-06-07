document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('safety-alert-toggle-form');
  const timerUrl = form.dataset.timerUrl;
  const contactUrl = form.dataset.contactUrl;
  const toggleUrl = form.dataset.toggleUrl;
  const safetyToggle = document.getElementById('safetyEnabled');
  const safetyDetails = document.getElementById('safety-details');
  const durationInput = document.getElementById('duration-input');
  const contactSelect = document.getElementById('contact-list-select');
  const durationPicker = document.getElementById('duration-picker');




  // Toggle SafetyAlert
  safetyToggle.addEventListener('change', function () {
    const enabled = safetyToggle.checked;
    fetch(toggleUrl, {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'enabled=' + enabled
    })
    .then(res => res.json())
    .then(data => {
    if (data.success && enabled) {
        safetyDetails.classList.add('active');
        if (data.timerDuration) {
        durationInput.value = data.timerDuration; // Montre la durée par défaut (24:00:00, ou ce qui est stocké)
        }
        // Réinitialise la valeur de contact list à Default List lorsque l'on active Safety Alert
        if (data.defaultListId) {
            contactSelect.value = data.defaultListId;
        }
    } else {
        safetyDetails.classList.remove('active');
    }
    });
  });

  // Show duration picker on input click
  durationInput.addEventListener('click', function () {
    durationPicker.style.display = 'flex';
  });

  // Pick duration (basic version, improve if needed)
  durationPicker.addEventListener('click', function (e) {
    if (!e.target.classList.contains('picker-option')) return;
    let h = durationPicker.querySelector('[data-type="h"].selected')?.textContent || '24';
    let m = durationPicker.querySelector('[data-type="m"].selected')?.textContent || '00';
    let s = durationPicker.querySelector('[data-type="s"].selected')?.textContent || '00';

    if (e.target.dataset.type === 'h') h = e.target.textContent;
    if (e.target.dataset.type === 'm') m = e.target.textContent;
    if (e.target.dataset.type === 's') s = e.target.textContent;

    durationPicker.querySelectorAll('.picker-option').forEach(opt => opt.classList.remove('selected'));
    e.target.classList.add('selected');
    durationInput.value = `${h}:${m}:${s}`;

    // AJAX update Timer
    fetch(timerUrl, {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded'},
      body: `hours=${parseInt(h,10)}&minutes=${parseInt(m,10)}&seconds=${parseInt(s,10)}`
    }).then(res => res.json())
      .then(data => {
        if (data.success) {
        durationInput.value = data.timerDuration || '00:00:00'; // Affiche la durée (HH:mm:ss)
        }
      });
    durationPicker.style.display = 'none';
  });

  // Change contact list
  contactSelect.addEventListener('change', function () {
    const listId = contactSelect.value;
    fetch(contactUrl, {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded'},
      body: `contact_list_id=${listId}`
    });
  });

  // Optional: hide duration picker on outside click
  document.addEventListener('click', function (e) {
    if (!durationPicker.contains(e.target) && e.target !== durationInput) {
      durationPicker.style.display = 'none';
    }
  });
});

document.addEventListener('DOMContentLoaded', function() {
    // UPLOAD TRACK (AJAX)
    const trackForm = document.getElementById('adventure-track-upload-form');
    const trackInput = document.getElementById('track-file-input');
    const feedback = document.getElementById('track-upload-feedback');
    if (trackForm) {
        trackForm.addEventListener('submit', function(e) {
            e.preventDefault();
            feedback.textContent = "";
            const data = new FormData();
            data.append('track', trackInput.files[0]);
            fetch(trackForm.action, {
                method: 'POST',
                body: data
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    feedback.textContent = "Trace GPS importée !";
                    window.location.reload();
                } else {
                    feedback.textContent = res.error || "Erreur.";
                }
            });
        });
    }

    // LEAFLET MAP
    let map = L.map('mapid');
    let points = [];
    try {
        points = JSON.parse(document.getElementById('mapid').dataset.points);
    } catch(e) {}
    let defaultLatLng = [42, 43]; // Géorgie !
    if (points.length > 0) {
        let latlngs = points.map(pt => [pt.latitude, pt.longitude]);
        map.setView(latlngs[0], 13);
        L.polyline(latlngs, {color: 'blue'}).addTo(map);
        L.marker(latlngs[0]).addTo(map).bindPopup("Départ");
        L.marker(latlngs.at(-1)).addTo(map).bindPopup("Arrivée");
        map.fitBounds(latlngs);
    } else {
        // fallback : Géorgie !
        map.setView([42.3154, 43.3569], 6);
    }
    L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        maxZoom: 17,
        attribution: 'Map data © OpenTopoMap'
    }).addTo(map);
});

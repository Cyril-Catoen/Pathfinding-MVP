let map; // 👈 portée globale

document.addEventListener('DOMContentLoaded', function() {
    const trackForm = document.getElementById('adventure-track-upload-form');
    const trackInput = document.getElementById('track-file-input');
    const mapEl = document.getElementById('mapid');
    if (!mapEl) return;
    const pointsUrl = mapEl.dataset.pointsUrl;
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
                    feedback.textContent = "Fichier GPS ajouté avec succès !";

                    fetch(pointsUrl)
                        .then(r => r.json())
                        .then(data => {
                            updateMapWithPoints(data.points); // 👈 map est maintenant accessible
                        });

                    trackForm.reset(); // ✅ corrigé ici
                } else {
                    feedback.textContent = res.error || "Erreur.";
                }
            });
        });
    }

    map = L.map('mapid'); // 👈 plus de let ici, on utilise la variable globale

    let points = [];
    try {
        points = JSON.parse(mapEl.dataset.points);
    } catch(e) {}

    let defaultLatLng = [42, 43];
    if (points.length > 0) {
        let latlngs = points.map(pt => [pt.latitude, pt.longitude]);
        map.setView(latlngs[0], 13);
        L.polyline(latlngs, {color: 'blue'}).addTo(map);
        L.marker(latlngs[0]).addTo(map).bindPopup("Départ");
        L.marker(latlngs.at(-1)).addTo(map).bindPopup("Arrivée");
        map.fitBounds(latlngs);
    } else {
        map.setView([42.3154, 43.3569], 6);
    }

    L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        maxZoom: 17,
        attribution: 'Map data © OpenTopoMap'
    }).addTo(map);
});

function updateMapWithPoints(points) {
    if (window.gpsTrackLayer) {
        map.removeLayer(window.gpsTrackLayer); // ✅ map maintenant visible ici
    }
    if (!points.length) return;

    const latlngs = points.map(pt => [pt.lat, pt.lng]);
    // Supprimer les anciens marqueurs s'ils existent
    if (window.startMarker) map.removeLayer(window.startMarker);
    if (window.endMarker) map.removeLayer(window.endMarker);

    // Nouveau tracé
    window.gpsTrackLayer = L.polyline(latlngs, {color: 'blue'}).addTo(map);

    // Marqueurs dynamique
    window.startMarker = L.marker(latlngs[0]).addTo(map).bindPopup("Départ");
    window.endMarker = L.marker(latlngs.at(-1)).addTo(map).bindPopup("Arrivée");

    // Zoom automatique
    map.fitBounds(window.gpsTrackLayer.getBounds());

}


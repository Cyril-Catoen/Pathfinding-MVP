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
                    feedback.textContent = "Fichier GPS ajouté avec succès !";

                    // Recharge les points depuis le serveur (route twig renvoyant du JSON)
                    fetch(`/Pathfinding-MVP/public/user/adventure/${adventureId}/points`)
                        .then(r => r.json())
                        .then(data => {
                            // Mets à jour la carte et/ou la liste en front JS
                            updateMapWithPoints(data.points);
                        });

                    // Reset le formulaire d’upload
                    form.reset();
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

function updateMapWithPoints(points) {
    // Supprime l’ancien tracé
    if (window.gpsTrackLayer) {
        map.removeLayer(window.gpsTrackLayer);
    }
    if (!points.length) return;

    // Construit un array de LatLng
    const latlngs = points.map(pt => [pt.lat, pt.lng]);
    window.gpsTrackLayer = L.polyline(latlngs, {color: 'blue'}).addTo(map);

    // Fit bounds (zoom auto)
    map.fitBounds(window.gpsTrackLayer.getBounds());
}


let map; // 👈 portée globale

document.addEventListener('DOMContentLoaded', function () {
    const mapEl = document.getElementById('mapid');
    if (!mapEl) return;

    map = L.map('mapid'); // initialisation

    let points = [];
    try {
        points = JSON.parse(mapEl.dataset.points);
    } catch (e) {}

    if (points.length > 0) {
        let latlngs = points.map(pt => [pt.latitude, pt.longitude]);
        map.setView(latlngs[0], 13);
        L.polyline(latlngs, { color: 'blue' }).addTo(map);
        L.marker(latlngs[0]).addTo(map).bindPopup("Départ");
        L.marker(latlngs.at(-1)).addTo(map).bindPopup("Arrivée");
        map.fitBounds(latlngs);
    } else {
        map.setView([42.3154, 43.3569], 6); // vue par défaut
    }

    L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        maxZoom: 17,
        attribution: 'Map data © OpenTopoMap'
    }).addTo(map);
});

function updateMapWithPoints(points) {
    if (window.gpsTrackLayer) {
        map.removeLayer(window.gpsTrackLayer);
    }
    if (!points.length) return;

    const latlngs = points.map(pt => [pt.lat, pt.lng]);

    if (window.startMarker) map.removeLayer(window.startMarker);
    if (window.endMarker) map.removeLayer(window.endMarker);

    window.gpsTrackLayer = L.polyline(latlngs, { color: 'blue' }).addTo(map);
    window.startMarker = L.marker(latlngs[0]).addTo(map).bindPopup("Départ");
    window.endMarker = L.marker(latlngs.at(-1)).addTo(map).bindPopup("Arrivée");

    map.fitBounds(window.gpsTrackLayer.getBounds());
}

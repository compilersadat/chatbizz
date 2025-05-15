@props(['lat' => 19.1545, 'lang' => 77.3210])

<div>
    <div id="map" style="height: 400px;" class="rounded-xl"></div>

    <input type="hidden" id="latitude" wire:model="data.lat" value="{{ $lat }}">
    <input type="hidden" id="longitude" wire:model="data.lang" value="{{ $lang }}">
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');

        const initialLat = parseFloat(latInput.value) || 19.1545;
        const initialLng = parseFloat(lngInput.value) || 77.3210;

        const map = L.map('map').setView([initialLat, initialLng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

        marker.on('dragend', function () {
            const pos = marker.getLatLng();
            latInput.value = pos.lat.toFixed(7);
            lngInput.value = pos.lng.toFixed(7);

            latInput.dispatchEvent(new Event('input'));
            lngInput.dispatchEvent(new Event('input'));
        });
    });
</script>

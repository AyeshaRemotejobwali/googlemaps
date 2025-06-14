<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map Explorer - Google Maps Clone</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <style>
        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            background: #f4f4f9;
            overflow: hidden;
        }
        #map {
            height: 100vh;
            width: 100%;
            z-index: 1;
        }
        .search-container {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            width: 400px;
            max-width: 90%;
        }
        .search-container input {
            width: calc(100% - 80px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            outline: none;
        }
        .search-container button {
            padding: 10px 20px;
            border: none;
            background: #1a73e8;
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 5px;
            transition: background 0.3s;
        }
        .search-container button:hover {
            background: #1557b0;
        }
        .directions-container {
            position: absolute;
            top: 100px;
            left: 20px;
            z-index: 1000;
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            width: 300px;
            max-width: 90%;
        }
        .directions-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .save-location {
            position: absolute;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background: #fff;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        .save-location input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 5px;
        }
        .save-location button {
            padding: 8px 15px;
            border: none;
            background: #34c759;
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .save-location button:hover {
            background: #2ea44f;
        }
        .saved-locations {
            position: absolute;
            top: 100px;
            right: 20px;
            z-index: 1000;
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            max-height: 300px;
            overflow-y: auto;
        }
        .saved-locations ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .saved-locations li {
            padding: 8px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.2s;
        }
        .saved-locations li:hover {
            background: #f0f0f0;
        }
        .street-view {
            position: absolute;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
            background: #fff;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        .street-view button {
            padding: 8px 15px;
            border: none;
            background: #ff5722;
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .street-view button:hover {
            background: #e64a19;
        }
        @media (max-width: 768px) {
            .search-container {
                width: 90%;
                top: 10px;
            }
            .directions-container, .saved-locations {
                width: 80%;
                left: 10px;
                right: 10px;
            }
            .save-location, .street-view {
                bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div id="map"></div>
    <div class="search-container">
        <input type="text" id="searchInput" placeholder="Search for a location...">
        <button onclick="searchLocation()">Search</button>
    </div>
    <div class="directions-container">
        <input type="text" id="start" placeholder="Starting point">
        <input type="text" id="end" placeholder="Destination">
        <button onclick="getDirections()">Get Directions</button>
    </div>
    <div class="save-location">
        <input type="text" id="locationName" placeholder="Location name">
        <button onclick="saveLocation()">Save Pin</button>
    </div>
    <div class="saved-locations">
        <h3>Saved Locations</h3>
        <ul id="savedLocationsList"></ul>
    </div>
    <div class="street-view">
        <button onclick="toggleStreetView()">Street View</button>
    </div>

    <script>
        // Initialize map
        let map = L.map('map').setView([51.505, -0.09], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map);

        let markers = [];
        let routingControl = null;
        let streetViewLayer = null;

        // Search location using Nominatim API
        async function searchLocation() {
            const query = document.getElementById('searchInput').value;
            if (!query) return;

            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&addressdetails=1`);
                const data = await response.json();
                if (data.length > 0) {
                    const { lat, lon, display_name } = data[0];
                    map.setView([lat, lon], 15);
                    addMarker(lat, lon, display_name);
                } else {
                    alert('Location not found!');
                }
            } catch (error) {
                console.error('Error searching location:', error);
            }
        }

        // Add marker to map
        function addMarker(lat, lng, title) {
            const marker = L.marker([lat, lng]).addTo(map)
                .bindPopup(title)
                .openPopup();
            markers.push(marker);
        }

        // Get directions
        function getDirections() {
            const start = document.getElementById('start').value;
            const end = document.getElementById('end').value;
            if (!start || !end) return;

            if (routingControl) {
                map.removeControl(routingControl);
            }

            Promise.all([
                fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(start)}&format=json`).then(res => res.json()),
                fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(end)}&format=json`).then(res => res.json())
            ]).then(([startData, endData]) => {
                if (startData.length > 0 && endData.length > 0) {
                    const startCoords = [startData[0].lat, startData[0].lon];
                    const endCoords = [endData[0].lat, endData[0].lon];

                    routingControl = L.Routing.control({
                        waypoints: [
                            L.latLng(startCoords[0], startCoords[1]),
                            L.latLng(endCoords[0], endCoords[1])
                        ],
                        routeWhileDragging: true
                    }).addTo(map);
                } else {
                    alert('Could not find one or both locations!');
                }
            }).catch(error => {
                console.error('Error getting directions:', error);
            });
        }

        // Save location to database
        async function saveLocation() {
            const name = document.getElementById('locationName').value;
            const center = map.getCenter();
            const lat = center.lat;
            const lng = center.lng;

            if (!name) {
                alert('Please enter a location name!');
                return;
            }

            try {
                const response = await fetch('save_location.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, lat, lng })
                });
                const result = await response.json();
                if (result.success) {
                    loadSavedLocations();
                    addMarker(lat, lng, name);
                } else {
                    alert('Error saving location!');
                }
            } catch (error) {
                console.error('Error saving location:', error);
            }
        }

        // Load saved locations
        async function loadSavedLocations() {
            try {
                const response = await fetch('get_locations.php');
                const locations = await response.json();
                const list = document.getElementById('savedLocationsList');
                list.innerHTML = '';
                locations.forEach(loc => {
                    const li = document.createElement('li');
                    li.textContent = loc.name;
                    li.onclick = () => map.setView([loc.lat, loc.lng], 15);
                    list.appendChild(li);
                });
            } catch (error) {
                console.error('Error loading locations:', error);
            }
        }

        // Toggle street view (simulated with different tile layer)
        function toggleStreetView() {
            if (streetViewLayer) {
                map.removeLayer(streetViewLayer);
                streetViewLayer = null;
            } else {
                streetViewLayer = L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                }).addTo(map);
            }
        }

        // Load saved locations on page load
        loadSavedLocations();

        // Handle redirection (if needed)
        function redirectTo(url) {
            window.location.href = url;
        }
    </script>
</body>
</html>

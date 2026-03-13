<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- PWA Manifest --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#4f46e5">

    <title>GCSBR - Map Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        #map {
            height: calc(100vh - 64px);
            width: 100%;
        }

        .legend {
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body class="bg-gray-100 h-screen overflow-hidden">

    <nav class="bg-white shadow-md h-16 flex items-center justify-between px-6 z-50 relative">
        <div class="flex items-center space-x-4">
            <a href="{{ route('units.index') }}" class="text-gray-500 hover:text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <h1 class="text-xl font-bold text-blue-800">Peta Sebaran Unit</h1>
        </div>

        <div class="flex space-x-2">
            <select id="kecFilter" class="border p-1 rounded text-sm w-32">
                <option value="">Semua Kec</option>
                @foreach($kecNames as $code => $name)
                    <option value="{{ $code }}">{{ $name }}</option>
                @endforeach
            </select>
            <button onclick="loadMapData()"
                class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">Load</button>
        </div>
    </nav>

    <div id="map"></div>

    <script>
        // Initialize Map
        // Default Center: Malang/Indonesia (Adjust to your region)
        var map = L.map('map').setView([-7.98, 112.63], 11);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var markersLayer = L.layerGroup().addTo(map);

        function loadMapData() {
            var kdkec = document.getElementById('kecFilter').value;
            var url = "{{ route('api.map_data') }}";
            if (kdkec) {
                url += "?kdkec=" + kdkec;
            }

            markersLayer.clearLayers();

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    L.geoJSON(data, {
                        pointToLayer: function (feature, latlng) {
                            var color = feature.properties.color;
                            return L.circleMarker(latlng, {
                                radius: 6,
                                fillColor: color,
                                color: "#fff",
                                weight: 1,
                                opacity: 1,
                                fillOpacity: 0.8
                            });
                        },
                        onEachFeature: function (feature, layer) {
                            var props = feature.properties;
                            var popupContent = `<strong>${props.name}</strong><br>
                                            Status: ${props.status}<br>
                                            Kondisi: ${props.condition}<br>
                                            <a href="/?search=${props.id}" target="_blank" class="text-blue-600 underline text-xs">Lihat Detail</a>`;
                            layer.bindPopup(popupContent);
                        }
                    }).addTo(markersLayer);

                    // Fit bounds if data exists
                    /* 
                    // Need a way to convert FeatureCollection to bounds or just loop points
                    // For now user can zoom
                    */
                })
                .catch(err => console.error("Error loading map data:", err));
        }

        // Load initial
        loadMapData();

    </script>

</body>

</html>
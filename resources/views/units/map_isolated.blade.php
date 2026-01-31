<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map Isolated</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />

    <style>
        /* Modern Reset */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body,
        html {
            height: 100%;
            width: 100%;
            overflow: hidden;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #1a1a1a;
        }

        #map {
            height: 100%;
            width: 100%;
            z-index: 1;
        }

        /* --- CUSTOM MARKERS (Premium Look) --- */

        /* Kecamatan Marker (Big Circles) */
        .marker-kec-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease;
        }

        .marker-kec-container:hover {
            transform: scale(1.1);
            z-index: 1000 !important;
        }

        .marker-kec-bubble {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border: 3px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            position: relative;
        }

        .marker-kec-name {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
            margin-bottom: 2px;
            text-align: center;
            line-height: 1;
        }

        .marker-kec-count {
            font-size: 16px;
            font-weight: 800;
        }

        .marker-bg-glow {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            animation: pulse-blue 2s infinite;
            z-index: -1;
        }

        /* Desa Marker (Medium Circles) */
        .marker-desa-bubble {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #10b981;
            /* Green */
            border: 2px solid white;
            box-shadow: 0 3px 10px rgba(16, 185, 129, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 13px;
            font-weight: 700;
            transition: transform 0.2s;
            cursor: pointer;
        }

        .marker-desa-bubble:hover {
            transform: scale(1.15);
            background: #059669;
        }

        .marker-desa-bubble.empty {
            background: #6b7280;
            /* Gray */
        }

        /* Tooltip Premium */
        .leaflet-tooltip.custom-tooltip {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 12px;
            border-radius: 6px;
            padding: 4px 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .leaflet-tooltip-top:before {
            border-top-color: rgba(15, 23, 42, 0.9);
        }

        /* Legend */
        .map-overlay-legend {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.95);
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            font-size: 12px;
            z-index: 1000;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(0, 0, 0, 0.05);
            min-width: 140px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            color: #334155;
            font-weight: 500;
        }

        .legend-item:last-child {
            margin-bottom: 0;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .dot-active {
            background: #10B981;
            box-shadow: 0 0 5px #10B981;
        }

        .dot-temp {
            background: #FBBF24;
        }

        .dot-closed {
            background: #EF4444;
        }

        @keyframes pulse-blue {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
            }

            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
            }

            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
            }
        }

        /* Reset Button */
        .btn-reset {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: white;
            border: 1px solid #e2e8f0;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            color: #475569;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.2s;
            display: none;
        }

        .btn-reset:hover {
            background: #f8fafc;
            color: #1e293b;
        }
    </style>
</head>

<body>

    <div id="map"></div>

    <!-- Legend -->
    <div class="map-overlay-legend">
        <div class="legend-item"><span class="dot" style="background: #3b82f6;"></span> Total Usaha</div>
        <div class="legend-item"><span class="dot dot-active"></span> Aktif</div>
        <div class="legend-item"><span class="dot dot-temp"></span> Tutup Sem.</div>
        <div class="legend-item"><span class="dot dot-closed"></span> Tutup</div>
    </div>

    <!-- Reset Button (Internal) -->
    <button id="btnReset" class="btn-reset" onclick="renderKecamatanView()">
        ← Kembali ke Kabupaten
    </button>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Turf.js/6.5.0/turf.min.js"></script>

    <script>
        let map, geojsonLayer, markersLayer;
        let slsDataGeoJSON = null;
        let unitStats = [];
        let currentLevel = 'KAB';
        let currentKecCode = null;

        const defaultCenter = [-1.7, 103.1];
        const defaultZoom = 9;

        document.addEventListener('DOMContentLoaded', async () => {
            initMap();
        });

        async function initMap() {
            // Dark Mode Tile Layer
            map = L.map('map', { zoomControl: false }).setView(defaultCenter, defaultZoom);
            L.control.zoom({ position: 'topright' }).addTo(map);

            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap &copy; CARTO',
                subdomains: 'abcd',
                maxZoom: 20
            }).addTo(map);

            try {
                // Fetch Data
                const [geoRes, unitRes] = await Promise.all([
                    fetch('{{ url("/sls_1504.geojson") }}'),
                    fetch('{{ route("api.map_stats") }}')
                ]);

                if (!geoRes.ok) throw new Error("Load GeoJSON failed");
                slsDataGeoJSON = await geoRes.json();

                if (unitRes.ok) {
                    unitStats = await unitRes.json();
                }

                renderKecamatanView();

            } catch (err) {
                console.error(err);
                alert("Gagal memuat data peta.");
            }
        }

        // --- VIEW LOGIC ---

        function renderKecamatanView() {
            currentLevel = 'KAB';
            document.getElementById('btnReset').style.display = 'none';
            updateParentBreadcrumb('');

            clearLayers();
            map.flyTo(defaultCenter, 9);

            // Group by Kecamatan
            const kecGroups = {};
            slsDataGeoJSON.features.forEach(f => {
                const kdkec = f.properties.nmkec;
                const fullId = String(f.properties.idsls);
                const kecCodeFull = fullId.substring(0, 7);
                if (!kecGroups[kdkec]) kecGroups[kdkec] = { name: kdkec, polys: [], code: kecCodeFull };
                kecGroups[kdkec].polys.push(f);
            });

            Object.values(kecGroups).forEach(group => {
                const center = calculateCentroid(group.polys);
                const rawKecCode = group.code.substring(4, 7);
                const total = unitStats.filter(u => String(u.kdkec).padStart(3, '0') === rawKecCode).length;

                const icon = L.divIcon({
                    className: 'custom-div-icon', // Empty class, styling in container
                    html: `
                        <div class="marker-kec-container">
                            <div class="marker-bg-glow"></div>
                            <div class="marker-kec-bubble">
                                <span class="marker-kec-name">${group.name.replace('BATANG HARI', '')}</span>
                                <span class="marker-kec-count">${total}</span>
                            </div>
                        </div>
                    `,
                    iconSize: [60, 60],
                    iconAnchor: [30, 30]
                });

                L.marker([center[1], center[0]], { icon: icon })
                    .addTo(map)
                    .on('click', () => renderDesaView(group.code, group.name, center));
            });
        }

        function renderDesaView(kecCodeFull, kecName, center) {
            currentLevel = 'KEC';
            document.getElementById('btnReset').style.display = 'block';
            updateParentBreadcrumb(`> ${kecName}`);

            clearLayers();
            map.flyTo([center[1], center[0]], 11);

            const desaGroups = {};
            slsDataGeoJSON.features.forEach(f => {
                const fullId = String(f.properties.idsls);
                if (!fullId.startsWith(kecCodeFull)) return;
                const nmdesa = f.properties.nmdesa;
                const desaCodeFull = fullId.substring(0, 10);
                if (!desaGroups[nmdesa]) desaGroups[nmdesa] = { name: nmdesa, polys: [], code: desaCodeFull };
                desaGroups[nmdesa].polys.push(f);
            });

            Object.values(desaGroups).forEach(group => {
                const dCenter = calculateCentroid(group.polys);
                const rawDesaCode = group.code.substring(4);
                const count = unitStats.filter(u => u.full_desa_code === rawDesaCode).length;
                const isEmpty = count === 0;

                const icon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `
                        <div class="marker-desa-bubble ${isEmpty ? 'empty' : ''}">
                            ${count}
                        </div>
                    `,
                    iconSize: [44, 44],
                    iconAnchor: [22, 22]
                });

                L.marker([dCenter[1], dCenter[0]], { icon: icon })
                    .addTo(map)
                    .bindTooltip(group.name, { className: 'custom-tooltip', direction: 'top', offset: [0, -25] })
                    .on('click', () => renderSlsView(group.code, group.name, dCenter));
            });
        }

        function renderSlsView(desaCodeFull, desaName, center) {
            currentLevel = 'DESA';
            updateParentBreadcrumb(`> ${desaName}`);

            clearLayers();
            map.flyTo([center[1], center[0]], 14);

            // 1. Polygons
            const slsFeatures = slsDataGeoJSON.features.filter(f => String(f.properties.idsls).startsWith(desaCodeFull));

            geojsonLayer = L.geoJSON(slsFeatures, {
                style: function (f) {
                    const slsId = String(f.properties.idsls);
                    const count = unitStats.filter(u => String(u.sls_id) === slsId).length;
                    // Neon Green lines for active SLS
                    return {
                        color: count > 0 ? '#10B981' : '#475569',
                        weight: 1,
                        opacity: 0.8,
                        fillColor: count > 0 ? '#10B981' : 'transparent',
                        fillOpacity: count > 0 ? 0.1 : 0
                    };
                },
                onEachFeature: function (f, layer) {
                    layer.bindTooltip(f.properties.nmsls, { className: 'custom-tooltip', sticky: true });
                }
            }).addTo(map);

            // 2. Unit Markers (Circles)
            const rawDesaCode = desaCodeFull.substring(4);
            const units = unitStats.filter(u => u.full_desa_code === rawDesaCode);

            const cluster = L.markerClusterGroup({
                disableClusteringAtZoom: 16,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
                zoomToBoundsOnClick: true
            });

            units.forEach(u => {
                if (!u.lat || !u.lng) return;
                const color = getColorByStatus(u.status);

                const marker = L.circleMarker([u.lat, u.lng], {
                    radius: 6,
                    fillColor: color,
                    color: '#fff',
                    weight: 1,
                    opacity: 1,
                    fillOpacity: 0.9
                }).bindPopup(`
                    <div style="font-family: sans-serif; color: #333;">
                        <strong>${u.name}</strong><br>
                        <span style="font-size: 11px; color: #666;">ID: ${u.id}</span>
                    </div>
                `);

                cluster.addLayer(marker);
            });
            map.addLayer(cluster);
            markersLayer = cluster;
        }

        // --- UTILS ---

        function clearLayers() {
            map.eachLayer(l => {
                if (l instanceof L.TileLayer) return;
                map.removeLayer(l);
            });
        }

        function calculateCentroid(features) {
            if (typeof turf !== 'undefined') {
                return turf.center(turf.featureCollection(features)).geometry.coordinates;
            }
            return defaultCenter.slice().reverse();
        }

        function getColorByStatus(s) {
            const map = { 1: '#10B981', 2: '#FBBF24', 4: '#EF4444' };
            return map[s] || '#9CA3AF';
        }

        function updateParentBreadcrumb(text) {
            try {
                if (window.parent && window.parent.document) {
                    const el = window.parent.document.getElementById('breadcrumb');
                    if (el) el.innerText = text;
                }
            } catch (e) { }
        }
    </script>
</body>

</html>
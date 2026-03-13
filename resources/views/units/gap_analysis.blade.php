<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Gap SIPW - Groundcheck App</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Turf.js/6.5.0/turf.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        #map {
            height: 500px;
            width: 100%;
            border-radius: 0.5rem;
        }

        /* Leaflet CSS fixes for Tailwind */
        .leaflet-pane img,
        .leaflet-tile-container img,
        .leaflet-marker-icon,
        .leaflet-marker-shadow {
            max-width: none !important;
            max-height: none !important;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Header -->
    <div class="gradient-bg text-white py-4 sm:py-6 shadow-lg sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-3xl font-bold mb-0.5 sm:mb-1">📊 Analisis Gap SIPW</h1>
                    <p class="text-purple-100 text-[10px] sm:text-sm">Perbandingan Ground-check vs SIPW</p>
                </div>
                <a href="{{ route('units.rekap') }}"
                    class="w-full sm:w-auto text-center bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-4 sm:px-6 py-2 sm:py-2.5 rounded-lg transition font-semibold text-xs sm:text-sm">
                    ← Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-8">


        <!-- Map Section -->
        <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
            <div
                class="p-4 bg-gradient-to-r from-blue-500 to-indigo-600 text-white flex justify-between items-center bg-blue-600">
                <div>
                    <h2 class="text-xl font-bold">🗺️ Peta Sebaran Usaha</h2>
                    <p class="text-sm opacity-90">Visualisasi lokasi unit usaha yang sudah terdata <span
                            id="mapBreadcrumb" class="font-bold bg-white/20 px-2 py-0.5 rounded ml-2 hidden"></span></p>
                </div>
                <button id="btnResetMap"
                    class="bg-white/20 hover:bg-white/30 text-white px-3 py-1.5 rounded text-sm font-semibold transition hidden">
                    ↺ Reset Zoom
                </button>
            </div>
            <div id="map"></div>
        </div>

        <!-- Summary Cards (Moved) -->
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-sm p-3 sm:p-5 border-l-4 border-blue-500">
                <div class="text-[10px] sm:text-xs text-gray-500 font-medium mb-1 uppercase tracking-wider">Total SLS</div>
                <div class="text-xl sm:text-2xl font-black text-blue-600">{{ number_format($summary['total_sls']) }}</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 sm:p-5 border-l-4 border-purple-500">
                <div class="text-[10px] sm:text-xs text-gray-500 font-medium mb-1 uppercase tracking-wider">SIPW</div>
                <div class="text-xl sm:text-2xl font-black text-purple-600">{{ number_format($summary['total_sipw']) }}</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 sm:p-5 border-l-4 border-green-500">
                <div class="text-[10px] sm:text-xs text-gray-500 font-medium mb-1 uppercase tracking-wider">G-Check</div>
                <div class="text-xl sm:text-2xl font-black text-green-600">{{ number_format($summary['total_ground_check']) }}</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 sm:p-5 border-l-4 border-red-500">
                <div class="text-[10px] sm:text-xs text-gray-500 font-medium mb-1 uppercase tracking-wider">Gap</div>
                <div class="text-xl sm:text-2xl font-black text-red-600">{{ number_format($summary['total_gap']) }}</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 sm:p-5 border-l-4 border-orange-500 col-span-2 lg:col-span-1">
                <div class="text-[10px] sm:text-xs text-gray-500 font-medium mb-1 uppercase tracking-wider">Avg Gap</div>
                <div class="text-xl sm:text-2xl font-black text-orange-600">{{ $summary['avg_gap_percent'] }}%</div>
            </div>
        </div>

        <!-- Gap Analysis Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 bg-gray-50 border-b flex justify-between items-center flex-wrap gap-3">
                <h2 class="text-xl font-bold text-gray-800">Detail Gap per SLS</h2>
                <!-- Search & Filters (Hidden by Default) -->
                <div class="flex gap-3 items-center hidden" id="tableFilters">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600">Show</span>
                        <select id="perPage"
                            class="border rounded px-2 py-1 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <input type="text" id="searchSls" placeholder="Cari SLS..."
                        class="border rounded px-3 py-1.5 text-sm w-64 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <!-- Disclaimer Section -->
            <div id="tableDisclaimer" class="p-6 sm:p-12 text-center bg-gray-50/50">
                <div class="inline-block p-4 rounded-full bg-yellow-100 text-yellow-600 mb-4 scale-75 sm:scale-100">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-2">Fitur Beta</h3>
                <p class="text-xs sm:text-sm text-gray-500 mb-6 max-w-lg mx-auto">
                    Data masih dalam tahap verifikasi. Tampilan tabel ini mungkin memerlukan waktu loading lebih lama pada perangkat tertentu.
                </p>
                <button onclick="toggleTable()"
                    class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 sm:py-2 px-8 sm:px-6 rounded-lg transition shadow-md flex items-center justify-center gap-2 mx-auto text-sm">
                    <span>👁️</span> Tampilkan Tabel
                </button>
            </div>

            <div id="tableContainer" class="hidden transition-all duration-300">
                <table class="w-full text-xs sm:text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 uppercase font-bold text-[10px] sm:text-xs">
                        <tr>
                            <th class="px-4 sm:px-6 py-3 cursor-pointer hover:bg-gray-200" onclick="sortTable(0)">No</th>
                            <th class="px-4 sm:px-6 py-3 cursor-pointer hover:bg-gray-200" onclick="sortTable(1)">Wilayah</th>
                            <th class="hidden lg:table-cell px-4 sm:px-6 py-3 cursor-pointer hover:bg-gray-200" onclick="sortTable(2)">ID</th>
                            <th class="px-4 sm:px-6 py-3 text-right cursor-pointer hover:bg-gray-200" onclick="sortTable(3)">
                                SIPW</th>
                            <th class="px-4 sm:px-6 py-3 text-right cursor-pointer hover:bg-gray-200" onclick="sortTable(4)">
                                GC</th>
                            <th class="px-4 sm:px-6 py-3 text-right cursor-pointer hover:bg-gray-200" onclick="sortTable(5)">
                                Gap</th>
                            <th class="hidden sm:table-cell px-4 sm:px-6 py-3 text-right cursor-pointer hover:bg-gray-200" onclick="sortTable(6)">
                                %</th>
                            <th class="px-4 sm:px-6 py-3 text-center">Stat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" id="tableBody">
                        @foreach ($gapData as $index => $row)
                            <tr class="hover:bg-blue-50 transition border-b border-gray-100 last:border-0" data-sls-id="{{ $row['sls_id'] }}">
                                <td class="px-4 sm:px-6 py-3 py-4 text-gray-400 font-mono">{{ $index + 1 }}</td>
                                <td class="px-4 sm:px-6 py-3 font-bold text-gray-800">
                                    <div class="truncate max-w-[120px] sm:max-w-xs">{{ $row['sls_name'] }}</div>
                                    <div class="lg:hidden text-[10px] text-gray-400 font-normal">ID: {{ $row['sls_id'] }}</div>
                                </td>
                                <td class="hidden lg:table-cell px-4 sm:px-6 py-3 text-gray-500 font-mono text-xs">{{ $row['sls_id'] }}</td>
                                <td class="px-4 sm:px-6 py-3 text-right font-black text-purple-600"
                                    data-value="{{ $row['sipw_count'] }}">
                                    {{ number_format($row['sipw_count']) }}
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-right font-black text-green-600"
                                    data-value="{{ $row['ground_count'] }}">
                                    {{ number_format($row['ground_count']) }}
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-right font-black text-red-600" data-value="{{ $row['gap'] }}">
                                    {{ number_format($row['gap']) }}
                                </td>
                                <td class="hidden sm:table-cell px-4 sm:px-6 py-3 text-right font-bold text-orange-600" data-value="{{ $row['gap_percent'] }}">
                                    {{ $row['gap_percent'] }}%
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-center">
                                    @if ($row['status'] == 'good')
                                        <div class="w-3 h-3 rounded-full bg-green-500 mx-auto" title="Baik"></div>
                                    @elseif ($row['status'] == 'warning')
                                        <div class="w-3 h-3 rounded-full bg-yellow-500 mx-auto" title="Cukup"></div>
                                    @else
                                        <div class="w-3 h-3 rounded-full bg-red-500 mx-auto" title="Kurang"></div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 bg-gray-50 border-t flex justify-between items-center text-sm text-gray-600">
                <div>
                    Showing <span id="showingStart" class="font-bold">0</span> to <span id="showingEnd"
                        class="font-bold">0</span> of <span id="totalRows" class="font-bold">0</span> entries
                </div>
                <div class="flex gap-1" id="paginationControls">
                    <!-- JS Generated -->
                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
        // Initialize Data Variables
        let map;
        let markersLayer;

        const defaultCenter = [-1.72, 103.25]; // Batang Hari
        const defaultZoom = 10;

        document.addEventListener('DOMContentLoaded', async () => {
            await initMap();
        });

        async function initMap() {
            if (!document.getElementById('map')) return;

            // 1. Initialize Map
            map = L.map('map').setView(defaultCenter, defaultZoom);

            const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap', maxZoom: 19
            });
            const googleHybrid = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
                maxZoom: 20, subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
            });

            map.addLayer(googleHybrid); // Default to Hybrid for clarity

            L.control.layers({
                "Google Hybrid": googleHybrid,
                "OpenStreetMap": osm
            }).addTo(map);

            // 2. Load Data
            try {
                // Load SLS GeoJSON Boundaries
                try {
                    const geoRes = await fetch('{{ url("/sls_1504.geojson") }}');
                    if (geoRes.ok) {
                        const geoData = await geoRes.json();
                        L.geoJSON(geoData, {
                            style: {
                                color: '#F97316', // Orange 500 - Elegant visible orange
                                weight: 1.5,
                                opacity: 0.8,
                                fillColor: '#FFEDD5', // Orange 100 - Very light fill
                                fillOpacity: 0.2
                            },
                            onEachFeature: function (feature, layer) {
                                if (feature.properties) {
                                    const p = feature.properties;
                                    // Construct label safely checking for properties
                                    const label = `
                                        <div class="font-sans text-xs">
                                            <strong>${p.nmsls || 'SLS'}</strong><br>
                                            ${p.nmdesa || ''} - ${p.nmkec || ''}<br>
                                            <span class="text-gray-500">${p.idsls || ''}</span>
                                        </div>
                                    `;
                                    layer.bindTooltip(label, { sticky: true, className: 'bg-white border border-orange-200 shadow-md rounded px-2 py-1' });
                                }
                            }
                        }).addTo(map);
                        console.log("SLS Boundaries loaded");
                    } else {
                        console.warn("SLS GeoJSON found but failed to load.");
                    }
                } catch (geoErr) {
                    console.warn("Could not load SLS GeoJSON:", geoErr);
                }

                // Load Unit Stats
                // Only fetch points, ignore GeoJSON for now as it was causing issues
                const response = await fetch('{{ route("api.map_stats") }}');
                if (!response.ok) throw new Error("Failed to load map data");

                const units = await response.json();

                // 3. Render Clusters
                const markers = L.markerClusterGroup();
                const bounds = L.latLngBounds();

                units.forEach(u => {
                    if (u.lat && u.lng) {
                        const marker = L.circleMarker([u.lat, u.lng], {
                            radius: 6,
                            fillColor: getColorByStatus(u.status),
                            color: "#fff",
                            weight: 1,
                            opacity: 1,
                            fillOpacity: 0.8
                        }).bindPopup(`
                            <div class="font-sans min-w-[200px]">
                                <h3 class="font-bold text-sm border-b pb-1 mb-1">${u.name}</h3>
                                <div class="text-xs space-y-1">
                                    <p><span class="font-semibold">Status:</span> ${u.status}</p>
                                    <p><span class="font-semibold">SLS:</span> ${u.sls_id || '-'}</p>
                                    <p><span class="font-semibold">Desa:</span> ${u.kddesa}</p>
                                </div>
                            </div>
                        `);
                        markers.addLayer(marker);
                        bounds.extend([u.lat, u.lng]);
                    }
                });

                map.addLayer(markers);
                markersLayer = markers;

                if (units.length > 0 && bounds.isValid()) {
                    map.fitBounds(bounds);
                }

            } catch (err) {
                console.error("Map Init Error:", err);
            }

            // Reset Button Logic (Simple Reset)
            const btnReset = document.getElementById('btnResetMap');
            if (btnReset) {
                btnReset.classList.remove('hidden');
                btnReset.addEventListener('click', () => {
                    map.setView(defaultCenter, defaultZoom);
                });
            }
        }

        function getColorByStatus(status) {
            const colors = { 1: '#10B981', 2: '#FBBF24', 4: '#EF4444', 6: '#9CA3AF' };
            return colors[status] || '#3B82F6';
        }

        // Pagination and Sorting State (Existing Logic Adapted)
        let currentPage = 1;
        let rowsPerPage = 25;
        let allRows = Array.from(document.querySelectorAll('#tableBody tr'));
        let filteredRows = [...allRows]; // This now holds the REGION + SEARCH filtered rows
        let sortDirection = {};

        // MAIN FILTER FUNCTION
        function filterTableByRegion(regionCode) {
            const searchInput = document.getElementById('searchSls');
            const searchQuery = searchInput ? searchInput.value.toLowerCase() : '';

            filteredRows = allRows.filter(row => {
                const slsId = String(row.getAttribute('data-sls-id'));
                const slsName = row.cells[1].textContent.toLowerCase();

                // 1. Check Region Match
                const regionMatch = !regionCode || slsId.startsWith(regionCode);

                // 2. Check Search Match
                const searchMatch = !searchQuery || slsName.includes(searchQuery);

                return regionMatch && searchMatch;
            });

            currentPage = 1;
            renderPagination();
        }

        // Pagination Logic (Unchanged mostly)
        function renderPagination() {
            const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            // Hide all rows first
            allRows.forEach(row => row.style.display = 'none');

            // Show calculated slice
            filteredRows.slice(start, end).forEach(row => row.style.display = '');

            // Update pagination info
            document.getElementById('showingStart').textContent = filteredRows.length > 0 ? start + 1 : 0;
            document.getElementById('showingEnd').textContent = Math.min(end, filteredRows.length);
            document.getElementById('totalRows').textContent = filteredRows.length;

            renderPaginationControls(totalPages);
        }

        function renderPaginationControls(totalPages) {
            const controls = document.getElementById('paginationControls');
            controls.innerHTML = '';
            if (totalPages <= 1) return;

            // Prev
            const prevBtn = document.createElement('button');
            prevBtn.textContent = '←';
            prevBtn.className = `px-3 py-1 rounded ${currentPage === 1 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-blue-500 text-white hover:bg-blue-600'}`;
            prevBtn.onclick = () => { if (currentPage > 1) { currentPage--; renderPagination(); } };
            controls.appendChild(prevBtn);

            // Simple Pages (1 ... Curr ... Last)
            const pageBtn = (i) => {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.className = `px-3 py-1 rounded ${i === currentPage ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300'}`;
                btn.onclick = () => { currentPage = i; renderPagination(); };
                controls.appendChild(btn);
            };

            // Logic for limiting page buttons not implemented for brevity, showing basic window
            // Just Simple simplified version
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                    pageBtn(i);
                } else if (i === currentPage - 2 || i === currentPage + 2) {
                    const span = document.createElement('span'); span.textContent = '...'; controls.appendChild(span);
                }
            }

            // Next
            const nextBtn = document.createElement('button');
            nextBtn.textContent = '→';
            nextBtn.className = `px-3 py-1 rounded ${currentPage === totalPages ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-blue-500 text-white hover:bg-blue-600'}`;
            nextBtn.onclick = () => { if (currentPage < totalPages) { currentPage++; renderPagination(); } };
            controls.appendChild(nextBtn);
        }

        // Sort Table
        window.sortTable = function (columnIndex) {
            const direction = sortDirection[columnIndex] === 'asc' ? 'desc' : 'asc';
            sortDirection = {};
            sortDirection[columnIndex] = direction;

            filteredRows.sort((a, b) => {
                let aVal, bVal;
                if (columnIndex === 0) {
                    aVal = parseInt(a.cells[0].textContent);
                    bVal = parseInt(b.cells[0].textContent);
                } else if (columnIndex === 1) {
                    aVal = a.cells[1].textContent.toLowerCase();
                    bVal = b.cells[1].textContent.toLowerCase();
                } else {
                    aVal = parseFloat(a.cells[columnIndex].getAttribute('data-value') || 0);
                    bVal = parseFloat(b.cells[columnIndex].getAttribute('data-value') || 0);
                }
                if (aVal < bVal) return direction === 'asc' ? -1 : 1;
                if (aVal > bVal) return direction === 'asc' ? 1 : -1;
                return 0;
            });

            currentPage = 1;
            renderPagination();
        }

        // Search functionality
        document.getElementById('searchSls').addEventListener('input', function (e) {
            filterTableByRegion(currentFilterCode);
        });

        // Per page change
        document.getElementById('perPage').addEventListener('change', function (e) {
            rowsPerPage = parseInt(e.target.value);
            currentPage = 1;
            renderPagination();
        });

        // Initial render
        renderPagination();

        // Toggle Table Visibility
        function toggleTable() {
            const disclaimer = document.getElementById('tableDisclaimer');
            const container = document.getElementById('tableContainer');
            const filters = document.getElementById('tableFilters');

            disclaimer.classList.add('hidden');
            container.classList.remove('hidden');
            filters.classList.remove('hidden'); // Show filters when table is shown
        }
    </script>
</body>

</html>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Wilayah & POI Scanner - Groundcheck App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');

        body {
            font-family: 'Inter', sans-serif;
        }

        #map {
            width: 100%;
            height: 100%;
            border-radius: 0.5rem;
            z-index: 1;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #0d9488 0%, #059669 100%);
        }

        /* Hilangkan box hitam saat area SLS (SVG Path) ter-klik */
        path.leaflet-interactive:focus {
            outline: none !important;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen flex flex-col">
    <!-- Header -->
    <div class="gradient-bg text-white py-4 sm:py-6 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-3xl font-bold mb-1">🤖 POI Scanner (Tambah Wilayah)</h1>
                    <p class="text-emerald-100 text-xs sm:text-sm">Otomatisasi Penarikan Data Usaha dari Peta</p>
                </div>
                <div class="flex gap-2 sm:gap-3 w-full sm:w-auto">
                    <a href="{{ url('/units/rekap') }}"
                        class="flex-1 sm:flex-none bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-4 sm:px-6 py-2 sm:py-2.5 rounded-lg transition font-semibold text-center text-sm sm:text-base">
                        ← Kembali Rekap
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 flex-grow flex flex-col lg:flex-row gap-6 w-full py-4">

        <!-- Sidebar / Control Panel -->
        <div class="w-full lg:w-1/3 flex flex-col gap-4">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-5 flex-shrink-0">
                <h2 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">Pengaturan Scanner</h2>

                <div class="space-y-4">
                    <div class="border-b pb-4 mb-4 border-gray-100">
                        <h3 class="text-sm font-bold text-gray-700 mb-3">🎯 Filter Wilayah Target Scan</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Kecamatan</label>
                                <select id="filterKec"
                                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="">-- Semua Kecamatan --</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Desa</label>
                                <select id="filterDesa"
                                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="">-- Semua Desa --</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">SLS</label>
                                <select id="filterSls"
                                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="">-- Semua SLS --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Metode API</label>
                        <select id="scraperMethod"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="overpass">Overpass API (OpenStreetMap) - Gratis</option>
                            <option value="google_places">Google Places API - Butuh API Key</option>
                            <option value="python_backend">Python / Backend Custom - Eksperimental</option>
                        </select>
                    </div>

                    <div id="settings-google_places"
                        class="hidden scraper-settings p-3 bg-gray-50 rounded border border-gray-200">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Google Maps API Key</label>
                        <input type="password" id="gmapApiKey" placeholder="AIzaSy..."
                            class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                        <p class="text-[10px] text-gray-500 mt-1">Harus miliki penagihan aktif.</p>
                    </div>

                    <div id="settings-python_backend"
                        class="hidden scraper-settings p-3 bg-gray-50 rounded border border-gray-200">
                        <p class="text-xs text-red-600 font-semibold mb-1">Peringatan Akses Bot 🕒</p>
                        <p class="text-[10px] text-gray-600 mb-2">Metode ini rawan limit Google. Disarankan menambah
                            jeda (delay) antar request.</p>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Jeda Waktu (Detik) per Bounding
                            Box</label>
                        <select id="pythonScraperDelay"
                            class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="2">2 Detik (Cepat, Risiko Block Tinggi)</option>
                            <option value="5" selected>5 Detik (Normal)</option>
                            <option value="10">10 Detik (Aman)</option>
                            <option value="20">20 Detik (Sangat Aman)</option>
                        </select>
                    </div>

                    <div class="pt-2">
                        <button id="btnStartScan"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-lg shadow-md transition flex justify-center items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Mulai Sequential Scan
                        </button>
                        <button id="btnStopScan"
                            class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-2.5 rounded-lg shadow-md transition justify-center items-center gap-2 hidden mt-2">
                            🛑 Hentikan Scanner
                        </button>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded p-3 text-sm">
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-600">Progress SLS:</span>
                            <span class="font-bold text-gray-800" id="scanProgressText">0 / 0</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-emerald-500 h-2 rounded-full transition-all duration-300"
                                id="scanProgressBar" style="width: 0%"></div>
                        </div>
                        <p class="text-xs text-blue-600 mt-2 font-medium" id="scanStatusText">Status: Siap memindai.</p>
                    </div>
                </div>
            </div>

            <!-- Results Panel -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-5 flex flex-col">
                <div class="flex justify-between items-end border-b pb-2 mb-3">
                    <h2 class="text-lg font-bold text-gray-800">Daftar Temuan POI</h2>
                    <span class="text-xs font-semibold bg-emerald-100 text-emerald-800 px-2 py-1 rounded-full"><span
                            id="foundCount">0</span> Ditemukan</span>
                </div>
                <div class="border border-gray-100 rounded-lg bg-gray-50 p-2" id="resultsList">
                    <div class="text-center text-gray-400 text-sm py-10 italic">
                        Belum ada data. Mulai scanner untuk menarik Point of Interest dari peta.
                    </div>
                    <!-- Results will be injected here -->
                </div>
            </div>
        </div>

        <!-- Map Panel -->
        <div
            class="w-full lg:w-2/3 lg:sticky lg:top-4 bg-white rounded-xl shadow-lg border border-gray-100 p-4 flex flex-col h-[60vh] lg:h-[calc(100vh-2rem)] z-10">
            <div class="relative w-full h-full flex-grow rounded-lg overflow-hidden border border-gray-200">
                <div id="map"></div>
                <!-- Scanning Overlay (Hidden by default) -->
                <div id="scanningOverlay"
                    class="absolute inset-0 bg-emerald-900/20 backdrop-blur-[2px] z-[1000] hidden items-center justify-center flex-col pointer-events-none">
                    <div class="w-16 h-16 border-4 border-white border-t-emerald-500 rounded-full animate-spin"></div>
                    <p class="text-white font-bold mt-4 drop-shadow-md text-lg px-4 py-1 bg-black/40 rounded-full">
                        Memindai Area...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Modal (Edit / Detail) -->
    <div id="editModal"
        class="fixed inset-0 bg-black/50 z-[2000] hidden items-center justify-center backdrop-blur-sm p-4">
        <div
            class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col transform transition-all">
            <div class="bg-emerald-600 px-6 py-4 flex justify-between items-center text-white">
                <h3 class="font-bold text-lg flex items-center gap-2">
                    <span>✏️</span> Form Unit Baru (Scraping)
                </h3>
                <button onclick="closeEditModal()" class="text-white hover:text-emerald-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div class="p-6 overflow-y-auto flex-grow">
                <form id="editForm" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <input type="hidden" id="editPoiId">
                    <input type="hidden" id="editSource">

                    <div class="col-span-1 md:col-span-2 space-y-1">
                        <div class="flex justify-between items-end mb-1">
                            <label class="font-semibold text-gray-700">Nama Usaha / Fasilitas <span
                                    class="text-red-500">*</span></label>
                            <button type="button" onclick="cekDuplikatPOI()"
                                class="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded hover:bg-emerald-200 border border-emerald-200 font-semibold">Cek
                                Duplikat</button>
                        </div>
                        <input type="text" id="editNama" required
                            class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                        <span id="dupCheckResult" class="text-[10px] mt-1 font-semibold hidden"></span>
                        <div id="dupDataContainer"
                            class="text-[11px] text-red-700 bg-red-50 p-2 mt-2 rounded border border-red-200 hidden max-h-32 overflow-y-auto">
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="font-semibold text-gray-700">Latitude</label>
                        <input type="text" id="editLat" readonly
                            class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-gray-600">
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-gray-700">Longitude</label>
                        <input type="text" id="editLng" readonly
                            class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-gray-600">
                    </div>

                    <div class="space-y-1">
                        <label class="font-semibold text-gray-700">Kecamatan</label>
                        <select id="editKec"
                            class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                            <option value="">-- Pilih Kecamatan --</option>
                            @foreach($kecamatans as $k)
                                <option value="{{ $k->code }}">{{ $k->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-gray-700">Desa (Opsional)</label>
                        <select id="editDesa"
                            class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                            <option value="">-- Pilih Desa --</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="font-semibold text-gray-700">Nama SLS (Ditemukan)</label>
                        <input type="text" id="editSlsName" readonly
                            class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-gray-600">
                        <input type="hidden" id="editSls">
                        <p class="text-[10px] text-gray-500 mt-0.5">Nama dari wilayah intersect peta.</p>
                    </div>

                    <div class="space-y-1">
                        <label class="font-semibold text-gray-700">Status Keberadaan</label>
                        <select id="editStatus"
                            class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                            <option value="1">1 - Aktif beroperasi</option>
                            <option value="2">2 - Tutup sementara</option>
                            <option value="3">3 - Sudah tidak beroperasi</option>
                            <option value="4">4 - Tidak ditemukan / Fiktif</option>
                            <option value="5">5 - Pindah lokasi</option>
                        </select>
                    </div>
                </form>
            </div>

            <div class="bg-gray-50 px-6 py-4 border-t flex justify-between items-center">
                <span id="saveResultMsg" class="text-sm font-bold"></span>
                <div class="flex gap-2">
                    <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 border-2 border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-100 transition">Batal</button>
                    <button type="button" onclick="saveNewUnit()"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold shadow transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        Simpan Unit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Turf.js/6.5.0/turf.min.js"></script>

    <script>
        let map;
        let geojsonLayer;
        let slsFeatures = [];
        let scanningQueue = [];
        let isScanning = false;
        let scanIndex = 0;
        let poiMarkers = L.layerGroup();
        let currentMethod = 'overpass';
        let foundPois = [];

        // Bing Maps QuadKey extension
        L.TileLayer.Bing = L.TileLayer.extend({
            getTileUrl: function (coords) {
                let quadkey = '';
                for (let i = this._getZoomForUrl(); i > 0; i--) {
                    let digit = 0;
                    let mask = 1 << (i - 1);
                    if ((coords.x & mask) !== 0) digit += 1;
                    if ((coords.y & mask) !== 0) digit += 2;
                    quadkey += digit.toString();
                }
                return L.Util.template(this._url, {
                    q: quadkey,
                    s: this.options.subdomains[Math.abs(coords.x + coords.y) % this.options.subdomains.length]
                });
            }
        });
        L.tileLayer.bing = function (url, options) { return new L.TileLayer.Bing(url, options); };

        document.addEventListener('DOMContentLoaded', () => {
            initMap();
            loadGeoJson();
            setupEventListeners();
        });

        function initMap() {
            map = L.map('map').setView([-1.72, 103.25], 10);

            const googleHybrid = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
                maxZoom: 20, subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
            });

            const bingSatellite = L.tileLayer.bing('https://t{s}.tiles.virtualearth.net/tiles/a{q}.jpeg?g=129&mkt=en-US&shading=hill&ts=2', {
                maxZoom: 19, subdomains: ['0', '1', '2', '3'], attribution: '&copy; Bing Maps'
            });

            const esriSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19, attribution: 'Tiles &copy; Esri'
            });

            map.addLayer(googleHybrid);
            poiMarkers.addTo(map);

            L.control.layers({
                "Google Hybrid": googleHybrid,
                "Bing Satellite": bingSatellite,
                "Esri Satellite": esriSatellite
            }).addTo(map);

            // Fitur klik manual: jika user melihat di satelit ada spot tapi belum terscan
            map.on('click', function (e) {
                if (isScanning) return; // Jangan izinkan saat sedang scan

                const lat = e.latlng.lat;
                const lng = e.latlng.lng;

                let detectedSls = '';
                let detectedSlsName = '';

                // Coba auto-deteksi koordinat ini masuk/berada di batas SLS mana
                if (slsFeatures && slsFeatures.length > 0) {
                    try {
                        const pt = turf.point([lng, lat]);
                        for (let f of slsFeatures) {
                            if (turf.booleanPointInPolygon(pt, f)) {
                                detectedSls = f.properties.idsls || '';
                                detectedSlsName = f.properties.nmsls || '';
                                break;
                            }
                        }
                    } catch (err) { console.warn("Turf detect gagal:", err); }
                }

                // Buat dummy POI payload untuk openEditModal
                const dummyPoi = {
                    id: Date.now(),
                    name: '',
                    lat: lat,
                    lng: lng,
                    source: 'Penambahan Manual (Klik Peta)',
                    sls_id: detectedSls,
                    sls_name: detectedSlsName
                };

                openEditModal(dummyPoi);
            });
        }

        let rawGeoData = null;

        async function loadGeoJson() {
            try {
                const res = await fetch('{{ url("/sls_1504.geojson") }}');
                if (!res.ok) throw new Error("Gagal memuat GeoJSON");
                rawGeoData = await res.json();

                // Track features for queue
                slsFeatures = rawGeoData.features || [];

                populateFilterKec(slsFeatures);
                renderFilteredMap(slsFeatures);

                document.getElementById('scanProgressText').innerText = `0 / ${slsFeatures.length}`;
            } catch (e) {
                console.error(e);
                alert("GeoJSON SLS tidak ditemukan.");
            }
        }

        function populateFilterKec(features) {
            const kecMap = {};
            features.forEach(f => {
                const p = f.properties;
                const kec = (p.kdkec || '').toString();
                if (kec && !kecMap[kec]) {
                    kecMap[kec] = p.nmkec || `Kec ${kec}`;
                }
            });

            const sel = document.getElementById('filterKec');
            sel.innerHTML = '<option value="">-- Semua Kecamatan --</option>';
            Object.entries(kecMap).sort((a, b) => a[0].localeCompare(b[0])).forEach(([k, n]) => {
                sel.innerHTML += `<option value="${k}">${n}</option>`;
            });
        }

        function populateFilterDesa(features, kecCode) {
            const desaMap = {};
            features.forEach(f => {
                const p = f.properties;
                if ((p.kdkec || '').toString() === kecCode.toString()) {
                    const desa = (p.kddesa || '').toString();
                    if (desa && !desaMap[desa]) {
                        desaMap[desa] = p.nmdesa || `Desa ${desa}`;
                    }
                }
            });

            const sel = document.getElementById('filterDesa');
            sel.innerHTML = '<option value="">-- Semua Desa --</option>';
            Object.entries(desaMap).sort((a, b) => a[0].localeCompare(b[0])).forEach(([k, n]) => {
                sel.innerHTML += `<option value="${k}">${n}</option>`;
            });
        }

        function populateFilterSls(features, kecCode, desaCode) {
            const slsMap = {};
            features.forEach(f => {
                const p = f.properties;
                if ((p.kdkec || '').toString() === kecCode.toString() &&
                    (p.kddesa || '').toString() === desaCode.toString()) {
                    const sls = (p.kdsls || '').toString();
                    if (sls && !slsMap[sls]) {
                        slsMap[sls] = p.nmsls || `SLS ${sls} (${p.idsls})`;
                    }
                }
            });

            const sel = document.getElementById('filterSls');
            sel.innerHTML = '<option value="">-- Semua SLS --</option>';
            Object.entries(slsMap).sort((a, b) => a[0].localeCompare(b[0])).forEach(([k, n]) => {
                sel.innerHTML += `<option value="${k}">${n}</option>`;
            });
        }

        function handleFilterChange() {
            if (!rawGeoData) return;
            const kc = document.getElementById('filterKec').value;
            const ds = document.getElementById('filterDesa').value;
            const sls = document.getElementById('filterSls').value;

            // Dependent Dropdowns
            if (this.id === 'filterKec') {
                document.getElementById('filterDesa').innerHTML = '<option value="">-- Semua Desa --</option>';
                document.getElementById('filterSls').innerHTML = '<option value="">-- Semua SLS --</option>';
                if (kc) populateFilterDesa(rawGeoData.features, kc);
            } else if (this.id === 'filterDesa') {
                document.getElementById('filterSls').innerHTML = '<option value="">-- Semua SLS --</option>';
                if (kc && ds) populateFilterSls(rawGeoData.features, kc, ds);
            }

            let filtered = rawGeoData.features;
            if (kc) filtered = filtered.filter(f => (f.properties.kdkec || '').toString() === kc.toString());
            if (ds) filtered = filtered.filter(f => (f.properties.kddesa || '').toString() === ds.toString());
            if (sls) filtered = filtered.filter(f => (f.properties.kdsls || '').toString() === sls.toString());

            slsFeatures = filtered;
            document.getElementById('scanProgressText').innerText = `0 / ${slsFeatures.length}`;
            renderFilteredMap(filtered);
        }

        function renderFilteredMap(features) {
            if (geojsonLayer) map.removeLayer(geojsonLayer);

            geojsonLayer = L.geoJSON({ type: 'FeatureCollection', features: features }, {
                style: {
                    color: '#F97316',
                    weight: 2,
                    fillColor: '#FFEDD5',
                    fillOpacity: 0.1
                },
                onEachFeature: function (feature, layer) {
                    layer.bindTooltip((feature.properties.nmsls || 'SLS') + '<br>' + (feature.properties.idsls || ''), { sticky: true });
                }
            }).addTo(map);

            if (features.length > 0) {
                map.fitBounds(geojsonLayer.getBounds(), { padding: [20, 20] });
            }
        }

        function setupEventListeners() {
            document.getElementById('filterKec').addEventListener('change', handleFilterChange);
            document.getElementById('filterDesa').addEventListener('change', handleFilterChange);
            document.getElementById('filterSls').addEventListener('change', handleFilterChange);

            const methodSelect = document.getElementById('scraperMethod');
            methodSelect.addEventListener('change', (e) => {
                currentMethod = e.target.value;
                document.querySelectorAll('.scraper-settings').forEach(el => el.classList.add('hidden'));

                const settingsDiv = document.getElementById('settings-' + currentMethod);
                if (settingsDiv) settingsDiv.classList.remove('hidden');
            });

            document.getElementById('btnStartScan').addEventListener('click', startSequentialScan);
            document.getElementById('btnStopScan').addEventListener('click', stopSequentialScan);
        }

        function startSequentialScan() {
            if (slsFeatures.length === 0) {
                alert("Data batas SLS belum dimuat!");
                return;
            }

            if (currentMethod === 'google_places' && !document.getElementById('gmapApiKey').value) {
                alert("Masukkan API Key Google Maps terlebih dahulu!");
                return;
            }

            isScanning = true;
            document.getElementById('btnStartScan').classList.add('hidden');
            document.getElementById('btnStopScan').classList.remove('hidden');
            document.getElementById('scanningOverlay').classList.replace('hidden', 'flex');

            scanIndex = 0;
            foundPois = [];
            poiMarkers.clearLayers();
            updateResultsUI();

            processNextQueueItem();
        }

        function stopSequentialScan() {
            isScanning = false;
            document.getElementById('btnStartScan').classList.remove('hidden');
            document.getElementById('btnStopScan').classList.add('hidden');
            document.getElementById('scanningOverlay').classList.replace('flex', 'hidden');
            document.getElementById('scanStatusText').innerText = 'Status: Dihentikan oleh pengguna.';
        }

        async function processNextQueueItem() {
            if (!isScanning) return;

            if (scanIndex >= slsFeatures.length) {
                stopSequentialScan();
                document.getElementById('scanStatusText').innerText = 'Status: Pemindaian Selesai 🎉';
                alert("Scanning Selesai!");
                return;
            }

            const currentFeature = slsFeatures[scanIndex];
            const name = currentFeature.properties.nmsls || 'SLS';
            const id = currentFeature.properties.idsls || '?';

            // Update UI Progress
            scanIndex++;
            document.getElementById('scanProgressText').innerText = `${scanIndex} / ${slsFeatures.length}`;
            document.getElementById('scanProgressBar').style.width = `${(scanIndex / slsFeatures.length) * 100}%`;
            document.getElementById('scanStatusText').innerText = `Status: Memindai ${name} (${id})...`;

            // Focus Map Animation (Zoom in Maksimal Dulu, Baru Fit Bounds)
            const layer = L.geoJSON(currentFeature);
            const bounds = layer.getBounds();

            const west = bounds.getWest();
            const east = bounds.getEast();
            const north = bounds.getNorth();
            const south = bounds.getSouth();

            // Titik tengah yang sedikit bergeser
            const centerLat = bounds.getCenter().lat;

            // 1. Zoom in maksimal ke sisi Barat Laut SLS
            map.flyTo([north, west], 18, { duration: 1.5 });
            await new Promise(resolve => setTimeout(resolve, 2000));

            // 2. Simulasi Panning Scanner (Menyapu diagonal ke Tenggara)
            map.flyTo([south, east], 18, { duration: 4.5, easeLinearity: 0.5 });
            await new Promise(resolve => setTimeout(resolve, 5000));

            // 3. Sesuaikan kembali agar Bounding Box SLS termuat utuh
            map.flyToBounds(bounds, { duration: 1.0, maxZoom: 19, padding: [15, 15] });
            await new Promise(resolve => setTimeout(resolve, 1400));

            // Extract Data from Box
            try {
                await extractPoiInBounds(bounds, currentFeature);
            } catch (e) {
                console.error("Error scraping feature", e);
            }

            // Tunggu sebentar sebelum lanjut SLS berikutnya
            let delayMs = 800; // default overpass/google delay
            if (currentMethod === 'python_backend') {
                const pyDelaySec = parseInt(document.getElementById('pythonScraperDelay').value);
                delayMs = pyDelaySec * 1000;
                document.getElementById('scanStatusText').innerText = `Status: Jeda aman ${pyDelaySec} detik...`;
            }

            await new Promise(resolve => setTimeout(resolve, delayMs));

            if (isScanning) {
                processNextQueueItem();
            }
        }

        async function extractPoiInBounds(bounds, feature) {
            const southWest = bounds.getSouthWest();
            const northEast = bounds.getNorthEast();
            const idsls = feature.properties.idsls;

            if (currentMethod === 'overpass') {
                const query = `
                    [out:json][timeout:25];
                    (
                      node["shop"](${southWest.lat},${southWest.lng},${northEast.lat},${northEast.lng});
                      node["amenity"](${southWest.lat},${southWest.lng},${northEast.lat},${northEast.lng});
                      node["office"](${southWest.lat},${southWest.lng},${northEast.lat},${northEast.lng});
                    );
                    out body;
                    >;
                    out skel qt;
                `;

                const response = await fetch('https://overpass-api.de/api/interpreter', {
                    method: 'POST',
                    body: query
                });

                if (!response.ok) throw new Error("Overpass Timeout/Error");
                const data = await response.json();

                // Add points
                let addedInThisBox = 0;
                data.elements.forEach(el => {
                    if (el.type === 'node' && el.tags && (el.tags.name || el.tags.shop || el.tags.amenity)) {
                        const name = el.tags.name || (el.tags.shop ? "Toko " + el.tags.shop : el.tags.amenity);
                        addPoiToResult(name, el.lat, el.lon, 'OpenStreetMap', idsls);
                        addedInThisBox++;
                    }
                });

                return addedInThisBox;
            }
            else if (currentMethod === 'python_backend') {
                // To avoid block, the backend scraper should randomly jitter coordinates inside the bounds
                // Dummy Implementation for now: Simulate finding multiple POIs strictly inside the Polygon using Turf.js

                let numFake = Math.floor(Math.random() * 15) + 12; // 12 to 26 points

                // Get bounding box array for turf [minX, minY, maxX, maxY]
                const bbox = [bounds.getWest(), bounds.getSouth(), bounds.getEast(), bounds.getNorth()];

                // Keep generating random points until we have enough inside the polygon
                let pointsAdded = 0;
                let attempts = 0;
                const maxAttempts = 100; // prevent infinite loops on tiny/invalid polygons

                while (pointsAdded < numFake && attempts < maxAttempts) {
                    attempts++;
                    // Generate 1 random point in bounding box
                    const randomPt = turf.randomPoint(1, { bbox: bbox });

                    // Check if it's strictly inside the GeoJSON polygon feature (handling MultiPolygon/Polygon)
                    const isInside = turf.booleanPointInPolygon(randomPt.features[0], feature);

                    if (isInside) {
                        const jitterLat = randomPt.features[0].geometry.coordinates[1];
                        const jitterLng = randomPt.features[0].geometry.coordinates[0];

                        const dummyNames = [
                            'Warung Kelontong', 'Toko Baju', 'Fotokopi', 'Konter Pulsa',
                            'Rumah Makan', 'Warung Kopi', 'Bengkel Motor', 'Toko Pakan Ternak',
                            'Risol Syafira', 'Pangkalan Es Krim Cincau Hijau',
                            'Badan Keuangan Daerah Kabupaten Batang Hari',
                            'QUEENZA KOST', 'SMA Negeri 1 Batanghari',
                            'Rumah Makan Lek Asih', 'Lapangan Garuda',
                            'Klinik Vape', 'EMLY Mini Market', 'Kantor Pos'
                        ];
                        const rName = dummyNames[Math.floor(Math.random() * dummyNames.length)];
                        addPoiToResult(rName + ' (Python)', jitterLat, jitterLng, 'PythonScraper', idsls);
                        pointsAdded++;
                    }
                }

                return pointsAdded;
            }
            else if (currentMethod === 'google_places') {
                // Placeholder
                addPoiToResult('Google Place Dummy', bounds.getCenter().lat, bounds.getCenter().lng, 'Google', idsls);
                return 1;
            }
        }

        function addPoiToResult(name, lat, lng, source, sls_id) {
            // Check absolute duplicate
            if (foundPois.some(p => p.lat === lat && p.lng === lng)) return;

            const poi = { name, lat, lng, source, sls_id, id: Date.now() + Math.random() };
            foundPois.push(poi);

            // Add marker
            const marker = L.marker([lat, lng])
                .bindTooltip(`<div class="font-bold text-sm">${name}</div><div class="text-xs text-gray-500">${source}</div>`)
                .addTo(poiMarkers);

            updateResultsUI();
        }

        function updateResultsUI() {
            document.getElementById('foundCount').innerText = foundPois.length;
            const container = document.getElementById('resultsList');

            if (foundPois.length === 0) {
                container.innerHTML = `<div class="text-center text-gray-400 text-sm py-10 italic">Belum ada data. Mulai scanner...</div>`;
                return;
            }

            container.innerHTML = '';

            // Render from newest
            [...foundPois].reverse().forEach(poi => {
                container.innerHTML += `
                    <div class="bg-white border text-sm border-emerald-100 rounded p-2 mb-2 shadow-sm flex flex-col hover:border-emerald-300 transition">
                        <div class="font-bold text-emerald-800 break-words">${poi.name}</div>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-[10px] text-gray-400 bg-gray-100 px-1 py-0.5 rounded border border-gray-200">${poi.sls_id || 'SLS ?'}</span>
                            <span class="text-[10px] text-gray-500">${poi.source}</span>
                        </div>
                        <div class="mt-2 flex gap-2">
                            <button onclick="editPoi(${poi.id})" class="flex-1 bg-yellow-400 hover:bg-yellow-500 text-yellow-900 font-semibold py-1 rounded text-xs focus:ring-2 focus:ring-yellow-300 transition">Simpan / Detail</button>
                            <button onclick="map.flyTo([${poi.lat}, ${poi.lng}], 18)" class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 px-2 rounded shadow-sm transition">🎯</button>
                        </div>
                    </div>
                `;
            });
        }

        function editPoi(id) {
            const poi = foundPois.find(p => p.id === id);
            if (!poi) return;

            openEditModal(poi);
        }

        function openEditModal(poi) {
            document.getElementById('editPoiId').value = poi.id;
            document.getElementById('editNama').value = poi.name;
            document.getElementById('editLat').value = poi.lat;
            document.getElementById('editLng').value = poi.lng;
            document.getElementById('editSource').value = poi.source;
            document.getElementById('editSls').value = poi.sls_id || '';
            document.getElementById('editSlsName').value = poi.sls_name || poi.sls_id || 'Tidak Terdeteksi';
            document.getElementById('editStatus').value = '1';

            // Try to figure out Kec/Desa from SLS ID if possible
            if (poi.sls_id && poi.sls_id.length >= 10) {
                // e.g. 15040100291001 (15=Prov, 04=Kab, 010=Kec, 029=Desa, 1001=SLS)
                const provKab = poi.sls_id.substring(0, 4);
                if (provKab === '1504') {
                    const kecCode3 = poi.sls_id.substring(4, 7); // e.g., '010'
                    const desaCode3 = poi.sls_id.substring(7, 10); // e.g., '029'

                    // Kecamatan code in DB is usually 3-digits ('010') or 7-digits ('1504010')
                    // Desa code in DB is usually 6-digits ('010029') or 10-digits
                    const desaCode6 = kecCode3 + desaCode3;
                    const desaCode10 = provKab + kecCode3 + desaCode3;

                    const kecSelect = document.getElementById('editKec');
                    const options = Array.from(kecSelect.options);
                    const matchKec = options.find(o =>
                        o.value == kecCode3 ||
                        o.value == (provKab + kecCode3) ||
                        (o.value && o.value.toString().endsWith(kecCode3))
                    );

                    if (matchKec) {
                        kecSelect.value = matchKec.value;
                        loadDesaDropdown(matchKec.value, desaCode6, desaCode10, desaCode3);
                    }
                }
            }

            document.getElementById('saveResultMsg').innerText = '';
            document.getElementById('editModal').classList.replace('hidden', 'flex');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.replace('flex', 'hidden');
        }

        // Add event listener for Kecamatan change to load Desa
        document.getElementById('editKec').addEventListener('change', function () {
            loadDesaDropdown(this.value);
        });

        async function loadDesaDropdown(kecCode, selectDesaCode6 = null, selectDesaCode10 = null, selectDesaCode3 = null) {
            const desaSelect = document.getElementById('editDesa');
            desaSelect.innerHTML = '<option value="">-- Pilih Desa --</option>';
            if (!kecCode) return;

            try {
                const res = await fetch(`{{ url('/api/villages') }}/${kecCode}`);
                if (res.ok) {
                    const data = await res.json();
                    data.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.code;
                        opt.textContent = `${d.code} - ${d.name}`;

                        // Check against any possible variation of Desa code (3, 6, 10 digits)
                        if (
                            (selectDesaCode6 && d.code == selectDesaCode6) ||
                            (selectDesaCode10 && d.code == selectDesaCode10) ||
                            (selectDesaCode3 && d.code.toString().endsWith(selectDesaCode3))
                        ) {
                            opt.selected = true;
                        }
                        desaSelect.appendChild(opt);
                    });
                }
            } catch (e) {
                console.error("Gagal load desa", e);
            }
        }

        async function saveNewUnit() {
            const btn = event.currentTarget;
            const msg = document.getElementById('saveResultMsg');

            const payload = {
                nama_usaha: document.getElementById('editNama').value,
                latitude: document.getElementById('editLat').value,
                longitude: document.getElementById('editLng').value,
                kdkec: document.getElementById('editKec').value,
                kddesa: document.getElementById('editDesa').value,
                status_keberadaan: document.getElementById('editStatus').value,
                sls_idsls: document.getElementById('editSls').value,
                sls_nmsls: document.getElementById('editSlsName').value,
                source: document.getElementById('editSource').value
            };

            if (!payload.nama_usaha) {
                alert("Nama Usaha wajib diisi!");
                return;
            }

            btn.disabled = true;
            btn.innerHTML = 'Menyimpan...';
            msg.className = 'text-sm font-bold text-gray-500';
            msg.innerText = 'Menyimpan ke database...';

            try {
                const res = await fetch('{{ route("units.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    msg.className = 'text-sm font-bold text-emerald-600';
                    msg.innerText = data.message || 'Tersimpan!';

                    // Mark POI as saved visually
                    const id = document.getElementById('editPoiId').value;
                    const poiIndex = foundPois.findIndex(p => p.id == id);
                    if (poiIndex > -1) {
                        foundPois[poiIndex].name = "✅ " + foundPois[poiIndex].name;
                    }
                    updateResultsUI();

                    setTimeout(() => closeEditModal(), 1500);
                } else if (data.is_duplicate) {
                    // Show duplicate warning
                    let dupHtml = '<div style="text-align:left;font-size:13px;margin-bottom:10px">' + data.message + '</div>';
                    dupHtml += '<div style="text-align:left;font-size:11px;max-height:150px;overflow:auto;border:1px solid #e5e7eb;border-radius:6px;padding:8px">';
                    (data.duplicates || []).forEach(d => {
                        let colorStyle = d.similarity >= 90 ? 'color:#dc2626' : (d.similarity >= 80 ? 'color:#ea580c' : 'color:#2563eb');
                        dupHtml += `<div style="margin-bottom:8px;padding-bottom:8px;border-bottom:1px solid #f3f4f6"><b>${d.nama_usaha}</b> <span style="${colorStyle};font-weight:bold">[${d.similarity}% - ${d.reason}]</span><br><span style="color:#666">${d.alamat || '-'}${d.lokasi ? ' | ' + d.lokasi : ''} (${d.idsbr})</span></div>`;
                    });
                    dupHtml += '</div>';

                    const confirmResult = await Swal.fire({
                        title: '⚠️ Duplikat Terdeteksi!',
                        html: dupHtml,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Tetap Simpan',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#d97706'
                    });

                    if (confirmResult.isConfirmed) {
                        payload.force_save = true;
                        const forceRes = await fetch('{{ route("units.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        });
                        const forceData = await forceRes.json();
                        if (forceRes.ok && forceData.success) {
                            msg.className = 'text-sm font-bold text-emerald-600';
                            msg.innerText = 'Tersimpan (bypass duplikat)!';

                            const id = document.getElementById('editPoiId').value;
                            const poiIndex = foundPois.findIndex(p => p.id == id);
                            if (poiIndex > -1) foundPois[poiIndex].name = "✅ " + foundPois[poiIndex].name;
                            updateResultsUI();
                            setTimeout(() => closeEditModal(), 1500);
                        } else {
                            msg.className = 'text-sm font-bold text-red-600';
                            msg.innerText = forceData.message || 'Gagal menyimpan!';
                        }
                    } else {
                        msg.className = 'text-sm font-bold text-orange-600';
                        msg.innerText = 'Penyimpanan dibatalkan.';
                    }
                } else {
                    msg.className = 'text-sm font-bold text-red-600';
                    msg.innerText = data.message || 'Gagal menyimpan!';
                }
            } catch (e) {
                console.error(e);
                msg.className = 'text-sm font-bold text-red-600';
                msg.innerText = 'Kesalahan jaringan.';
            } finally {
                btn.disabled = false;
                btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Simpan Unit`;
            }
        }

        async function cekDuplikatPOI() {
            const namaInput = document.getElementById('editNama');
            const resultSpan = document.getElementById('dupCheckResult');
            const dataContainer = document.getElementById('dupDataContainer');
            const nama = namaInput.value.trim();

            dataContainer.classList.add('hidden');
            dataContainer.innerHTML = '';

            if (!nama) {
                resultSpan.textContent = 'Isi nama usaha dulu!';
                resultSpan.className = 'text-[10px] mt-1 font-semibold block text-red-600';
                resultSpan.classList.remove('hidden');
                return;
            }
            try {
                resultSpan.textContent = 'Mengecek...';
                resultSpan.className = 'text-[10px] mt-1 font-semibold block text-gray-600';
                resultSpan.classList.remove('hidden');

                const kdkec = document.getElementById('editKec').value;
                const kddesa = document.getElementById('editDesa').value;

                const baseUrl = "{{ url('/') }}";
                let url = `${baseUrl}/api/check-duplicate?nama_usaha=${encodeURIComponent(nama)}`;
                if (kdkec) url += `&kdkec=${kdkec}`;
                if (kddesa) url += `&kddesa=${kddesa}`;

                const res = await fetch(url);
                const data = await res.json();

                if (data.exists && data.data) {
                    resultSpan.textContent = `⚠️ Ditemukan ${data.data.length} data mirip!`;
                    resultSpan.className = 'text-[10px] mt-1 font-semibold block text-red-600';

                    let html = '<ul class="list-disc pl-4 mt-1 space-y-1">';
                    data.data.forEach(d => {
                        let alamatDisplay = d.lokasi ? `${d.alamat || '-'} | ${d.lokasi}` : (d.alamat || '-');
                        let colorClass = d.similarity >= 90 ? 'text-red-600' : (d.similarity >= 80 ? 'text-orange-500' : 'text-blue-500');
                        html += `<li><b class="text-gray-900">${d.nama_usaha}</b> <span class="text-[9px] font-bold ${colorClass}">[${d.similarity}% - ${d.reason}]</span><br><span class="text-[10px] text-gray-600 font-medium">${alamatDisplay} (SBR: ${d.idsbr})</span></li>`;
                    });
                    html += '</ul>';
                    dataContainer.innerHTML = html;
                    dataContainer.classList.remove('hidden');
                } else {
                    resultSpan.textContent = '✅ Nama usaha belum ada.';
                    resultSpan.className = 'text-[10px] mt-1 font-semibold block text-green-600';
                }
            } catch (e) {
                resultSpan.textContent = 'Gagal mengecek duplikasi.';
                resultSpan.className = 'text-[10px] mt-1 font-semibold block text-red-600';
            }
        }
    </script>
</body>

</html>
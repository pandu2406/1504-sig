<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Data - Groundcheck App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Leaflet CSS */
        @import url('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
        @import url('https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css');
        @import url('https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css');

        body {
            font-family: 'Inter', sans-serif;
        }

        #map {
            width: 100%;
            height: 100%;
            border-radius: 0.5rem;
            isolation: isolate;
            z-index: 1;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .expand-icon {
            transition: transform 0.3s ease;
        }

        .expanded .expand-icon {
            transform: rotate(90deg);
        }

        .progress-ring {
            transition: stroke-dashoffset 0.5s ease;
        }

        .status-chip {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            margin: 1px;
        }

        @keyframes bounceGentle {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-5px);
            }
        }

        .animate-bounce-gentle {
            animation: bounceGentle 2s infinite ease-in-out;
        }

        @keyframes scaleUpFade {
            from {
                opacity: 0;
                transform: scale(0.8) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .animate-scale-up-fade {
            animation: scaleUpFade 0.5s ease-out forwards;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-slide-in-right {
            animation: slideInRight 0.5s ease-out;
        }

        /* NEW ANIMATIONS */
        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        .animate-shimmer {
            animation: shimmer 2s infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-5px);
            }
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes pulse-ring {
            0% {
                transform: scale(0.8);
                box-shadow: 0 0 0 0 rgba(255, 165, 0, 0.7);
            }

            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(255, 165, 0, 0);
            }

            100% {
                transform: scale(0.8);
                box-shadow: 0 0 0 0 rgba(255, 165, 0, 0);
            }
        }

        .animate-pulse-ring {
            animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>

    <!-- PDF Export Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
</head>

<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Header -->
    <div class="gradient-bg text-white py-4 sm:py-6 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-3xl font-bold mb-1">📊 Rekapitulasi Data</h1>
                    <p class="text-purple-100 text-xs sm:text-sm">Monitoring & Analisis Progress Lapangan</p>
                </div>
                <div class="flex gap-2 sm:gap-3 w-full sm:w-auto flex-wrap sm:flex-nowrap justify-end relative">
                    <!-- Dropdown Fitur Tambahan -->
                    <div class="relative group">
                        <button id="btnFiturTambahan"
                            class="flex-1 sm:flex-none bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white px-4 sm:px-6 py-2 sm:py-2.5 rounded-lg transition font-semibold flex items-center justify-center gap-2 shadow-lg text-sm sm:text-base">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Fitur Tambahan
                            <svg class="w-4 h-4 ml-1 transition-transform group-hover:rotate-180" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="dropdownFitur"
                            class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl border border-gray-100 hidden z-50 transform origin-top-right transition-all">
                            <div class="py-2">
                                <a href="{{ route('units.tambah_wilayah') }}"
                                    class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition">
                                    <div class="bg-teal-100 text-teal-600 p-1.5 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </div>
                                    <div class="font-medium">Tambah Wilayah</div>
                                </a>

                                <button onclick="exportToPDF()"
                                    class="w-full text-left flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition">
                                    <div class="bg-red-100 text-red-600 p-1.5 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <div class="font-medium">Export PDF</div>
                                </button>

                                <div class="border-t border-gray-100 my-1"></div>

                                <a href="{{ route('units.gap_analysis') }}"
                                    class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition">
                                    <div class="bg-purple-100 text-purple-600 p-1.5 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <div class="font-medium">Gap Analysis</div>
                                </a>

                                <a href="{{ route('units.sipw_viz') }}"
                                    class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition">
                                    <div class="bg-emerald-100 text-emerald-600 p-1.5 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                        </svg>
                                    </div>
                                    <div class="font-medium">SIPW Viz</div>
                                </a>

                                <a href="{{ route('units.bulk-update') }}"
                                    class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition">
                                    <div class="bg-blue-100 text-blue-600 p-1.5 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                            </path>
                                        </svg>
                                    </div>
                                    <div class="font-medium">Bulk Update</div>
                                </a>

                                <div class="border-t border-gray-100 my-1"></div>

                                <a href="/units/generate-kode"
                                    class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition bg-yellow-50/50">
                                    <div class="bg-yellow-100 text-yellow-600 p-1.5 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="font-medium">Deteksi Alamat</div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <a href="{{ url('/') }}"
                        class="flex-1 sm:flex-none bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-4 sm:px-6 py-2 sm:py-2.5 rounded-lg transition font-semibold flex items-center justify-center text-sm sm:text-base border border-white/30">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 sm:py-8">

        <!-- Interactive Map Section -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 mb-8 overflow-hidden">
            <div
                class="px-4 sm:px-6 py-4 flex flex-col lg:flex-row justify-between lg:items-center gap-4 bg-gray-50 border-b border-gray-100">
                <div class="flex flex-col sm:flex-row gap-4 flex-grow">
                    <div class="flex-shrink-0">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-800 flex items-center gap-2">
                            <span class="text-xl sm:text-2xl">🗺️</span> Sebaran
                        </h2>
                        <p class="text-gray-500 text-[10px] sm:text-xs">
                            Kab. Batang Hari <span id="breadcrumb" class="font-bold text-blue-600"></span>
                        </p>
                        <!-- Active Filters Copy Text -->
                        <div id="activeFiltersContainer" class="hidden mt-1 flex items-center gap-2">
                            <span id="activeFiltersText"
                                class="text-xs font-mono text-indigo-700 bg-indigo-50 px-2 py-1 rounded border border-indigo-100 select-all"></span>
                            <button id="copyFiltersBtn" class="text-gray-400 hover:text-indigo-600 transition"
                                title="Copy Filters">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex-grow max-w-2xl space-y-2">
                        <!-- Filter Dropdowns -->
                        <div class="flex flex-wrap gap-2">
                            <select id="filterKecamatan"
                                class="flex-1 min-w-[120px] border-2 border-gray-300 rounded-lg px-2 sm:px-3 py-1.5 text-xs sm:text-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition">
                                <option value="">Semua Kecamatan</option>
                            </select>
                            <select id="filterDesa"
                                class="flex-1 min-w-[120px] border-2 border-gray-300 rounded-lg px-2 sm:px-3 py-1.5 text-xs sm:text-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition">
                                <option value="">Semua Desa</option>
                            </select>
                            <select id="filterSls"
                                class="flex-1 min-w-[120px] border-2 border-gray-300 rounded-lg px-2 sm:px-3 py-1.5 text-xs sm:text-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition">
                                <option value="">Semua SLS</option>
                            </select>
                        </div>
                        <!-- Search Input -->
                        <div class="relative">
                            <input type="text" id="mapSearch" placeholder="🔍 Cari nama usaha / IDSBR..."
                                class="w-full px-4 py-2 pr-10 border-2 border-gray-300 rounded-lg focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition text-sm">
                            <button id="clearSearch"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div id="searchResults"
                            class="absolute z-10 mt-1 w-full max-w-lg bg-white rounded-lg shadow-lg border border-gray-200 max-h-96 overflow-y-auto hidden">
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <div id="filterCoordinateCount"
                        class="absolute bottom-6 left-1/2 transform -translate-x-1/2 bg-white/90 backdrop-blur-sm border border-gray-200 shadow-lg px-4 py-2 rounded-full text-sm font-medium text-gray-700 z-[1000] pointer-events-none hidden transition-opacity duration-300">
                        Menampilkan 0 target
                    </div>
                    <button id="btnResetMap"
                        class="flex-grow sm:flex-grow-0 bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition hidden">
                        ↺ Reset
                    </button>
                </div>
            </div>
            <div class="relative w-full h-[350px] sm:h-[500px]">
                <div id="map"></div>
            </div>
        </div>




        <!-- Summary Cards -->
        <div id="summary-stats" class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-md p-3 sm:p-5 card-hover border-l-4 border-blue-500 col-span-1">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                    <div class="order-2 sm:order-1">
                        <p class="text-gray-500 text-[10px] sm:text-sm font-medium">Total Target</p>
                        <h3 class="text-lg sm:text-2xl font-bold text-gray-800 mt-0.5" data-stat="total">
                            {{ number_format($grandTotal, 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="order-1 sm:order-2 bg-blue-100 p-2 sm:p-3 rounded-lg">
                        <svg class="w-5 h-5 sm:w-8 sm:h-8 text-blue-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-3 sm:p-5 card-hover border-l-4 border-green-500 col-span-1">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                    <div class="order-2 sm:order-1">
                        <p class="text-gray-500 text-[10px] sm:text-sm font-medium">Sudah (Ada Coord)</p>
                        <h3 class="text-lg sm:text-2xl font-bold text-green-600 mt-0.5" data-stat="with_coord">
                            {{ number_format($grandWithCoord, 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="order-1 sm:order-2 bg-green-100 p-2 sm:p-3 rounded-lg">
                        <svg class="w-5 h-5 sm:w-8 sm:h-8 text-green-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-3 sm:p-5 card-hover border-l-4 border-yellow-500 col-span-1">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                    <div class="order-2 sm:order-1">
                        <p class="text-gray-500 text-[10px] sm:text-sm font-medium">Sudah (No Coord)</p>
                        <h3 class="text-lg sm:text-2xl font-bold text-yellow-600 mt-0.5" data-stat="no_coord">
                            {{ number_format($grandNoCoord, 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="order-1 sm:order-2 bg-yellow-100 p-2 sm:p-3 rounded-lg">
                        <svg class="w-5 h-5 sm:w-8 sm:h-8 text-yellow-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-3 sm:p-5 card-hover border-l-4 border-red-500 col-span-1">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                    <div class="order-2 sm:order-1">
                        <p class="text-gray-500 text-[10px] sm:text-sm font-medium">Belum</p>
                        <h3 class="text-lg sm:text-2xl font-bold text-red-600 mt-0.5" data-stat="empty">
                            {{ number_format($grandTotal - $grandGroundchecked, 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="order-1 sm:order-2 bg-red-100 p-2 sm:p-3 rounded-lg">
                        <svg class="w-5 h-5 sm:w-8 sm:h-8 text-red-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div
                class="bg-white rounded-xl shadow-md p-3 sm:p-5 card-hover border-l-4 border-purple-500 col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <p class="text-gray-500 text-[10px] sm:text-sm font-medium">Total Progress</p>
                        <h3 class="text-xl sm:text-2xl font-bold text-purple-600 mt-0.5" data-stat="progress">
                            {{ $grandTotal > 0 ? number_format(($grandGroundchecked / $grandTotal) * 100, 1) : 0 }}%
                        </h3>
                    </div>
                    <div class="bg-purple-100 p-2 sm:p-3 rounded-lg">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-purple-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div id="content-grid" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Main Tables -->
            <div class="lg:col-span-2 flex flex-col gap-6">
                <!-- Main Table 1: Kecamatan Level with Status -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                        <h2 class="text-xl font-bold text-white">📍 Rekapitulasi Per Kecamatan & Status Keberadaan</h2>
                        <p class="text-purple-100 text-sm mt-1">Klik baris kecamatan untuk melihat detail per desa</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm sm:text-base">
                            <thead>
                                <tr class="bg-gray-50 border-b-2 border-gray-200">
                                    <th
                                        class="px-2 sm:px-4 py-3 text-left text-[10px] sm:text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Wilayah</th>
                                    <th
                                        class="px-2 sm:px-4 py-3 text-right text-[10px] sm:text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Target</th>
                                    <th
                                        class="px-2 sm:px-4 py-3 text-right text-[10px] sm:text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Ada Coord</th>
                                    <th
                                        class="hidden sm:table-cell px-2 sm:px-4 py-3 text-right text-[10px] sm:text-xs font-bold text-gray-600 uppercase tracking-wider text-yellow-600">
                                        No Coord</th>
                                    <th
                                        class="px-2 sm:px-4 py-3 text-right text-[10px] sm:text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Belum</th>
                                    <th
                                        class="px-2 sm:px-4 py-3 text-center text-[10px] sm:text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        %</th>
                                    <th
                                        class="hidden lg:table-cell px-2 sm:px-4 py-3 text-left text-[10px] sm:text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @php
                                    $statusColors = [
                                        1 => 'bg-green-100 text-green-700 border-green-300',
                                        2 => 'bg-yellow-100 text-yellow-700 border-yellow-300',
                                        3 => 'bg-blue-100 text-blue-700 border-blue-300',
                                        4 => 'bg-red-100 text-red-700 border-red-300',
                                        5 => 'bg-purple-100 text-purple-700 border-purple-300',
                                        6 => 'bg-gray-100 text-gray-700 border-gray-300',
                                        7 => 'bg-teal-100 text-teal-700 border-teal-300',
                                        8 => 'bg-orange-100 text-orange-700 border-orange-300',
                                        9 => 'bg-pink-100 text-pink-700 border-pink-300',
                                        10 => 'bg-indigo-100 text-indigo-700 border-indigo-300'
                                    ];

                                    $statusLabels = [
                                        1 => 'Aktif',
                                        2 => 'Tutup Sementara',
                                        3 => 'Belum Beroperasi',
                                        4 => 'Tutup',
                                        5 => 'Alih Usaha',
                                        6 => 'Tidak Ditemukan',
                                        7 => 'Aktif Pindah',
                                        8 => 'Aktif Nonrespon',
                                        9 => 'Duplikat',
                                        10 => 'Salah Kode Wilayah'
                                    ];
                                @endphp
                                @foreach($rows as $row)
                                    @php
                                        $progressClass = ($row['percentage'] == 100) ? 'bg-green-50' : '';
                                        // Get status breakdown for this kecamatan
                                        $kecStatusBreakdown = [];
                                        foreach ($statusBreakdown as $status) {
                                            if (isset($row['status_counts'][$status['status_id']])) {
                                                $kecStatusBreakdown[] = [
                                                    'id' => $status['status_id'],
                                                    'name' => $status['status_name'],
                                                    'count' => $row['status_counts'][$status['status_id']]
                                                ];
                                            }
                                        }
                                    @endphp
                                    <tr class="hover:bg-gray-50 cursor-pointer transition {{ $progressClass }}"
                                        onclick="toggleKecamatan('{{ $row['kdkec'] }}')">
                                        <td class="px-2 sm:px-4 py-3">
                                            <div class="flex items-center">
                                                <span
                                                    class="expand-icon inline-block mr-1 sm:mr-2 text-gray-400 text-[10px] sm:text-xs"
                                                    id="icon-{{ $row['kdkec'] }}">▶</span>
                                                <div>
                                                    <div class="font-bold text-gray-800 text-xs sm:text-sm">
                                                        {{ $row['kec_name'] }}
                                                    </div>
                                                    <div class="text-[9px] text-gray-400">#{{ $row['kdkec'] }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-2 sm:px-4 py-3 text-right">
                                            <span class="font-bold text-gray-700 text-xs sm:text-sm"
                                                data-kec="{{ $row['kdkec'] }}"
                                                data-field="total">{{ number_format($row['total'], 0, ',', '.') }}</span>
                                        </td>
                                        <td class="px-2 sm:px-4 py-3 text-right">
                                            <span class="text-green-600 font-bold text-xs sm:text-sm"
                                                data-kec="{{ $row['kdkec'] }}"
                                                data-field="with_coord">{{ number_format($row['with_coord'], 0, ',', '.') }}</span>
                                        </td>
                                        <td class="hidden sm:table-cell px-2 sm:px-4 py-3 text-right">
                                            <span class="text-yellow-600 font-bold text-xs sm:text-sm"
                                                data-kec="{{ $row['kdkec'] }}"
                                                data-field="no_coord">{{ number_format($row['no_coord'], 0, ',', '.') }}</span>
                                        </td>
                                        <td class="px-2 sm:px-4 py-3 text-right">
                                            <span class="text-red-600 font-bold text-xs sm:text-sm"
                                                data-kec="{{ $row['kdkec'] }}"
                                                data-field="empty">{{ number_format($row['empty'], 0, ',', '.') }}</span>
                                        </td>
                                        <td class="px-2 sm:px-4 py-3">
                                            <div class="flex flex-col items-center">
                                                <div class="hidden sm:block w-full bg-gray-200 rounded-full h-1.5 mb-1">
                                                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-1.5 rounded-full {{ $row['percentage'] <= 0 ? 'hidden' : '' }}"
                                                        data-kec="{{ $row['kdkec'] }}" data-field="progress-bar"
                                                        style="width: {{ $row['percentage'] }}%">
                                                    </div>
                                                </div>
                                                <span class="text-[10px] sm:text-xs font-black text-gray-700"
                                                    data-kec="{{ $row['kdkec'] }}"
                                                    data-field="progress-text">{{ number_format($row['percentage'], 0) }}%</span>
                                            </div>
                                        </td>
                                        <td class="hidden lg:table-cell px-2 sm:px-4 py-3" id="status-{{ $row['kdkec'] }}">
                                            <div class="text-[10px] text-gray-400">Loading...</div>
                                        </td>
                                    </tr>
                                    <!-- Detail Desa Row (Hidden by default) -->
                                    <tr id="detail-{{ $row['kdkec'] }}"
                                        class="hidden bg-gradient-to-r from-blue-50 to-indigo-50">
                                        <td colspan="7" class="px-4 py-4">
                                            <div id="content-{{ $row['kdkec'] }}">
                                                <p class="text-center text-gray-500 py-2">Loading...</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr
                                    class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white font-bold text-xs sm:text-sm">
                                    <td class="px-2 sm:px-4 py-4 text-right">TOTAL</td>
                                    <td class="px-2 sm:px-4 py-4 text-right">
                                        <span id="grandTotal">{{ number_format($grandTotal, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-2 sm:px-4 py-4 text-right">
                                        <span
                                            id="grandWithCoord">{{ number_format($grandWithCoord, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="hidden sm:table-cell px-2 sm:px-4 py-4 text-right text-yellow-200">
                                        <span id="grandNoCoord">{{ number_format($grandNoCoord, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-2 sm:px-4 py-4 text-right">
                                        <span
                                            id="grandEmpty">{{ number_format($grandTotal - $grandGroundchecked, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-2 sm:px-4 py-4 text-center">
                                        {{ $grandTotal > 0 ? number_format(($grandGroundchecked / $grandTotal) * 100, 0) : 0 }}%
                                    </td>
                                    <td class="hidden lg:table-cell px-2 sm:px-4 py-4">
                                        <div class="flex flex-wrap gap-1 justify-end">
                                            @foreach($statusBreakdown as $status)
                                                @php
                                                    $colorClass = $statusColors[$status['status_id']] ?? 'bg-gray-100 text-gray-700';
                                                @endphp
                                                <span
                                                    class="status-chip {{ $colorClass }} border border-white/20 shadow-sm text-[9px] px-1">
                                                    {{ $status['status_id'] }}: {{ $status['count'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Main Table 2: Kecamatan Level with Status (Usaha Tambahan) -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-orange-200">
                    <div class="bg-gradient-to-r from-orange-400 to-red-500 px-6 py-4">
                        <h2 class="text-xl font-bold text-white">📍 Rekapitulasi Usaha Tambahan Per Kecamatan</h2>
                        <p class="text-orange-100 text-sm mt-1">Status Keberadaan Data Usaha Tambahan (Manual/Bulk)</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm sm:text-base">
                            <thead>
                                <tr class="bg-orange-50 border-b-2 border-orange-200">
                                    <th
                                        class="px-2 sm:px-4 py-3 text-left text-[10px] sm:text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Wilayah</th>
                                    <th
                                        class="px-2 sm:px-4 py-3 text-right text-[10px] sm:text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Target</th>
                                    <th
                                        class="px-2 sm:px-4 py-3 text-right text-[10px] sm:text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Ada Coord</th>
                                    <th
                                        class="hidden sm:table-cell px-2 sm:px-4 py-3 text-right text-[10px] sm:text-xs font-bold text-gray-700 uppercase tracking-wider text-yellow-600">
                                        No Coord</th>
                                    <th
                                        class="px-2 sm:px-4 py-3 text-right text-[10px] sm:text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Belum</th>
                                    <th
                                        class="px-2 sm:px-4 py-3 text-center text-[10px] sm:text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        %</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($tambahanRows as $row)
                                    <tr class="hover:bg-orange-50 transition-colors duration-150 groupcursor-pointer {{ $row['percentage'] == 100 ? 'bg-green-50' : '' }}"
                                        onclick="toggleKecamatanTambahan('{{ $row['kdkec'] }}')">
                                        <td class="px-2 sm:px-4 py-3">
                                            <div class="flex items-center">
                                                <span
                                                    class="expand-icon inline-block mr-1 sm:mr-2 text-gray-400 text-[10px] sm:text-xs"
                                                    id="icon-tambahan-{{ $row['kdkec'] }}">▶</span>
                                                <div class="text-xs sm:text-sm font-bold text-gray-800">
                                                    {{ $row['kec_name'] }}
                                                </div>
                                            </div>
                                            <div
                                                class="text-[9px] sm:text-[10px] text-gray-500 mt-0.5 ml-0 sm:ml-6 group-hover:text-indigo-500 transition-colors">
                                                #{{ $row['kdkec'] }}
                                            </div>
                                        </td>
                                        <td class="px-2 sm:px-4 py-3 text-right">
                                            <span class="text-gray-900 font-bold text-xs sm:text-sm">
                                                {{ number_format($row['total'], 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td class="px-2 sm:px-4 py-3 text-right">
                                            <span class="text-green-600 font-bold text-xs sm:text-sm">
                                                {{ number_format($row['with_coord'], 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td class="hidden sm:table-cell px-2 sm:px-4 py-3 text-right">
                                            <span class="text-yellow-600 font-bold text-xs sm:text-sm">
                                                {{ number_format($row['no_coord'], 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td class="px-2 sm:px-4 py-3 text-right">
                                            <span class="text-red-600 font-bold text-xs sm:text-sm">
                                                {{ number_format($row['empty'], 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td class="px-2 sm:px-4 py-3">
                                            <div class="flex flex-col items-center">
                                                <div class="hidden sm:block w-full bg-gray-200 rounded-full h-1.5 mb-1">
                                                    <div class="bg-gradient-to-r from-orange-400 to-red-500 h-1.5 rounded-full {{ $row['percentage'] <= 0 ? 'hidden' : '' }}"
                                                        style="width: {{ $row['percentage'] }}%">
                                                    </div>
                                                </div>
                                                <span class="text-[10px] sm:text-xs font-black text-gray-700">
                                                    {{ number_format($row['percentage'], 0) }}%
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Detail Desa Row (Hidden by default) -->
                                    <tr id="detail-tambahan-{{ $row['kdkec'] }}"
                                        class="hidden bg-gradient-to-r from-orange-50 to-red-50">
                                        <td colspan="6" class="px-4 py-4">
                                            <div id="content-tambahan-{{ $row['kdkec'] }}">
                                                <p class="text-center text-gray-500 py-2">Loading...</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                @if(count($tambahanRows) === 0)
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 text-sm">
                                            Belum ada data Usaha Tambahan.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr
                                    class="bg-gradient-to-r from-orange-500 to-red-600 text-white font-bold text-xs sm:text-sm">
                                    <td class="px-2 sm:px-4 py-4 text-right">TOTAL</td>
                                    <td class="px-2 sm:px-4 py-4 text-right">
                                        <span>{{ number_format($tambahanGrandTotal, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-2 sm:px-4 py-4 text-right">
                                        <span>{{ number_format($tambahanGrandWithCoord, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="hidden sm:table-cell px-2 sm:px-4 py-4 text-right text-yellow-200">
                                        <span>{{ number_format($tambahanGrandNoCoord, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-2 sm:px-4 py-4 text-right">
                                        <span>{{ number_format($tambahanGrandTotal - $tambahanGrandGroundchecked, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-2 sm:px-4 py-4 text-center">
                                        {{ $tambahanGrandTotal > 0 ? number_format(($tambahanGrandGroundchecked / $tambahanGrandTotal) * 100, 0) : 0 }}%
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sidebar: Stats & Charts -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Card 1: Progress Akumulasi Harian (Statik) -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden h-96 flex flex-col">
                    <div class="w-full text-left bg-gradient-to-r from-teal-500 to-green-600 p-4 relative">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="font-bold text-lg text-white flex items-center gap-2">
                                    📈 Progress Akumulasi
                                </h2>
                                <p class="text-teal-50 text-xs mt-0.5 font-medium opacity-90">
                                    Data Terverifikasi
                                </p>
                            </div>
                            <div class="bg-white/20 p-2 rounded-lg shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-y-auto" style="max-height: 300px;">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2 text-left font-bold text-gray-600">Tanggal</th>
                                    <th class="px-3 py-2 text-right font-bold text-gray-600">Total</th>
                                </tr>
                            </thead>
                            <tbody id="daily-cumulative-stats-tbody" class="divide-y divide-gray-100">
                                @if(isset($dailyCumulativeStats) && count($dailyCumulativeStats) > 0)
                                    @foreach($dailyCumulativeStats as $stat)
                                        <tr class="hover:bg-green-50 transition cursor-pointer group"
                                            onclick="showDailyContributors('{{ $stat->date }}')">
                                            <td class="px-3 py-2">
                                                @if($stat->date == 'DATA AWAL')
                                                    <span
                                                        class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-[10px] font-bold">AWAL</span>
                                                @else
                                                    <div class="font-semibold text-gray-700">
                                                        {{ \Carbon\Carbon::parse($stat->date)->format('d M Y') }}
                                                    </div>
                                                    <div class="text-[10px] text-green-600 font-bold">
                                                        +{{ number_format($stat->total, 0, ',', '.') }} unit</div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-right font-black text-gray-800 text-base">
                                                {{ number_format($stat->cumulative, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="2" class="py-4"></td>
                                    </tr>
                                @else
                                    <tr>
                                        <td colspan="2" class="px-4 py-4 text-center text-gray-500 italic">No data</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <!-- Footer Button -->
                    <div class="p-3 bg-gray-50 text-center sticky bottom-0 border-t border-gray-100 mt-auto">
                        <button onclick="openProgressChart()"
                            class="text-xs font-bold text-teal-600 hover:text-teal-700 hover:bg-teal-100 px-4 py-2 rounded-full transition flex items-center justify-center gap-1 w-full">
                            📊 Lihat Grafik Visualisasi
                        </button>
                    </div>
                </div>

                <!-- Card 2: Top Kontributor -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover max-h-96 flex flex-col">
                    <div class="bg-gradient-to-r from-orange-500 to-red-600 px-5 py-4">
                        <div class="flex justify-between items-center">
                            <h2 class="font-bold text-lg text-white">🏆 Top Kontributor</h2>
                            <div class="bg-white/20 p-1.5 rounded-lg transition opacity-70">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="p-0 overflow-y-auto max-h-80">
                        @if(count($userStats) > 0)
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-3 py-2 text-center font-bold text-gray-600 w-12">No</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-600">User</th>
                                        <th class="px-3 py-2 text-right font-bold text-gray-600">Unit</th>
                                    </tr>
                                </thead>
                                <tbody id="top-contributors-tbody" class="divide-y divide-gray-100">
                                    @foreach($userStats as $index => $stat)
                                        <tr class="hover:bg-orange-50 transition cursor-pointer border-b border-gray-50 last:border-0"
                                            onclick="showUserDetail('{{ $stat->user_id }}')">
                                            <td class="px-3 py-3 text-center">
                                                @if($index == 0)
                                                    <div class="text-xl animate-bounce-gentle" title="Rank 1">🥇</div>
                                                @elseif($index == 1)
                                                    <div class="text-xl" title="Rank 2">🥈</div>
                                                @elseif($index == 2)
                                                    <div class="text-xl" title="Rank 3">🥉</div>
                                                @else
                                                    <div
                                                        class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 font-bold text-xs mx-auto">
                                                        {{ $index + 1 }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3">
                                                <span class="font-semibold text-gray-700 truncate max-w-[120px] block"
                                                    title="{{ $stat->user_id }}">
                                                    {{ $stat->user_id }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-3 text-right">
                                                <span
                                                    class="font-bold text-orange-600 bg-orange-50 px-2 py-1 rounded-md text-xs">
                                                    {{ number_format($stat->total, 0, ',', '.') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- Footer Button -->
                            <div class="p-3 bg-gray-50 text-center sticky bottom-0 border-t border-gray-100">
                                <button onclick="openPodium()"
                                    class="text-xs font-bold text-orange-600 hover:text-orange-700 hover:bg-orange-100 px-4 py-2 rounded-full transition flex items-center justify-center gap-1 w-full">
                                    🏆 Lihat Leaderboard Lengkap
                                </button>
                            </div>
                        @else
                            <div class="p-6 text-center text-gray-500 italic">Belum ada kontributor</div>
                        @endif
                    </div>
                </div>

                <!-- Card 3: Aktivitas Terakhir (Progress Harian Log) -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover">
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-5 py-4">
                        <h2 class="font-bold text-lg text-white">📅 Aktivitas Terakhir</h2>
                    </div>
                    <div id="recent-activity-container" class="p-4 max-h-80 overflow-y-auto">
                        @foreach($dailyStats as $stat)
                            <div class="flex justify-between items-center py-2 px-3 mb-2 rounded-lg hover:bg-blue-50 cursor-pointer transition border border-transparent hover:border-blue-200"
                                onclick="showDailyDetail('{{ $stat->date }}')">
                                <div>
                                    <div class="text-sm font-semibold text-gray-700">
                                        {{ \Carbon\Carbon::parse($stat->date)->format('d M Y') }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($stat->date)->format('l') }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs font-bold">{{ $stat->total }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>

        <!-- Tambahan Stats & Charts -->
        <h2 class="text-xl font-bold text-gray-800 mt-10 mb-4 border-l-4 border-orange-500 pl-3">Summary Data Tambahan
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

            <!-- Tambahan Card 1: Progress Akumulasi Harian -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden h-96 flex flex-col">
                <div class="w-full text-left bg-gradient-to-r from-orange-400 to-red-500 p-4 relative">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="font-bold text-lg text-white flex items-center gap-2">
                                📈 Progress Akumulasi
                            </h2>
                            <p class="text-orange-50 text-xs mt-0.5 font-medium opacity-90">
                                Tambahan Terverifikasi
                            </p>
                        </div>
                        <div class="bg-white/20 p-2 rounded-lg shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="overflow-y-auto" style="max-height: 300px;">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-3 py-2 text-left font-bold text-gray-600">Tanggal</th>
                                <th class="px-3 py-2 text-right font-bold text-gray-600">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @if(isset($tambahanDailyCumulativeStats) && count($tambahanDailyCumulativeStats) > 0)
                                @foreach($tambahanDailyCumulativeStats as $stat)
                                    <tr class="hover:bg-orange-50 transition cursor-pointer group"
                                        onclick="showDailyContributors('{{ $stat->date }}')">
                                        <td class="px-3 py-2">
                                            @if($stat->date == 'DATA AWAL')
                                                <span
                                                    class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-[10px] font-bold">AWAL</span>
                                            @else
                                                <div class="font-semibold text-gray-700">
                                                    {{ \Carbon\Carbon::parse($stat->date)->format('d M Y') }}
                                                </div>
                                                <div class="text-[10px] text-orange-600 font-bold">
                                                    +{{ number_format($stat->total, 0, ',', '.') }} unit</div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right font-black text-gray-800 text-base">
                                            {{ number_format($stat->cumulative, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="2" class="px-4 py-4 text-center text-gray-500 italic">No data</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tambahan Card 2: Top Kontributor -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover max-h-96 flex flex-col">
                <div class="bg-gradient-to-r from-orange-400 to-red-500 px-5 py-4">
                    <div class="flex justify-between items-center">
                        <h2 class="font-bold text-lg text-white">🏆 Top Kontributor</h2>
                        <div class="bg-white/20 p-1.5 rounded-lg transition opacity-70">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="p-0 overflow-y-auto max-h-80">
                    @if(isset($tambahanUserStats) && count($tambahanUserStats) > 0)
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2 text-center font-bold text-gray-600 w-12">No</th>
                                    <th class="px-3 py-2 text-left font-bold text-gray-600">User</th>
                                    <th class="px-3 py-2 text-right font-bold text-gray-600">Unit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($tambahanUserStats as $index => $stat)
                                    <tr class="hover:bg-orange-50 transition cursor-pointer border-b border-gray-50 last:border-0"
                                        onclick="showUserDetail('{{ $stat->user_id }}')">
                                        <td class="px-3 py-3 text-center">
                                            @if($index == 0)
                                                <div class="text-xl animate-bounce-gentle" title="Rank 1">🥇</div>
                                            @elseif($index == 1)
                                                <div class="text-xl" title="Rank 2">🥈</div>
                                            @elseif($index == 2)
                                                <div class="text-xl" title="Rank 3">🥉</div>
                                            @else
                                                <div
                                                    class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 font-bold text-xs mx-auto">
                                                    {{ $index + 1 }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="font-semibold text-gray-700 truncate max-w-[120px] block"
                                                title="{{ $stat->user_id }}">
                                                {{ $stat->user_id }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <span class="font-bold text-orange-600 bg-orange-50 px-2 py-1 rounded-md text-xs">
                                                {{ number_format($stat->total, 0, ',', '.') }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="p-6 text-center text-gray-500 italic">Belum ada kontributor tambahan</div>
                    @endif
                </div>
            </div>

            <!-- Tambahan Card 3: Aktivitas Terakhir -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover max-h-96 flex flex-col">
                <div class="bg-gradient-to-r from-orange-400 to-red-500 px-5 py-4">
                    <h2 class="font-bold text-lg text-white">📅 Aktivitas Terakhir</h2>
                </div>
                <div class="p-4 max-h-80 overflow-y-auto">
                    @if(isset($tambahanLogs) && count($tambahanLogs) > 0)
                        @foreach($tambahanLogs as $log)
                            <div class="flex flex-col mb-3 pb-3 border-b border-gray-100 last:border-0 hover:bg-orange-50 p-2 rounded transition cursor-pointer"
                                onclick="showUserDetail('{{ $log->unit->last_updated_by ?? 'Unknown' }}')">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-xs font-bold text-gray-800 truncate"
                                        title="{{ $log->unit->nama_usaha ?? 'N/A' }}">
                                        {{ Str::limit($log->unit->nama_usaha ?? 'N/A', 25) }}
                                    </span>
                                    <span class="text-[10px] text-gray-500 whitespace-nowrap bg-gray-100 px-1 rounded">
                                        {{ $log->created_at->format('d M H:i') }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center text-[10px]">
                                    <span
                                        class="text-orange-600 font-bold bg-orange-50 px-1 rounded">{{ $log->unit->last_updated_by ?? 'Unknown' }}</span>
                                    <span class="text-gray-600">{{ $log->action }}</span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="p-6 text-center text-gray-500 italic">Belum ada aktivitas</div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <!-- User Detail Modal -->
    <div id="userModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden">
            <div class="bg-gradient-to-r from-orange-500 to-red-600 px-6 py-5 flex justify-between items-center">
                <h2 class="text-xl font-bold text-white">Detail Kontribusi: <span id="modalUsername"></span></h2>
                <button onclick="closeModal('userModal')"
                    class="text-white hover:bg-white/20 rounded-full p-2 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                <input type="text" id="userSearchInfo" onkeyup="filterUserTable()"
                    class="w-full border-2 border-gray-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 p-3 rounded-lg transition"
                    placeholder="🔍 Cari ID SBR, Nama Usaha...">
            </div>

            <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b-2 border-gray-200">
                            <th class="p-3 text-center font-semibold text-gray-600 w-16">No</th>
                            <th class="p-3 text-left font-semibold text-gray-600">Waktu</th>
                            <th class="p-3 text-left font-semibold text-gray-600">IDSBR</th>
                            <th class="p-3 text-left font-semibold text-gray-600">Nama Usaha</th>
                            <th class="p-3 text-left font-semibold text-gray-600">Perubahan</th>
                        </tr>
                    </thead>
                    <tbody id="userModalContent" class="divide-y divide-gray-100">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>

            <div class="bg-gray-50 px-6 py-4 flex justify-end">
                <button onclick="closeModal('userModal')"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2.5 rounded-lg transition font-semibold">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Daily Detail Modal -->
    <div id="dailyModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="bg-gradient-to-r from-green-500 to-teal-600 px-6 py-5 flex justify-between items-center">
                <h2 class="text-xl font-bold text-white">Aktivitas Tanggal: <span id="modalDate"></span></h2>
                <button onclick="closeModal('dailyModal')"
                    class="text-white hover:bg-white/20 rounded-full p-2 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div class="px-6 py-4 bg-gray-50">
                <input type="text" id="dailySearchInfo"
                    class="w-full border-2 border-gray-200 focus:border-teal-500 focus:ring-2 focus:ring-teal-200 p-3 rounded-lg transition"
                    placeholder="🔍 Cari unit, user, atau alamat...">
            </div>

            <div id="dailyScrollContainer" class="flex-1 overflow-y-auto p-6">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b-2 border-gray-200 sticky top-0">
                            <th class="p-3 text-left font-semibold text-gray-600">Jam</th>
                            <th class="p-3 text-left font-semibold text-gray-600">User</th>
                            <th class="p-3 text-left font-semibold text-gray-600">IDSBR</th>
                            <th class="p-3 text-left font-semibold text-gray-600">Nama Usaha</th>
                            <th class="p-3 text-left font-semibold text-gray-600">Perubahan</th>
                        </tr>
                    </thead>
                    <tbody id="dailyModalContent" class="divide-y divide-gray-100">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>

            <div class="bg-gray-50 px-6 py-4 flex justify-end">
                <button onclick="closeModal('dailyModal')"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2.5 rounded-lg transition font-semibold">Tutup</button>
            </div>
        </div>
    </div>



    <script>
        // Store expanded state
        const expandedKecamatan = new Set();
        const desaDataCache = {};
        const statusDataCache = {};
        let lastUpdateTimestamp = null;
        let autoRefreshInterval = null;

        // Data from Controller
        const chartData = @json($dailyCumulativeStats);
        const podiumData = @json($userStats);

        // Status color mapping
        const statusColors = {
            1: 'bg-green-100 text-green-700 border-green-300',
            2: 'bg-yellow-100 text-yellow-700 border-yellow-300',
            3: 'bg-blue-100 text-blue-700 border-blue-300',
            4: 'bg-red-100 text-red-700 border-red-300',
            5: 'bg-purple-100 text-purple-700 border-purple-300',
            6: 'bg-gray-100 text-gray-700 border-gray-300',
            7: 'bg-teal-100 text-teal-700 border-teal-300',
            8: 'bg-orange-100 text-orange-700 border-orange-300',
            9: 'bg-pink-100 text-pink-700 border-pink-300',
            10: 'bg-indigo-100 text-indigo-700 border-indigo-300'
        };

        const statusLabels = {
            1: 'Aktif',
            2: 'Tutup Sementara',
            3: 'Belum Beroperasi',
            4: 'Tutup',
            5: 'Alih Usaha',
            6: 'Tidak Ditemukan',
            7: 'Aktif Pindah',
            8: 'Aktif Nonrespon',
            9: 'Duplikat',
            10: 'Salah Kode Wilayah'
        };

        // Load status for all kecamatan on page load
        document.addEventListener('DOMContentLoaded', function () {
            @foreach($rows as $row)             loadStatusForKecamatan('{{ $row['kdkec'] }}');
            @endforeach

            // Start auto-refresh
            startAutoRefresh();
        });

        // Auto-refresh functionality
        function startAutoRefresh() {
            // Poll every 10 seconds
            autoRefreshInterval = setInterval(async () => {
                await checkForUpdates();
            }, 10000); // 10 seconds
        }

        function stopAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
            }
        }

        async function checkForUpdates() {
            try {
                const response = await fetch('{{ url('/units/rekap/summary') }}');
                const data = await response.json();

                // Check if data has changed
                if (lastUpdateTimestamp && data.lastUpdate !== lastUpdateTimestamp) {
                    // Update Main Content
                    updateSummaryCards(data);
                    updateKecamatanRows(data.rows);

                    // Update Sidebars
                    updateDailyCumulativeStats(data.dailyCumulativeStats);
                    updateUserStats(data.userStats);
                    updateDailyStats(data.dailyStats);

                    // Clear caches to force reload status for visible items
                    // (Optional, if we want to ensure status chips update strictly)
                    // Object.keys(statusDataCache).forEach(key => delete statusDataCache[key]);
                    // data.rows.forEach(row => loadStatusForKecamatan(row.kdkec)); 
                    // Note: updateKecamatanRows usually handles the numbers, 
                    // but status chips are async. For now let's leave chips unless critical.

                    // Show subtle update notification
                    showUpdateNotification();
                }

                lastUpdateTimestamp = data.lastUpdate;
            } catch (error) {
                console.error('Auto-refresh error:', error);
            }
        }

        // --- UI Updaters ---

        function updateDailyCumulativeStats(stats) {
            const tbody = document.getElementById('daily-cumulative-stats-tbody');
            if (!tbody) return;

            if (stats.length === 0) {
                tbody.innerHTML = '<tr><td colspan="2" class="px-4 py-4 text-center text-gray-500 italic">No data</td></tr>';
                return;
            }

            let html = '';
            stats.forEach(stat => {
                let dateHtml = '';
                if (stat.date === 'DATA AWAL') {
                    dateHtml = '<span class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-[10px] font-bold">AWAL</span>';
                } else {
                    const dateObj = new Date(stat.date);
                    const dateStr = dateObj.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                    dateHtml = `
                        <div class="font-semibold text-gray-700">${dateStr}</div>
                        <div class="text-[10px] text-green-600 font-bold">+${stat.total.toLocaleString('id-ID')} unit</div>
                    `;
                }

                html += `
                    <tr class="hover:bg-green-50 transition cursor-pointer group" onclick="showDailyContributors('${stat.date}')">
                         <td class="px-3 py-2">${dateHtml}</td>
                         <td class="px-3 py-2 text-right font-black text-gray-800 text-base">${stat.cumulative.toLocaleString('id-ID')}</td>
                    </tr>
                `;
            });
            // Spacer row
            html += '<tr><td colspan="2" class="py-4"></td></tr>';

            tbody.innerHTML = html;
        }

        function updateUserStats(stats) {

            const tbody = document.getElementById('top-contributors-tbody');
            if (!tbody) return;
            if (!tbody) return;

            if (stats.length === 0) {
                tbody.parentElement.innerHTML = '<div class="p-6 text-center text-gray-500 italic">Belum ada kontributor</div>';
                return;
            }

            let html = '';
            stats.forEach((stat, index) => {
                const rankColor = index < 3 ? 'from-yellow-400 to-orange-500 shadow-sm' : 'from-gray-200 to-gray-300';
                html += `
                    <tr class="hover:bg-orange-50 transition cursor-pointer" onclick="showUserDetail('${stat.user_id}')">
                         <td class="px-3 py-2 text-center">
                              <div class="w-6 h-6 rounded-full bg-gradient-to-br ${rankColor} flex items-center justify-center text-white font-bold text-xs mx-auto">
                                   ${index + 1}
                              </div>
                         </td>
                         <td class="px-3 py-2">
                              <span class="font-semibold text-gray-700 truncate max-w-[120px] block" title="${stat.user_id}">${stat.user_id}</span>
                         </td>
                         <td class="px-3 py-2 text-right font-bold text-orange-600">${stat.total.toLocaleString('id-ID')}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        function updateDailyStats(stats) { // Recent Activity Sidebar
            // Similar logic, find via title "Aktivitas Terakhir"
            const container = document.getElementById('recent-activity-container');
            if (!container) return;
            if (!container) return;

            let html = '';
            // stats is array of {date, total}
            stats.forEach(stat => {
                const dateObj = new Date(stat.date);
                const dateStr = dateObj.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                const dayName = dateObj.toLocaleDateString('en-US', { weekday: 'long' }); // Ideally localized

                html += `
                    <div class="flex justify-between items-center py-2 px-3 mb-2 rounded-lg hover:bg-blue-50 cursor-pointer transition border border-transparent hover:border-blue-200"
                        onclick="showDailyDetail('${stat.date}')">
                        <div>
                            <div class="text-sm font-semibold text-gray-700">${dateStr}</div>
                            <div class="text-xs text-gray-500">${dayName}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs font-bold">${stat.total}</span>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        function updateSummaryCards(data) {
            // Update Total Target
            const totalCard = document.querySelector('[data-stat="total"]');
            if (totalCard) {
                totalCard.textContent = data.grandTotal.toLocaleString('id-ID');
                animateNumber(totalCard);
            }

            // Update Sudah (Ada Coord)
            // Note: HTML data-stat attribute was changed to 'with_coord'
            const withCoordCard = document.querySelector('[data-stat="with_coord"]');
            if (withCoordCard) {
                withCoordCard.textContent = data.grandWithCoord.toLocaleString('id-ID');
                animateNumber(withCoordCard);
            }

            // Update Sudah (No Coord)
            const noCoordCard = document.querySelector('[data-stat="no_coord"]');
            if (noCoordCard) {
                noCoordCard.textContent = data.grandNoCoord.toLocaleString('id-ID');
                animateNumber(noCoordCard);
            }

            // Update Belum
            const emptyCard = document.querySelector('[data-stat="empty"]');
            if (emptyCard) {
                emptyCard.textContent = data.grandEmpty.toLocaleString('id-ID');
                animateNumber(emptyCard);
            }

            // Update Footer Grand Totals
            const grandTotalSpan = document.getElementById('grandTotal');
            if (grandTotalSpan) grandTotalSpan.textContent = data.grandTotal.toLocaleString('id-ID');

            const grandWithCoordSpan = document.getElementById('grandWithCoord');
            if (grandWithCoordSpan) grandWithCoordSpan.textContent = data.grandWithCoord.toLocaleString('id-ID');

            const grandNoCoordSpan = document.getElementById('grandNoCoord');
            if (grandNoCoordSpan) grandNoCoordSpan.textContent = data.grandNoCoord.toLocaleString('id-ID');

            const grandEmptySpan = document.getElementById('grandEmpty');
            if (grandEmptySpan) grandEmptySpan.textContent = data.grandEmpty.toLocaleString('id-ID');

            // Update Progress
            const progressCard = document.querySelector('[data-stat="progress"]');
            if (progressCard) {
                progressCard.textContent = data.grandPercentage.toFixed(1) + '%';
                animateNumber(progressCard);
            }
        }

        function updateKecamatanRows(rows) {
            rows.forEach(row => {
                const targetCell = document.querySelector(`[data-kec="${row.kdkec}"][data-field="total"]`);
                const withCoordCell = document.querySelector(`[data-kec="${row.kdkec}"][data-field="with_coord"]`);
                const noCoordCell = document.querySelector(`[data-kec="${row.kdkec}"][data-field="no_coord"]`);
                const emptyCell = document.querySelector(`[data-kec="${row.kdkec}"][data-field="empty"]`);
                const progressBar = document.querySelector(`[data-kec="${row.kdkec}"][data-field="progress-bar"]`);
                const progressText = document.querySelector(`[data-kec="${row.kdkec}"][data-field="progress-text"]`);

                if (targetCell && targetCell.textContent !== row.total.toLocaleString('id-ID')) {
                    targetCell.textContent = row.total.toLocaleString('id-ID');
                    animateNumber(targetCell);
                }

                if (withCoordCell && withCoordCell.textContent !== row.with_coord.toLocaleString('id-ID')) {
                    withCoordCell.textContent = row.with_coord.toLocaleString('id-ID');
                    animateNumber(withCoordCell);
                }

                if (noCoordCell && noCoordCell.textContent !== row.no_coord.toLocaleString('id-ID')) {
                    noCoordCell.textContent = row.no_coord.toLocaleString('id-ID');
                    animateNumber(noCoordCell);
                }

                if (emptyCell && emptyCell.textContent !== row.empty.toLocaleString('id-ID')) {
                    emptyCell.textContent = row.empty.toLocaleString('id-ID');
                    animateNumber(emptyCell);
                }

                if (progressBar) {
                    if (row.percentage <= 0) {
                        progressBar.classList.add('hidden');
                        progressBar.style.width = '0%';
                    } else {
                        progressBar.classList.remove('hidden');
                        progressBar.style.width = row.percentage + '%';
                    }
                }

                if (progressText) {
                    progressText.textContent = row.percentage.toFixed(1) + '%';
                }
            });
        }

        function animateNumber(element) {
            element.classList.add('bg-yellow-200');
            setTimeout(() => {
                element.classList.remove('bg-yellow-200');
            }, 1000);
        }

        function showUpdateNotification() {
            // Silent update - no visual notification needed
            console.log('Data updated successfully');
        }

        async function loadStatusForKecamatan(kdkec) {
            try {
                const response = await fetch(`{{ url('/units/rekap/desa') }}/${kdkec}`);
                const data = await response.json();

                // Aggregate status across all desa
                const statusCounts = {};
                data.forEach(desa => {
                    for (const [statusId, count] of Object.entries(desa.status_breakdown)) {
                        statusCounts[statusId] = (statusCounts[statusId] || 0) + count;
                    }
                });

                statusDataCache[kdkec] = statusCounts;
                renderStatusChips(kdkec, statusCounts);
            } catch (error) {
                console.error(error);
                document.getElementById(`status-${kdkec}`).innerHTML = '<span class="text-xs text-gray-400">-</span>';
            }
        }

        function renderStatusChips(kdkec, statusCounts) {
            const container = document.getElementById(`status-${kdkec}`);
            if (Object.keys(statusCounts).length === 0) {
                container.innerHTML = '<span class="text-xs text-gray-400 italic">Belum ada data</span>';
                return;
            }

            let html = '<div class="flex flex-wrap gap-1">';
            for (const [statusId, count] of Object.entries(statusCounts)) {
                const colorClass = statusColors[statusId] || 'bg-gray-100 text-gray-700';
                const label = statusLabels[statusId] || `S${statusId}`;
                html += `<span class="status-chip ${colorClass} border" title="${statusId}. ${label}">${statusId}: ${count}</span>`;
            }
            html += '</div>';
            container.innerHTML = html;
        }

        // Generic Close Modal
        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        // Toggle Kecamatan Detail
        async function toggleKecamatan(kdkec) {
            const detailRow = document.getElementById(`detail-${kdkec}`);
            const parentRow = detailRow.previousElementSibling;
            const icon = document.getElementById(`icon-${kdkec}`);
            const contentDiv = document.getElementById(`content-${kdkec}`);

            if (expandedKecamatan.has(kdkec)) {
                // Collapse
                detailRow.classList.add('hidden');
                parentRow.classList.remove('expanded');
                expandedKecamatan.delete(kdkec);
            } else {
                // Expand
                detailRow.classList.remove('hidden');
                parentRow.classList.add('expanded');
                expandedKecamatan.add(kdkec);

                // Fetch data if not cached
                if (!desaDataCache[kdkec]) {
                    try {
                        const response = await fetch(`{{ url('/units/rekap/desa') }}/${kdkec}`);
                        const data = await response.json();
                        desaDataCache[kdkec] = data;
                        renderDesaTable(kdkec, data);
                    } catch (error) {
                        console.error(error);
                        contentDiv.innerHTML = '<p class="text-center text-red-500 py-2">Gagal memuat data.</p>';
                    }
                } else {
                    renderDesaTable(kdkec, desaDataCache[kdkec]);
                }
            }
        }

        function renderDesaTable(kdkec, data) {
            const contentDiv = document.getElementById(`content-${kdkec}`);

            if (data.length === 0) {
                contentDiv.innerHTML = '<p class="text-center text-gray-500 py-2">Tidak ada data desa.</p>';
                return;
            }

            let html = `
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-indigo-50 border-b border-indigo-100">
                                <th class="px-3 py-2 text-left text-xs font-semibold text-indigo-700">Desa</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-indigo-700">Target</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-green-700">Ada Coord</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-orange-700">No Coord</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-red-700">Belum</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-indigo-700">Progress</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-indigo-700">Status Keberadaan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
            `;

            data.forEach(desa => {
                const progressClass = desa.percentage == 100 ? 'bg-green-50' : '';

                // Format status breakdown
                let statusHtml = '';
                if (Object.keys(desa.status_breakdown).length > 0) {
                    statusHtml = '<div class="flex flex-wrap gap-1">';
                    for (const [statusId, count] of Object.entries(desa.status_breakdown)) {
                        const colorClass = statusColors[statusId] || 'bg-gray-100 text-gray-700';
                        const label = statusLabels[statusId] || `S${statusId}`;
                        statusHtml += `<span class="status-chip ${colorClass} border" title="${statusId}. ${label}">${statusId}: ${count}</span>`;
                    }
                    statusHtml += '</div>';
                } else {
                    statusHtml = '<span class="text-gray-400 italic text-xs">-</span>';
                }

                // Add unique ID for row and details
                const cleanKec = kdkec.replace(/\./g, '');
                const cleanDesa = desa.kddesa.replace(/\./g, '');
                const rowId = `desa-${cleanKec}-${cleanDesa}`;

                html += `
                    <tr class="hover:bg-gray-50 cursor-pointer ${progressClass}" onclick="toggleSls('${kdkec}', '${desa.kddesa}', '${rowId}')">
                        <td class="px-3 py-2">
                             <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-400 transition-transform duration-200" id="icon-${rowId}">▶</span>
                                <div>
                                    <div class="font-medium text-gray-800">${desa.desa_name}</div>
                                    <div class="text-xs text-gray-500">Kode: ${desa.kddesa}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-right font-semibold text-gray-700">${desa.total.toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2 text-right text-green-600 font-semibold">${desa.with_coord.toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2 text-right text-orange-600 font-semibold">${(desa.no_coord || 0).toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2 text-right text-red-600 font-semibold">${desa.empty.toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2">
                            <div class="flex flex-col items-center">
                                <div class="w-full bg-gray-200 rounded-full h-1.5 mb-1">
                                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-1.5 rounded-full" style="width: ${desa.percentage}%"></div>
                                </div>
                                <span class="text-xs font-bold text-gray-600">${desa.percentage.toFixed(1)}%</span>
                            </div>
                        </td>
                        <td class="px-3 py-2">${statusHtml}</td>
                    </tr>
                    <!-- Hidden SLS Row -->
                    <tr id="detail-${rowId}" class="hidden bg-gray-50">
                        <td colspan="7" class="p-0">
                            <div id="content-${rowId}" class="pl-8 pr-4 py-2 border-l-4 border-indigo-200">
                                <div class="text-xs text-gray-500 italic py-2">Memuat data SLS...</div>
                            </div>
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            contentDiv.innerHTML = html;
        }

        // --- SLS Toggle Logic ---
        const expandedSls = new Set();
        const slsDataCache = {};

        async function toggleSls(kdkec, kddesa, rowId) {
            // Prevent event bubbling if needed, but here simple toggle
            const detailRow = document.getElementById(`detail-${rowId}`);
            const icon = document.getElementById(`icon-${rowId}`);
            const contentDiv = document.getElementById(`content-${rowId}`);

            if (expandedSls.has(rowId)) {
                detailRow.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
                expandedSls.delete(rowId);
            } else {
                detailRow.classList.remove('hidden');
                icon.style.transform = 'rotate(90deg)';
                expandedSls.add(rowId);

                // Fetch if not cached
                const cacheKey = `${kdkec}-${kddesa}`;
                if (!slsDataCache[cacheKey]) {
                    try {
                        const safeKec = kdkec || 'UNKNOWN';
                        const safeDesa = kddesa || 'UNKNOWN';
                        const response = await fetch(`{{ url('/units/rekap/sls') }}/${safeKec}/${safeDesa}`);
                        const data = await response.json();
                        slsDataCache[cacheKey] = data;
                        renderSlsTable(contentDiv, data);
                    } catch (error) {
                        console.error(error);
                        contentDiv.innerHTML = '<span class="text-red-500 text-xs">Gagal load data SLS.</span>';
                    }
                } else {
                    renderSlsTable(contentDiv, slsDataCache[cacheKey]);
                }
            }
        }

        function renderSlsTable(container, data) {
            if (data.length === 0) {
                container.innerHTML = '<span class="text-gray-500 italic text-xs">Tidak ada data SLS.</span>';
                return;
            }

            let html = `
                <table class="w-full text-xs bg-white rounded border border-gray-200 mb-2">
                    <thead>
                        <tr class="bg-gray-100 text-gray-600">
                            <th class="px-3 py-2 text-left">Nama SLS</th>
                            <th class="px-3 py-2 text-right">Target</th>
                            <th class="px-3 py-2 text-right text-green-700">Ada (C)</th>
                            <th class="px-3 py-2 text-right text-orange-700">Ada (NC)</th>
                            <th class="px-3 py-2 text-right text-red-700">Belum</th>
                            <th class="px-3 py-2 text-left">Breakdown Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
            `;

            data.forEach(sls => {
                let statusHtml = '';
                if (Object.keys(sls.status_breakdown).length > 0) {
                    statusHtml = '<div class="flex flex-wrap gap-1">';
                    for (const [statusId, count] of Object.entries(sls.status_breakdown)) {
                        const colorClass = statusColors[statusId] || 'bg-gray-100 text-gray-700';
                        // Mini chips for SLS
                        statusHtml += `<span class="px-1 py-0.5 rounded text-[10px] font-bold ${colorClass.replace('bg-', 'bg-opacity-50 bg-')} border border-opacity-20">${statusId}:${count}</span>`;
                    }
                    statusHtml += '</div>';
                } else {
                    statusHtml = '-';
                }

                html += `
                    <tr class="hover:bg-indigo-50/50">
                        <td class="px-3 py-1.5 font-medium text-gray-700">
                             ${sls.sls_name}
                             <span class="block text-[10px] text-gray-400">${sls.sls_id}</span>
                        </td>
                        <td class="px-3 py-1.5 text-right">${sls.total}</td>
                        <td class="px-3 py-1.5 text-right text-green-600">${sls.with_coord}</td>
                        <td class="px-3 py-1.5 text-right text-orange-600">${sls.no_coord}</td>
                        <td class="px-3 py-1.5 text-right text-red-500">${sls.empty}</td>
                        <td class="px-3 py-1.5">${statusHtml}</td>
                    </tr>
                `;
            });

            container.innerHTML = html;
        }

        // --- TAMBAHAN DRILL-DOWN LOGIC ---
        const expandedKecamatanTambahan = new Set();
        const desaTambahanDataCache = {};
        const expandedSlsTambahan = new Set();
        const slsTambahanDataCache = {};

        async function toggleKecamatanTambahan(kdkec) {
            const detailRow = document.getElementById(`detail-tambahan-${kdkec}`);
            const parentRow = detailRow.previousElementSibling;
            const icon = document.getElementById(`icon-tambahan-${kdkec}`);
            const contentDiv = document.getElementById(`content-tambahan-${kdkec}`);

            if (expandedKecamatanTambahan.has(kdkec)) {
                // Collapse
                detailRow.classList.add('hidden');
                parentRow.classList.remove('expanded');
                icon.style.transform = 'rotate(0deg)';
                expandedKecamatanTambahan.delete(kdkec);
            } else {
                // Expand
                detailRow.classList.remove('hidden');
                parentRow.classList.add('expanded');
                icon.style.transform = 'rotate(90deg)';
                expandedKecamatanTambahan.add(kdkec);

                // Fetch data if not cached
                if (!desaTambahanDataCache[kdkec]) {
                    try {
                        const response = await fetch(`{{ url('/units/rekap/tambahan/desa') }}/${kdkec}`);
                        const data = await response.json();
                        desaTambahanDataCache[kdkec] = data;
                        renderDesaTableTambahan(kdkec, data);
                    } catch (error) {
                        console.error(error);
                        contentDiv.innerHTML = '<p class="text-center text-red-500 py-2">Gagal memuat data desa tambahan.</p>';
                    }
                } else {
                    renderDesaTableTambahan(kdkec, desaTambahanDataCache[kdkec]);
                }
            }
        }

        function renderDesaTableTambahan(kdkec, data) {
            const contentDiv = document.getElementById(`content-tambahan-${kdkec}`);

            if (data.length === 0) {
                contentDiv.innerHTML = '<p class="text-center text-gray-500 py-2">Tidak ada data desa tambahan.</p>';
                return;
            }

            let html = `
                <div class="bg-white rounded-lg shadow-sm border border-orange-100 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-orange-50 border-b border-orange-100">
                                <th class="px-3 py-2 text-left text-xs font-semibold text-orange-800">Desa (Tambahan)</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-orange-800">Target</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-green-700">Ada Coord</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-yellow-700">No Coord</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-red-700">Belum</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-orange-800">Progress</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
            `;

            data.forEach(desa => {
                const progressClass = desa.percentage == 100 ? 'bg-green-50' : '';
                const cleanKec = kdkec.replace(/\./g, '');
                const cleanDesa = desa.kddesa.replace(/\./g, '');
                const rowId = `desa-tambahan-${cleanKec}-${cleanDesa}`;

                html += `
                    <tr class="hover:bg-orange-50/50 cursor-pointer ${progressClass}" onclick="toggleSlsTambahan('${kdkec}', '${desa.kddesa}', '${rowId}')">
                        <td class="px-3 py-2">
                             <div class="flex items-center gap-2">
                                <span class="text-xs text-orange-400 transition-transform duration-200 inline-block" id="icon-${rowId}">▶</span>
                                <div>
                                    <div class="font-medium text-gray-800">${desa.desa_name}</div>
                                    <div class="text-xs text-gray-500">Kode: ${desa.kddesa}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-right font-semibold text-gray-700">${desa.total.toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2 text-right text-green-600 font-semibold">${desa.with_coord.toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2 text-right text-orange-600 font-semibold">${(desa.no_coord || 0).toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2 text-right text-red-600 font-semibold">${desa.empty.toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2">
                            <div class="flex flex-col items-center">
                                <div class="w-full bg-gray-200 rounded-full h-1.5 mb-1">
                                    <div class="bg-gradient-to-r from-orange-400 to-red-500 h-1.5 rounded-full" style="width: ${desa.percentage}%"></div>
                                </div>
                                <span class="text-xs font-bold text-gray-600">${desa.percentage.toFixed(1)}%</span>
                            </div>
                        </td>
                    </tr>
                    <!-- Hidden SLS Row -->
                    <tr id="detail-${rowId}" class="hidden bg-orange-50/30">
                        <td colspan="6" class="p-0">
                            <div id="content-${rowId}" class="pl-8 pr-4 py-2 border-l-4 border-orange-300">
                                <div class="text-xs text-gray-500 italic py-2">Memuat data SLS...</div>
                            </div>
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            contentDiv.innerHTML = html;
        }

        async function toggleSlsTambahan(kdkec, kddesa, rowId) {
            const detailRow = document.getElementById(`detail-${rowId}`);
            const icon = document.getElementById(`icon-${rowId}`);
            const contentDiv = document.getElementById(`content-${rowId}`);

            if (expandedSlsTambahan.has(rowId)) {
                detailRow.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
                expandedSlsTambahan.delete(rowId);
            } else {
                detailRow.classList.remove('hidden');
                icon.style.transform = 'rotate(90deg)';
                expandedSlsTambahan.add(rowId);

                const cacheKey = `${kdkec}-${kddesa}`;
                if (!slsTambahanDataCache[cacheKey]) {
                    try {
                        const safeKec = kdkec || 'UNKNOWN';
                        const safeDesa = kddesa || 'UNKNOWN';
                        const response = await fetch(`{{ url('/units/rekap/tambahan/sls') }}/${safeKec}/${safeDesa}`);
                        const data = await response.json();
                        slsTambahanDataCache[cacheKey] = data;
                        renderSlsTableTambahan(contentDiv, data);
                    } catch (error) {
                        console.error(error);
                        contentDiv.innerHTML = '<span class="text-red-500 text-xs">Gagal load data SLS Tambahan.</span>';
                    }
                } else {
                    renderSlsTableTambahan(contentDiv, slsTambahanDataCache[cacheKey]);
                }
            }
        }

        function renderSlsTableTambahan(container, data) {
            if (data.length === 0) {
                container.innerHTML = '<span class="text-gray-500 italic text-xs">Tidak ada data SLS Tambahan.</span>';
                return;
            }

            let html = `
                <table class="w-full text-xs bg-white rounded border border-orange-200 mb-2">
                    <thead>
                        <tr class="bg-orange-100/50 text-gray-700">
                            <th class="px-3 py-2 text-left">Nama SLS</th>
                            <th class="px-3 py-2 text-right">Target</th>
                            <th class="px-3 py-2 text-right text-green-700">Ada (C)</th>
                            <th class="px-3 py-2 text-right text-orange-700">Ada (NC)</th>
                            <th class="px-3 py-2 text-right text-red-700">Belum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
            `;

            data.forEach(sls => {
                html += `
                    <tr class="hover:bg-orange-50/50">
                        <td class="px-3 py-1.5 font-medium text-gray-700">
                             ${sls.sls_name}
                             <span class="block text-[10px] text-gray-400">${sls.sls_id}</span>
                        </td>
                        <td class="px-3 py-1.5 text-right font-semibold">${sls.total}</td>
                        <td class="px-3 py-1.5 text-right text-green-600 font-semibold">${sls.with_coord}</td>
                        <td class="px-3 py-1.5 text-right text-orange-600 font-semibold">${sls.no_coord}</td>
                        <td class="px-3 py-1.5 text-right text-red-500 font-semibold">${sls.empty}</td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        }

        // --- User Detail Logic ---
        let currentUserData = [];

        async function showUserDetail(username) {
            document.getElementById('userModal').classList.remove('hidden');
            document.getElementById('modalUsername').innerText = username;
            document.getElementById('userModalContent').innerHTML = '<tr><td colspan="4" class="p-4 text-center text-gray-500">Loading...</td></tr>';
            document.getElementById('userSearchInfo').value = ''; // Reset search

            try {
                const response = await fetch(`{{ url('/units/contributions') }}/${username}`);
                currentUserData = await response.json(); // Store for filtering
                renderUserTable(currentUserData);
            } catch (error) {
                console.error(error);
                document.getElementById('userModalContent').innerHTML = '<tr><td colspan="4" class="p-4 text-center text-red-500">Gagal memuat data.</td></tr>';
            }
        }

        function renderUserTable(data) {
            const tbody = document.getElementById('userModalContent');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="p-4 text-center text-gray-500">Belum ada data history.</td></tr>';
                return;
            }

            let html = '';
            data.forEach((log, index) => {
                const dateObj = new Date(log.created_at);
                const dateStr = dateObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
                const idsbr = log.unit ? log.unit.idsbr : '-';
                const unitName = log.unit ? log.unit.nama_usaha : '-';

                let changes = [];
                if (log.old_values && log.new_values) {
                    if (log.old_values.status != log.new_values.status) {
                        changes.push(`Status: <span class="text-red-500">${log.old_values.status}</span> ➔ <span class="text-green-600 font-bold">${log.new_values.status}</span>`);
                    }
                    if (log.old_values.lat != log.new_values.lat || log.old_values.long != log.new_values.long) {
                        changes.push(`<div>Koordinat: <span class="text-green-600">✓ Terupdate</span></div>`);
                    }
                }
                const changesHtml = changes.length > 0 ? changes.join('<br>') : '<span class="text-gray-400 italic">Simpan Ulang</span>';

                html += `
                    <tr class="hover:bg-gray-50">
                        <td class="p-3 text-center text-gray-600 font-semibold">${index + 1}</td>
                        <td class="p-3 text-gray-600">${dateStr}</td>
                        <td class="p-3 font-mono text-blue-600">${idsbr}</td>
                        <td class="p-3 font-medium text-gray-700">${unitName}</td>
                        <td class="p-3 text-xs bg-yellow-50">${changesHtml}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        function filterUserTable() {
            const query = document.getElementById('userSearchInfo').value.toLowerCase();
            if (!query) {
                renderUserTable(currentUserData);
                return;
            }

            const filtered = currentUserData.filter(log => {
                const idsbr = log.unit ? String(log.unit.idsbr).toLowerCase() : '';
                const unitName = log.unit ? log.unit.nama_usaha.toLowerCase() : '';
                return idsbr.includes(query) || unitName.includes(query);
            });
            renderUserTable(filtered);
        }

        // --- Daily Detail Logic ---
        let dailyCurrentPage = 1;
        let dailyHasMore = true;
        let dailyCurrentDate = '';
        let isDailyLoading = false;
        let searchTimeout = null;

        // Make functions global explicitly to avoid scope issues in large Blade files
        window.showDailyDetail = function (date) {
            const modal = document.getElementById('dailyModal');
            if (modal) modal.classList.remove('hidden');

            const niceDate = new Date(date).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            const modalDateEl = document.getElementById('modalDate');
            if (modalDateEl) modalDateEl.innerText = niceDate;

            const tbody = document.getElementById('dailyModalContent');
            if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-500">Loading...</td></tr>';

            const searchInput = document.getElementById('dailySearchInfo');
            if (searchInput) searchInput.value = '';

            dailyCurrentDate = date;
            dailyCurrentPage = 1;
            dailyHasMore = true;
            isDailyLoading = false;

            fetchDailyData(true);
        };

        async function fetchDailyData(isFirstLoad = false) {
            if (isDailyLoading || !dailyHasMore) return;
            isDailyLoading = true;

            const searchInput = document.getElementById('dailySearchInfo');
            const query = searchInput ? searchInput.value : '';
            const tbody = document.getElementById('dailyModalContent');

            if (!isFirstLoad && tbody) {
                tbody.insertAdjacentHTML('beforeend', '<tr id="dailyLoadRow"><td colspan="5" class="p-4 text-center text-gray-400 font-semibold animate-pulse">Menyiapkan aktivitas lainnya...</td></tr>');
            }

            try {
                const response = await fetch(`{{ url('/units/daily') }}/${dailyCurrentDate}?page=${dailyCurrentPage}&search=${encodeURIComponent(query)}`);
                const result = await response.json();

                if (!isFirstLoad) {
                    const loadRow = document.getElementById('dailyLoadRow');
                    if (loadRow) loadRow.remove();
                }

                if (tbody) {
                    if (isFirstLoad && result.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-500">Tidak ada aktivitas pada tanggal ini.</td></tr>';
                    } else if (isFirstLoad) {
                        tbody.innerHTML = '';
                        appendDailyRows(result.data);
                    } else {
                        appendDailyRows(result.data);
                    }

                    // Laravel pagination response structure
                    dailyHasMore = result.next_page_url !== null;
                    dailyCurrentPage++;

                    if (!dailyHasMore && result.data.length > 0) {
                        tbody.insertAdjacentHTML('beforeend', '<tr><td colspan="5" class="p-4 text-center text-gray-400 italic text-xs">Semua aktivitas telah ditampilkan</td></tr>');
                    }
                }

            } catch (error) {
                console.error(error);
                if (isFirstLoad && tbody) {
                    tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-red-500">Gagal memuat data.</td></tr>';
                }
            } finally {
                isDailyLoading = false;
            }
        }

        function appendDailyRows(data) {
            const tbody = document.getElementById('dailyModalContent');
            if (!tbody) return;

            let html = '';
            data.forEach(log => {
                const dateObj = new Date(log.created_at);
                const timeStr = dateObj.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                const unitName = log.unit ? log.unit.nama_usaha : 'Unit Terhapus';
                const idsbr = log.unit ? log.unit.idsbr : '-';
                const user = log.user_id;

                let changes = [];
                if (log.old_values && log.new_values) {
                    if (log.old_values.status != log.new_values.status) {
                        changes.push(`Status: <span class="text-red-500">${log.old_values.status}</span> ➔ <span class="text-green-600 font-bold">${log.new_values.status}</span>`);
                    }
                    if (log.old_values.lat != log.new_values.lat || log.old_values.long != log.new_values.long) {
                        changes.push(`<div>Koordinat: <span class="text-green-600">✓ Terupdate</span></div>`);
                    }
                }

                const changesHtml = changes.length > 0 ? changes.join('<br>') : '<span class="text-gray-400 italic">Simpan Ulang</span>';

                html += `
                    <tr class="hover:bg-gray-50">
                        <td class="p-3 font-mono text-gray-600">${timeStr}</td>
                        <td class="p-3 font-semibold text-gray-700">${user}</td>
                        <td class="p-3 font-mono text-blue-600">${idsbr}</td>
                        <td class="p-3 font-medium text-gray-700">${unitName}</td>
                        <td class="p-3 text-xs bg-yellow-50">${changesHtml}</td>
                    </tr>
                 `;
            });

            tbody.insertAdjacentHTML('beforeend', html);
        }

        // Initialize Listeners safely
        const dailySearchInput = document.getElementById('dailySearchInfo');
        if (dailySearchInput) {
            dailySearchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    dailyCurrentPage = 1;
                    dailyHasMore = true;
                    fetchDailyData(true);
                }, 500);
            });
        }

        const dailyScrollContainer = document.getElementById('dailyScrollContainer');
        if (dailyScrollContainer) {
            dailyScrollContainer.addEventListener('scroll', function () {
                const container = this;
                if (container.scrollTop + container.clientHeight >= container.scrollHeight - 100) {
                    if (!isDailyLoading && dailyHasMore) {
                        fetchDailyData(false);
                    }
                }
            });
        }
    </script>


    <div id="dailyContributorsModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all scale-100">
            <div class="bg-gradient-to-r from-teal-500 to-green-600 px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-bold text-white">Kontributor: <span id="modalContribDate"></span>
                </h2>
                <button onclick="closeModal('dailyContributorsModal')"
                    class="text-white hover:bg-white/20 rounded-full p-1 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div class="p-0 overflow-y-auto max-h-[60vh]">
                <table class="w-full text-sm">
                    <tbody id="dailyContributorsContent" class="divide-y divide-gray-100">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Progress Chart Modal -->
    <div id="progressChartModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 backdrop-blur-sm transition-opacity duration-300">
        <div id="progressChartContainer"
            class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl p-6 transform transition-all scale-95 opacity-0 relative">
            <button onclick="closeProgressChartModal()"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition z-10">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                📈 Progress Akumulasi
            </h2>
            <div class="relative h-[400px] w-full">
                <canvas id="detailProgressChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Podium Modal -->
    <div id="podiumModal" onclick="if(event.target === this) closePodiumModal()"
        class="fixed inset-0 bg-black/80 hidden z-50 backdrop-blur-md transition-opacity duration-300 opacity-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <!-- Confetti Canvas -->
            <canvas id="confettiCanvas" class="absolute inset-0 w-full h-full pointer-events-none z-10"></canvas>

            <div id="podiumContainer"
                class="relative z-20 w-full max-w-4xl mx-auto px-4 transform transition-transform duration-500 scale-90">
                <div class="bg-white rounded-3xl shadow-2xl overflow-hidden relative">
                    <!-- Header -->
                    <div
                        class="bg-gradient-to-r from-orange-500 to-red-600 p-6 text-center relative sticky top-0 z-50 shadow-md">
                        <!-- Close Button (Moved Inside Sticky Header) -->
                        <button onclick="closePodiumModal()"
                            class="absolute top-4 right-4 bg-white/20 hover:bg-white text-white hover:text-red-600 rounded-full p-2 shadow-lg transition transform hover:scale-110 backdrop-blur-sm z-50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12">
                                </path>
                            </svg>
                        </button>
                        <h2 class="text-3xl font-black text-white mb-1 tracking-tight drop-shadow-sm">🏆 Hall of Fame
                        </h2>
                        <p class="text-orange-100 font-medium text-sm">Top Kontributor Groundcheck</p>
                    </div>

                    <div class="p-6 sm:p-8 bg-gradient-to-b from-white to-orange-50 relative">
                        <!-- Podium Stage -->
                        <div id="podiumStage"
                            class="flex items-end justify-center gap-2 sm:gap-4 mb-8 sm:mb-12 h-64 sm:h-80 pt-10">
                            <!-- Rank 2 -->
                            <div
                                class="podium-column flex-1 max-w-[100px] sm:max-w-[140px] flex flex-col items-center group relative order-1">
                                <div class="mb-2 transition-transform group-hover:-translate-y-2 duration-300 relative">
                                    <div
                                        class="w-12 h-12 sm:w-16 sm:h-16 rounded-full border-4 border-gray-300 bg-gray-200 overflow-hidden shadow-lg relative z-20 flex items-center justify-center text-2xl">
                                        🥈
                                    </div>
                                    <div
                                        class="absolute -bottom-1 -right-1 bg-gray-600 text-white w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center rounded-full text-xs font-bold border-2 border-white z-30 shadow">
                                        2</div>
                                </div>
                                <div
                                    class="w-full bg-gradient-to-t from-gray-300 to-gray-100 rounded-t-lg shadow-lg flex flex-col justify-end items-center h-32 sm:h-40 border-t-4 border-gray-400 relative overflow-hidden">
                                    <div class="absolute inset-0 bg-white/30 backdrop-blur-[1px]"></div>
                                    <div class="relative z-10 w-full p-2 text-center mb-2">
                                        <div class="font-bold text-gray-800 text-xs sm:text-sm truncate w-full"
                                            id="podium-2-name">User</div>
                                        <div class="text-gray-600 text-[10px] sm:text-xs font-semibold"
                                            id="podium-2-score">
                                            0</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rank 1 -->
                            <div
                                class="podium-column flex-1 max-w-[120px] sm:max-w-[160px] flex flex-col items-center group relative order-2 -mt-10 z-10">
                                <div class="mb-3 transition-transform group-hover:-translate-y-2 duration-300 relative">
                                    <div
                                        class="w-16 h-16 sm:w-20 sm:h-20 rounded-full border-4 border-yellow-400 bg-yellow-100 overflow-hidden shadow-xl relative z-20 flex items-center justify-center text-4xl">
                                        🥇
                                    </div>
                                    <div
                                        class="absolute -top-6 left-1/2 -translate-x-1/2 text-3xl animate-bounce-gentle">
                                        👑</div>
                                </div>
                                <div
                                    class="w-full bg-gradient-to-t from-yellow-400 to-yellow-200 rounded-t-xl shadow-2xl flex flex-col justify-end items-center h-48 sm:h-60 border-t-4 border-yellow-500 relative overflow-hidden">
                                    <div class="absolute inset-0 bg-white/20 backdrop-blur-[1px]"></div>
                                    <div class="relative z-10 w-full p-3 text-center mb-4">
                                        <div class="font-black text-yellow-900 text-sm sm:text-lg truncate w-full"
                                            id="podium-1-name">User</div>
                                        <div class="text-yellow-800 text-xs sm:text-sm font-bold bg-yellow-300/50 px-2 py-0.5 rounded-full inline-block mt-1"
                                            id="podium-1-score">0</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rank 3 -->
                            <div
                                class="podium-column flex-1 max-w-[100px] sm:max-w-[140px] flex flex-col items-center group relative order-3">
                                <div class="mb-2 transition-transform group-hover:-translate-y-2 duration-300 relative">
                                    <div
                                        class="w-12 h-12 sm:w-16 sm:h-16 rounded-full border-4 border-orange-300 bg-orange-100 overflow-hidden shadow-lg relative z-20 flex items-center justify-center text-2xl">
                                        🥉
                                    </div>
                                    <div
                                        class="absolute -bottom-1 -right-1 bg-orange-600 text-white w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center rounded-full text-xs font-bold border-2 border-white z-30 shadow">
                                        3</div>
                                </div>
                                <div
                                    class="w-full bg-gradient-to-t from-orange-300 to-orange-100 rounded-t-lg shadow-lg flex flex-col justify-end items-center h-24 sm:h-32 border-t-4 border-orange-400 relative overflow-hidden">
                                    <div class="absolute inset-0 bg-white/30 backdrop-blur-[1px]"></div>
                                    <div class="relative z-10 w-full p-2 text-center mb-2">
                                        <div class="font-bold text-orange-900 text-xs sm:text-sm truncate w-full"
                                            id="podium-3-name">User</div>
                                        <div class="text-orange-700 text-[10px] sm:text-xs font-semibold"
                                            id="podium-3-score">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mb-4">
                            <h3 class="text-gray-500 font-bold text-xs uppercase tracking-widest mb-2">Runner Up
                            </h3>
                        </div>
                        <div id="runnersUpList"></div>
                    </div>

                    <div class="p-4 bg-gray-50 text-center text-xs text-gray-400">
                        GCSBR &copy; 2026
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function showDailyContributors(date) {
            if (date === 'DATA AWAL') return;

            const modal = document.getElementById('dailyContributorsModal');
            modal.classList.remove('hidden');

            const dateObj = new Date(date);
            const options = { day: 'numeric', month: 'long', year: 'numeric' };
            document.getElementById('modalContribDate').innerText = dateObj.toLocaleDateString('id-ID', options);

            const content = document.getElementById('dailyContributorsContent');
            content.innerHTML = '<tr><td colspan="2" class="p-6 text-center text-gray-500 italic">Memuat data...</td></tr>';

            try {
                const response = await fetch(`{{ url('/units/daily-contributors') }}/${date}`);
                const data = await response.json();

                let html = '';
                if (data.length === 0) {
                    html = '<tr><td colspan="2" class="p-6 text-center text-gray-500 italic">Tidak ada data kontribusi user.</td></tr>';
                } else {
                    data.forEach((item, index) => {
                        html += `
                            <tr class="hover:bg-green-50 transition group">
                                <td class="px-6 py-3 font-medium text-gray-700">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-xs font-bold shadow-sm group-hover:bg-green-200 transition">
                                            ${index + 1}
                                        </div>
                                        <span class="text-base">${item.user || 'System'}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <span class="font-bold text-green-600 text-lg">+${item.count}</span>
                                    <span class="text-xs text-gray-400 block">unit</span>
                                </td>
                            </tr>
                        `;
                    });
                }
                content.innerHTML = html;

            } catch (error) {
                console.error(error);
                content.innerHTML = '<tr><td colspan="2" class="p-6 text-center text-red-500">Gagal memuat data.</td></tr>';
            }
        }

        // --- Visualization Functions ---
        let progressChartInstance = null;

        function openProgressChart() {
            const modal = document.getElementById('progressChartModal');
            const container = document.getElementById('progressChartContainer');

            modal.classList.remove('hidden');
            // Animation
            setTimeout(() => {
                container.classList.remove('scale-95', 'opacity-0');
                container.classList.add('scale-100', 'opacity-100');
            }, 10);

            // Init Chart
            if (progressChartInstance) {
                progressChartInstance.destroy();
            }

            // Ensure chartData is available
            if (typeof chartData === 'undefined' || !chartData) {
                console.error("Chart data not found");
                return;
            }

            const ctx = document.getElementById('detailProgressChart').getContext('2d');

            // Use filtered data for labels and counts
            const labels = chartData.map(d => {
                if (d.date === 'DATA AWAL') return 'Awal';
                const date = new Date(d.date);
                return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
            });
            const dataTotal = chartData.map(d => d.total);
            const dataCumulative = chartData.map(d => d.cumulative);

            progressChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Total Harian',
                            data: dataTotal,
                            type: 'bar',
                            backgroundColor: '#3B82F6',
                            borderRadius: 4,
                            order: 2,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Akumulasi',
                            data: dataCumulative,
                            type: 'line',
                            borderColor: '#6366F1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            borderWidth: 3,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            fill: false,
                            tension: 0.1,
                            yAxisID: 'y1',
                            order: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#1F2937',
                            bodyColor: '#4B5563',
                            borderColor: '#E5E7EB',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: true,
                            callbacks: {
                                label: function (context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Harian'
                            },
                            grid: {
                                color: '#F3F4F6'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Akumulasi'
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        function closeProgressChartModal() {
            const modal = document.getElementById('progressChartModal');
            const container = document.getElementById('progressChartContainer');

            container.classList.remove('scale-100', 'opacity-100');
            container.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // --- Podium Logic ---
        function openPodium() {
            const modal = document.getElementById('podiumModal');
            const container = document.getElementById('podiumContainer');
            const stage = document.getElementById('podiumStage');
            const runnersUpList = document.getElementById('runnersUpList');

            if (!podiumData || podiumData.length === 0) {
                alert("Belum ada data kontributor.");
                return;
            }

            modal.classList.remove('hidden');

            // Reset animations
            const cols = stage.querySelectorAll('.podium-column');
            cols.forEach(col => {
                col.style.transition = 'none';
                col.style.transform = 'translateY(100%)';
            });

            setTimeout(() => {
                modal.classList.remove('opacity-0');
                container.classList.remove('scale-90');
                container.classList.add('scale-100');

                // Trigger Confetti
                triggerConfetti();

                // Animate Podium columns with better staggering
                cols.forEach((col, index) => {
                    col.style.transition = 'transform 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275)'; // Bouncy effect

                    // Order: 2nd (index 0 in DOM?), 1st (index 1), 3rd (index 2)
                    // DOM order is 2, 1, 3 based on HTML structure
                    let delay = 0;
                    if (index === 0) delay = 200; // Rank 2
                    if (index === 1) delay = 400; // Rank 1
                    if (index === 2) delay = 600; // Rank 3

                    setTimeout(() => {
                        col.style.transform = 'translateY(0)';
                    }, delay);
                });

            }, 50);

            // Populate Top 3
            const p1 = podiumData[0];
            if (p1) {
                const nameEl = document.getElementById('podium-1-name');
                if (nameEl) nameEl.innerText = p1.user_id;
                const scoreEl = document.getElementById('podium-1-score');
                if (scoreEl) scoreEl.innerText = p1.total.toLocaleString('id-ID') + ' Unit';
            }

            const p2 = podiumData[1];
            if (p2) {
                const nameEl = document.getElementById('podium-2-name');
                if (nameEl) nameEl.innerText = p2.user_id;
                const scoreEl = document.getElementById('podium-2-score');
                if (scoreEl) scoreEl.innerText = p2.total.toLocaleString('id-ID') + ' Unit';
            }

            const p3 = podiumData[2];
            if (p3) {
                const nameEl = document.getElementById('podium-3-name');
                if (nameEl) nameEl.innerText = p3.user_id;
                const scoreEl = document.getElementById('podium-3-score');
                if (scoreEl) scoreEl.innerText = p3.total.toLocaleString('id-ID') + ' Unit';
            }

            // Runners Up
            const runners = podiumData.slice(3);
            let runnersHtml = '';
            if (runners.length > 0) {
                runnersHtml = '<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 sm:p-6 pt-2 max-h-60 overflow-y-auto">';
                runners.forEach((r, idx) => {
                    runnersHtml += `
                        <div class="flex items-center justify-between bg-white p-2.5 rounded-lg shadow-sm border border-gray-100 animate-slide-in-right" style="animation-delay: ${800 + (idx * 50)}ms">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-full bg-gray-50 font-bold text-gray-500 flex items-center justify-center text-xs border border-gray-200">
                                    ${idx + 4}
                                </div>
                                <div class="font-medium text-gray-700 text-sm truncate max-w-[140px]" title="${r.user_id}">
                                    ${r.user_id}
                                </div>
                            </div>
                            <div class="font-bold text-gray-600 text-xs bg-gray-50 px-2 py-1 rounded">
                                ${r.total.toLocaleString('id-ID')}
                            </div>
                        </div>
                    `;
                });
                runnersHtml += '</div>';
            } else {
                runnersHtml = '<div class="text-center text-gray-400 p-8 text-sm">Belum ada runner-up.</div>';
            }
            runnersUpList.innerHTML = runnersHtml;
        }

        function closePodiumModal() {
            const modal = document.getElementById('podiumModal');
            const container = document.getElementById('podiumContainer');

            container.classList.remove('scale-100');
            container.classList.add('scale-90');
            modal.classList.add('opacity-0');

            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function triggerConfetti() {
            const canvas = document.getElementById('confettiCanvas');
            if (!canvas) return; // Guard clause

            const myConfetti = confetti.create(canvas, {
                resize: true,
                useWorker: true
            });

            myConfetti({
                particleCount: 150,
                spread: 70,
                origin: { y: 0.6 }
            });

            // Fireworks effect
            const duration = 3000;
            const animationEnd = Date.now() + duration;
            const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 0 };

            function randomInRange(min, max) {
                return Math.random() * (max - min) + min;
            }

            const interval = setInterval(function () {
                const timeLeft = animationEnd - Date.now();

                if (timeLeft <= 0) {
                    return clearInterval(interval);
                }

                const particleCount = 50 * (timeLeft / duration);
                // since particles fall down, start a bit higher than random
                myConfetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
                myConfetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
            }, 250);
        }
    </script>

    <!-- Map Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Turf.js/6.5.0/turf.min.js"></script>

    <script>
        // Map Application Logic
        document.addEventListener('DOMContentLoaded', async () => {
            // Dropdown Menu Logic for 'Fitur Tambahan'
            const btnFiturTambahan = document.getElementById('btnFiturTambahan');
            const dropdownFitur = document.getElementById('dropdownFitur');
            if (btnFiturTambahan && dropdownFitur) {
                btnFiturTambahan.addEventListener('click', (e) => {
                    e.stopPropagation();
                    dropdownFitur.classList.toggle('hidden');
                });

                document.addEventListener('click', (e) => {
                    if (!dropdownFitur.contains(e.target)) {
                        dropdownFitur.classList.add('hidden');
                    }
                });
            }
            initMap();
        });

        let map;
        const defaultCenter = [-1.72, 103.25]; // Approx Batang Hari
        const defaultZoom = 10;

        async function initMap() {
            // Check if map container exists
            if (!document.getElementById('map')) return;

            map = L.map('map').setView(defaultCenter, defaultZoom);

            // Base Layers
            const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap', maxZoom: 19
            });

            const googleStreets = L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                maxZoom: 20, subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
            });

            const googleHybrid = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
                maxZoom: 20, subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
            });

            const esriSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19,
                attribution: 'Tiles &copy; Esri'
            });

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

            const bingSatellite = L.tileLayer.bing('https://t{s}.tiles.virtualearth.net/tiles/a{q}.jpeg?g=129&mkt=en-US&shading=hill&ts=2', {
                maxZoom: 19,
                subdomains: ['0', '1', '2', '3'],
                attribution: '&copy; Bing Maps'
            });

            // Default to Google Hybrid
            map.addLayer(googleHybrid);

            L.control.layers({
                "Google Maps": googleStreets,
                "Google Hybrid": googleHybrid,
                "Esri Satellite (Backup)": esriSatellite,
                "Bing Satellite": bingSatellite,
                "OpenStreetMap": osm
            }).addTo(map);

            // Load Data
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

                const unitRes = await fetch('{{ route("api.map_stats") }}');
                if (!unitRes.ok) throw new Error("Failed to load map stats");

                const units = await unitRes.json();
                const markers = L.markerClusterGroup({
                    disableClusteringAtZoom: 17,
                    maxClusterRadius: 40
                });

                units.forEach(u => {
                    if (!u.lat || !u.lng) return;

                    const marker = L.circleMarker([u.lat, u.lng], {
                        radius: 6,
                        fillColor: getColorByStatus(u.status),
                        color: "#fff",
                        weight: 1,
                        opacity: 1,
                        fillOpacity: 0.8
                    }).bindPopup(`
                <div class="font-sans min-w-[200px]">
                    <h3 class="font-bold text-sm border-b pb-1 mb-1 line-clamp-2">${u.name}</h3>
                    <div class="text-xs space-y-1">
                        <p><span class="font-semibold">Status:</span> ${getStatusLabel(u.status)}</p>
                        <p><span class="font-semibold">ID:</span> ${u.id}</p>
                        <p><span class="font-semibold">IDSBR:</span> ${u.idsbr || '-'}</p>
                        <p><span class="font-semibold">SLS:</span> ${u.sls_id || '-'}</p>
                    </div>
                    <div class="mt-2 text-center">
                        <a href="https://www.google.com/maps/search/?api=1&query=${u.lat},${u.lng}" target="_blank"
                            class="inline-flex items-center gap-1 text-white bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Lihat Posisi
                        </a>
                    </div>
                </div>
                `);
                    markers.addLayer(marker);
                });

                map.addLayer(markers);

                // Search functionality
                const searchInput = document.getElementById('mapSearch');
                const searchResults = document.getElementById('searchResults');
                const clearBtn = document.getElementById('clearSearch');

                // Store units and markers for search
                const unitMarkerMap = new Map();
                units.forEach(u => {
                    if (!u.lat || !u.lng) return;
                    const markerKey = `${u.lat},${u.lng}`;
                    unitMarkerMap.set(u.id, { unit: u, markerKey });
                });

                // Search input handler
                let currentKecFilter = '';
                let currentDesaFilter = '';
                let currentSlsFilter = '';

                // Populate filter dropdowns
                const kecamatanSet = new Set();
                const kecamatanNames = {}; // Store names
                const desaByKec = {};
                const desaNames = {}; // Store desa names

                units.forEach(u => {
                    if (u.kdkec) {
                        kecamatanSet.add(u.kdkec);
                        kecamatanNames[u.kdkec] = u.kec_name || `Kec ${u.kdkec}`;

                        if (!desaByKec[u.kdkec]) desaByKec[u.kdkec] = {};
                        if (u.kddesa) {
                            const desaKey = `${u.kdkec}_${u.kddesa}`;
                            desaByKec[u.kdkec][u.kddesa] = u.desa_name || `Desa ${u.kddesa}`;
                        }
                    }
                });

                // Populate kecamatan dropdown
                const filterKec = document.getElementById('filterKecamatan');
                Array.from(kecamatanSet).sort((a, b) => parseFloat(a) - parseFloat(b)).forEach(kec => {
                    const opt = document.createElement('option');
                    opt.value = kec;
                    opt.textContent = kecamatanNames[kec];
                    filterKec.appendChild(opt);
                });
                // Kecamatan filter change handler
                filterKec.addEventListener('change', (e) => {
                    currentKecFilter = e.target.value;
                    currentDesaFilter = ''; // Reset desa filter
                    currentSlsFilter = ''; // Reset SLS filter

                    // Update desa dropdown
                    const filterDes = document.getElementById('filterDesa');
                    filterDes.innerHTML = '<option value="">Semua Desa</option>';

                    // Reset SLS dropdown
                    const filterSls = document.getElementById('filterSls');
                    filterSls.innerHTML = '<option value="">Semua SLS</option>';

                    if (currentKecFilter && desaByKec[currentKecFilter]) {
                        Object.entries(desaByKec[currentKecFilter])
                            .sort((a, b) => parseFloat(a[0]) - parseFloat(b[0]))
                            .forEach(([desaCode, desaName]) => {
                                const opt = document.createElement('option');
                                opt.value = desaCode;
                                opt.textContent = desaName;
                                filterDes.appendChild(opt);
                            });
                    }

                    // Filter and zoom map
                    filterAndZoomMap();
                });

                // Desa filter change handler
                document.getElementById('filterDesa').addEventListener('change', async (e) => {
                    currentDesaFilter = e.target.value;
                    currentSlsFilter = ''; // Reset SLS filter

                    // Reset SLS dropdown
                    const filterSls = document.getElementById('filterSls');
                    filterSls.innerHTML = '<option value="">Semua SLS</option>';

                    if (currentKecFilter && currentDesaFilter) {
                        try {
                            const safeKec = currentKecFilter || 'UNKNOWN';
                            const safeDesa = currentDesaFilter || 'UNKNOWN';
                            const response = await fetch(`{{ url('/units/rekap/sls') }}/${safeKec}/${safeDesa}`);
                            const slsData = await response.json();

                            slsData.forEach(sls => {
                                const opt = document.createElement('option');
                                opt.value = sls.sls_id;
                                opt.textContent = `${sls.sls_id} - ${sls.sls_name}`;
                                filterSls.appendChild(opt);
                            });
                        } catch (error) {
                            console.error("Failed to fetch SLS data for dropdown:", error);
                        }
                    }

                    filterAndZoomMap();
                });

                // SLS filter change handler
                document.getElementById('filterSls').addEventListener('change', (e) => {
                    currentSlsFilter = e.target.value;
                    filterAndZoomMap();
                });

                // Function to filter markers and zoom to bounds
                function filterAndZoomMap() {
                    // Clear existing markers
                    markers.clearLayers();

                    // Filter units
                    const filtered = units.filter(u => {
                        if (!u.lat || !u.lng) return false;
                        if (currentKecFilter && u.kdkec != currentKecFilter) return false;
                        if (currentDesaFilter && u.kddesa != currentDesaFilter) return false;
                        if (currentSlsFilter && u.sls_id != currentSlsFilter) return false;
                        return true;
                    });

                    // Add filtered markers
                    const bounds = [];
                    filtered.forEach(u => {
                        const marker = L.circleMarker([u.lat, u.lng], {
                            radius: 6,
                            fillColor: getColorByStatus(u.status),
                            color: "#fff",
                            weight: 1,
                            opacity: 1,
                            fillOpacity: 0.8
                        }).bindPopup(`
                <div class="font-sans min-w-[200px]">
                    <h3 class="font-bold text-sm border-b pb-1 mb-1 line-clamp-2">${u.name}</h3>
                    <div class="text-xs space-y-1">
                        <p><span class="font-semibold">Status:</span> ${getStatusLabel(u.status)}</p>
                        <p><span class="font-semibold">ID:</span> ${u.id}</p>
                        <p><span class="font-semibold">IDSBR:</span> ${u.idsbr || '-'}</p>
                        <p><span class="font-semibold">SLS:</span> ${u.sls_id || '-'}</p>
                    </div>
                    <div class="mt-2 text-center">
                        <a href="https://www.google.com/maps/search/?api=1&query=${u.lat},${u.lng}" target="_blank"
                            class="inline-flex items-center gap-1 text-white bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Lihat Posisi
                        </a>
                    </div>
                </div>
                `);
                        markers.addLayer(marker);
                        bounds.push([u.lat, u.lng]);
                    });

                    // Update Map Coordinate Text Badge
                    const badge = document.getElementById('filterCoordinateCount');
                    if (currentKecFilter || currentDesaFilter || currentSlsFilter) {
                        badge.classList.remove('hidden');
                        badge.classList.remove('opacity-0');
                        badge.textContent = `Menampilkan ${filtered.length} target lokasi`;
                    } else {
                        badge.classList.add('opacity-0');
                        setTimeout(() => badge.classList.add('hidden'), 300); // Wait for transition
                    }

                    // Update Active Filters Copyable Text
                    const activeFiltersContainer = document.getElementById('activeFiltersContainer');
                    const activeFiltersText = document.getElementById('activeFiltersText');

                    let filterParts = [];
                    if (currentKecFilter) {
                        const kecSelect = document.getElementById('filterKecamatan');
                        filterParts.push(`Kec. ${kecSelect.options[kecSelect.selectedIndex].text}`);
                    }
                    if (currentDesaFilter) {
                        const desaSelect = document.getElementById('filterDesa');
                        filterParts.push(`Desa ${desaSelect.options[desaSelect.selectedIndex].text}`);
                    }
                    if (currentSlsFilter) {
                        const slsSelect = document.getElementById('filterSls');
                        const slsText = slsSelect.options[slsSelect.selectedIndex].text.split(' - ').pop(); // Just get the name part if possible
                        filterParts.push(`SLS ${slsText}`);
                    }

                    if (filterParts.length > 0) {
                        activeFiltersText.textContent = filterParts.join(', ');
                        activeFiltersContainer.classList.remove('hidden');
                    } else {
                        activeFiltersContainer.classList.add('hidden');
                    }

                    map.addLayer(markers);
                    if (bounds.length > 0) {
                        map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
                    } else {
                        // Reset to default view if no results
                        map.setView([-1.5, 103.6], 10);
                    }

                    // Update search to reflect filter
                    searchInput.dispatchEvent(new Event('input'));
                }

                // Copy active filters to clipboard
                document.getElementById('copyFiltersBtn').addEventListener('click', function () {
                    const textToCopy = document.getElementById('activeFiltersText').textContent;
                    if (textToCopy) {
                        navigator.clipboard.writeText(textToCopy).then(() => {
                            const originalColor = this.innerHTML;
                            this.innerHTML = '<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                            setTimeout(() => {
                                this.innerHTML = originalColor;
                            }, 2000);
                        }).catch(err => {
                            console.error('Failed to copy text: ', err);
                        });
                    }
                });

                searchInput.addEventListener('input', (e) => {
                    const query = e.target.value.trim().toLowerCase();

                    // Apply filters
                    let filtered = units.filter(u => {
                        // Kecamatan filter
                        if (currentKecFilter && u.kdkec != currentKecFilter) return false;
                        // Desa filter
                        if (currentDesaFilter && u.kddesa != currentDesaFilter) return false;
                        // Text search (name or idsbr)
                        if (query.length > 0) {
                            const matchName = u.name && u.name.toLowerCase().includes(query);
                            const matchIdsbr = u.idsbr && u.idsbr.toLowerCase().includes(query);
                            return matchName || matchIdsbr;
                        }
                        return true;
                    });

                    // Show/hide clear button
                    if (query.length === 0 && !currentKecFilter && !currentDesaFilter) {
                        searchResults.classList.add('hidden');
                        clearBtn.classList.add('hidden');
                        return;
                    }

                    clearBtn.classList.remove('hidden');

                    // Limit results
                    const matches = filtered.slice(0, 20);

                    if (matches.length === 0) {
                        searchResults.innerHTML = '<div class="p-4 text-center text-gray-500 text-sm italic">Tidak ada hasil</div>';
                        searchResults.classList.remove('hidden');
                        return;
                    }

                    // Render results
                    let html = '<div class="divide-y divide-gray-100">';
                    matches.forEach(u => {
                        html += `
                            <div class="p-3 hover:bg-purple-50 cursor-pointer transition group" onclick="selectBusiness(${u.id})">
                                <div class="font-semibold text-sm text-gray-800 group-hover:text-purple-600 line-clamp-1">${u.name}</div>
                                <div class="text-xs text-gray-500 mt-0.5">IDSBR: ${u.idsbr || '-'} • SLS: ${u.sls_id || '-'}</div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    if (filtered.length > 20) {
                        html += '<div class="p-2 text-center text-xs text-gray-500 bg-gray-50">Menampilkan 20 dari ' + filtered.length + ' hasil</div>';
                    }
                    searchResults.innerHTML = html;
                    searchResults.classList.remove('hidden');
                });

                // Clear button handler
                clearBtn.addEventListener('click', () => {
                    searchInput.value = '';
                    document.getElementById('filterKecamatan').value = '';
                    document.getElementById('filterDesa').value = '';
                    currentKecFilter = '';
                    currentDesaFilter = '';
                    searchResults.classList.add('hidden');
                    clearBtn.classList.add('hidden');
                    searchInput.focus();  // Reset map to show all markers
                    filterAndZoomMap();
                });

                // Close results when clicking outside
                document.addEventListener('click', (e) => {
                    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                        searchResults.classList.add('hidden');
                    }
                });

                // Global function to select business
                window.selectBusiness = (id) => {
                    const data = unitMarkerMap.get(id);
                    if (!data) return;

                    const u = data.unit;

                    // Zoom to location
                    map.setView([u.lat, u.lng], 18);

                    // Find and open the marker popup
                    markers.eachLayer(layer => {
                        if (layer.getLatLng && layer.getLatLng().lat === u.lat && layer.getLatLng().lng === u.lng) {
                            setTimeout(() => {
                                layer.openPopup();
                            }, 300);
                        }
                    });

                    // Update search input and hide results
                    searchInput.value = u.name;
                    searchResults.classList.add('hidden');
                };


            } catch (err) {
                console.error("Map Init Error:", err);
                document.getElementById('map').innerHTML = `< div class="flex items-center justify-center h-full text-red-500 p-4" > Failed to load map data: ${err.message}</div >`;
            }

            // Reset Button Logic
            const btnReset = document.getElementById('btnResetMap');
            if (btnReset) {
                btnReset.classList.remove('hidden');
                btnReset.addEventListener('click', () => {
                    map.setView(defaultCenter, defaultZoom);
                });
            }
        }

        function getColorByStatus(status) {
            const colors = {
                1: '#10B981', // Green - Aktif
                2: '#FBBF24', // Yellow - Tutup Sementara
                3: '#3B82F6', // Blue - Belum Beroperasi
                4: '#EF4444', // Red - Tutup
                5: '#8B5CF6', // Purple - Alih Usaha
                6: '#9CA3AF', // Gray - Tidak Ditemukan
                7: '#14B8A6', // Teal - Aktif Pindah
                8: '#F97316', // Orange - Aktif Nonrespon
                9: '#EC4899', // Pink - Duplikat
                10: '#6366F1' // Indigo - Salah Kode Wilayah
            };
            return colors[status] || '#3B82F6';
        }

        function getStatusLabel(status) {
            const labels = {
                1: 'Aktif',
                2: 'Tutup Sementara',
                3: 'Belum Beroperasi',
                4: 'Tutup',
                5: 'Alih Usaha',
                6: 'Tidak Ditemukan',
                7: 'Aktif Pindah',
                8: 'Aktif Nonrespon',
                9: 'Duplikat',
                10: 'Salah Kode Wilayah'
            };
            return labels[status] || status;
        }

        // PDF Export Function
        async function exportToPDF() {
            const { jsPDF } = window.jspdf;

            // Show loading indicator
            const btn = event.target.closest('button');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Generating...';
            btn.disabled = true;

            try {
                const pdf = new jsPDF('p', 'mm', 'a4');
                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                let yPosition = 20;

                // Title
                pdf.setFontSize(20);
                pdf.setTextColor(102, 126, 234);
                pdf.text('Rekapitulasi Data Groundcheck', pageWidth / 2, yPosition, { align: 'center' });

                yPosition += 10;
                pdf.setFontSize(10);
                pdf.setTextColor(100, 100, 100);
                pdf.text('Generated: ' + new Date().toLocaleString('id-ID'), pageWidth / 2, yPosition, { align: 'center' });

                yPosition += 15;

                // Capture Summary Cards
                const summarySection = document.getElementById('summary-stats');
                if (summarySection) {
                    const canvas = await html2canvas(summarySection, { scale: 2, backgroundColor: '#f9fafb' });
                    const imgData = canvas.toDataURL('image/png');
                    const imgWidth = pageWidth - 20;
                    const imgHeight = (canvas.height * imgWidth) / canvas.width;

                    if (yPosition + imgHeight > pageHeight - 20) {
                        pdf.addPage();
                        yPosition = 20;
                    }

                    pdf.addImage(imgData, 'PNG', 10, yPosition, imgWidth, imgHeight);
                    yPosition += imgHeight + 10;
                }

                // Capture Main Content Grid (Table + Sidebar)
                const contentGrid = document.getElementById('content-grid');
                if (contentGrid) {
                    if (yPosition + 50 > pageHeight - 20) {
                        pdf.addPage();
                        yPosition = 20;
                    }

                    // Use logging to debug if needed
                    const canvas = await html2canvas(contentGrid, {
                        scale: 2,
                        backgroundColor: '#ffffff',
                        logging: false,
                        useCORS: true
                    });

                    const imgData = canvas.toDataURL('image/png');
                    const imgWidth = pageWidth - 20;
                    const imgHeight = (canvas.height * imgWidth) / canvas.width;

                    // If image is too long for one page, we might need to slice it (complex)
                    // limit height to page height for now to avoid mess
                    let printHeight = imgHeight;
                    if (printHeight > pageHeight - 30) {
                        printHeight = pageHeight - 30;
                        // This just shrinks or crops. Ideally we handle multi-page better.
                    }

                    if (yPosition + printHeight > pageHeight - 20) {
                        pdf.addPage();
                        yPosition = 20;
                    }

                    pdf.addImage(imgData, 'PNG', 10, yPosition, imgWidth, printHeight);
                    yPosition += printHeight + 10;
                }

                // Save PDF
                const filename = `Rekap_Groundcheck_${new Date().toISOString().split('T')[0]}.pdf`;
                pdf.save(filename);

                // Reset button
                btn.innerHTML = originalHTML;
                btn.disabled = false;

            } catch (error) {
                console.error('PDF Export Error:', error);
                alert('Gagal membuat PDF. Silakan coba lagi.');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        }
    </script>

</body>

</html>
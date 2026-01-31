<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Data - Groundcheck App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" /><link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" /><link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" /><style>@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        body {
            font-family: 'Inter', sans-serif;
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

        #map {
            height: 500px;
            width: 100%;
            z-index: 1;
            border-radius: 0.75rem;
        }

        /* Fix Tailwind vs Leaflet Conflict - SUPER AGGRESSIVE */
        /* Map Styles */
        #map {
            width: 100%;
            height: 100%;
            border-radius: 0.5rem;
            isolation: isolate;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Header -->
    <div class="gradient-bg text-white py-6 shadow-lg">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold mb-1">📊 Rekapitulasi Data Groundcheck</h1>
                    <p class="text-purple-100 text-sm">Monitoring & Analisis Progress Lapangan</p>
                </div>
                <a href="{{ url('/') }}"
                    class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-6 py-2.5 rounded-lg transition font-semibold">
                    ← Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-8">

        <!-- Interactive Map Section -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 mb-8 overflow-hidden">
            <div
                class="px-6 py-4 flex justify-between items-center sm:flex-row flex-col gap-4 bg-gray-50 border-b border-gray-100">
                <div>
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <span class="text-2xl">🗺️</span> Sebaran Usaha
                    </h2>
                    <p class="text-gray-500 text-sm">
                        Kab. Batang Hari
                        <span id="breadcrumb" class="font-bold text-blue-600"></span>
                    </p>
                </div>
                <div>
                    <button id="btnResetMap"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition hidden">
                        ↺ Reset Zoom
                    </button>
                </div>
            </div>

            <div class="relative w-full h-[500px]">
                <div id="map"></div>

                <!-- Simple Legend -->
                <div
                    class="absolute bottom-6 left-6 z-[1000] bg-white p-3 rounded-lg shadow-xl border border-gray-100 text-xs text-gray-600 select-none">
                    <div class="font-bold text-gray-800 mb-2">Keterangan:</div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-green-500"></div> Aktif
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div> Tutup Sementara
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500"></div> Tutup
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-blue-500"></div> Titik Sebaran
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-md p-5 card-hover border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Total Target</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1 transition-all duration-300" data-stat="total">
                            {{ number_format($grandTotal, 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-5 card-hover border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Sudah Koordinat</p>
                        <h3 class="text-2xl font-bold text-green-600 mt-1 transition-all duration-300"
                            data-stat="filled">
                            {{ number_format($grandFilled, 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="bg-green-100 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-5 card-hover border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Belum Koordinat</p>
                        <h3 class="text-2xl font-bold text-red-600 mt-1 transition-all duration-300" data-stat="empty">
                            {{ number_format($grandTotal - $grandFilled, 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="bg-red-100 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-5 card-hover border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Progress</p>
                        <h3 class="text-2xl font-bold text-purple-600 mt-1 transition-all duration-300"
                            data-stat="progress">
                            {{ $grandTotal > 0 ? number_format(($grandFilled / $grandTotal) * 100, 1) : 0 }}%
                        </h3>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Table: Kecamatan Level with Status -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                        <h2 class="text-xl font-bold text-white">📍 Rekapitulasi Per Kecamatan & Status Keberadaan</h2>
                        <p class="text-purple-100 text-sm mt-1">Klik baris kecamatan untuk melihat detail per desa</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b-2 border-gray-200">
                                    <th
                                        class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Kecamatan</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Target</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Sudah</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Belum</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Progress</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Status Keberadaan</th>
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
                                        <td class="px-4 py-3">
                                            <div class="flex items-center">
                                                <span class="expand-icon inline-block mr-2 text-gray-400"
                                                    id="icon-{{ $row['kdkec'] }}">▶</span>
                                                <div>
                                                    <div class="font-semibold text-gray-800">{{ $row['kec_name'] }}</div>
                                                    <div class="text-xs text-gray-500">Kode: {{ $row['kdkec'] }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="font-bold text-gray-700 transition-all duration-300"
                                                data-kec="{{ $row['kdkec'] }}"
                                                data-field="total">{{ number_format($row['total'], 0, ',', '.') }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="text-green-600 font-semibold transition-all duration-300"
                                                data-kec="{{ $row['kdkec'] }}"
                                                data-field="filled">{{ number_format($row['filled'], 0, ',', '.') }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="text-red-600 font-semibold transition-all duration-300"
                                                data-kec="{{ $row['kdkec'] }}"
                                                data-field="empty">{{ number_format($row['empty'], 0, ',', '.') }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-col items-center">
                                                <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
                                                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2 rounded-full transition-all duration-500"
                                                        data-kec="{{ $row['kdkec'] }}" data-field="progress-bar"
                                                        style="width: {{ $row['percentage'] }}%">
                                                    </div>
                                                </div>
                                                <span class="text-xs font-bold text-gray-700" data-kec="{{ $row['kdkec'] }}"
                                                    data-field="progress-text">{{ number_format($row['percentage'], 1) }}%</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3" id="status-{{ $row['kdkec'] }}">
                                            <div class="text-xs text-gray-500">Loading...</div>
                                        </td>
                                    </tr>
                                    <!-- Detail Desa Row (Hidden by default) -->
                                    <tr id="detail-{{ $row['kdkec'] }}"
                                        class="hidden bg-gradient-to-r from-blue-50 to-indigo-50">
                                        <td colspan="6" class="px-4 py-4">
                                            <div id="content-{{ $row['kdkec'] }}">
                                                <p class="text-center text-gray-500 py-2">Loading...</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white font-bold">
                                    <td class="px-4 py-4 text-right">TOTAL KESELURUHAN</td>
                                    <td class="px-4 py-4 text-right">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                                    <td class="px-4 py-4 text-right">{{ number_format($grandFilled, 0, ',', '.') }}</td>
                                    <td class="px-4 py-4 text-right">
                                        {{ number_format($grandTotal - $grandFilled, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        {{ $grandTotal > 0 ? number_format(($grandFilled / $grandTotal) * 100, 1) : 0 }}%
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex flex-wrap gap-1 justify-end">
                                            @foreach($statusBreakdown as $status)
                                                @php
                                                    $colorClass = $statusColors[$status['status_id']] ?? 'bg-gray-100 text-gray-700';
                                                    $label = $statusLabels[$status['status_id']] ?? $status['status_id'];
                                                @endphp
                                                <span class="status-chip {{ $colorClass }} border border-white/20 shadow-sm"
                                                    title="{{ $status['status_name'] }}">
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
            </div>

            <!-- Sidebar: Stats & Charts -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Card 1: Progress Akumulasi Harian (New Feature) -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden h-96">
                    <div class="bg-gradient-to-r from-teal-500 to-green-600 px-5 py-4">
                        <h2 class="font-bold text-lg text-white">📈 Progress Akumulasi</h2>
                        <p class="text-teal-100 text-xs mt-1">Klik tanggal untuk detail</p>
                    </div>
                    <div class="overflow-y-auto h-full pb-16">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2 text-left font-bold text-gray-600">Tanggal</th>
                                    <th class="px-3 py-2 text-right font-bold text-gray-600">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @if(isset($dailyCumulativeStats) && count($dailyCumulativeStats) > 0)
                                    @foreach($dailyCumulativeStats as $stat)
                                        <tr class="hover:bg-green-50 transition cursor-pointer group"
                                            onclick="showDailyContributors('{{ $stat->date }}')">
                                            <td class="px-3 py-2">
                                                @if($stat->date == 'DATA AWAL')
                                                    <span
                                                        class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs font-bold">AWAL</span>
                                                @else
                                                    <div class="font-semibold text-gray-700">
                                                        {{ \Carbon\Carbon::parse($stat->date)->format('d M') }}
                                                    </div>
                                                    <div class="text-xs text-green-600 font-bold">
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

                <!-- Card 2: Top Kontributor -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover max-h-96">
                    <div class="bg-gradient-to-r from-orange-500 to-red-600 px-5 py-4">
                        <h2 class="font-bold text-lg text-white">🏆 Top Kontributor</h2>
                    </div>
                    <div class="p-0 overflow-y-auto max-h-80">
                        @if(count($userStats) > 0)
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-bold text-gray-600">User</th>
                                        <th class="px-3 py-2 text-right font-bold text-gray-600">Unit</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($userStats as $index => $stat)
                                        <tr class="hover:bg-orange-50 transition cursor-pointer"
                                            onclick="showUserDetail('{{ $stat->user_id }}')">
                                            <td class="px-3 py-2 flex items-center gap-2">
                                                <div
                                                    class="w-6 h-6 rounded-full bg-gradient-to-br {{ $index < 3 ? 'from-yellow-400 to-orange-500 shadow-sm' : 'from-gray-200 to-gray-300' }} flex items-center justify-center text-white font-bold text-xs">
                                                    {{ $index + 1 }}
                                                </div>
                                                <span class="font-semibold text-gray-700 truncate max-w-[120px]"
                                                    title="{{ $stat->user_id }}">{{ $stat->user_id }}</span>
                                            </td>
                                            <td class="px-3 py-2 text-right font-bold text-orange-600">
                                                {{ number_format($stat->total, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
                    <div class="p-4 max-h-80 overflow-y-auto">
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

            <div class="flex-1 overflow-y-auto p-6">
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
            @foreach($rows as $row)
                loadStatusForKecamatan('{{ $row['kdkec'] }}');
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
                    // Data has changed, update the display
                    updateSummaryCards(data);
                    updateKecamatanRows(data.rows);

                    // Clear caches to force reload
                    Object.keys(desaDataCache).forEach(key => delete desaDataCache[key]);
                    Object.keys(statusDataCache).forEach(key => delete statusDataCache[key]);

                    // Reload status for visible kecamatan
                    data.rows.forEach(row => {
                        loadStatusForKecamatan(row.kdkec);
                    });

                    // Show subtle update notification
                    showUpdateNotification();
                }

                lastUpdateTimestamp = data.lastUpdate;
            } catch (error) {
                console.error('Auto-refresh error:', error);
            }
        }

        function updateSummaryCards(data) {
            // Update Total Target
            const totalCard = document.querySelector('[data-stat="total"]');
            if (totalCard) {
                totalCard.textContent = data.grandTotal.toLocaleString('id-ID');
                animateNumber(totalCard);
            }

            // Update Sudah Koordinat
            const filledCard = document.querySelector('[data-stat="filled"]');
            if (filledCard) {
                filledCard.textContent = data.grandFilled.toLocaleString('id-ID');
                animateNumber(filledCard);
            }

            // Update Belum Koordinat
            const emptyCard = document.querySelector('[data-stat="empty"]');
            if (emptyCard) {
                emptyCard.textContent = data.grandEmpty.toLocaleString('id-ID');
                animateNumber(emptyCard);
            }

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
                const filledCell = document.querySelector(`[data-kec="${row.kdkec}"][data-field="filled"]`);
                const emptyCell = document.querySelector(`[data-kec="${row.kdkec}"][data-field="empty"]`);
                const progressBar = document.querySelector(`[data-kec="${row.kdkec}"][data-field="progress-bar"]`);
                const progressText = document.querySelector(`[data-kec="${row.kdkec}"][data-field="progress-text"]`);

                if (targetCell && targetCell.textContent !== row.total.toLocaleString('id-ID')) {
                    targetCell.textContent = row.total.toLocaleString('id-ID');
                    animateNumber(targetCell);
                }

                if (filledCell && filledCell.textContent !== row.filled.toLocaleString('id-ID')) {
                    filledCell.textContent = row.filled.toLocaleString('id-ID');
                    animateNumber(filledCell);
                }

                if (emptyCell && emptyCell.textContent !== row.empty.toLocaleString('id-ID')) {
                    emptyCell.textContent = row.empty.toLocaleString('id-ID');
                    animateNumber(emptyCell);
                }

                if (progressBar) {
                    progressBar.style.width = row.percentage + '%';
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
                                <th class="px-3 py-2 text-right text-xs font-semibold text-indigo-700">Sudah</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-indigo-700">Belum</th>
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
                        <td class="px-3 py-2 text-right text-green-600 font-semibold">${desa.filled.toLocaleString('id-ID')}</td>
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
                        <td colspan="6" class="p-0">
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
                        const response = await fetch(`{{ url('/units/rekap/sls') }}/${kdkec}/${kddesa}`);
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
                            <th class="px-3 py-2 text-right">Sudah</th>
                            <th class="px-3 py-2 text-right">Belum</th>
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
                        <td class="px-3 py-1.5 text-right text-green-600">${sls.filled}</td>
                        <td class="px-3 py-1.5 text-right text-red-500">${sls.empty}</td>
                        <td class="px-3 py-1.5">${statusHtml}</td>
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
            data.forEach(log => {
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
        let currentDailyData = [];

        async function showDailyDetail(date) {
            document.getElementById('dailyModal').classList.remove('hidden');
            const niceDate = new Date(date).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('modalDate').innerText = niceDate;
            document.getElementById('dailyModalContent').innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-500">Loading...</td></tr>';
            document.getElementById('dailySearchInfo').value = '';

            try {
                const response = await fetch(`{{ url('/units/daily') }}/${date}`);
                currentDailyData = await response.json();
                renderDailyTable(currentDailyData);
            } catch (error) {
                console.error(error);
                document.getElementById('dailyModalContent').innerHTML = '<tr><td colspan="5" class="p-4 text-center text-red-500">Gagal memuat data.</td></tr>';
            }
        }

        function renderDailyTable(data) {
            const tbody = document.getElementById('dailyModalContent');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-500">Tidak ada aktivitas pada tanggal ini.</td></tr>';
                return;
            }

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
            tbody.innerHTML = html;
        }

        function filterDailyTable() {
            const query = document.getElementById('dailySearchInfo').value.toLowerCase();
            if (!query) {
                renderDailyTable(currentDailyData);
                return;
            }

            const filtered = currentDailyData.filter(log => {
                const unitName = log.unit ? log.unit.nama_usaha.toLowerCase() : '';
                const user = log.user_id.toLowerCase();
                const idsbr = log.unit ? String(log.unit.idsbr) : '';
                return unitName.includes(query) || user.includes(query) || idsbr.includes(query);
            });
            renderDailyTable(filtered);
        }
    </script>


    <!-- Daily Contributors Modal -->
    <div id="dailyContributorsModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all scale-100">
            <div class="bg-gradient-to-r from-teal-500 to-green-600 px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-bold text-white">Kontributor: <span id="modalContribDate"></span></h2>
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
    </script>
    <!-- Map Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Turf.js/6.5.0/turf.min.js"></script>

    <script>
        // Map Application Logic
        document.addEventListener('DOMContentLoaded', async () => {
            initMap();
        });

        let map, geojsonLayer, markersLayer;
        let slsDataGeoJSON = null;
        let unitStats = []; // Raw unit data
        let currentLevel = 'KAB'; // KAB, KEC, DESA
        let currentKecCode = null;
        let currentDesaCode = null;

        const defaultCenter = [-1.7, 103.1]; // Approx Batang Hari
        const defaultZoom = 9;

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

            const cartoLight = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; CARTO', maxZoom: 20
            });

            // Default to Google Streets for reliability
            map.addLayer(googleStreets);

            // Layer Control
            const baseMaps = {
                "Google Maps": googleStreets,
                "Google Hybrid": googleHybrid,
                "Clean Light": cartoLight,
                "OpenStreetMap": osm
            };
            L.control.layers(baseMaps).addTo(map);

            // Load Data Parallelly
            try {
                const [geoRes, unitRes] = await Promise.all([
                    fetch('{{ url("/sls_1504.geojson") }}'), // Must be in public folder
                    fetch('{{ route("api.map_stats") }}')
                ]);

                if (!geoRes.ok) throw new Error("Failed to load SLS GeoJSON");
                slsDataGeoJSON = await geoRes.json();

                if (unitRes.ok) {
                    unitStats = await unitRes.json();
                }

                console.log("Loaded GeoJSON Features:", slsDataGeoJSON.features.length);
                console.log("Loaded Units:", unitStats.length);

                renderKecamatanView();

            } catch (err) {
                console.error("Map Init Error:", err);
                // Fallback or alert
                document.getElementById('map').innerHTML = `<div class="flex items-center justify-center h-full text-red-500 p-4">Failed to load map data: ${err.message}. Ensure sls_1504.geojson is in public folder.</div>`;
            }

            document.getElementById('btnResetMap').addEventListener('click', () => {
                renderKecamatanView();
            });
        }

        // --- LEVEL 1: KABUPATEN VIEW (Show 8 Kecamatan Markers) ---
        function renderKecamatanView() {
            currentLevel = 'KAB';
            currentKecCode = null;
            updateBreadcrumb('');
            document.getElementById('btnResetMap').classList.add('hidden');

            clearLayers();
            map.flyTo(defaultCenter, 9);

            // 1. Group SLS by Kecamatan to find centroid
            const kecGroups = {};
            slsDataGeoJSON.features.forEach(f => {
                const kdkec = f.properties.nmkec; // Name
                // Structure: PPPP KKK DDD SSSS
                // 1504 (ProvKab) + 010 (Kec) + 001 (Desa) + 0001 (SLS)
                const fullId = String(f.properties.idsls);
                const kecCodeFull = fullId.substring(0, 7); // 1504010

                if (!kecGroups[kdkec]) {
                    kecGroups[kdkec] = {
                        name: kdkec,
                        polys: [],
                        code: kecCodeFull
                    };
                }
                kecGroups[kdkec].polys.push(f);
            });

            // 2. Render Markers for each Kecamatan
            Object.values(kecGroups).forEach(group => {
                // Calculate Centroid of all SLS in this Kecamatan
                const center = calculateGroupCentroid(group.polys);

                // Get Stats
                // Filter units where kdkec matches
                // Note: group.code is '1504010'. unit.kdkec is '010' or '10'.
                const rawKecCode = group.code.substring(4, 7); // '010'
                // Handle potentially missing leading zeros in database vs geojson
                // In DB we cleaned it to '010', '020' etc.
                const kecUnits = unitStats.filter(u => String(u.kdkec).padStart(3, '0') === rawKecCode);
                const total = kecUnits.length;

                const marker = L.divIcon({
                    className: 'custom-div-icon',
                    html: `
                        <div class="flex flex-col items-center justify-center group">
                            <div class="w-16 h-16 rounded-full bg-blue-600/90 text-white flex flex-col items-center justify-center shadow-lg border-4 border-white group-hover:scale-110 transition cursor-pointer group-hover:bg-blue-700">
                                <span class="text-[10px] uppercase font-bold text-blue-100 text-center leading-none mb-1 px-1">${group.name.replace('BATANG HARI', '')}</span>
                                <span class="text-lg font-black">${total}</span>
                            </div>
                            <div class="mt-1 bg-white/90 backdrop-blur px-2 py-1 rounded shadow text-xs font-bold text-gray-700 opacity-0 group-hover:opacity-100 transition whitespace-nowrap">
                                Klik untuk Detail
                            </div>
                        </div>
                    `,
                    iconSize: [80, 100],
                    iconAnchor: [40, 50]
                });

                L.marker([center[1], center[0]], { icon: marker })
                    .addTo(map)
                    .bindTooltip(`Kecamatan ${group.name}`, { direction: 'top', offset: [0, -40] })
                    .on('click', () => {
                        renderDesaView(group.code, group.name, center);
                    });
            });
        }

        // --- LEVEL 2: KECAMATAN VIEW (Show Desa Markers) ---
        function renderDesaView(kecCodeFull, kecName, center) {
            currentLevel = 'KEC';
            currentKecCode = kecCodeFull;
            updateBreadcrumb(`> ${kecName}`);
            document.getElementById('btnResetMap').classList.remove('hidden');

            clearLayers();
            map.flyTo([center[1], center[0]], 11);

            // 1. Group by Desa
            const desaGroups = {};
            slsDataGeoJSON.features.forEach(f => {
                const fullId = String(f.properties.idsls);
                if (!fullId.startsWith(kecCodeFull)) return;

                const nmdesa = f.properties.nmdesa;
                const desaCodeFull = fullId.substring(0, 10); // 1504010001

                if (!desaGroups[nmdesa]) {
                    desaGroups[nmdesa] = {
                        name: nmdesa,
                        polys: [],
                        code: desaCodeFull
                    };
                }
                desaGroups[nmdesa].polys.push(f);
            });

            // 2. Render Desa Markers
            Object.values(desaGroups).forEach(group => {
                const dCenter = calculateGroupCentroid(group.polys);

                // Get Stats
                // unit.full_desa_code "010001" vs group.code "1504010001" -> substring(4)
                const rawDesaCode = group.code.substring(4); // 010001
                // Ensure padding logic matches backend
                const desaUnits = unitStats.filter(u => u.full_desa_code === rawDesaCode);

                const count = desaUnits.length;
                const color = count > 0 ? 'bg-green-600' : 'bg-gray-400';
                const hoverColor = count > 0 ? 'group-hover:bg-green-700' : 'group-hover:bg-gray-500';

                const marker = L.divIcon({
                    className: 'custom-div-icon',
                    html: `
                        <div class="flex flex-col items-center justify-center group">
                            <div class="w-12 h-12 rounded-full ${color} text-white flex flex-col items-center justify-center shadow border-2 border-white group-hover:scale-110 transition cursor-pointer ${hoverColor}">
                                <span class="text-[8px] leading-tight text-center font-bold px-1 overflow-hidden h-3">${group.name.substring(0, 10)}</span>
                                <span class="text-sm font-bold">${count}</span>
                            </div>
                        </div>
                    `,
                    iconSize: [60, 60],
                    iconAnchor: [30, 30]
                });

                L.marker([dCenter[1], dCenter[0]], { icon: marker })
                    .addTo(map)
                    .bindTooltip(`Desa ${group.name}`)
                    .on('click', () => {
                        renderSlsView(group.code, group.name, dCenter);
                    });
            });
        }

        // --- LEVEL 3: SLS VIEW (Polygons + Units) ---
        function renderSlsView(desaCodeFull, desaName, center) {
            currentLevel = 'DESA';
            // Simplify name for breadcrumb
            const kecNameSimple = currentKecCode ? currentKecCode.substring(0, 7) : 'Kecamatan';
            updateBreadcrumb(`> ${desaName}`); // Just show Desa name to save space

            clearLayers();
            map.flyTo([center[1], center[0]], 14);

            // 1. Filter Features
            const slsFeatures = slsDataGeoJSON.features.filter(f => String(f.properties.idsls).startsWith(desaCodeFull));

            // 2. Render Polygons
            geojsonLayer = L.geoJSON(slsFeatures, {
                style: function (feature) {
                    // Check if units exist in this SLS
                    const slsId = String(feature.properties.idsls);
                    // unitStats.sls_id usually matches raw idsls
                    const count = unitStats.filter(u => String(u.sls_id) === slsId).length;

                    return {
                        color: count > 0 ? '#10B981' : '#9CA3AF', // Green vs Gray-400
                        weight: 1,
                        opacity: 0.8,
                        fillColor: count > 0 ? '#34D399' : '#E5E7EB',
                        fillOpacity: 0.2
                    };
                },
                onEachFeature: function (feature, layer) {
                    const props = feature.properties;
                    const slsId = String(props.idsls);
                    const unitsInSls = unitStats.filter(u => String(u.sls_id) === slsId);

                    const content = `
                        <div class="font-sans">
                            <h3 class="font-bold text-sm border-b pb-1 mb-1">${props.nmsls}</h3>
                            <p class="text-xs">Desa: ${props.nmdesa}</p>
                            <p class="text-xs font-bold mt-1">Jml Usaha: ${unitsInSls.length}</p>
                        </div>
                    `;
                    layer.bindPopup(content);
                }
            }).addTo(map);

            // 3. Render Individual Units
            // Filter units in this Desa
            const rawDesaCode = desaCodeFull.substring(4);
            const units = unitStats.filter(u => u.full_desa_code === rawDesaCode);

            const markers = L.markerClusterGroup({
                disableClusteringAtZoom: 17,
                maxClusterRadius: 40
            });

            units.forEach(u => {
                let color = 'blue';
                if (!u.lat || !u.lng) return;

                // Status Color Mapping
                // 1:Aktif(Green), 2:TutupSem(Yellow), 4:Tutup(Red), 6:TdkDitemukan(Gray)
                let markerColor = 'blue'; // default
                if (u.status == 1) markerColor = 'green';
                else if (u.status == 2) markerColor = 'yellow';
                else if (u.status == 4) markerColor = 'red';
                else if (u.status == 6) markerColor = 'grey';

                // Create custom marker logic if needed, or stick to simple circle markers
                // CircleMarker is better for performance and looks
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
                            </div>
                            <div class="mt-2 text-right">
                                <a href="/?search=${encodeURIComponent(u.name)}" target="_blank" class="text-blue-600 hover:underline text-xs bg-blue-50 px-2 py-1 rounded">Lihat Detail ➡</a>
                            </div>
                        </div>
                    `);
                markers.addLayer(marker);
            });

            map.addLayer(markers);
            markersLayer = markers;
        }

        function clearLayers() {
            if (map) {
                map.eachLayer(layer => {
                    // Remove all layers except tiles
                    if (layer instanceof L.TileLayer) return;
                    map.removeLayer(layer);
                });
            }
        }

        function calculateGroupCentroid(features) {
            // Simple average of all coords in all features
            // Better: use Turf.js centerOfMass or bbox center
            // Since we imported Turf, let's use it relative to a FeatureCollection
            if (typeof turf !== 'undefined') {
                const fc = turf.featureCollection(features);
                // Use center (bbox center) for better fit than centerOfMass sometimes, but centerOfMass is safer inside polygon
                // Let's use internal point or bbox center
                const center = turf.center(fc);
                return center.geometry.coordinates; // [lon, lat]
            } else {
                // Fallback
                return defaultCenter.slice().reverse();
            }
        }

        function updateBreadcrumb(text) {
            const el = document.getElementById('breadcrumb');
            if (el) el.innerText = text;
        }

        function getColorByStatus(status) {
            const colors = {
                1: '#10B981', // Green
                2: '#FBBF24', // Yellow
                3: '#3B82F6', // Blue
                4: '#EF4444', // Red
                5: '#8B5CF6', // Purple
                6: '#9CA3AF', // Gray
                7: '#14B8A6', // Teal
                8: '#F97316', // Orange
                9: '#EC4899', // Pink
                10: '#6366F1' // Indigo
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

    </script>
</body>

</html>
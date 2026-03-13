<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualisasi Data SIPW - Groundcheck App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #059669 0%, #10B981 100%);
            /* Green Theme for SIPW Viz */
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="gradient-bg text-white py-4 sm:py-6 shadow-lg sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-3xl font-bold mb-0.5 sm:mb-1">📈 Visualisasi Data SIPW</h1>
                    <p class="text-green-100 text-[10px] sm:text-sm">Analisis <code>export_sipw.xlsx</code></p>
                </div>
                <a href="{{ route('units.rekap') }}"
                    class="w-full sm:w-auto text-center bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-4 sm:px-6 py-2 sm:py-2.5 rounded-lg transition font-semibold text-xs sm:text-sm">
                    ← Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-8">
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 mb-6">
            <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                <div class="text-gray-500 text-[10px] sm:text-sm font-medium uppercase tracking-wider">SIPW Units</div>
                <div class="text-xl sm:text-3xl font-black text-gray-800 mt-1 sm:mt-2">
                    {{ number_format($stats['total_businesses']) }}</div>
            </div>
            <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border-l-4 border-green-500">
                <div class="text-gray-500 text-[10px] sm:text-sm font-medium uppercase tracking-wider">Total SLS</div>
                <div class="text-xl sm:text-3xl font-black text-gray-800 mt-1 sm:mt-2">
                    {{ number_format($stats['total_sls']) }}</div>
            </div>
            <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border-l-4 border-purple-500 col-span-2 lg:col-span-1">
                <div class="text-gray-500 text-[10px] sm:text-sm font-medium uppercase tracking-wider">Top Category
                </div>
                @php
                    $topType = array_key_first($stats['types']);
                    $topCount = reset($stats['types']);
                @endphp
                <div class="text-lg sm:text-2xl font-black text-gray-800 mt-1 sm:mt-2 truncate">{{ $topType }}</div>
                <div class="text-[10px] sm:text-xs text-gray-400 font-bold">{{ number_format($topCount) }} Units</div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Chart: Top 5 SLS (Lightweight HTML Version) -->
            <div class="bg-white p-6 rounded-xl shadow-md">
                <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">Top 5 SLS (Unit Terbanyak)</h3>
                <div class="space-y-4">
                    @php
                        $maxVal = empty($stats['topSLS']) ? 1 : max($stats['topSLS']);
                        if ($maxVal == 0)
                            $maxVal = 1;
                    @endphp
                    @foreach($stats['topSLS'] as $slsName => $val)
                        <div>
                            <div class="flex justify-between items-end mb-1">
                                <span class="text-sm font-semibold text-gray-700 truncate w-3/4" title="{{ $slsName }}">
                                    {{ $loop->iteration }}. {{ $slsName }}
                                </span>
                                <span class="text-xs font-bold text-emerald-600">{{ $val }} Unit</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2.5">
                                <div class="bg-emerald-500 h-2.5 rounded-full" style="width: {{ ($val / $maxVal) * 100 }}%">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Chart: Business Types -->
            <div class="bg-white p-6 rounded-xl shadow-md">
                <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">Proporsi Jenis Usaha</h3>
                <div class="max-h-[250px] flex justify-center">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- NEW: Dominant Load & Hierarchy -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Dominant Load Chart -->
            <div class="bg-white p-6 rounded-xl shadow-md lg:col-span-1">
                <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">Muatan Dominan</h3>
                <div class="max-h-[300px] flex justify-center">
                    <canvas id="dominantChart"></canvas>
                </div>
                <!-- Legend Legend Text -->
                <div class="mt-4 text-xs text-gray-500 space-y-1 h-32 overflow-y-auto">
                    @foreach($stats['dominant'] as $label => $val)
                        <div class="flex justify-between">
                            <span>{{ $label }}</span>
                            <span class="font-bold">{{ $val }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Hierarchy Table -->
            <div class="bg-white p-6 rounded-xl shadow-md lg:col-span-2 overflow-hidden flex flex-col">
                <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">Rekapitulasi Wilayah (Kec/Desa/SLS)</h3>
                <div class="overflow-auto flex-1 max-h-[400px]">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100 sticky top-0">
                            <tr>
                                <th class="px-3 sm:px-4 py-2">Wilayah</th>
                                <th class="hidden sm:table-cell px-4 py-2 text-center text-[10px]">Desa</th>
                                <th class="px-3 sm:px-4 py-2 text-center text-[10px]">SLS</th>
                                <th class="px-3 sm:px-4 py-2 text-center text-[10px]">Non</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($hierarchy as $kc => $kecData)
                                <!-- Kecamatan Header -->
                                <tr class="bg-blue-50/50 font-bold text-gray-800 text-[10px] sm:text-xs">
                                    <td class="px-3 sm:px-4 py-2" colspan="4">
                                        {{ $kecData['name'] }}
                                        <span class="float-right bg-blue-100 text-blue-800 px-2 rounded-full font-bold">
                                            {{ count($kecData['desas']) }}D
                                        </span>
                                    </td>
                                </tr>
                                <!-- Desa Rows -->
                                @foreach($kecData['desas'] as $dc => $desaData)
                                    <tr class="hover:bg-gray-50 text-[10px] sm:text-xs">
                                        <td class="px-3 sm:px-4 py-2 pl-4 sm:pl-8 border-l-2 sm:border-l-4 border-l-gray-300">
                                            {{ $desaData['name'] }}
                                        </td>
                                        <td class="hidden sm:table-cell px-4 py-2 text-center text-gray-400 italic">Desa</td>
                                        <td class="px-3 sm:px-4 py-2 text-center font-black text-green-600">
                                            {{ $desaData['sls_count'] }}
                                        </td>
                                        <td class="px-3 sm:px-4 py-2 text-center font-black text-amber-600">
                                            {{ $desaData['non_sls_count'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Data Table Section -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6 border-b bg-gray-50 flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-700">Data Mentah (Export SIPW)</h3>
                    <p class="text-xs text-gray-500">Menampilkan seluruh kolom data dari file Excel</p>
                </div>
                <input type="text" id="tableSearch" placeholder="Cari data..."
                    class="border border-gray-300 rounded-lg px-4 py-2 text-sm w-64 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
            </div>

            <!-- FIXED HEIGHT SCROLLABLE CONTAINER -->
            <div class="overflow-auto max-h-[600px] w-full relative">
                <table class="w-full text-xs text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-100 sticky top-0 shadow-sm z-10">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap bg-gray-100">No</th>
                            @if(count($data) > 0)
                                @foreach(array_keys($data[0]) as $header)
                                    @if(strpos($header, '_logic_') !== 0) <!-- Hide logic keys -->
                                        <th class="px-4 py-3 whitespace-nowrap bg-gray-100">{{ $header }}</th>
                                    @endif
                                @endforeach
                            @else
                                <th class="px-4 py-3">Data Kosong</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody id="dataTableBody" class="divide-y divide-gray-100">
                        @forelse($data as $index => $row)
                            <tr class="bg-white hover:bg-green-50 transition">
                                <td class="px-4 py-2 border-r">{{ $loop->iteration }}</td>
                                @foreach($row as $key => $val)
                                    @if(strpos($key, '_logic_') !== 0)
                                        <td class="px-4 py-2 whitespace-nowrap border-r max-w-xs truncate" title="{{ $val }}">
                                            {{ $val !== null ? $val : '-' }}
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="100%" class="px-6 py-8 text-center text-gray-500 italic">
                                    Tidak ada data ditemukan dalam file export.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3 bg-gray-50 border-t flex justify-between items-center">
                <div class="text-xs text-gray-500">
                    Total {{ number_format(count($data)) }} baris data.
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- Chart Data Preparation ---
        const slsLabels = {!! json_encode(array_keys($stats['topSLS'])) !!};
        const slsData = {!! json_encode(array_values($stats['topSLS'])) !!};

        const typeLabels = {!! json_encode(array_keys($stats['types'])) !!};
        const typeData = {!! json_encode(array_values($stats['types'])) !!};

        // --- Render Charts ---

        // (SLS Chart removed - Replaced with HTML List)

        // Business Type Chart (Doughnut)
        new Chart(document.getElementById('typeChart'), {
            type: 'doughnut',
            data: {
                labels: typeLabels,
                datasets: [{
                    data: typeData,
                    backgroundColor: [
                        '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                        '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#64748B'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } }
                },
                cutout: '65%'
            }
        });

        // --- NEW: Dominant Chart ---
        const domLabels = {!! json_encode(array_keys($stats['dominant'])) !!};
        const domData = {!! json_encode(array_values($stats['dominant'])) !!};

        new Chart(document.getElementById('dominantChart'), {
            type: 'pie',
            data: {
                labels: domLabels,
                datasets: [{
                    data: domData,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#C9CBCF', '#E7E9ED', '#71B37C', '#E6B0AA'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false } // Hide legend to save space, we show custom list below
                }
            }
        });

        // --- Simple Search (No Pagination) ---
        // Just filtering visibility
        document.getElementById('tableSearch').addEventListener('keyup', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#dataTableBody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>
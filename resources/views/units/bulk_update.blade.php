<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Update - Groundcheck App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="p-6 max-w-7xl mx-auto">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1
                    class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                    Bulk Update Koordinat</h1>
                <p class="text-sm text-gray-500 mt-1">Alat khusus admin untuk mengupdate batch data unit yang belum
                    di-groundcheck</p>
            </div>
            <div>
                <a href="{{ route('units.rekap') }}"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg shadow-sm hover:bg-gray-200 transition-colors flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Kembali ke Rekap
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded shadow-sm">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3 text-xl"></i>
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3 text-xl"></i>
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <!-- Filter & Exclude Data -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
            <h3 class="text-sm font-semibold text-gray-800 border-b pb-3 mb-4"><i class="fas fa-filter text-blue-500 mr-2"></i>Filter & Pengecualian Wilayah</h3>
            <form action="{{ route('units.bulk-update') }}" method="GET" id="form-filter">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    <!-- Target Area -->
                    <div class="bg-blue-50/50 p-4 rounded-lg border border-blue-100">
                        <label class="block text-xs font-bold text-blue-800 mb-2 uppercase tracking-wide"><i class="fas fa-bullseye mr-1"></i> Target Pencarian (WAJIB)</label>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Pilih Kecamatan</label>
                                <select name="filter_kec" id="filter_kec"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                                    onchange="this.form.submit()">
                                    <option value="">Semua Kecamatan</option>
                                    <option value="UNKNOWN" {{ $filterKec === 'UNKNOWN' ? 'selected' : '' }}>[ - ] Tidak Diketahui / Kosong</option>
                                    @foreach($kecamatans as $kec)
                                        <option value="{{ $kec->code }}" {{ $filterKec == $kec->code ? 'selected' : '' }}>[{{ $kec->code }}] {{ $kec->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Pilih Desa</label>
                                <select name="filter_desa" id="filter_desa"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                                    onchange="this.form.submit()" {{ !$filterKec || $filterKec === 'UNKNOWN' ? 'disabled' : '' }}>
                                    <option value="">Semua Desa</option>
                                    @if($filterKec && $filterKec !== 'UNKNOWN')
                                        <option value="UNKNOWN" {{ $filterDesa === 'UNKNOWN' ? 'selected' : '' }}>[ - ] Tidak Diketahui / Kosong</option>
                                    @endif
                                    @foreach($desas as $desa)
                                        <option value="{{ $desa->code }}" {{ $filterDesa == $desa->code ? 'selected' : '' }}>[{{ substr($desa->code, 3, 3) }}] {{ $desa->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Exclude Area -->
                    <div class="bg-red-50/50 p-4 rounded-lg border border-red-100">
                        <label class="block text-xs font-bold text-red-800 mb-2 uppercase tracking-wide"><i class="fas fa-ban mr-1"></i> Pengecualian (OPSIONAL)</label>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Kecuali Kecamatan</label>
                                <select name="exclude_kec[]" id="exclude_kec" multiple="multiple"
                                    class="w-full border border-gray-300 rounded-lg text-sm select2-multi">
                                    @foreach($kecamatans as $kec)
                                        <option value="{{ $kec->code }}" {{ in_array($kec->code, $excludeKec) ? 'selected' : '' }}>[{{ $kec->code }}] {{ $kec->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Kecuali Desa</label>
                                <select name="exclude_desa[]" id="exclude_desa" multiple="multiple"
                                    class="w-full border border-gray-300 rounded-lg text-sm select2-multi">
                                    @foreach($allDesas as $desa)
                                        <option value="{{ $desa->code }}" {{ in_array($desa->code, $excludeDesa) ? 'selected' : '' }}>[{{ substr($desa->code, 0, 3) }}-{{ substr($desa->code, 3, 3) }}] {{ $desa->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end items-center gap-3 border-t pt-4">
                    @if($filterKec && $filterKec !== '' || $filterDesa && $filterDesa !== '' || !empty($excludeKec) || !empty($excludeDesa))
                        <a href="{{ route('units.bulk-update') }}" class="text-sm font-medium text-red-500 hover:text-red-700 hover:underline transition-colors px-3 py-2">Reset Filter</a>
                    @endif
                    <button type="submit" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-blue-700 transition-colors shadow-sm focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="fas fa-search mr-1.5"></i> Terapkan Pencarian & Pengecualian
                    </button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Status Card -->
            <div
                class="md:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-3 mb-4">Status Data Belum Terupdate</h3>

                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 text-center">
                            <div class="text-3xl font-bold text-blue-700 mb-1">
                                {{ number_format($stats['kec_desa'], 0, ',', '.') }}
                            </div>
                            <div class="text-xs text-blue-600 font-medium">Kecamatan & Desa<br>Diketahui</div>
                        </div>
                        <div class="bg-amber-50 p-4 rounded-lg border border-amber-100 text-center">
                            <div class="text-3xl font-bold text-amber-700 mb-1">
                                {{ number_format($stats['kec_only'], 0, ',', '.') }}
                            </div>
                            <div class="text-xs text-amber-600 font-medium">Hanya Kecamatan<br>Diketahui</div>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg border border-red-100 text-center">
                            <div class="text-3xl font-bold text-red-700 mb-1">
                                {{ number_format($stats['none'], 0, ',', '.') }}
                            </div>
                            <div class="text-xs text-red-600 font-medium">Tidak Diketahui<br>(Kec & Desa Kosong)</div>
                        </div>
                    </div>

                    <div class="flex justify-between items-center bg-gray-50 p-4 rounded-lg border">
                        <span class="font-medium text-gray-600">Total data siap diproses:</span>
                        <span class="text-2xl font-bold text-gray-800">{{ number_format($stats['total'], 0, ',', '.') }}
                            Unit</span>
                    </div>
                </div>

                <div class="mt-6">
                    <form action="{{ route('units.bulk-update.execute') }}" method="POST" id="form-bulk-update">
                        @csrf
                        <input type="hidden" name="filter_kec" value="{{ $filterKec }}">
                        <input type="hidden" name="filter_desa" value="{{ $filterDesa }}">
                        @foreach($excludeKec as $ek)
                            <input type="hidden" name="exclude_kec[]" value="{{ $ek }}">
                        @endforeach
                        @foreach($excludeDesa as $ed)
                            <input type="hidden" name="exclude_desa[]" value="{{ $ed }}">
                        @endforeach

                        <!-- Metode Update -->
                        <div class="mb-5 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3"><i
                                    class="fas fa-cog text-blue-500 mr-2"></i>Metode Update</h4>
                            <div class="flex flex-col gap-2">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="update_method" value="with_coord"
                                        class="form-radio text-blue-600 focus:ring-blue-500 w-4 h-4" checked
                                        onchange="toggleTargetTagging(this.value)">
                                    <span class="ml-2 text-sm text-gray-700 font-medium">Generate Koordinat Acak (Sesuai
                                        wilayah / Target Tagging)</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="update_method" value="no_coord"
                                        class="form-radio text-blue-600 focus:ring-blue-500 w-4 h-4"
                                        onchange="toggleTargetTagging(this.value)">
                                    <span class="ml-2 text-sm text-gray-700 font-medium">Kosongkan Koordinat (Lebih
                                        cepat, Update Langsung ke status Aktif)</span>
                                </label>
                            </div>
                        </div>

                        <!-- Target Tagging -->
                        <div id="target-tagging-container"
                            class="mb-5 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3"><i
                                    class="fas fa-map-marker-alt text-blue-500 mr-2"></i>Target Tagging (Opsional)</h4>
                            <p class="text-xs text-gray-500 mb-3">Pilih target lokasi spesifik untuk menimpa asal
                                wilayah unit. Biarkan default jika ingin diacak sesuai wilayah unit masing-masing.</p>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Target Kecamatan</label>
                                    <select name="target_kec" id="target_kec"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                                        <option value="">-- Default Unit --</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Target Desa</label>
                                    <select name="target_desa" id="target_desa"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                                        <option value="">-- Default Unit --</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Target SLS</label>
                                    <select name="target_sls" id="target_sls"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                                        <option value="">-- Default (Acak) --</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button type="button" onclick="confirmBulkUpdate()"
                            class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg shadow-md hover:from-blue-700 hover:to-indigo-700 transition flex items-center justify-center gap-2 {{ $stats['total'] == 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                            {{ $stats['total'] == 0 ? 'disabled' : '' }}>
                            <i class="fas fa-play-circle text-lg"></i> Mulai Proses Bulk Update
                        </button>
                        <p class="text-xs text-center text-gray-500 mt-2">Semua data di atas akan otomatis diubah
                            statusnya
                            menjadi <b>Aktif</b> dan dicarikan koordinat random sesuai wilayahnya.</p>
                    </form>
                </div>
            </div>

            <!-- Info Card -->
            <div class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-xl shadow-sm border border-indigo-100 p-6">
                <h3 class="text-lg font-semibold text-indigo-800 border-b border-indigo-200 pb-3 mb-4">Mekanisme Update
                </h3>
                <ul class="space-y-3 text-sm text-indigo-900">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-indigo-500 mt-0.5"></i>
                        <span>Tiap unit dengan Kec&Desa diketahui akan diacak posisinya dalam SLS di desa
                            tersebut.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-indigo-500 mt-0.5"></i>
                        <span>Jika hanya Kecamatan diketahui, akan dipilih Desa secara acak dalam kecamatan itu.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-indigo-500 mt-0.5"></i>
                        <span>Jika lokasi tidak diketahui, status akan diverifikasi tanpa titik koordinat.</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Riwayat & Rollback -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-800 border-b pb-3 mb-4"><i
                    class="fas fa-history text-gray-500 mr-2"></i>Riwayat & Batal (Rollback)</h3>

            @if($history->isEmpty())
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                        <i class="fas fa-folder-open text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 font-medium">Belum ada riwayat bulk update</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold border-b">
                            <tr>
                                <th class="px-4 py-3">Batch ID</th>
                                <th class="px-4 py-3">Waktu Eksekusi</th>
                                <th class="px-4 py-3 text-right">Jumlah Unit</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($history as $batch)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 font-mono text-xs text-blue-700">{{ $batch->batch_id }}</td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ \Carbon\Carbon::parse($batch->created_at)->timezone('Asia/Jakarta')->format('d M Y - H:i:s') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-700">
                                        {{ number_format($batch->total_units, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <form action="{{ url('unit/bulk-update/rollback') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="batch_id" value="{{ $batch->batch_id }}">
                                            <button type="button"
                                                onclick="confirmRollback(this.form, '{{ $batch->batch_id }}', {{ $batch->total_units }})"
                                                class="btn-cancel px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded text-xs font-medium border border-red-200 transition-colors inline-flex items-center gap-1" title="Rollback Semua">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </form>

                                        <!-- Partial Rollback Button -->
                                        <button type="button" onclick="promptPartialRollback('{{ $batch->batch_id }}')"
                                            class="px-3 py-1.5 bg-yellow-50 text-yellow-600 hover:bg-yellow-100 rounded text-xs font-medium border border-yellow-200 transition-colors inline-flex items-center gap-1 ml-1"
                                            title="Rollback Sebagian Berdasarkan Kode">
                                            <i class="fas fa-eraser"></i> Sebagian
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Partial Rollback Modal -->
    <div id="partialRollbackModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex w-full h-full items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col">
            <div class="bg-gradient-to-r from-yellow-500 to-orange-600 px-6 py-4 flex justify-between items-center">
                <h2 class="text-lg font-bold text-white"><i class="fas fa-eraser mr-2"></i> Rollback Sebagian Data</h2>
                <button type="button" onclick="closePartialRollbackModal()" class="text-white hover:bg-white/20 rounded-full p-1 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form action="{{ route('units.bulk-update.rollback') }}" method="POST" id="form-partial-rollback">
                @csrf
                <input type="hidden" name="batch_id" id="modal_partial_batch_id">
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4">Pilih wilayah yang ingin di-rollback ke status <span class="font-bold text-gray-800">PENDING</span>. Data di luar wilayah ini pada batch terkait akan tetap aktif/terupdate.</p>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Target Kecamatan</label>
                            <select name="partial_kec[]" id="partial_kec" multiple="multiple" class="w-full border border-gray-300 rounded-lg text-sm select2-partial">
                                @foreach($kecamatans as $kec)
                                    <option value="{{ $kec->code }}">[{{ $kec->code }}] {{ $kec->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Target Desa</label>
                            <select name="partial_desa[]" id="partial_desa" multiple="multiple" class="w-full border border-gray-300 rounded-lg text-sm select2-partial">
                                @foreach($allDesas as $desa)
                                    <option value="{{ $desa->code }}">[{{ substr($desa->code, 0, 3) }}-{{ substr($desa->code, 3, 3) }}] {{ $desa->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-gray-400 mt-1 mt-2">Bisa dikosongkan jika ingin menarik sebagian kecamatan saja.</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 rounded-b-2xl border-t border-gray-100">
                    <button type="button" onclick="closePartialRollbackModal()" class="text-gray-600 hover:bg-gray-200 px-4 py-2 rounded-lg transition text-sm font-semibold">Batal</button>
                    <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 focus:ring focus:ring-yellow-300 text-white px-5 py-2 rounded-lg transition shadow-md font-semibold text-sm">Ya, Eksekusi Rollback Sebagian</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        function toggleTargetTagging(value) {
            const container = document.getElementById('target-tagging-container');
            if (value === 'no_coord') {
                container.style.display = 'none';
            } else {
                container.style.display = 'block';
            }
        }

        function promptPartialRollback(batchId) {
            document.getElementById('modal_partial_batch_id').value = batchId;
            document.getElementById('partialRollbackModal').classList.remove('hidden');
            
            // Re-init select2 inside modal for proper width rendering
            $('.select2-partial').select2({
                placeholder: 'Cari nama/kode...',
                allowClear: true,
                width: '100%',
                theme: 'classic',
                dropdownParent: $('#partialRollbackModal')
            });
        }

        function closePartialRollbackModal() {
            document.getElementById('partialRollbackModal').classList.add('hidden');
            document.getElementById('modal_partial_batch_id').value = '';
            $('#partial_kec').val(null).trigger('change');
            $('#partial_desa').val(null).trigger('change');
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadGeoJsonOptions();

            // Initialize Select2 for Exclude fields
            $('.select2-multi').select2({
                placeholder: 'Cari wilayah...',
                allowClear: true,
                width: '100%',
                theme: 'classic'
            });
        });

        let geoData = null;

        async function loadGeoJsonOptions() {
            try {
                const res = await fetch("{{ url('/data/sls_1504.geojson') }}");
                if (!res.ok) throw new Error('GeoJSON not found');
                geoData = await res.json();

                const kecMap = {};
                geoData.features.forEach(f => {
                    const p = f.properties;
                    const kec = (p.kdkec || '').toString();
                    if (kec && !kecMap[kec]) {
                        kecMap[kec] = p.nmkec || `Kec ${kec}`;
                    }
                });

                const kecSelect = document.getElementById('target_kec');
                Object.entries(kecMap).sort((a, b) => a[0].localeCompare(b[0])).forEach(([code, name]) => {
                    const opt = document.createElement('option');
                    opt.value = code;
                    opt.textContent = name;
                    kecSelect.appendChild(opt);
                });

                kecSelect.addEventListener('change', function () {
                    populateTargetDesa(this.value);
                    document.getElementById('target_sls').innerHTML = '<option value="">-- Default (Acak) --</option>';
                });

                document.getElementById('target_desa').addEventListener('change', function () {
                    populateTargetSls(document.getElementById('target_kec').value, this.value);
                });

                // Sync with filter data
                const filterKecValue = "{{ $filterKec }}";
                let filterDesaValue = "{{ $filterDesa }}";

                if (filterKecValue && filterKecValue !== 'UNKNOWN') {
                    kecSelect.value = filterKecValue.padStart(3, '0');
                    populateTargetDesa(filterKecValue);

                    if (filterDesaValue && filterDesaValue !== 'UNKNOWN') {
                        if (filterDesaValue.length === 6) {
                            filterDesaValue = filterDesaValue.substring(3);
                        }
                        const targetDesaVal = filterDesaValue.padStart(3, '0');
                        document.getElementById('target_desa').value = targetDesaVal;
                        populateTargetSls(filterKecValue, targetDesaVal);
                    }
                }

            } catch (err) {
                console.error("Failed to load geo parameters", err);
            }
        }

        function populateTargetDesa(kecCode) {
            const desaSelect = document.getElementById('target_desa');
            desaSelect.innerHTML = '<option value="">-- Default Unit --</option>';
            if (!kecCode || !geoData) return;

            const desaMap = {};
            geoData.features.forEach(f => {
                const p = f.properties;
                const normalizeKecCode = kecCode.toString().replace(/^0+/, ''); // Remove leading zeros if any
                const featKec = (p.kdkec || '').toString().replace(/^0+/, '');

                if (featKec === normalizeKecCode || featKec === kecCode.toString()) {
                    const desa = (p.kddesa || '').toString();
                    if (desa && !desaMap[desa]) {
                        desaMap[desa] = p.nmdesa || `Desa ${desa}`;
                    }
                }
            });

            Object.entries(desaMap).sort((a, b) => a[0].localeCompare(b[0])).forEach(([code, name]) => {
                const opt = document.createElement('option');
                // Format appropriately (keep 3 digits pad)
                opt.value = code.padStart(3, '0');
                opt.textContent = name;
                desaSelect.appendChild(opt);
            });
        }

        function populateTargetSls(kecCode, desaCode) {
            const slsSelect = document.getElementById('target_sls');
            slsSelect.innerHTML = '<option value="">-- Default (Acak) --</option>';
            if (!kecCode || !desaCode || !geoData) return;

            const slsMap = {};
            geoData.features.forEach(f => {
                const p = f.properties;
                const featKec = (p.kdkec || '').toString().padStart(3, '0');
                const featDesa = (p.kddesa || '').toString().padStart(3, '0');

                if (featKec === kecCode.toString().padStart(3, '0') &&
                    featDesa === desaCode.toString().padStart(3, '0')) {
                    const sls = (p.kdsls || '').toString();
                    if (sls && !slsMap[sls]) {
                        slsMap[sls] = p.nmsls || `SLS ${sls}`;
                    }
                }
            });

            Object.entries(slsMap).sort((a, b) => a[0].localeCompare(b[0])).forEach(([code, name]) => {
                const opt = document.createElement('option');
                opt.value = code;
                opt.textContent = name;
                slsSelect.appendChild(opt);
            });
        }

        async function confirmBulkUpdate() {
            Swal.fire({
                title: 'Sedang menyiapkan preview...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            try {
                const formData = new FormData(document.getElementById('form-bulk-update'));
                const response = await fetch("{{ route('units.bulk-update.preview') }}", {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (!result.success || result.data.length === 0) {
                    Swal.fire('Oops', result.message || 'Gagal memuat preview data.', 'error');
                    return;
                }

                let tableHtml = `
                    <div class="text-left text-sm text-gray-700 mb-3">
                        <p>Preview 3 data pertama yang akan diupdate:</p>
                    </div>
                    <div class="overflow-x-auto text-left">
                        <table class="w-full text-xs border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 border-b">ID SBR</th>
                                    <th class="p-2 border-b">Metode</th>
                                    <th class="p-2 border-b">Koordinat</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                result.data.forEach(unit => {
                    tableHtml += `
                        <tr class="border-b">
                            <td class="p-2 whitespace-nowrap"><span class="font-medium text-blue-600">${unit.idsbr}</span><br><span class="text-gray-500 max-w-[150px] truncate block" title="${unit.nama_usaha}">${unit.nama_usaha}</span></td>
                            <td class="p-2 text-gray-600">${unit.method}</td>
                            <td class="p-2 font-mono text-xs text-gray-500 block break-all">
                                ${unit.method === 'KOSONGKAN_KOORDINAT' ? '<span class="text-yellow-600 font-semibold mb-1 block">Koordinat Dikosongkan</span>' : (unit.latitude ? `${unit.latitude.toFixed(6)}, ${unit.longitude.toFixed(6)}` : '<span class="text-red-500 font-semibold mb-1 block">Gagal (Data/Wilayah Tidak Valid)</span>')}
                            </td>
                        </tr>
                    `;
                });

                tableHtml += `
                            </tbody>
                        </table>
                    </div>
                `;

                Swal.fire({
                    title: 'Konfirmasi Bulk Update',
                    html: tableHtml + "<br><p class='text-sm mt-2 text-gray-600'>Proses ini akan mengupdate ribuan data secara otomatis. Apakah Anda yakin ingin melanjutkan?</p>",
                    icon: 'warning',
                    width: '600px',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Jalankan!',
                    cancelButtonText: 'Batal'
                }).then((res) => {
                    if (res.isConfirmed) {
                        Swal.fire({
                            title: 'Sedang Memproses...',
                            text: 'Harap tunggu, proses ini mungkin memakan waktu beberapa saat.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading()
                            }
                        });
                        document.getElementById('form-bulk-update').submit();
                    }
                });

            } catch (error) {
                console.error('Error fetching preview:', error);
                Swal.fire('Error', 'Terjadi kesalahan saat mengambil data preview.', 'error');
            }
        }

        function confirmRollback(form, batchId, count) {
            Swal.fire({
                title: 'Konfirmasi Rollback',
                html: `Anda akan membatalkan update untuk batch <b>${batchId}</b> yang terdiri dari <b>${count}</b> unit data.<br><br>Gunakan opsi ini hanya jika hasil plot koordinat dirasa kurang pas. Data akan dikembalikan ke status 'PENDING'.`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Kembalikan Data',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Sedang Memproses Rollback...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading()
                        }
                    });
                    form.submit();
                }
            })
        }
    </script>
    </div>
</body>

</html>
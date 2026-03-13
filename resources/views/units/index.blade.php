<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Groundcheck App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="bg-gray-100 p-2 sm:p-4 lg:p-6">
    <div class="max-w-7xl mx-auto bg-white shadow-md rounded-lg p-3 sm:p-6">
        <h1 class="text-xl sm:text-2xl font-bold mb-4">Groundcheck Monitor</h1>

        <!-- Username Input -->
        <div
            class="mb-4 bg-yellow-50 p-3 border border-yellow-200 rounded flex flex-col sm:flex-row sm:items-center gap-2">
            <label class="font-semibold text-sm sm:text-base">Nama Petugas:</label>
            <input type="text" id="usernameInput" class="border p-1.5 rounded w-full sm:w-64 text-sm"
                placeholder="Masukkan Nama Anda..." onchange="saveName(this.value)">
            <span class="text-[10px] sm:text-xs text-gray-500">(Otomatis tersimpan)</span>
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('units.index') }}"
            class="mb-6 flex flex-col sm:flex-row flex-wrap gap-2 sm:gap-4">
            <input type="text" name="search" id="searchInput" placeholder="Cari Nama Usaha / IDSBR..."
                value="{{ request('search') }}" class="border p-2 rounded w-full sm:w-64 text-sm"
                onkeyup="debounceSearch()">

            <!-- Kecamatan -->
            <select name="kdkec" id="kdkec" class="border p-2 rounded w-full sm:w-48 text-sm"
                onchange="loadVillages(this.value)">
                <option value="">Semua Kecamatan</option>
                <option value="UNKNOWN" {{ request('kdkec') == 'UNKNOWN' ? 'selected' : '' }}
                    class="bg-red-100 font-bold">Kecamatan Kosong</option>
                @foreach($kecamatans as $kec)
                    <option value="{{ $kec->code }}" {{ request('kdkec') == $kec->code ? 'selected' : '' }}>
                        {{ $kec->name }}
                    </option>
                @endforeach
            </select>

            <!-- Desa -->
            <select name="kddesa" id="kddesa" class="border p-2 rounded w-full sm:w-48 text-sm">
                <option value="">Semua Desa</option>
                <!-- Populated via JS -->
            </select>

            <!-- Status Keberadaan Filter -->
            <select name="status_keberadaan" class="border p-2 rounded w-full sm:w-48 text-sm">
                <option value="">- Status Keberadaan -</option>
                @php
                    $statuses = [
                        1 => '1. Aktif',
                        2 => '2. Tutup Sementara',
                        3 => '3. Belum Beroperasi',
                        4 => '4. Tutup',
                        5 => '5. Alih Usaha',
                        6 => '6. Tidak Ditemukan',
                        7 => '7. Aktif Pindah',
                        8 => '8. Aktif Nonrespon',
                        9 => '9. Duplikat',
                        10 => '10. Salah Kode Wilayah'
                    ];
                @endphp
                @foreach($statuses as $code => $label)
                    <option value="{{ $code }}" {{ request('status_keberadaan') == $code ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="border p-2 rounded w-full sm:w-48 text-sm">
                <option value="">Semua Status</option>
                <option value="NO_COORD" {{ request('status') == 'NO_COORD' ? 'selected' : '' }}>Belum Ada Koordinat
                </option>
                <option value="HAS_COORD" {{ request('status') == 'HAS_COORD' ? 'selected' : '' }}>Sudah Ada Koordinat
                </option>
                <option value="OUTSIDE_MAP" {{ request('status') == 'OUTSIDE_MAP' ? 'selected' : '' }}
                    class="text-red-600 font-bold">Tagging Diluar Kabupaten</option>
            </select>

            <select name="sort" class="border p-2 rounded w-full sm:w-32 text-sm">
                <option value="">Sortir</option>
                <option value="updated" {{ request('sort') == 'updated' ? 'selected' : '' }}>Terbaru</option>
            </select>

            <select name="tipe_usaha" class="border p-2 rounded w-full sm:w-48 text-sm">
                <option value="">Semua Tipe Usaha</option>
                <option value="PRELIST" {{ request('tipe_usaha') == 'PRELIST' ? 'selected' : '' }}>Data Prelist Asli
                </option>
                <option value="TAMBAHAN" {{ request('tipe_usaha') == 'TAMBAHAN' ? 'selected' : '' }}>Usaha Tambahan Baru
                </option>
            </select>

            <select name="petugas" class="border p-2 rounded w-full sm:w-48 text-sm">
                <option value="">Semua Petugas</option>
                @foreach($allPetugas as $p)
                    <option value="{{ $p }}" {{ request('petugas') == $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>

            <!-- Date Filter -->
            <input type="date" name="updated_at" value="{{ request('updated_at') }}"
                class="border p-2 rounded w-full sm:w-auto text-sm" title="Filter Tanggal Update">

            <!-- Rows Per Page -->
            <select name="per_page" id="perPage" class="border p-2 rounded w-full sm:w-24 text-sm"
                onchange="this.form.submit()">
                <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20 Baris</option>
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 Baris</option>
                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 Baris</option>
                <option value="200" {{ request('per_page') == 200 ? 'selected' : '' }}>200 Baris</option>
            </select>

            <div class="flex gap-2 w-full sm:w-auto mt-2 sm:mt-0 flex-wrap">
                <button type="submit"
                    class="flex-1 sm:flex-none bg-blue-500 text-white px-6 py-2 rounded font-semibold text-sm hover:bg-blue-600">Filter</button>
                <a href="{{ route('units.rekap') }}"
                    class="flex-1 sm:flex-none bg-indigo-600 text-white px-4 py-2 rounded text-center text-sm hover:bg-indigo-700">Rekap</a>
                <a href="{{ route('units.download_verified', request()->all()) }}"
                    class="flex-1 sm:flex-none bg-purple-600 text-white px-4 py-2 rounded text-center text-sm hover:bg-purple-700">Export</a>

                <button type="button" onclick="openAddModal()"
                    class="flex-1 sm:flex-none bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-center text-sm font-semibold flex items-center justify-center gap-1 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg> Tambah Usaha
                </button>
                <button type="button" onclick="openBulkModal()"
                    class="flex-1 sm:flex-none bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-center text-sm font-semibold flex items-center justify-center gap-1 transition-colors"
                    title="Tambah massal via Excel">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg> Bulk Excel
                </button>
                <button type="button" onclick="openBulkHistoryModal()"
                    class="flex-1 sm:flex-none bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-center text-sm font-semibold flex items-center justify-center gap-1 transition-colors"
                    title="Riwayat Bulk Update">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg> Histori Bulk
                </button>
            </div>
        </form>

        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        <div
            class="mb-4 text-sm font-medium text-blue-700 bg-blue-50 px-4 py-2 rounded-lg border border-blue-100 flex justify-between items-center shadow-sm">
            <span>Daftar Unit SBR</span>
            <span>Total: {{ number_format($units->total(), 0, ',', '.') }} baris</span>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 border">IDSBR</th>
                        <th class="p-2 border">Kecamatan</th>
                        <th class="p-2 border">Desa</th>
                        <th class="p-2 border">Nama Usaha</th>
                        <th class="p-2 border">Alamat</th>
                        <th class="p-2 border">Lat/Long</th>
                        <th class="p-2 border">Update Terakhir</th>
                        <th class="p-2 border">Aksi</th>
                    </tr>
                </thead>
                <tbody id="unitTableBody">
                    @include('units.partials.table_rows')
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $units->appends(request()->except('page'))->links() }}
        </div>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-2 sm:p-4">
        <div class="bg-white p-4 sm:p-6 rounded shadow-lg w-full max-w-4xl max-h-[95vh] flex flex-col">
            <h2 class="text-xl font-bold mb-4 flex-shrink-0">Detail Unit</h2>
            <div class="overflow-y-auto flex-grow">
                <table class="w-full text-sm border-collapse border border-gray-300">
                    <tbody id="detailContent">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-right">
                <button onclick="closeModal()" class="bg-gray-500 text-white px-4 py-2 rounded">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Map Modal -->
    <div id="mapModal"
        class="fixed inset-0 bg-black bg-opacity-75 hidden flex items-center justify-center z-[60] p-2 sm:p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl h-[90vh] flex flex-col relative">
            <!-- Close Button -->
            <button onclick="closeMapModal()"
                class="absolute top-3 right-3 bg-red-500 hover:bg-red-600 text-white rounded-full p-2 shadow-xl transition-colors"
                style="z-index: 10000;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <!-- Filter Toolbar (only visible in picker mode) -->
            <div id="mapFilterBar"
                class="hidden bg-gradient-to-r from-orange-50 to-amber-50 px-4 py-2.5 border-b border-orange-200 flex flex-wrap items-center gap-2 flex-shrink-0 rounded-t-lg"
                style="z-index: 9999;">
                <span class="text-sm font-bold text-orange-700">📍 Filter Wilayah:</span>
                <select id="mapFilterKec"
                    class="border border-orange-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-orange-400 focus:border-orange-400 w-44">
                    <option value="">Semua Kecamatan</option>
                </select>
                <select id="mapFilterDesa"
                    class="border border-orange-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-orange-400 focus:border-orange-400 w-44">
                    <option value="">Semua Desa</option>
                </select>
                <select id="mapFilterSls"
                    class="border border-orange-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-orange-400 focus:border-orange-400 w-48">
                    <option value="">Semua SLS</option>
                </select>
                <span id="mapFilterInfo" class="text-xs text-orange-600 ml-2"></span>
            </div>
            <div id="leafletMap" class="w-full flex-grow rounded-b-lg"></div>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-2 sm:p-4">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl max-h-[95vh] flex flex-col relative">
            <div class="p-4 border-b flex justify-between items-center bg-white rounded-t-lg">
                <div class="flex items-center gap-3">
                    <div
                        class="bg-green-600 rounded-lg p-2 text-white shadow-sm flex items-center justify-center h-10 w-10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Tambah Usaha Baru</h2>
                        <p class="text-xs text-gray-500">Isi data usaha/perusahaan</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="closeAddModal()"
                        class="bg-white hover:bg-gray-100 text-gray-500 rounded-md p-1.5 border border-gray-200 shadow-sm transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="overflow-y-auto p-4 bg-gray-50 flex-grow">
                <form id="addForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Petugas <span
                                class="text-red-500">*</span></label>
                        <input type="text" id="addPetugas"
                            class="w-full border rounded p-2 text-sm focus:ring focus:ring-blue-200"
                            placeholder="Misal: Andi (PCL/PML)" required>
                    </div>
                    <div>
                        <div class="flex justify-between items-end mb-1">
                            <label class="block text-xs font-semibold text-gray-600">Nama Usaha <span
                                    class="text-red-500">*</span></label>
                            <button type="button" onclick="cekDuplikat()"
                                class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded hover:bg-blue-200 border border-blue-200 font-semibold"
                                title="Cek apakah nama usaha sudah ada">Cek Duplikat</button>
                        </div>
                        <input type="text" id="addNamaUsaha"
                            class="w-full border rounded p-2 text-sm focus:ring focus:ring-blue-200"
                            placeholder="NAMA USAHA/PERUSAHAAN" required>
                        <span id="dupCheckResult" class="text-[10px] mt-1 font-semibold hidden"></span>
                        <div id="dupDataContainer"
                            class="text-[11px] text-red-700 bg-red-50 p-2 mt-2 rounded border border-red-200 hidden max-h-32 overflow-y-auto">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Alamat Usaha <span
                                class="text-red-500">*</span></label>
                        <input type="text" id="addAlamat"
                            class="w-full border rounded p-2 text-sm focus:ring focus:ring-blue-200"
                            placeholder="ALAMAT USAHA/PERUSAHAAN" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Provinsi <span
                                class="text-red-500">*</span></label>
                        <select
                            class="w-full border rounded p-2 text-sm bg-white text-gray-700 pointer-events-none appearance-none">
                            <option selected>[15] JAMBI</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Kabupaten/Kota <span
                                class="text-red-500">*</span></label>
                        <select
                            class="w-full border rounded p-2 text-sm bg-white text-gray-700 pointer-events-none appearance-none">
                            <option selected>[04] BATANG HARI</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Kecamatan <span
                                class="text-red-500">*</span></label>
                        <select id="addKec"
                            class="w-full border rounded p-2 text-sm bg-white focus:ring focus:ring-blue-200"
                            onchange="loadAddVillages(this.value)" required>
                            <option value="">-- Pilih Kecamatan --</option>
                            @foreach($kecamatans as $kec)
                                <option value="{{ $kec->code }}">
                                    [{{ str_pad(substr($kec->code, -3), 3, '0', STR_PAD_LEFT) }}] {{ $kec->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Kelurahan/Desa <span
                                class="text-red-500">*</span></label>
                        <select id="addDesa"
                            class="w-full border rounded p-2 text-sm bg-white focus:ring focus:ring-blue-200" required>
                            <option value="">-- Pilih Kelurahan/Desa --</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">S L S (Satuan Lingkungan Setempat)
                            <span class="text-red-500">*</span></label>
                        <select id="addSls"
                            class="w-full border rounded p-2 text-sm bg-white focus:ring focus:ring-blue-200" required>
                            <option value="">-- Pilih SLS --</option>
                        </select>
                    </div>
                    <div class="pt-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-2">Lokasi Usaha <span
                                class="text-red-500">*</span></label>
                        <button type="button" onclick="getCurrentLocationAdd()"
                            class="border border-blue-400 bg-white text-blue-600 hover:bg-blue-50 px-4 py-1.5 rounded-md text-sm font-medium flex items-center justify-center gap-2 mb-3 shadow-sm transition-colors">
                            <span class="text-red-500 text-lg">📍</span> Ambil Lokasi Saat Ini
                        </button>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Latitude <span
                                    class="text-red-500">*</span></label>
                            <input type="text" id="addLat"
                                class="w-full border rounded p-2 text-sm bg-white focus:ring focus:ring-blue-200"
                                required onchange="updateAddPreviewMap()" onkeyup="updateAddPreviewMap()">
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Longitude <span
                                    class="text-red-500">*</span></label>
                            <input type="text" id="addLng"
                                class="w-full border rounded p-2 text-sm bg-white focus:ring focus:ring-blue-200"
                                required onchange="updateAddPreviewMap()" onkeyup="updateAddPreviewMap()">
                        </div>
                    </div>

                    <div class="border rounded-md shadow-inner h-64 w-full mt-3 z-0 relative bg-gray-200"
                        id="addPreviewMap">
                        <div class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm">Memuat
                            Peta...</div>
                    </div>

                    <!-- Optional Detail Section -->
                    <div class="mt-6 border-t pt-4">
                        <h3 class="text-sm font-bold text-gray-700 mb-3 border-l-4 border-blue-500 pl-2">Detail Tambahan
                            (Opsional)</h3>
                        <div class="space-y-4">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nomor Telepon
                                        (Opsional)</label>
                                    <input type="text" id="addTelepon"
                                        class="w-full border rounded p-2 text-sm focus:ring focus:ring-blue-200 bg-white"
                                        placeholder="Contoh: 08123456789">
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Email
                                        (Opsional)</label>
                                    <input type="email" id="addEmail"
                                        class="w-full border rounded p-2 text-sm focus:ring focus:ring-blue-200 bg-white"
                                        placeholder="Contoh: info@usaha.com">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Deskripsi Kegiatan
                                    Usaha</label>
                                <textarea id="addDeskripsi"
                                    class="w-full border rounded p-2 text-sm focus:ring focus:ring-blue-200 flex-grow"
                                    rows="2"
                                    placeholder="Contoh: Menjual pakaian jadi, toko kelontong grosir..."></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">KBLI / Kategori</label>
                                <input type="text" id="addKbli" list="kbliList"
                                    class="w-full border rounded p-2 text-sm focus:ring focus:ring-blue-200"
                                    placeholder="Ketik untuk mencari KBLI Excel Legalitas...">
                                <datalist id="kbliList"></datalist>
                            </div>
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Skala Usaha / Badan
                                        Hukum</label>
                                    <select id="addSkala"
                                        class="w-full border rounded p-2 text-sm bg-white focus:ring focus:ring-blue-200">
                                        <option value="">-- Pilih Skala --</option>
                                        <option value="Usaha Besar">Usaha Besar</option>
                                        <option value="Menengah">Menengah</option>
                                        <option value="Usaha Mikro dan Kecil">Usaha Mikro dan Kecil</option>
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Sumber Data</label>
                                    <input type="text" id="addSumber"
                                        class="w-full border rounded p-2 text-sm focus:ring focus:ring-blue-200"
                                        placeholder="Sumber registrasi">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Catatan Profiling</label>
                                <textarea id="addCatatan"
                                    class="w-full border rounded p-2 text-sm focus:ring focus:ring-blue-200 flex-grow"
                                    rows="2" placeholder="Catatan atau keterangan lainnya..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div
                class="p-4 border-t flex justify-end gap-2 bg-white rounded-b-lg shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                <button type="button" onclick="closeAddModal()"
                    class="px-5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 rounded text-sm font-medium transition-colors">Batal</button>
                <button type="button" id="btnSubmitAdd" onclick="submitAddUsaha()"
                    class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded text-sm font-medium flex items-center gap-2 transition-colors focus:ring focus:ring-green-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg> Simpan
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Add Modal -->
    <div id="bulkAddModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-[70] p-2 sm:p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col relative">
            <div class="p-4 border-b flex justify-between items-center bg-gray-50 rounded-t-lg">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Tambah Bulk Excel</h2>
                    <p class="text-xs text-gray-500 mt-1">Upload file excel sesuai template untuk menambah data banyak
                        sekaligus.</p>
                </div>
                <button onclick="closeBulkModal()"
                    class="text-gray-400 hover:text-red-500 transition-colors p-2 rounded-full hover:bg-red-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div id="bulkUploadSection" class="p-4 overflow-y-auto w-full">
                <div class="mb-4 bg-teal-50 border border-teal-200 text-teal-800 p-3 rounded text-sm">
                    <strong>Penting:</strong>
                    <ul class="list-disc pl-5 mt-1">
                        <li>Pastikan menggunakan format template Excel yang disediakan.</li>
                        <li>Kolom bertanda (*) wajib diisi.</li>
                        <li>Koordinat (Latitude/Longitude) jika diisi akan otomatis mengubah status menjadi "Aktif".
                        </li>
                    </ul>
                    <div class="mt-2">
                        <button type="button" onclick="downloadTemplate()"
                            class="text-teal-700 font-semibold underline hover:text-teal-900 flex items-center gap-1 text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg> Download Template Excel
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih File Excel</label>
                    <input type="file" id="bulkExcelFile" accept=".xlsx, .xls, .csv"
                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2">
                </div>
            </div>

            <div id="bulkReviewSection" class="p-4 overflow-y-auto w-full hidden flex-1">
                <div class="mb-2 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Review Data (<span id="reviewCount">0</span> baris)</h3>
                    <div class="text-sm">
                        <span class="inline-block w-3 h-3 bg-red-100 border border-red-300 rounded mr-1"></span>
                        Duplikat
                    </div>
                </div>
                <div class="overflow-x-auto border rounded max-h-[50vh]">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-100 sticky top-0 shadow">
                            <tr>
                                <th class="p-2 border-b w-10 text-center"><input type="checkbox" id="checkAllReview"
                                        onchange="toggleAllReview(this)"
                                        class="w-4 h-4 text-teal-600 bg-gray-100 border-gray-300 rounded focus:ring-teal-500">
                                </th>
                                <th class="p-2 border-b">Nama Usaha</th>
                                <th class="p-2 border-b">Alamat / Lokasi</th>
                                <th class="p-2 border-b">Petugas</th>
                                <th class="p-2 border-b">Status</th>
                            </tr>
                        </thead>
                        <tbody id="reviewTableBody">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="p-4 border-t bg-gray-50 flex justify-end gap-2 rounded-b-lg">
                <button type="button" onclick="closeBulkModal()"
                    class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400 font-medium text-sm transition-colors">Batal</button>
                <button type="button" id="btnPreviewBulk" onclick="previewBulkExcel()"
                    class="bg-teal-600 text-white px-4 py-2 rounded hover:bg-teal-700 font-medium text-sm transition-colors shadow flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg> Review Data
                </button>
                <button type="button" id="btnSubmitBulk" onclick="submitBulkExcel()"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 font-medium text-sm transition-colors shadow flex items-center gap-1 hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg> Import Data Terpilih (<span id="selectedCount">0</span>)
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk History Modal -->
    <div id="bulkHistoryModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-[70] p-2 sm:p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col relative">
            <div class="p-4 border-b flex justify-between items-center bg-gray-50 rounded-t-lg">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Histori Bulk Excel</h2>
                    <p class="text-xs text-gray-500 mt-1">Daftar riwayat penambahan unit usaha massal.</p>
                </div>
                <button onclick="closeBulkHistoryModal()"
                    class="text-gray-400 hover:text-red-500 transition-colors p-2 rounded-full hover:bg-red-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-4 overflow-y-auto w-full flex-1">
                <div class="overflow-x-auto border rounded max-h-[60vh]">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-100 sticky top-0 shadow">
                            <tr>
                                <th class="p-2 border-b">Waktu Import</th>
                                <th class="p-2 border-b text-center">Jumlah Data</th>
                                <th class="p-2 border-b">Petugas</th>
                                <th class="p-2 border-b text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr>
                                <td colspan="4" class="text-center p-4">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="p-4 border-t bg-gray-50 flex justify-end gap-2 rounded-b-lg">
                <button type="button" onclick="closeBulkHistoryModal()"
                    class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 font-medium text-sm transition-colors">Tutup</button>
            </div>
        </div>
    </div>

    <!-- SheetJS Config -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <!-- Leaflet Config -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>


        // Map Logic
        let mapInstance = null;
        let mapLayerGroup = null;
        let currentUnitId = null;
        let pickingMode = false;

        function openMapVisualization(unitId, lat, lng, kdkec = null, kddesa = null) {
            const modal = document.getElementById('mapModal');
            modal.classList.remove('hidden');
            currentUnitId = unitId;

            const hasCoord = (lat !== null && lng !== null);
            pickingMode = !hasCoord;

            setTimeout(() => {
                // Completely reset map instance to avoid "Map container is already initialized" error
                if (mapInstance) {
                    mapInstance.remove();
                    mapInstance = null;
                }

                // Default view (Jambi approx)
                const initialView = hasCoord ? [lat, lng] : [-1.61, 103.6];
                const initialZoom = hasCoord ? 17 : 9;

                mapInstance = L.map('leafletMap').setView(initialView, initialZoom);

                const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
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

                // Add default layer (Google Hybrid like Rekap)
                mapInstance.addLayer(googleHybrid);

                // Layer Control
                L.control.layers({
                    "Google Hybrid": googleHybrid,
                    "Esri Satellite (Backup)": esriSatellite,
                    "Bing Satellite": bingSatellite,
                    "Google Maps": googleStreets,
                    "OpenStreetMap": osmLayer
                }).addTo(mapInstance);

                mapLayerGroup = L.layerGroup().addTo(mapInstance);

                // Legend Control
                const legend = L.control({ position: 'bottomright' });
                legend.onAdd = function () {
                    const div = L.DomUtil.create('div', 'leaflet-legend');
                    div.style.cssText = 'background:white;padding:10px 14px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.3);font-size:12px;line-height:1.8;min-width:160px;';
                    div.innerHTML = `
                        <div style="font-weight:700;margin-bottom:6px;font-size:13px;border-bottom:1px solid #e5e7eb;padding-bottom:4px;">📍 Legenda</div>
                        <div style="display:flex;align-items:center;gap:6px;"><span style="display:inline-block;width:20px;height:3px;background:#F97316;border-radius:2px;"></span> Batas SLS</div>
                        <div style="display:flex;align-items:center;gap:6px;"><span style="display:inline-block;width:12px;height:12px;background:#FFEDD5;border:1.5px solid #F97316;border-radius:2px;"></span> Area SLS</div>
                        <div style="display:flex;align-items:center;gap:6px;"><span style="display:inline-block;width:20px;height:3px;background:#3B82F6;border-radius:2px;"></span> SLS Unit Ini</div>
                        <div style="display:flex;align-items:center;gap:6px;"><span style="display:inline-block;width:12px;height:16px;color:#3B82F6;text-align:center;font-size:14px;">📍</span> Lokasi Unit</div>
                        <div style="margin-top:6px;padding-top:6px;border-top:1px solid #e5e7eb;color:#6B7280;font-size:11px;">Klik peta untuk memilih lokasi</div>
                    `;
                    return div;
                };
                legend.addTo(mapInstance);

                // Current Location Control
                const locateControl = L.control({ position: 'topleft' });
                locateControl.onAdd = function () {
                    const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
                    const btn = L.DomUtil.create('a', '', container);
                    btn.innerHTML = '📍';
                    btn.href = '#';
                    btn.style.cssText = 'width:34px; height:34px; background:white; cursor:pointer; line-height:34px; text-align:center; font-size:18px; text-decoration:none; display:block;';
                    btn.title = "Lokasi Saya Saat Ini";
                    btn.onclick = function (e) {
                        e.preventDefault();
                        btn.innerHTML = '⏳';
                        mapInstance.locate({ setView: true, maxZoom: 18 });
                    };

                    mapInstance.on('locationfound', function (e) {
                        btn.innerHTML = '📍';
                        const radius = e.accuracy / 2;
                        L.circle(e.latlng, radius, {
                            color: '#ef4444',
                            fillColor: '#ef4444',
                            fillOpacity: 0.2
                        }).addTo(mapLayerGroup);
                        L.marker(e.latlng).addTo(mapLayerGroup)
                            .bindPopup("Anda berada dalam radius " + Math.round(radius) + " meter dari titik ini").openPopup();
                    });

                    mapInstance.on('locationerror', function (e) {
                        btn.innerHTML = '📍';
                        alert("Gagal mendapatkan lokasi Anda. Pastikan GPS aktif dan browser diizinkan mengakses lokasi.");
                    });

                    return container;
                };
                locateControl.addTo(mapInstance);

                // Click listener for Picker Mode
                mapInstance.on('click', function (e) {
                    if (pickingMode && currentUnitId) {
                        const clickedLat = e.latlng.lat.toFixed(6);
                        const clickedLng = e.latlng.lng.toFixed(6);

                        // Remove old marker
                        mapLayerGroup.eachLayer(layer => {
                            if (layer instanceof L.Marker) mapLayerGroup.removeLayer(layer);
                        });

                        L.marker([clickedLat, clickedLng]).addTo(mapLayerGroup)
                            .bindPopup("Lokasi Terpilih").openPopup();

                        const latInput = document.getElementById(`lat-${currentUnitId}`);
                        const longInput = document.getElementById(`long-${currentUnitId}`);
                        if (latInput && longInput) {
                            latInput.value = clickedLat;
                            longInput.value = clickedLng;
                            checkSlsPreview(currentUnitId);

                            latInput.classList.add('bg-green-100');
                            longInput.classList.add('bg-green-100');
                            setTimeout(() => {
                                latInput.classList.remove('bg-green-100');
                                longInput.classList.remove('bg-green-100');
                            }, 500);
                        }
                    }
                });

                const baseUrl = "{{ url('/') }}";

                if (hasCoord) {
                    // VIEW MODE: Show marker + SLS boundary + filter toolbar with context
                    document.getElementById('mapFilterBar').classList.remove('hidden');

                    L.marker([lat, lng]).addTo(mapLayerGroup)
                        .bindPopup("<b>Lokasi Unit</b><br>Lat: " + lat + "<br>Long: " + lng).openPopup();

                    // Load specific SLS boundary (blue) for this unit's coordinates
                    fetch(`${baseUrl}/api/check-sls?lat=${lat}&lng=${lng}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success && data.geometry) {
                                const polygonLayer = L.geoJSON(data.geometry, {
                                    style: { color: '#3B82F6', weight: 3, opacity: 0.9, fillColor: '#DBEAFE', fillOpacity: 0.15 }
                                }).addTo(mapLayerGroup);

                                polygonLayer.bindPopup(`<b>${data.nmsls}</b><br>Desa: ${data.nmdesa}<br>Kec: ${data.nmkec}<br>ID: ${data.idsls}`);

                                const bounds = polygonLayer.getBounds();
                                bounds.extend([lat, lng]);
                                mapInstance.fitBounds(bounds, { padding: [50, 50] });
                            }
                        })
                        .catch(err => console.error("Map Error:", err));

                    // Also load surrounding SLS boundaries (orange) with auto-filter
                    loadSlsForPicker(baseUrl, kdkec, kddesa);
                } else {
                    // PICKER MODE: Show filter toolbar & load GeoJSON
                    document.getElementById('mapFilterBar').classList.remove('hidden');
                    loadSlsForPicker(baseUrl, kdkec, kddesa);
                }
            }, 300);
        }

        // === PICKER MODE: SLS Filter Logic ===
        let cachedGeoData = null;

        async function loadSlsForPicker(baseUrl, initialKec, initialDesa) {
            const filterInfo = document.getElementById('mapFilterInfo');
            filterInfo.textContent = 'Memuat data SLS...';

            try {
                // Load GeoJSON once, cache it
                if (!cachedGeoData) {
                    const res = await fetch(`${baseUrl}/sls_1504.geojson`);
                    if (!res.ok) throw new Error('GeoJSON not found');
                    cachedGeoData = await res.json();
                }

                // Build kecamatan list from GeoJSON properties
                const kecMap = {};
                cachedGeoData.features.forEach(f => {
                    const p = f.properties;
                    const kec = (p.kdkec || '').toString();
                    if (kec && !kecMap[kec]) {
                        kecMap[kec] = p.nmkec || `Kec ${kec}`;
                    }
                });

                // Populate Kecamatan dropdown
                const kecSelect = document.getElementById('mapFilterKec');
                kecSelect.innerHTML = '<option value="">Semua Kecamatan</option>';
                Object.entries(kecMap).sort((a, b) => a[0].localeCompare(b[0])).forEach(([code, name]) => {
                    const opt = document.createElement('option');
                    opt.value = code;
                    opt.textContent = name;
                    kecSelect.appendChild(opt);
                });

                // Event listeners for filters
                kecSelect.onchange = function () {
                    populateDesaDropdown(this.value);
                    document.getElementById('mapFilterSls').innerHTML = '<option value="">Semua SLS</option>';
                    renderFilteredSls();
                };
                document.getElementById('mapFilterDesa').onchange = function () {
                    const kecVal = document.getElementById('mapFilterKec').value;
                    populateSlsDropdown(kecVal, this.value);
                    renderFilteredSls();
                };
                document.getElementById('mapFilterSls').onchange = function () {
                    renderFilteredSls();
                };

                // Auto-select initial kec/desa if valid (normalize to match GeoJSON format)
                if (initialKec && initialKec !== '0' && initialKec !== 'UNKNOWN') {
                    // Try exact match first, then padded match
                    const kecOptions = Array.from(kecSelect.options);
                    const exactMatch = kecOptions.find(o => o.value === initialKec);
                    const paddedMatch = kecOptions.find(o => o.value === initialKec.toString().padStart(3, '0'));
                    const numericMatch = kecOptions.find(o => parseInt(o.value) === parseInt(initialKec));

                    const matchedKec = exactMatch || paddedMatch || numericMatch;
                    if (matchedKec) {
                        kecSelect.value = matchedKec.value;
                        populateDesaDropdown(matchedKec.value);

                        if (initialDesa && initialDesa !== '0') {
                            const desaSelect = document.getElementById('mapFilterDesa');
                            const desaOptions = Array.from(desaSelect.options);
                            const desaExact = desaOptions.find(o => o.value === initialDesa);
                            const desaPadded = desaOptions.find(o => o.value === initialDesa.toString().padStart(3, '0'));
                            const desaNumeric = desaOptions.find(o => parseInt(o.value) === parseInt(initialDesa));

                            const matchedDesa = desaExact || desaPadded || desaNumeric;
                            if (matchedDesa) {
                                desaSelect.value = matchedDesa.value;
                                populateSlsDropdown(matchedKec.value, matchedDesa.value);
                            }
                        }
                    }
                }

                // Initial render
                renderFilteredSls();

            } catch (err) {
                console.error('Failed to load SLS GeoJSON:', err);
                filterInfo.textContent = 'Gagal memuat data SLS.';
            }
        }

        function populateDesaDropdown(kecCode) {
            const desaSelect = document.getElementById('mapFilterDesa');
            desaSelect.innerHTML = '<option value="">Semua Desa</option>';
            if (!kecCode || !cachedGeoData) return;

            const desaMap = {};
            cachedGeoData.features.forEach(f => {
                const p = f.properties;
                if ((p.kdkec || '').toString() === kecCode.toString()) {
                    const desa = (p.kddesa || '').toString();
                    if (desa && !desaMap[desa]) {
                        desaMap[desa] = p.nmdesa || `Desa ${desa}`;
                    }
                }
            });

            Object.entries(desaMap).sort((a, b) => a[0].localeCompare(b[0])).forEach(([code, name]) => {
                const opt = document.createElement('option');
                opt.value = code;
                opt.textContent = name;
                desaSelect.appendChild(opt);
            });
        }

        function populateSlsDropdown(kecCode, desaCode) {
            const slsSelect = document.getElementById('mapFilterSls');
            slsSelect.innerHTML = '<option value="">Semua SLS</option>';
            if (!kecCode || !desaCode || !cachedGeoData) return;

            const slsMap = {};
            cachedGeoData.features.forEach(f => {
                const p = f.properties;
                if ((p.kdkec || '').toString() === kecCode.toString() &&
                    (p.kddesa || '').toString() === desaCode.toString()) {
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

        function renderFilteredSls() {
            if (!cachedGeoData || !mapInstance) return;

            const selKec = document.getElementById('mapFilterKec').value;
            const selDesa = document.getElementById('mapFilterDesa').value;
            const selSls = document.getElementById('mapFilterSls').value;
            const filterInfo = document.getElementById('mapFilterInfo');

            // Clear previous SLS layers (keep markers)
            mapLayerGroup.eachLayer(layer => {
                if (!(layer instanceof L.Marker)) {
                    mapLayerGroup.removeLayer(layer);
                }
            });

            // Filter features
            let filtered = cachedGeoData.features;
            if (selKec) {
                filtered = filtered.filter(f => (f.properties.kdkec || '').toString() === selKec.toString());
            }
            if (selDesa) {
                filtered = filtered.filter(f => (f.properties.kddesa || '').toString() === selDesa.toString());
            }
            if (selSls) {
                filtered = filtered.filter(f => (f.properties.kdsls || '').toString() === selSls.toString());
            }

            if (filtered.length === 0) {
                filterInfo.textContent = 'Tidak ada SLS ditemukan.';
                return;
            }

            filterInfo.textContent = `${filtered.length} SLS ditampilkan`;

            const slsLayer = L.geoJSON({ type: 'FeatureCollection', features: filtered }, {
                style: {
                    color: '#F97316',
                    weight: 1.5,
                    opacity: 0.8,
                    fillColor: '#FFEDD5',
                    fillOpacity: 0.2
                },
                onEachFeature: function (feature, layer) {
                    if (feature.properties) {
                        const p = feature.properties;
                        let label = `
                            <div class="font-sans text-xs">
                                <strong>${p.nmsls || 'SLS'}</strong><br>
                                ${p.nmdesa || ''} - ${p.nmkec || ''}<br>
                                <span class="text-gray-500">${p.idsls || ''}</span>
                            </div>
                        `;
                        // Assuming 'results' and 'html' are defined elsewhere if this is to be used.
                        // This block is inserted as per instruction, but its context (results, html) is missing.
                        // If this is meant to be part of the label, it needs to be integrated.
                        // For now, inserting as a standalone block as per instruction.
                        /*
                        results.data.forEach(dup => {
                        const scoreClass = dup.similarity >= 90 ? 'text-danger fw-bold' : (dup.similarity >= 80 ? 'text-warning fw-bold' : 'text-info');
                        html += `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">${dup.nama_usaha}</h6>
                                    <p class="mb-1 small">${dup.alamat || '-'}</p>
                                    <small class="text-muted">${dup.lokasi || '-'}</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-light ${scoreClass}">${dup.similarity}% Mirip</span>
                                    <br><small class="text-muted" style="font-size: 0.7rem">${dup.reason}</small>
                                </div>
                            </div>
                        </div>`;
                    });
    `;
                        */
                        layer.bindTooltip(label, { sticky: true, className: 'bg-white border border-orange-200 shadow-md rounded px-2 py-1' });
                    }
                }
            }).addTo(mapLayerGroup);

            mapInstance.fitBounds(slsLayer.getBounds(), { padding: [30, 30] });
        }

        function closeMapModal() {
            document.getElementById('mapModal').classList.add('hidden');
            document.getElementById('mapFilterBar').classList.add('hidden');
        }

        // Username Persistence
        document.addEventListener("DOMContentLoaded", () => {
            const savedName = localStorage.getItem('gc_username');
            if (savedName) {
                document.getElementById('usernameInput').value = savedName;
            }

            // Event Delegation: Attach listeners to table body (persists after AJAX updates)
            const tableBody = document.getElementById('unitTableBody');

            // Handle paste events for coordinate inputs
            tableBody.addEventListener('paste', function (e) {
                const target = e.target;
                if (target.id && target.id.startsWith('lat-')) {
                    const id = target.id.replace('lat-', '');
                    handlePaste(e, id);
                }
            });

            // Handle input events for SLS Preview (Debounced)
            let debounceTimer;
            tableBody.addEventListener('input', function (e) {
                const target = e.target;
                if (target.id && (target.id.startsWith('lat-') || target.id.startsWith('long-'))) {
                    const id = target.id.replace('lat-', '').replace('long-', '');

                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        checkSlsPreview(id);
                    }, 800); // 800ms delay to request
                }
            });
        });

        async function checkSlsPreview(id) {
            const latInput = document.getElementById(`lat-${id}`);
            const longInput = document.getElementById(`long-${id}`);
            const slsContainer = document.getElementById(`sls-info-${id}`);

            if (!latInput || !longInput || !slsContainer) return;

            const lat = latInput.value;
            const long = longInput.value;

            // Only check if both are present
            if (!lat || !long) {
                return;
            }

            // Show loading state
            slsContainer.innerHTML = `<div class="mt-1 text-[10px] text-blue-600 animate-pulse">Checking location...</div>`;

            const baseUrl = "{{ url('/') }}";
            try {
                const res = await fetch(`${baseUrl}/api/check-sls?lat=${lat}&lng=${long}`);
                const data = await res.json();

                if (data.success) {
                    slsContainer.innerHTML = `
                        <div class="mt-1 text-[10px] bg-blue-50 text-blue-800 p-1 rounded border border-blue-200">
                            <span class="font-bold text-xs">🔍 Preview:</span><br>
                            <span class="font-bold">Lokasi:</span> ${data.nmsls} <br>
                            <span class="font-bold">Desa:</span> ${data.nmdesa} <br>
                            <span class="font-bold">Kecamatan:</span> ${data.nmkec}
                        </div>
                    `;
                } else {
                    slsContainer.innerHTML = `
                        <div class="mt-1 text-[10px] bg-gray-100 text-gray-500 p-1 rounded border border-gray-200">
                            <span class="font-bold">🔍 Preview:</span> Tidak ditemukan wilayah yang cocok.
                        </div>
                    `;
                }
            } catch (err) {
                console.error("Preview Error", err);
                slsContainer.innerHTML = `<div class="mt-1 text-[10px] text-red-400">Preview Error</div>`;
            }
        }


        function saveName(val) {
            localStorage.setItem('gc_username', val);
        }

        function openDetail(id) {
            try {
                const el = document.getElementById(`detail-data-${id}`);
                if (!el) {
                    console.error("Detail data not found for ID", id);
                    return;
                }
                const jsonStr = el.value;
                const data = JSON.parse(jsonStr);
                showDetail(data);
            } catch (e) {
                console.error("Error parsing detail data", e);
                alert("Gagal menampilkan detail: " + e.message);
            }
        }

        function showDetail(rawJson) {
            const data = typeof rawJson === 'string' ? JSON.parse(rawJson) : rawJson;
            const tbody = document.getElementById('detailContent');
            tbody.innerHTML = '';

            for (const [key, value] of Object.entries(data)) {
                tbody.innerHTML += `
                    <tr class="border-b">
                        <td class="p-2 font-medium bg-gray-100 w-1/3">${key}</td>
                        <td class="p-2 break-all">${value !== null ? value : '-'}</td>
                    </tr>
                `;
            }

            document.getElementById('detailModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }

        function handlePaste(e, id) {
            const clipboardData = e.clipboardData || window.clipboardData;
            const pastedData = clipboardData.getData('Text');

            if (pastedData.includes(',')) {
                e.preventDefault();

                const parts = pastedData.split(',').map(s => s.trim());
                if (parts.length >= 2) {
                    const latInput = document.getElementById(`lat-${id}`);
                    const longInput = document.getElementById(`long-${id}`);

                    latInput.value = parts[0];
                    longInput.value = parts[1];

                    // Trigger Input Event for Preview
                    checkSlsPreview(id);

                    latInput.classList.add('bg-green-100');
                    longInput.classList.add('bg-green-100');
                    setTimeout(() => {
                        latInput.classList.remove('bg-green-100');
                        longInput.classList.remove('bg-green-100');
                    }, 1000);
                }
            }
        }

        async function updateUnit(e, id) {
            e.preventDefault();
            const lat = document.getElementById(`lat-${id}`).value;
            const long = document.getElementById(`long-${id}`).value;
            const status = document.getElementById(`status-${id}`).value;
            const msgSpan = document.getElementById(`msg-${id}`);

            // Get Username & Concurrency Timestamp
            const username = document.getElementById('usernameInput').value;
            const lastUpdatedAt = document.getElementById(`updated-at-${id}`).value;
            if (!username) {
                alert("Mohon isi 'Nama Petugas' terlebih dahulu di bagian atas halaman!");
                document.getElementById('usernameInput').focus();
                return;
            }

            msgSpan.innerText = "Saving...";

            // Fix URL for subfolder
            const baseUrl = "{{ url('/') }}";
            const url = `${baseUrl}/units/${id}/update`;

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        latitude: lat,
                        longitude: long,
                        status_keberadaan: status,
                        username: username,
                        last_updated_at: lastUpdatedAt
                    })
                });

                const data = await res.json();
                console.log("Update Response:", data);

                if (res.ok && data.success) {
                    msgSpan.innerText = "Saved!";
                    if (data.debug_sls && data.debug_sls.error) {
                        msgSpan.innerText = "Saved! (SLS Error: " + data.debug_sls.error + ")";
                        msgSpan.className = "text-xs ml-2 text-orange-600 font-bold";
                        console.warn("SLS Debug:", data.debug_sls);
                    } else {
                        msgSpan.className = "text-xs ml-2 text-green-600 font-bold";
                    }
                    setTimeout(() => msgSpan.innerText = "", 5000);

                    // 1. Update "Last Update" Column & Timestamp
                    const lastUpdateCell = document.getElementById(`last-update-${id}`);
                    if (lastUpdateCell) {
                        lastUpdateCell.innerHTML = `
                            <div>${data.last_update}</div>
                            <div class="text-[10px] font-bold text-gray-600">Terakhir: ${data.user}</div>
                            <span class="bg-green-200 text-green-800 px-1 rounded text-[10px]">Barusan</span>
                        `;
                    }

                    // Update the hidden timestamp for next save
                    const tsInput = document.getElementById(`updated-at-${id}`);
                    if (tsInput && data.new_timestamp) {
                        tsInput.value = data.new_timestamp;
                    }

                    // 1b. Update SLS Info (Yellow Box)
                    const slsContainer = document.getElementById(`sls-info-${id}`);
                    if (slsContainer && data.detail_json) {
                        const d = data.detail_json;
                        // Check if we have SLS data to show
                        if (d.sls_nmsls) {
                            slsContainer.innerHTML = `
                                <div class="mt-1 text-[10px] bg-yellow-100 text-yellow-800 p-1 rounded border border-yellow-200">
                                    <span class="font-bold">Lokasi:</span> ${d.sls_nmsls} <br>
                                    <span class="font-bold">Desa:</span> ${d.sls_nmdesa || d.nmdesa || '-'} <br>
                                    <span class="font-bold">Kecamatan:</span> ${d.sls_nmkec || '-'}
                                </div>
                             `;
                        }
                    }

                    const detailTextarea = document.getElementById(`detail-data-${id}`);
                    if (detailTextarea && data.detail_json) {
                        detailTextarea.value = JSON.stringify(data.detail_json);
                    }

                    // 3. Update Row Color to Green (Verified)
                    const row = document.getElementById(`lat-${id}`).closest('tr');
                    if (row) {
                        // Remove potential previous classes
                        row.classList.remove('bg-white', 'bg-blue-100', 'bg-emerald-100', 'text-emerald-900', 'hover:bg-gray-50');
                        // Add Verified Green class
                        row.classList.add('bg-green-50');
                    }

                } else {
                    console.error("Save Error:", data);
                    msgSpan.innerText = "Fail: " + (data.message || "Unknown error");
                    msgSpan.className = "text-xs ml-2 text-red-600 font-bold";
                    alert("Error saving: " + (data.message || "Unknown error"));
                }
            } catch (err) {
                console.error("Fetch Error:", err);
                msgSpan.innerText = "Net Fail";
                alert("Network Error: " + err);
            }
        }


        async function cancelUnit(e, id) {
            e.preventDefault();

            // SweetAlert Popup for Code Input
            const { value: code } = await Swal.fire({
                title: 'Konfirmasi Pembatalan',
                text: "Masukkan Kode Pembatalan untuk melanjutkan:",
                input: 'password',
                inputAttributes: {
                    autocapitalize: 'off',
                    placeholder: 'Kode Pembatalan'
                },
                showCancelButton: true,
                confirmButtonText: 'Proses Pembatalan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
                showLoaderOnConfirm: true,
                preConfirm: async (code) => {
                    if (code !== "batal123") {
                        Swal.showValidationMessage('Kode Salah!');
                        return false;
                    }

                    // API Call Logic
                    const username = document.getElementById('usernameInput').value || 'Admin';
                    const msgSpan = document.getElementById(`msg-${id}`);
                    if (msgSpan) {
                        msgSpan.innerText = "Cancelling...";
                        msgSpan.className = "text-xs ml-2 text-orange-600 font-bold";
                    }

                    const baseUrl = "{{ url('/') }}";
                    try {
                        const res = await fetch(`${baseUrl}/units/${id}/cancel`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ username: username })
                        });

                        const data = await res.json();
                        if (!res.ok || !data.success) {
                            throw new Error(data.message || 'Gagal membatalkan');
                        }
                        return data;
                    } catch (error) {
                        Swal.showValidationMessage(`Request failed: ${error}`);
                    }
                },
                allowOutsideClick: () => !Swal.isLoading()
            });

            if (code) {
                // Success Handling after Popup Checks
                // Reset UI
                document.getElementById(`lat-${id}`).value = "";
                document.getElementById(`long-${id}`).value = "";
                document.getElementById(`status-${id}`).value = "";

                const slsDiv = document.getElementById(`sls-info-${id}`);
                if (slsDiv) slsDiv.innerHTML = "";

                const msgSpan = document.getElementById(`msg-${id}`);
                if (msgSpan) {
                    msgSpan.innerText = "Cancelled!";
                    msgSpan.className = "text-xs ml-2 text-red-600 font-bold";
                }

                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Data update telah dibatalkan.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });

                // Refresh Table
                setTimeout(() => debounceSearch(false), 500);
            }
        }

        async function loadVillages(kecCode, selectedDesa = null) {
            const desaSelect = document.getElementById('kddesa');
            desaSelect.innerHTML = '<option value="">Loading...</option>';

            if (!kecCode) {
                desaSelect.innerHTML = '<option value="">Semua Desa</option>';
                return;
            }

            try {
                const baseUrl = "{{ url('/') }}";
                const res = await fetch(`${baseUrl}/api/villages/${kecCode}`);
                const villages = await res.json();

                let options = '<option value="">Semua Desa</option>';
                villages.forEach(v => {
                    const isSelected = (selectedDesa && String(selectedDesa) === String(v.code)) ? 'selected' : '';
                    options += `<option value="${v.code}" ${isSelected}>${v.name}</option>`;
                });

                desaSelect.innerHTML = options;
            } catch (e) {
                console.error(e);
                desaSelect.innerHTML = '<option value="">Error Loading</option>';
            }
        }

        let searchTimeout;
        function debounceSearch(resetPage = true) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const query = document.getElementById('searchInput').value;
                const kdkec = document.getElementById('kdkec').value;
                const kddesa = document.getElementById('kddesa').value;
                const perPage = document.getElementById('perPage').value;

                // Construct URL
                const url = new URL(window.location.href);
                url.searchParams.set('search', query);
                if (kdkec) url.searchParams.set('kdkec', kdkec);
                if (kddesa) url.searchParams.set('kddesa', kddesa);
                if (perPage) url.searchParams.set('per_page', perPage);

                // Only reset page if explicitly requested (e.g. new search)
                if (resetPage) {
                    url.searchParams.set('page', 1);
                }

                // Fetch
                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('unitTableBody').innerHTML = html;
                        // Also update URL address bar without reload
                        window.history.pushState({}, '', url);
                    })
                    .catch(err => console.error("Search failed", err));

            }, 500); // 500ms delay
        }

        // Auto load desa if kec is selected
        const currentKec = "{{ request('kdkec') }}";
        const currentDesa = "{{ request('kddesa') }}";
        if (currentKec) {
            loadVillages(currentKec, currentDesa);
        }

        // --- ADD USAHA LOGIC ---
        let addMapInstance = null;
        let addMapLayerGroup = null;
        let addMarker = null;
        let kbliLoaded = false;

        async function loadKbliData() {
            if (kbliLoaded) return;
            try {
                const res = await fetch("{{ url('/') }}/kbli.json");
                if (!res.ok) throw new Error("Gagal load KBLI");
                const data = await res.json();
                const dl = document.getElementById('kbliList');
                let options = '';
                data.forEach(item => {
                    options += `<option value="${item.kbli} - ${item.deskripsi}"></option>`;
                });
                dl.innerHTML = options;
                kbliLoaded = true;
            } catch (e) {
                console.error(e);
            }
        }

        async function deleteUnit(id) {
            const { value: code } = await Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Masukkan kode untuk menghapus data ini',
                input: 'password',
                inputPlaceholder: 'Kode Hapus',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Hapus Unit'
            });

            if (code) {
                if (code === 'hapus123') {
                    try {
                        const baseUrl = "{{ url('/') }}";
                        const res = await fetch(`${baseUrl}/units/${id}/delete`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            await Swal.fire('Terhapus!', data.message || 'Data berhasil dihapus.', 'success');
                            window.location.reload();
                        } else {
                            Swal.fire('Gagal!', data.message || 'Terjadi kesalahan saat menghapus data.', 'error');
                        }
                    } catch (e) {
                        Swal.fire('Error!', 'Koneksi ke server gagal.', 'error');
                    }
                } else {
                    Swal.fire('Salah!', 'Kode yang Anda masukkan salah.', 'error');
                }
            }
        }

        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');

            // Initialize map if not yet
            if (!addMapInstance) {
                addMapInstance = L.map('addPreviewMap').setView([-1.711645, 103.111404], 9); // Default Jambi/Batanghari

                // --- Basemaps ---
                const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' });
                const googleMaps = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', { maxZoom: 20, attribution: '© Google Maps' });
                const googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', { maxZoom: 20, attribution: '© Google Maps' });
                const esriSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, attribution: '© Esri' });
                const bingMaps = L.tileLayer('https://ecn.t3.tiles.virtualearth.net/tiles/a{q}.jpeg?g=1', { maxZoom: 19, attribution: '© Bing Maps' });

                googleHybrid.addTo(addMapInstance);

                const baseMaps = {
                    "Google Satellite (Hybrid)": googleHybrid,
                    "Google Maps (Jalan)": googleMaps,
                    "Esri Satellite": esriSatellite,
                    "Bing Maps Satellite": bingMaps,
                    "OpenStreetMap": osmLayer
                };

                L.control.layers(baseMaps, null, { collapsed: true }).addTo(addMapInstance);

                addMapLayerGroup = L.featureGroup().addTo(addMapInstance);

                // --- Legend ---
                const legend = L.control({ position: 'bottomright' });
                legend.onAdd = function () {
                    const div = L.DomUtil.create('div', 'info legend');
                    div.style.cssText = 'background:white;padding:10px 14px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.3);font-size:12px;line-height:1.8;min-width:160px;';
                    div.innerHTML = `
                        <div style="font-weight:700;margin-bottom:6px;font-size:13px;border-bottom:1px solid #e5e7eb;padding-bottom:4px;">📍 Legenda</div>
                        <div style="display:flex;align-items:center;gap:6px;"><span style="display:inline-block;width:20px;height:3px;background:#F97316;border-radius:2px;"></span> Batas SLS</div>
                        <div style="display:flex;align-items:center;gap:6px;"><span style="display:inline-block;width:12px;height:12px;background:#FFEDD5;border:1.5px solid #F97316;border-radius:2px;"></span> Area SLS</div>
                        <div style="display:flex;align-items:center;gap:6px;"><span style="display:inline-block;width:12px;height:16px;color:#2563EB;text-align:center;font-size:14px;">📍</span> Lokasi Usaha</div>
                        <div style="margin-top:6px;padding-top:6px;border-top:1px solid #e5e7eb;color:#6B7280;font-size:11px;">Klik peta untuk titik lokasi</div>
                    `;
                    return div;
                };
                legend.addTo(addMapInstance);

                // Add map click listener
                addMapInstance.on('click', function (e) {
                    const lat = e.latlng.lat.toFixed(6);
                    const lng = e.latlng.lng.toFixed(6);

                    document.getElementById('addLat').value = lat;
                    document.getElementById('addLng').value = lng;

                    updateAddPreviewMap();
                });
            }

            // Fix map size issue when rendered in hidden div
            setTimeout(() => {
                addMapInstance.invalidateSize();
                loadAddSlsBoundary();
                loadKbliData();
            }, 300);
        }

        async function loadAddSlsBoundary(trigger = null) {
            if (!addMapInstance || !addMapLayerGroup) return;

            const kecVal = document.getElementById('addKec').value;
            const desaVal = document.getElementById('addDesa').value;
            const slsVal = document.getElementById('addSls').value;

            // Clear previous boundaries
            addMapLayerGroup.eachLayer(layer => {
                if (!(layer instanceof L.Marker) && !(layer instanceof L.Circle)) {
                    addMapLayerGroup.removeLayer(layer);
                }
            });

            try {
                // Fetch cached GeoData if not already loaded
                if (!cachedGeoData) {
                    document.getElementById('addSls').innerHTML = '<option value="">Loading Peta SLS...</option>';
                    const baseUrl = "{{ url('/') }}";
                    const res = await fetch(`${baseUrl}/sls_1504.geojson`);
                    if (!res.ok) throw new Error('GeoJSON not found');
                    cachedGeoData = await res.json();
                }

                // Filter GeoJSON
                let filtered = cachedGeoData.features;
                let targetIdKec = kecVal ? "1504" + kecVal.toString().padStart(3, '0') : null;
                let targetIdDesa = desaVal ? "1504" + desaVal.toString().padStart(6, '0') : null;

                if (targetIdKec) {
                    filtered = filtered.filter(f => (f.properties.idkec || '').toString() === targetIdKec);
                }
                if (targetIdDesa) {
                    filtered = filtered.filter(f => (f.properties.iddesa || '').toString() === targetIdDesa);
                }
                if (slsVal) {
                    filtered = filtered.filter(f => (f.properties.idsls || '').toString() === slsVal.toString());
                }

                // Populate SLS Dropdown if trigger is not 'sls' itself
                if (trigger !== 'sls') {
                    const slsSelect = document.getElementById('addSls');
                    slsSelect.innerHTML = '<option value="">-- Pilih SLS --</option>';

                    let slsListItems = cachedGeoData.features;
                    if (targetIdKec) slsListItems = slsListItems.filter(f => (f.properties.idkec || '').toString() === targetIdKec);
                    if (targetIdDesa) slsListItems = slsListItems.filter(f => (f.properties.iddesa || '').toString() === targetIdDesa);

                    const slsMap = {};
                    slsListItems.forEach(f => {
                        const p = f.properties;
                        if (p.idsls && p.kdsls) {
                            slsMap[p.idsls] = { id: p.idsls, name: p.nmsls, kdsls: p.kdsls };
                        }
                    });

                    Object.values(slsMap).sort((a, b) => a.kdsls.localeCompare(b.kdsls)).forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.id;
                        opt.textContent = `[${s.kdsls}] ${s.name || 'N/A'}`;
                        if (s.id === slsVal) opt.selected = true; // Retain selection if valid
                        slsSelect.appendChild(opt);
                    });
                }

                if (filtered.length > 0) {
                    const slsLayer = L.geoJSON({ type: 'FeatureCollection', features: filtered }, {
                        style: {
                            color: '#F97316',
                            weight: 1.5,
                            opacity: 0.8,
                            fillColor: '#FFEDD5',
                            fillOpacity: 0.2
                        },
                        onEachFeature: function (feature, layer) {
                            if (feature.properties) {
                                const p = feature.properties;
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
                    }).addTo(addMapLayerGroup);

                    // Fit map bounds
                    const lat = document.getElementById('addLat').value;
                    const lng = document.getElementById('addLng').value;
                    if (!lat || !lng || trigger) {
                        addMapInstance.fitBounds(slsLayer.getBounds(), { padding: [30, 30] });
                    }
                } else if (!kecVal && !desaVal) {
                    document.getElementById('addSls').innerHTML = '<option value="">-- SLS Tidak Ditemukan --</option>';
                }
            } catch (err) {
                console.error("Failed to load SLS for Add:", err);
            }
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
            document.getElementById('addForm').reset();
            document.getElementById('addSls').innerHTML = '<option value="">-- Pilih SLS --</option>';
            document.getElementById('addDesa').innerHTML = '<option value="">-- Pilih Kelurahan/Desa --</option>';
            if (addMarker) {
                if (addMapLayerGroup) addMapLayerGroup.removeLayer(addMarker);
                addMarker = null;
            }
            if (addMapLayerGroup) addMapLayerGroup.clearLayers();
        }

        document.getElementById('addKec').addEventListener('change', function () {
            loadAddVillages(this.value);
            loadAddSlsBoundary('kec');
        });

        document.getElementById('addDesa').addEventListener('change', function () {
            loadAddSlsBoundary('desa');
        });

        document.getElementById('addSls').addEventListener('change', function () {
            loadAddSlsBoundary('sls');
        });

        async function loadAddVillages(kecCode) {
            const desaSelect = document.getElementById('addDesa');
            desaSelect.innerHTML = '<option value="">Loading...</option>';

            if (!kecCode) {
                desaSelect.innerHTML = '<option value="">-- Pilih Kelurahan/Desa --</option>';
                return;
            }

            try {
                const baseUrl = "{{ url('/') }}";
                const res = await fetch(`${baseUrl}/api/villages/${kecCode}`);
                if (!res.ok) throw new Error("Failed to fetch");
                const villages = await res.json();

                let options = '<option value="">-- Pilih Kelurahan/Desa --</option>';
                villages.forEach(v => {
                    options += `<option value="${v.code}">[${String(v.code).slice(-3).padStart(3, '0')}] ${v.name}</option>`;
                });

                desaSelect.innerHTML = options;
            } catch (e) {
                console.error(e);
                desaSelect.innerHTML = '<option value="">Error Loading</option>';
            }
        }

        function getCurrentLocationAdd() {
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    const lat = position.coords.latitude.toFixed(6);
                    const lng = position.coords.longitude.toFixed(6);
                    document.getElementById('addLat').value = lat;
                    document.getElementById('addLng').value = lng;
                    updateAddPreviewMap();

                    Swal.fire({
                        title: 'Berhasil',
                        text: 'Lokasi Anda saat ini berhasil didapatkan!',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }, function (error) {
                    alert("Gagal mendapatkan lokasi. Pastikan GPS aktif dan browser diizinkan mengakses lokasi.");
                }, {
                    enableHighAccuracy: true
                });
            } else {
                alert("Browser Anda tidak mendukung Geolocation.");
            }
        }

        function updateAddPreviewMap() {
            if (!addMapInstance) return;
            const lat = parseFloat(document.getElementById('addLat').value);
            const lng = parseFloat(document.getElementById('addLng').value);

            if (!isNaN(lat) && !isNaN(lng)) {
                const latlng = [lat, lng];
                if (addMarker) {
                    addMarker.setLatLng(latlng);
                } else {
                    addMarker = L.marker(latlng).addTo(addMapLayerGroup);
                }
                addMapInstance.setView(latlng, 17);
            }
        }

        async function submitAddUsaha() {
            const btn = document.getElementById('btnSubmitAdd');
            const form = document.getElementById('addForm');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const petugas = document.getElementById('addPetugas').value;
            const nama_usaha = document.getElementById('addNamaUsaha').value;
            const kdkec = document.getElementById('addKec').value;
            const kddesa = document.getElementById('addDesa').value;
            const sls_idsls = document.getElementById('addSls').value;
            const latitude = document.getElementById('addLat').value;
            const longitude = document.getElementById('addLng').value;
            const alamat_usaha = document.getElementById('addAlamat').value;

            // Optional Detail Fields
            const addtelepon = document.getElementById('addTelepon').value;
            const addemail = document.getElementById('addEmail').value;
            const deskripsi_kegiatan_usaha = document.getElementById('addDeskripsi').value;
            const kbli_kategori = document.getElementById('addKbli').value;
            const skala_usaha = document.getElementById('addSkala').value;
            const sumber_data = document.getElementById('addSumber').value;
            const catatan_profiling = document.getElementById('addCatatan').value;

            btn.disabled = true;
            btn.innerHTML = 'Menyimpan...';

            try {
                const baseUrl = "{{ url('/') }}";
                const res = await fetch(`${baseUrl}/units/store`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        petugas: petugas,
                        nama_usaha: nama_usaha,
                        alamat_usaha: alamat_usaha,
                        kdkec: kdkec,
                        kddesa: kddesa,
                        sls_idsls: sls_idsls,
                        latitude: latitude,
                        longitude: longitude,
                        telepon: addtelepon,
                        email: addemail,
                        deskripsi_kegiatan_usaha: deskripsi_kegiatan_usaha,
                        kbli_kategori: kbli_kategori,
                        skala_usaha: skala_usaha,
                        sumber_data: sumber_data,
                        catatan_profiling: catatan_profiling,
                        source: 'Web Groundcheck - Tambah Manual'
                    })
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Usaha baru telah ditambahkan.',
                        icon: 'success'
                    }).then(() => {
                        window.location.reload();
                    });
                } else if (data.is_duplicate) {
                    // Show duplicate warning with details
                    let dupHtml = '<div class="text-left text-sm mb-3">' + data.message + '</div>';
                    dupHtml += '<div class="text-left text-xs max-h-40 overflow-auto border rounded p-2">';
                    (data.duplicates || []).forEach(d => {
                        let colorClass = d.similarity >= 90 ? 'color:#dc2626' : (d.similarity >= 80 ? 'color:#ea580c' : 'color:#2563eb');
                        dupHtml += `<div class="mb-2 pb-2 border-b"><b>${d.nama_usaha}</b> <span style="${colorClass};font-weight:bold">[${d.similarity}% - ${d.reason}]</span><br><span style="color:#666">${d.alamat || '-'}${d.lokasi ? ' | ' + d.lokasi : ''} (${d.idsbr})</span></div>`;
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
                        // Re-send with force_save
                        const forceRes = await fetch(`${baseUrl}/units/store`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                petugas, nama_usaha, alamat_usaha, kdkec, kddesa, sls_idsls,
                                latitude, longitude, telepon: addtelepon, email: addemail,
                                deskripsi_kegiatan_usaha, kbli_kategori, skala_usaha,
                                sumber_data, catatan_profiling,
                                source: 'Web Groundcheck - Tambah Manual',
                                force_save: true
                            })
                        });
                        const forceData = await forceRes.json();
                        if (forceRes.ok && forceData.success) {
                            Swal.fire('Berhasil!', 'Usaha berhasil disimpan (bypass duplikat).', 'success').then(() => window.location.reload());
                        } else {
                            throw new Error(forceData.message || 'Gagal menyimpan.');
                        }
                    }
                } else {
                    throw new Error(data.message || 'Terjadi kesalahan saat menyimpan.');
                }
            } catch (err) {
                Swal.fire('Error', err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width=" 2" d="M5 13l4 4L19 7"/></svg> Simpan';
            }
        }

        let currentBulkData = [];

        function openBulkModal() {
            document.getElementById('bulkExcelFile').value = '';
            document.getElementById('bulkUploadSection').classList.remove('hidden');
            document.getElementById('bulkReviewSection').classList.add('hidden');
            document.getElementById('btnPreviewBulk').classList.remove('hidden');
            document.getElementById('btnSubmitBulk').classList.add('hidden');
            document.getElementById('checkAllReview').checked = true;
            document.getElementById('bulkAddModal').classList.remove('hidden');
        }
        function closeBulkModal() {
            document.getElementById('bulkAddModal').classList.add('hidden');
        }

        async function previewBulkExcel() {
            const fileInput = document.getElementById('bulkExcelFile');
            if (!fileInput.files.length) {
                Swal.fire('Perhatian', 'Silakan pilih file Excel terlebih dahulu.', 'warning');
                return;
            }
            const file = fileInput.files[0];
            const reader = new FileReader();

            Swal.fire({
                title: 'Memproses...',
                text: 'Membaca dan mengecek duplikasi data...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            reader.onload = async function (e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheet = workbook.SheetNames[0];
                    const rows = XLSX.utils.sheet_to_json(workbook.Sheets[firstSheet], { defval: "" });

                    if (rows.length === 0) {
                        Swal.fire('Gagal', 'File Excel kosong atau tidak valid.', 'error');
                        return;
                    }

                    const baseUrl = "{{ url('/') }}";
                    const res = await fetch(`${baseUrl}/units/bulk-preview`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(rows)
                    });

                    const result = await res.json();
                    if (res.ok && result.success) {
                        currentBulkData = result.data;
                        renderReviewTable();
                        Swal.close();

                        // Switch view
                        document.getElementById('bulkUploadSection').classList.add('hidden');
                        document.getElementById('bulkReviewSection').classList.remove('hidden');
                        document.getElementById('btnPreviewBulk').classList.add('hidden');
                        document.getElementById('btnSubmitBulk').classList.remove('hidden');
                    } else {
                        Swal.fire('Gagal!', result.message || 'Terjadi kesalahan.', 'error');
                    }
                } catch (error) {
                    console.error(error);
                    Swal.fire('Error', 'Gagal memproses file Excel.', 'error');
                }
            };
            reader.readAsArrayBuffer(file);
        }

        function renderReviewTable() {
            const tbody = document.getElementById('reviewTableBody');
            tbody.innerHTML = '';

            const escapeHtml = (unsafe) => {
                return (unsafe || '').toString()
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            };

            let html = '';
            let defaultSelected = 0;

            currentBulkData.forEach((row, i) => {
                const nama = escapeHtml(row['Nama Usaha'] || row['nama_usaha'] || '-');
                const alamat = escapeHtml(row['Alamat Usaha'] || row['alamat'] || '-');
                const petugas = escapeHtml(row['Petugas'] || row['petugas'] || '-');
                const isDup = row.is_duplicate;
                const checkedStr = isDup ? '' : 'checked';

                if (!isDup) defaultSelected++;

                const rowClass = isDup ? 'bg-red-50 hover:bg-red-100 border-b' : 'bg-white hover:bg-gray-50 border-b';
                let dupLabel;
                if (isDup) {
                    const dupInfo = escapeHtml(row.duplicate_info || 'Terdeteksi Duplikat');
                    dupLabel = `<span class="text-xs font-bold text-red-600">⚠️ ${dupInfo}</span>`;
                } else {
                    dupLabel = '<span class="text-xs text-green-600">✅ Baru</span>';
                }

                let alamatHtml = `<div class="truncate max-w-[200px]" title="${alamat}">${alamat}</div>`;
                let lat = row['Latitude'] || row['latitude'];
                let lng = row['Longitude'] || row['longitude'];
                if (row['_auto_coord']) {
                    alamatHtml += `<div class="text-[10px] bg-orange-100 text-orange-800 px-1.5 py-0.5 rounded border border-orange-200 mt-1 inline-flex items-center gap-1" title="Koordinat otomatis dari SLS"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg> Auto Coords: ${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)}</div>`;
                } else if (lat && lng) {
                    alamatHtml += `<div class="text-[10px] bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded border border-blue-200 mt-1 inline-flex items-center gap-1" title="Koordinat dari Excel"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg> ${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)}</div>`;
                }

                html += `
                    <tr class="${rowClass}">
                        <td class="p-2 text-center">
                            <input type="checkbox" class="review-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500" data-idx="${i}" ${checkedStr} onchange="updateSelectedCount()">
                        </td>
                        <td class="p-2 font-medium">${nama}</td>
                        <td class="p-2 text-xs text-gray-600">${alamatHtml}</td>
                        <td class="p-2 text-xs">${petugas}</td>
                        <td class="p-2">${dupLabel}</td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
            document.getElementById('reviewCount').innerText = currentBulkData.length;
            document.getElementById('selectedCount').innerText = defaultSelected;
        }

        function toggleAllReview(cb) {
            const checkboxes = document.querySelectorAll('.review-checkbox');
            checkboxes.forEach(chk => {
                chk.checked = cb.checked;
            });
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const count = document.querySelectorAll('.review-checkbox:checked').length;
            document.getElementById('selectedCount').innerText = count;
        }

        async function submitBulkExcel() {
            const checkboxes = document.querySelectorAll('.review-checkbox:checked');
            if (checkboxes.length === 0) {
                Swal.fire('Perhatian', 'Tidak ada data yang dipilih untuk di-import.', 'warning');
                return;
            }

            const selectedData = [];
            checkboxes.forEach(chk => {
                const idx = parseInt(chk.getAttribute('data-idx'));
                selectedData.push(currentBulkData[idx]);
            });

            Swal.fire({
                title: 'Mengimport Data...',
                text: `Mengirim ${selectedData.length} data ke server...`,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const baseUrl = "{{ url('/') }}";
                const res = await fetch(`${baseUrl}/units/bulk-import`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(selectedData)
                });

                const result = await res.json();
                if (res.ok && result.success) {
                    await Swal.fire('Berhasil!', result.message, 'success');
                    window.location.reload();
                } else {
                    Swal.fire('Gagal!', result.message || 'Terjadi kesalahan.', 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Gagal memproses file Excel.', 'error');
            }
        }

        // --- BULK HISTORY MODAL ---
        function openBulkHistoryModal() {
            const modal = document.getElementById('bulkHistoryModal');
            modal.classList.remove('hidden');
            loadBulkHistory();
        }

        function closeBulkHistoryModal() {
            document.getElementById('bulkHistoryModal').classList.add('hidden');
        }

        async function loadBulkHistory() {
            const tbody = document.getElementById('historyTableBody');
            tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4">Loading...</td></tr>';

            try {
                const baseUrl = "{{ url('/') }}";
                const res = await fetch(`${baseUrl}/units/bulk-history`);
                const result = await res.json();

                if (result.success) {
                    if (result.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-gray-500">Belum ada riwayat bulk excel.</td></tr>';
                        return;
                    }

                    let html = '';
                    result.data.forEach(item => {
                        html += `
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-2">${item.date}</td>
                                <td class="p-2 text-center"><span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs font-bold">${item.count}</span></td>
                                <td class="p-2 text-xs">${item.petugas || '-'}</td>
                                <td class="p-2 text-center">
                                    <button onclick="cancelBulkBatch('${item.batch_id}')" class="text-red-600 hover:text-red-800 text-xs font-medium underline flex items-center justify-center gap-1 mx-auto">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg> Revert
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    tbody.innerHTML = html;
                }
            } catch (err) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-red-500">Gagal memuat data</td></tr>';
            }
        }

        async function cancelBulkBatch(batchId) {
            const { value: code } = await Swal.fire({
                title: 'Konfirmasi Revert Bulk',
                text: "Semua data dari proses upload ini akan dihapus. Masukkan kode: revert123",
                input: 'password',
                inputAttributes: {
                    autocapitalize: 'off',
                    placeholder: 'Kode Konfirmasi'
                },
                showCancelButton: true,
                confirmButtonText: 'Revert Data',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33'
            });

            if (code) {
                if (code !== 'revert123') {
                    Swal.fire('Salah!', 'Kode konfirmasi salah.', 'error');
                    return;
                }

                Swal.fire({
                    title: 'Menghapus...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                try {
                    const baseUrl = "{{ url('/') }}";
                    const res = await fetch(`${baseUrl}/units/bulk-cancel`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ batch_id: batchId })
                    });

                    const data = await res.json();
                    if (res.ok && data.success) {
                        await Swal.fire('Berhasil', data.message, 'success');
                        window.location.reload();
                    } else {
                        Swal.fire('Gagal!', data.message || 'Error saat menghapus histori', 'error');
                    }
                } catch (e) {
                    Swal.fire('Error!', 'Koneksi ke server gagal.', 'error');
                }
            }
        }

        function downloadTemplate() {
            const headers = ['Nama Usaha', 'Alamat Usaha', 'Kecamatan', 'Desa', 'SLS', 'Latitude', 'Longitude', 'Petugas', 'Telepon', 'Email', 'KBLI', 'Skala Usaha', 'Sumber Data', 'Deskripsi'];
            const dummyData = [
                'Toko Serbaguna Dummy',
                'Jl. Contoh No. 12',
                '1504010',
                '1504010001',
                '15040100010001',
                '-1.6111',
                '103.6222',
                'Andi',
                '08123456789',
                'info@contoh.com',
                '01111 - PERTANIAN PADI',
                'Usaha Mikro dan Kecil',
                'SBR',
                'Menjual barang kebutuhan sehari-hari'
            ];
            const ws = XLSX.utils.aoa_to_sheet([headers, dummyData]);
            const wscols = headers.map(h => ({ wch: h.length + 12 }));
            ws['!cols'] = wscols;

            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Template");
            XLSX.writeFile(wb, "Template_Tambah_Bulk.xlsx");
        }

        async function cekDuplikat() {
            const namaInput = document.getElementById('addNamaUsaha');
            const resultSpan = document.getElementById('dupCheckResult');
            const dataContainer = document.getElementById('dupDataContainer');
            const nama = namaInput.value.trim();

            dataContainer.classList.add('hidden');
            dataContainer.innerHTML = '';

            if (!nama) {
                resultSpan.textContent = 'Isi nama usaha dulu!';
                resultSpan.className = 'text-[10px] mt-1 font-semibold block text-red-600';
                return;
            }
            try {
                resultSpan.textContent = 'Mengecek...';
                resultSpan.className = 'text-[10px] mt-1 font-semibold block text-gray-600';

                const kdkec = document.getElementById('addKec').value;
                const kddesa = document.getElementById('addDesa').value;

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
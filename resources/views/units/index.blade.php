<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Groundcheck App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="bg-gray-100 p-6">
    <div class="max-w-7xl mx-auto bg-white shadow-md rounded-lg p-6">
        <h1 class="text-2xl font-bold mb-4">Groundcheck Monitor</h1>

        <!-- Username Input -->
        <div class="mb-4 bg-yellow-50 p-3 border border-yellow-200 rounded flex items-center">
            <label class="mr-2 font-semibold">Nama Petugas:</label>
            <input type="text" id="usernameInput" class="border p-1 rounded w-64" placeholder="Masukkan Nama Anda..."
                onchange="saveName(this.value)">
            <span class="text-xs text-gray-500 ml-2">(Otomatis tersimpan)</span>
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('units.index') }}" class="mb-6 flex gap-4 flex-wrap">
            <input type="text" name="search" id="searchInput" placeholder="Cari Nama Usaha..."
                value="{{ request('search') }}" class="border p-2 rounded w-64" onkeyup="debounceSearch()">

            <!-- Kecamatan -->
            <select name="kdkec" id="kdkec" class="border p-2 rounded w-48" onchange="loadVillages(this.value)">
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
            <select name="kddesa" id="kddesa" class="border p-2 rounded w-48">
                <option value="">Semua Desa</option>
                <!-- Populated via JS -->
            </select>

            <!-- Status Keberadaan Filter -->
            <select name="status_keberadaan" class="border p-2 rounded w-48">
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

            <select name="status" class="border p-2 rounded w-48">
                <option value="">Semua Status</option>
                <option value="NO_COORD" {{ request('status') == 'NO_COORD' ? 'selected' : '' }}>Belum Ada Koordinat
                </option>
                <option value="HAS_COORD" {{ request('status') == 'HAS_COORD' ? 'selected' : '' }}>Sudah Ada Koordinat
                </option>
            </select>

            <select name="sort" class="border p-2 rounded w-48">
                <option value="">Default Sort</option>
                <option value="updated" {{ request('sort') == 'updated' ? 'selected' : '' }}>Baru Diupdate</option>
            </select>

            <!-- Export Button -->
            <a href="{{ route('units.rekap') }}" class="bg-indigo-600 text-white px-4 py-2 rounded text-center">Rekap
                Data</a>
            <a href="{{ url('/download-hasil-verifikasi') }}" class="bg-purple-600 text-white px-4 py-2 rounded text-center">Export
                Excel</a>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Filter</button>
        </form>

        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">{{ session('error') }}</div>
        @endif

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
                <tbody>
                <tbody id="unitTableBody">
                    @include('units.partials.table_rows')
                </tbody>
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $units->links() }}
        </div>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded shadow-lg w-3/4 max-h-[90vh] overflow-y-auto">
            <h2 class="text-xl font-bold mb-4">Detail Unit</h2>
            <div class="overflow-x-auto">
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

    <script>
        // Username Persistence
        document.addEventListener("DOMContentLoaded", () => {
            const savedName = localStorage.getItem('gc_username');
            if (savedName) {
                document.getElementById('usernameInput').value = savedName;
            }
        });

        function saveName(val) {
            localStorage.setItem('gc_username', val);
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

            // Get Username
            const username = document.getElementById('usernameInput').value;
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
                        username: username
                    })
                });

                const data = await res.json();
                console.log("Update Response:", data);

                if (res.ok && data.success) {
                    msgSpan.innerText = "Saved!";
                    msgSpan.className = "text-xs ml-2 text-green-600 font-bold";
                    setTimeout(() => msgSpan.innerText = "", 2000);

                    // 1. Update "Last Update" Column
                    const lastUpdateCell = document.getElementById(`last-update-${id}`);
                    if (lastUpdateCell) {
                        lastUpdateCell.innerHTML = `
                            <div>${data.last_update}</div>
                            <div class="text-[10px] font-bold text-gray-600">Oleh: ${data.user}</div>
                            <span class="bg-green-200 text-green-800 px-1 rounded text-[10px]">Barusan</span>
                        `;
                    }

                    // 2. Update "Detail" Button Data
                    const btnDetail = document.getElementById(`btn-detail-${id}`);
                    if (btnDetail && data.detail_json) {
                        // We must re-attach the onclick event with new data
                        // NOTE: standard addEventListener might be cleaner, but simple onclick replacement works for inline
                        // We need to be careful with quotes in JSON string
                        const jsonStr = JSON.stringify(data.detail_json);

                        // We can't easily change onclick attribute string safely due to escaping hell.
                        // Better approach: change the click handler to read from a data property or global store.
                        // Or simply assign a new function.
                        btnDetail.onclick = function () {
                            showDetail(data.detail_json);
                        };
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
        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const query = document.getElementById('searchInput').value;
                const kdkec = document.getElementById('kdkec').value;
                const kddesa = document.getElementById('kddesa').value;

                // Construct URL
                const url = new URL(window.location.href);
                url.searchParams.set('search', query);
                if (kdkec) url.searchParams.set('kdkec', kdkec);
                if (kddesa) url.searchParams.set('kddesa', kddesa);
                // Reset page to 1 on search
                url.searchParams.set('page', 1);

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
    </script>
</body>

</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Kode Wilayah - Groundcheck App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Custom scrollbar for log area */
        #logArea::-webkit-scrollbar {
            width: 8px;
        }
        #logArea::-webkit-scrollbar-track {
            background: #1f2937; /* gray-800 */
            border-radius: 4px;
        }
        #logArea::-webkit-scrollbar-thumb {
            background: #4b5563; /* gray-600 */
            border-radius: 4px;
        }
        #logArea::-webkit-scrollbar-thumb:hover {
            background: #6b7280; /* gray-500 */
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-7xl mx-auto">
        <!-- Header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 font-bold mb-2">🔍 Deteksi & Generate Kode Wilayah dari Alamat
                </h1>
                <p class="text-gray-500 text-sm">Tool cerdas untuk mendeteksi secara otomatis Kode Kecamatan, Desa, dan
                    sub-SLS/RT dari isian teks alamat pada file Excel Anda.</p>
            </div>
            <div>
                <a href="{{ url('/units/rekap') }}"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring focus:ring-blue-200 active:text-gray-800 active:bg-gray-50 disabled:opacity-25 transition">
                    <svg class="w-4 h-4 fill-current shrink-0 mr-2" viewBox="0 0 16 16">
                        <path d="M11.7 15.3L10.3 14l5-5-5-5 1.4-1.3L18 9l-6.3 6.3z"
                            transform="scale(-1 1) translate(-17 0)" />
                    </svg>
                    Kembali ke Rekap
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Input Section -->
            <div class="bg-white rounded-xl shadow border border-gray-100 p-6 overflow-hidden">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-100 pb-2 flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm font-bold">1</div> Upload File Anda
                </h2>
                <p class="text-sm text-gray-600 mb-4">
                    Siapkan file Excel (`.xlsx` atau `.xls`) Anda. Sistem akan mencari kolom <strong>Alamat Usaha</strong>
                    dan melakukan pencocokan fuzzy untuk mencari kode.
                </p>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="sheetName">Sheet Name
                        (Opsional)</label>
                    <input id="sheetName" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:outline-none transition-shadow" type="text"
                        placeholder="Biarkan kosong untuk otomatis sheet pertama" />
                </div>

                <div class="relative group mt-2">
                    <input type="file" id="addressFile" accept=".xlsx, .xls"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                    <div
                        class="w-full h-32 border-2 border-dashed border-gray-300 group-hover:border-blue-500 rounded-xl bg-gray-50 flex flex-col items-center justify-center transition-colors">
                        <svg class="w-8 h-8 text-gray-400 group-hover:text-blue-500 mb-2 transition-colors" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                            </path>
                        </svg>
                        <span class="text-sm text-gray-500 font-medium font-sans" id="fileChosenText">Klik atau Drag file
                            Excel ke sini</span>
                    </div>
                </div>

                <button id="btnProcess" disabled
                    class="mt-6 w-full inline-flex items-center justify-center px-4 py-3 bg-blue-600 border border-transparent rounded-lg font-semibold text-white tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:bg-gray-400 disabled:cursor-not-allowed shadow">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Proses & Generate Kode
                </button>
            </div>

            <!-- Progress & Log Section -->
            <div
                class="bg-gray-900 rounded-xl shadow border border-gray-800 p-6 text-gray-300 flex flex-col font-mono text-xs h-[400px]">
                <h2 class="text-sm font-semibold text-white mb-4 border-b border-gray-700 pb-2 flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center font-bold font-sans">2</div> Console Log
                    <div id="loader"
                        class="hidden w-4 h-4 rounded-full border-2 border-green-500 border-t-transparent animate-spin ml-auto">
                    </div>
                </h2>

                <div id="logArea" class="flex-grow overflow-y-auto space-y-2 pb-4 pr-2">
                    <div class="text-gray-500">> Siap menunggu file...</div>
                </div>

                <div id="downloadContainer" class="hidden mt-4 pt-4 border-t border-gray-700">
                    <div class="text-green-400 mb-3 font-semibold text-sm">✅ Selesai diproses!</div>
                    <button id="btnDownload"
                        class="w-full inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 border border-transparent rounded-lg font-bold text-white tracking-widest hover:from-emerald-600 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition shadow">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download Excel Hasil
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Require SheetJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const addressFile = document.getElementById('addressFile');
            const fileChosenText = document.getElementById('fileChosenText');
            const btnProcess = document.getElementById('btnProcess');
            const logArea = document.getElementById('logArea');
            const loader = document.getElementById('loader');
            const downloadContainer = document.getElementById('downloadContainer');
            const btnDownload = document.getElementById('btnDownload');
            const sheetNameInput = document.getElementById('sheetName');

            let originalWorkbook = null;
            let modifiedWorkbook = null;
            let currentFileName = '';

            function log(msg, type = 'info') {
                const colors = {
                    'info': 'text-gray-300',
                    'success': 'text-green-400',
                    'warning': 'text-yellow-400',
                    'error': 'text-red-400',
                    'cmd': 'text-blue-400 font-bold'
                };
                const col = colors[type] || colors['info'];
                logArea.innerHTML += `<div class="${col}">> ${msg}</div>`;
                logArea.scrollTop = logArea.scrollHeight;
            }

            addressFile.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    fileChosenText.textContent = file.name;
                    fileChosenText.classList.replace('text-gray-500', 'text-blue-600');
                    btnProcess.disabled = false;
                    currentFileName = file.name;
                    log(`File selected: ${file.name}`, 'info');
                    // clear previous states
                    downloadContainer.classList.add('hidden');
                    originalWorkbook = null;
                    modifiedWorkbook = null;
                } else {
                    fileChosenText.textContent = 'Klik atau Drag file Excel ke sini';
                    fileChosenText.classList.replace('text-blue-600', 'text-gray-500');
                    btnProcess.disabled = true;
                }
            });

            btnProcess.addEventListener('click', async () => {
                const file = addressFile.files[0];
                if (!file) return;

                btnProcess.disabled = true;
                loader.classList.remove('hidden');
                log(`Mulai memproses file...`, 'cmd');

                const reader = new FileReader();

                reader.onload = async (e) => {
                    try {
                        const data = new Uint8Array(e.target.result);
                        log(`Membaca workbook...`);
                        originalWorkbook = XLSX.read(data, { type: 'array' });

                        let targetSheetName = sheetNameInput.value.trim();
                        if (!targetSheetName) {
                            targetSheetName = originalWorkbook.SheetNames[0];
                        }

                        if (!originalWorkbook.Sheets[targetSheetName]) {
                            log(`Sheet "${targetSheetName}" tidak ditemukan!`, 'error');
                            btnProcess.disabled = false;
                            loader.classList.add('hidden');
                            return;
                        }

                        log(`Menggunakan sheet: ${targetSheetName}`, 'success');
                        const worksheet = originalWorkbook.Sheets[targetSheetName];
                        const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 }); // Read as 2D array to preserve structure and emptiness

                        if (jsonData.length <= 1) {
                            log(`File kosong atau hanya berisi header.`, 'error');
                            btnProcess.disabled = false;
                            loader.classList.add('hidden');
                            return;
                        }

                        // Find "Alamat Usaha" column index
                        const headerRow = jsonData[0];
                        let alamatIdx = -1;
                        for (let i = 0; i < headerRow.length; i++) {
                            if (headerRow[i] && typeof headerRow[i] === 'string' && headerRow[i].toLowerCase().includes('alamat')) {
                                alamatIdx = i;
                                break;
                            }
                        }

                        if (alamatIdx === -1) {
                            log(`Tidak bisa menemukan kolom Header yang mengandung kata "Alamat".`, 'error');
                            btnProcess.disabled = false;
                            loader.classList.add('hidden');
                            return;
                        }

                        log(`Ditemukan kolom Alamat pada indeks ${alamatIdx} ("${headerRow[alamatIdx]}")`, 'success');

                        // Batch processing setup
                        const batchSize = 500;
                        log(`Mengekstrak baris untuk diproses ke server...`);

                        // Add new headers if they don't exist
                        headerRow.push('Kode Kecamatan (Auto)', 'Kode Desa (Auto)', 'Kode SLS (Auto)');
                        const newKecIdx = headerRow.length - 3;
                        const newDesaIdx = headerRow.length - 2;
                        const newSlsIdx = headerRow.length - 1;

                        // Group addresses for batching
                        let addressesToProcess = [];
                        let rowIndexMap = []; // Maps batch index to original json row index

                        for (let rowIndex = 1; rowIndex < jsonData.length; rowIndex++) {
                            const row = jsonData[rowIndex];
                            // Ensure row has enough elements for the new columns
                            while (row.length < headerRow.length) row.push('');

                            const alamat = row[alamatIdx] ? String(row[alamatIdx]).trim() : '';
                            if (alamat !== '') {
                                addressesToProcess.push(alamat);
                                rowIndexMap.push(rowIndex);
                            }
                        }

                        log(`Total baris beralamat: ${addressesToProcess.length}`);

                        let machedCount = 0;

                        for (let i = 0; i < addressesToProcess.length; i += batchSize) {
                            const batch = addressesToProcess.slice(i, i + batchSize);
                            const batchIndices = rowIndexMap.slice(i, i + batchSize);
                            log(`Memproses batch ${Math.floor(i / batchSize) + 1} dari ${Math.ceil(addressesToProcess.length / batchSize)}...`, 'info');

                            const response = await fetch('/api/detect-addresses', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ addresses: batch })
                            });

                            if (!response.ok) {
                                throw new Error(`Server err: ${response.statusText}`);
                            }

                            const result = await response.json();
                            const resultData = result.data || [];

                            resultData.forEach((res, idx) => {
                                const originalRowIdx = batchIndices[idx];
                                // Write back to jsonData
                                jsonData[originalRowIdx][newKecIdx] = res.kode_kecamatan || '';
                                jsonData[originalRowIdx][newDesaIdx] = res.kode_desa || '';
                                jsonData[originalRowIdx][newSlsIdx] = res.kode_sls || '';

                                if (res.kode_sls) machedCount++;
                            });
                        }

                        log(`Pencocokan selesai! ${machedCount} dari ${addressesToProcess.length} alamat berhasil dipetakan secara penuh ke SLS.`, 'success');

                        // Create new worksheet from updated jsonData
                        const newWorksheet = XLSX.utils.aoa_to_sheet(jsonData);
                        originalWorkbook.Sheets[targetSheetName] = newWorksheet;
                        modifiedWorkbook = originalWorkbook;

                        loader.classList.add('hidden');
                        downloadContainer.classList.remove('hidden');
                        log(`File siap diunduh.`, 'cmd');

                    } catch (err) {
                        console.error(err);
                        log(`Terjadi kesalahan: ${err.message}`, 'error');
                        btnProcess.disabled = false;
                        loader.classList.add('hidden');
                    }
                };

                reader.readAsArrayBuffer(file);
            });

            btnDownload.addEventListener('click', () => {
                if (!modifiedWorkbook) return;
                log('Men-download file...', 'cmd');
                const newName = currentFileName.replace(/\.xlsx$/, '_KODE.xlsx').replace(/\.xls$/, '_KODE.xls');
                XLSX.writeFile(modifiedWorkbook, newName);
                log('Selesai.', 'success');
            });
        });
    </script>
</body>
</html>
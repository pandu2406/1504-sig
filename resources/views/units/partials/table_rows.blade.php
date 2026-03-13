@php
    // Calculate Weekly Deadline: Monday 07:00 WIB
    $now = now()->timezone('Asia/Jakarta');
    // Start of week is Monday (by default or forced here)
    $deadline = $now->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->setTime(7, 0);

    // If current time is BEFORE Monday 07:00, deadline is LAST WEEK's Monday 07:00
    if ($now->lt($deadline)) {
        $deadline->subWeek();
    }
@endphp

@foreach($units as $unit)
    @php
        $isUpdatedToday = $unit->updated_at->isToday() && $unit->current_status == 'VERIFIED';

        // LOCK LOGIC: 
        // 1. Initial Data (No User) -> Always Locked
        // 2. Weekly Expired (Updated BEFORE Deadline) -> Locked
        $isLocked = ($unit->current_status == 'VERIFIED') && (
            empty($unit->last_updated_by) ||
            ($unit->updated_at && $unit->updated_at->lt($deadline))
        );

        // MANUAL LOGIC: Verified and has User (regardless of date, but NOT Locked)
        $isManual = ($unit->current_status == 'VERIFIED' && !empty($unit->last_updated_by) && !$isLocked);

        // --- WEEK 1 LOCK OVERRIDE (Jan 26 - Feb 1) ---
        $w1Start = \Carbon\Carbon::create(2026, 1, 26, 0, 0, 0, 'Asia/Jakarta');
        $w1End = \Carbon\Carbon::create(2026, 2, 1, 23, 59, 59, 'Asia/Jakarta');

        $isWeek1 = false;
        $week1Style = false;

        if ($unit->updated_at && $unit->current_status == 'VERIFIED') {
            $uTime = $unit->updated_at->timezone('Asia/Jakarta');
            if ($uTime->between($w1Start, $w1End)) {
                $isWeek1 = true;
                // Only override if it has a USER (Not Data Awal)
                if (!empty($unit->last_updated_by)) {
                    $isLocked = true; // Force lock for Week 1 updates
                    $week1Style = true;
                }
            }
        }

        // EXCEPTION: If Unit is "Tagging diluar kabupaten" (Latitude/Longitude exists but NO SLS/Lokasi matched),
        // ALLOW EDITing regardless of lock status/time/user.
        if (
            $unit->latitude && $unit->longitude &&
            !isset($unit->raw_data['sls_nmsls']) && !isset($unit->raw_data['[AUTO] Lokasi'])
        ) {
            $isLocked = false;
            // Also disable week1Style if we are unlocking it, so it looks editable (standard or manual)
            // But if it was Week 1, maybe we should keep it looking special? 
            // User just said "open the edit". 
            // If we unlock it, better to show standard colours for unlocked rows (White/Green-50).
            $week1Style = false;
        }

        // Final Row Class Determination
        if ($isLocked) {
            if ($week1Style) {
                $rowClass = 'bg-emerald-100 border-b border-emerald-200 text-emerald-900';
            } else {
                $rowClass = 'bg-blue-100 border-b';
            }
        } elseif ($isManual) {
            // If manual and unlocked (e.g. current week)
            $rowClass = 'bg-green-50 border-b';
        } else {
            $rowClass = 'bg-white border-b hover:bg-gray-50';
        }

        // Resolve Names
        $kCode = sprintf('%03d', (int) $unit->kdkec);
        $dCode = $kCode . sprintf('%03d', (int) $unit->kddesa);

        $kecName = ($unit->kdkec == 'UNKNOWN') ? 'Kecamatan Kosong' : ($kecNames[$kCode] ?? $unit->kdkec);
        $desaName = $desaNames[$dCode] ?? $unit->kddesa;
    @endphp
    <tr class="{{ $rowClass }}">
        <td class="p-2 font-mono text-xs font-bold">{{ $unit->idsbr }}</td>
        <td class="p-2">
            <div class="font-medium">{{ $kecName }}</div>
            <div class="text-[10px] text-gray-400">{{ $unit->kdkec }}</div>
        </td>
        <td class="p-2">
            <div>{{ $desaName }}</div>
            <div class="text-[10px] text-gray-400">{{ $unit->kddesa }}</div>
        </td>
        <td class="p-2 font-semibold">{{ $unit->nama_usaha }}</td>
        <td class="p-2 text-sm">{{ $unit->alamat }}</td>
        <td class="p-2">
            <input type="hidden" id="updated-at-{{ $unit->id }}"
                value="{{ $unit->updated_at ? $unit->updated_at->toIso8601String() : '' }}">
            <div class="flex flex-col gap-1">
                <div class="flex gap-1">
                    <input type="text" name="latitude" id="lat-{{ $unit->id }}" value="{{ $unit->latitude }}"
                        class="border p-1 w-24 rounded text-xs {{ $isLocked ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : '' }}"
                        placeholder="Lat" onpaste="handlePaste(event, {{ $unit->id }})" {{ $isLocked ? 'disabled' : '' }}>
                    <input type="text" name="longitude" id="long-{{ $unit->id }}" value="{{ $unit->longitude }}"
                        class="border p-1 w-24 rounded text-xs {{ $isLocked ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : '' }}"
                        placeholder="Long" onpaste="handlePaste(event, {{ $unit->id }})" {{ $isLocked ? 'disabled' : '' }}>
                </div>
                <div>
                    <select id="status-{{ $unit->id }}"
                        class="border p-1 w-full rounded text-xs bg-gray-50 {{ $isLocked ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : '' }}"
                        {{ $isLocked ? 'disabled' : '' }}>
                        <option value="">- Status -</option>
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
                            <option value="{{ $code }}" {{ $unit->status_keberadaan == $code ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div id="sls-info-{{ $unit->id }}">
                    @if(isset($unit->raw_data['sls_nmsls']) || isset($unit->raw_data['[AUTO] Lokasi']))
                        <div class="mt-1 text-[10px] bg-yellow-100 text-yellow-800 p-1 rounded border border-yellow-200">
                            {{-- Line 1: Lokasi / SLS --}}
                            <span class="font-bold">Lokasi:</span>
                            {{ $unit->raw_data['sls_nmsls'] ?? \Illuminate\Support\Str::after($unit->raw_data['[AUTO] Lokasi'] ?? 'Unknown', '- ') }}
                            <br>

                            {{-- Line 2: Desa --}}
                            <span class="font-bold">Desa:</span>
                            {{ $unit->raw_data['sls_nmdesa'] ?? ($unit->raw_data['nmdesa'] ?? $desaName) }} <br>

                            {{-- Line 3: Kecamatan --}}
                            <span class="font-bold">Kecamatan:</span>
                            {{ $unit->raw_data['sls_nmkec'] ?? $kecName }}
                        </div>
                    @elseif($unit->latitude && $unit->longitude)
                        <div class="mt-1 text-[10px] bg-red-100 text-red-800 p-1 rounded border border-red-200 font-bold">
                            Tagging diluar kabupaten
                        </div>
                    @endif
                </div>
                @php
                    $query = urlencode("$unit->nama_usaha $unit->alamat $desaName $kecName");
                @endphp
                <div class="flex flex-wrap gap-2 mt-1">
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $query }}" target="_blank"
                        class="text-[10px] text-blue-600 hover:underline flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Cari di Maps
                    </a>
                    <button type="button"
                        onclick="openMapVisualization({{ $unit->id }}, {{ $unit->latitude ?? 'null' }}, {{ $unit->longitude ?? 'null' }}, '{{ $unit->kdkec ?? '0' }}', '{{ $unit->kddesa ?? '0' }}')"
                        class="text-[10px] {{ ($unit->latitude && $unit->longitude) ? 'text-green-600' : 'text-blue-600' }} hover:underline flex items-center gap-1 bg-transparent border-none cursor-pointer">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        {{ ($unit->latitude && $unit->longitude) ? 'Lihat di Peta' : 'Cari Lokasi' }}
                    </button>
                </div>
            </div>
        </td>
        <td class="p-2 text-xs text-gray-500" id="last-update-{{ $unit->id }}">
            @if($unit->current_status == 'VERIFIED')
                <div>{{ $unit->updated_at ? $unit->updated_at->timezone('Asia/Jakarta')->format('d/m H:i') : '-' }}</div>
                @if($unit->last_updated_by)
                    <div class="text-[10px] font-bold text-gray-600">Terakhir: {{ $unit->last_updated_by }}</div>
                    @if(!empty($unit->first_updated_by) && $unit->first_updated_by !== $unit->last_updated_by)
                        <div class="text-[9px] text-gray-400 italic">Oleh pertama: {{ $unit->first_updated_by }}</div>
                    @endif
                    @if($isUpdatedToday)
                        <span class="bg-green-200 text-green-800 px-1 rounded text-[10px]">Barusan</span>
                    @endif
                @else
                    <!-- No User = System/Initial -->
                    <span class="bg-blue-200 text-blue-800 px-1 rounded text-[10px]">Data Awal</span>
                @endif
            @else
                -
            @endif
        </td>
        <td class="p-2 flex items-center">
            @if(!$isLocked)
                <button type="button" onclick="updateUnit(event, {{ $unit->id }})"
                    class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600 shadow">Save</button>
            @else
                @if(empty($unit->last_updated_by))
                    <button type="button" disabled
                        class="bg-blue-300 text-blue-800 px-3 py-1 rounded text-sm cursor-not-allowed font-semibold">Data
                        Awal</button>
                @elseif($isWeek1 ?? false)
                    <!-- Unified Week 1 Display -->
                    @php
                        $lockLabel = 'Locked Week 1';
                        if ($unit->updated_at) {
                            $weekDiff = $w1Start->diffInDays($unit->updated_at->timezone('Asia/Jakarta'), false);
                            if ($weekDiff >= 7) {
                                $weekNum = floor($weekDiff / 7) + 1;
                                $lockLabel = 'Locked Week ' . $weekNum;
                            }
                        }
                    @endphp
                    <div class="flex flex-col items-center">
                        <button type="button" disabled
                            class="bg-gray-300 text-gray-500 px-3 py-1 rounded text-sm cursor-not-allowed font-medium"
                            title="Data terkunci. Hubungi Admin.">{{ $lockLabel }}</button>
                        <span class="text-[10px] text-gray-400 mt-0.5">Chat Admin</span>
                    </div>
                @else
                    @php
                        $lockLabel = 'Locked';
                        if ($unit->updated_at) {
                            $weekDiff = $w1Start->diffInDays($unit->updated_at->timezone('Asia/Jakarta'), false);
                            if ($weekDiff >= 0) {
                                $weekNum = floor($weekDiff / 7) + 1;
                                $lockLabel = 'Locked Week ' . $weekNum;
                            }
                        }
                    @endphp
                    <div class="flex flex-col items-center">
                        <button type="button" disabled
                            class="bg-gray-300 text-gray-500 px-3 py-1 rounded text-sm cursor-not-allowed font-medium">{{ $lockLabel }}</button>
                        <span class="text-[10px] text-gray-400 mt-0.5">Chat Admin</span>
                    </div>
                @endif
            @endif

            @php
                // Merge current updates into raw_data for display in Detail
                $detailData = $unit->raw_data ?? [];
                if (!is_array($detailData)) {
                    $detailData = [];
                }

                // --- Sub-SLS Logic (Consistent with Export) ---
                $subC = '';
                if ($unit->latitude && $unit->longitude) { // Check if coordinates exist (Verified/Updated)
                    $rawSub = $detailData['kdsubsls'] ?? $detailData['subsls'] ?? null;
                    if ($rawSub !== null && $rawSub !== '') {
                        $subC = $rawSub;
                    } else {
                        $rawId = $detailData['sls_idsls'] ?? $detailData['idsls'] ?? '';
                        $cleanId = preg_replace('/[^0-9]/', '', $rawId);
                        if (strlen($cleanId) >= 16) {
                            $subC = substr($cleanId, -2);
                        } else {
                            $subC = '00';
                        }
                    }
                }
                // Inject into Detail Data (Order matters: After sls_idsls)
                $newDetail = [];
                $inserted = false;
                foreach ($detailData as $k => $v) {
                    $newDetail[$k] = $v;
                    if ($k === 'sls_idsls' || $k === 'idsls') {
                        $newDetail['Kode Sub-SLS'] = $subC;
                        $inserted = true;
                    }
                }
                if (!$inserted) {
                    // Fallback: prepend if ID not found, or append?
                    // Prepend ensures visibility if ID is missing.
                    $newDetail = array_merge(['Kode Sub-SLS' => $subC], $newDetail);
                }
                $detailData = $newDetail;

                $detailData['--- UPDATE TERKINI ---'] = '';
                $detailData['Latitude (Baru)'] = $unit->latitude;
                $detailData['Longitude (Baru)'] = $unit->longitude;
                $detailData['Status (Baru)'] = $statuses[$unit->status_keberadaan] ?? $unit->status_keberadaan;
                $detailData['Pengisi Pertama'] = $unit->first_updated_by;
                $detailData['Poin Milik (Terakhir)'] = $unit->last_updated_by;
                $detailData['Waktu Update'] = $unit->updated_at ? $unit->updated_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') : '-';
            @endphp
            <textarea id="detail-data-{{ $unit->id }}" style="display:none;">{{ json_encode($detailData) }}</textarea>

            <button type="button" onclick="openDetail({{ $unit->id }})"
                class="bg-gray-500 text-white px-2 py-1 rounded text-sm hover:bg-gray-600 ml-2">Detail</button>

            <button type="button" onclick="cancelUnit(event, {{ $unit->id }})"
                class="bg-orange-500 text-white px-2 py-1 rounded text-sm hover:bg-orange-600 ml-1">Batal</button>

            <button type="button" onclick="deleteUnit({{ $unit->id }})"
                class="bg-red-600 text-white px-2 py-1 rounded text-sm hover:bg-red-700 ml-1">Hapus</button>

            <span id="msg-{{ $unit->id }}" class="text-xs ml-1"></span>
        </td>
    </tr>
@endforeach
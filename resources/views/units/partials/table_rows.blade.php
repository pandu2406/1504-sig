@php
    // Calculate Weekly Deadline: Monday 08:00 WIB
    $now = now()->timezone('Asia/Jakarta');
    // Start of week is Monday (by default or forced here)
    $deadline = $now->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->setTime(8, 0);

    // If current time is BEFORE Monday 08:00, deadline is LAST WEEK's Monday 08:00
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
        // If it's manual but old (locked), it falls into Blue.
        // If it's manual and new (this week), it falls into Green.
        $isManual = ($unit->current_status == 'VERIFIED' && !empty($unit->last_updated_by) && !$isLocked);

        if ($isLocked) {
            $rowClass = 'bg-blue-100 border-b'; // Blue for Locked (Initial OR Expired)
        } elseif ($isManual) {
            $rowClass = 'bg-green-50 border-b'; // Green for Active/Recent
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
        <form onsubmit="updateUnit(event, {{ $unit->id }})">
            <td class="p-2">
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
                    @if(isset($unit->raw_data['sls_nmsls']))
                        <div class="mt-1 text-[10px] bg-yellow-100 text-yellow-800 p-1 rounded border border-yellow-200">
                            <span class="font-bold">Lokasi:</span> {{ $unit->raw_data['sls_nmsls'] }} <br>
                            <span class="font-bold">Desa:</span> {{ $unit->raw_data['sls_nmdesa'] ?? '-' }}
                        </div>
                    @endif
                    @php
                        $query = urlencode("$unit->nama_usaha $unit->alamat $desaName $kecName");
                    @endphp
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $query }}" target="_blank"
                        class="text-[10px] text-blue-600 hover:underline flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Cari di Maps
                    </a>
                </div>
            </td>
            <td class="p-2 text-xs text-gray-500" id="last-update-{{ $unit->id }}">
                @if($unit->current_status == 'VERIFIED')
                    <div>{{ $unit->updated_at ? $unit->updated_at->timezone('Asia/Jakarta')->format('d/m H:i') : '-' }}</div>
                    @if($unit->last_updated_by)
                        <div class="text-[10px] font-bold text-gray-600">Oleh: {{ $unit->last_updated_by }}</div>
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
                    <button type="submit"
                        class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">Save</button>
                @else
                    <button type="button" disabled
                        class="bg-gray-300 text-gray-500 px-3 py-1 rounded text-sm cursor-not-allowed">Locked</button>
                @endif

                @php
                    // Merge current updates into raw_data for display in Detail
                    $detailData = $unit->raw_data ?? [];
                    if (!is_array($detailData)) {
                        $detailData = [];
                    }

                    $detailData['--- UPDATE TERKINI ---'] = '';
                    $detailData['Latitude (Baru)'] = $unit->latitude;
                    $detailData['Longitude (Baru)'] = $unit->longitude;
                    $detailData['Status (Baru)'] = $statuses[$unit->status_keberadaan] ?? $unit->status_keberadaan;
                    $detailData['Petugas'] = $unit->last_updated_by;
                    $detailData['Waktu Update'] = $unit->updated_at ? $unit->updated_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') : '-';
                @endphp
                <button type="button" id="btn-detail-{{ $unit->id }}" onclick="showDetail({{ json_encode($detailData) }})"
                    class="bg-gray-500 text-white px-2 py-1 rounded text-sm hover:bg-gray-600 ml-2">Detail</button>
                <span id="msg-{{ $unit->id }}" class="text-xs ml-1"></span>
            </td>
        </form>
    </tr>
@endforeach
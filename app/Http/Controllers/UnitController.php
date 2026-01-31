<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $query = \App\Models\Unit::query();

        if ($request->has('search') && $request->search) {
            $query->where('nama_usaha', 'like', '%' . $request->search . '%');
        }

        if ($request->has('status') && $request->status) {
            $val = $request->status;
            if ($val === 'NO_COORD') {
                $query->where(function ($q) {
                    $q->whereNull('latitude')
                        ->orWhere('latitude', '')
                        ->orWhereNull('longitude')
                        ->orWhere('longitude', '');
                });
            } elseif ($val === 'HAS_COORD') {
                $query->where(function ($q) {
                    $q->whereNotNull('latitude')
                        ->where('latitude', '!=', '')
                        ->whereNotNull('longitude')
                        ->where('longitude', '!=', '');
                });
            } else {
                $query->where('status_awal', $val);
            }
        }

        if ($request->has('status_keberadaan') && $request->status_keberadaan) {
            $query->where('status_keberadaan', $request->status_keberadaan);
        }

        // Filter Kecamatan
        if ($request->has('kdkec') && $request->kdkec) {
            $val = $request->kdkec;
            if ($val === 'UNKNOWN') {
                $query->where('kdkec', 'UNKNOWN');
            } else {
                $valFloat = (float) $val;
                $query->where(function ($q) use ($valFloat) {
                    $q->where('kdkec', $valFloat)
                        ->orWhere('kdkec', $valFloat . '.0');
                });
            }
        }

        // Filter Desa
        if ($request->has('kddesa') && $request->kddesa) {
            // Input is "010009" (full code) or just "009"?
            // User requirement: "mencocokkan kode 26 pada kolom T kddesa"
            // Code Keldes CSV has "010026".
            // So we need to Extract the last 3 digits -> "026" -> 26.0

            // If request->kddesa comes from Region model code "010026"
            $fullCode = $request->kddesa;
            $villageSuffix = substr($fullCode, 3, 3); // "026"
            $val = (float) $villageSuffix; // 26

            $query->where(function ($q) use ($val) {
                $q->where('kddesa', $val)
                    ->orWhere('kddesa', $val . '.0');
            });
        }

        // Sorting
        if ($request->has('sort') && $request->sort == 'updated') {
            $query->orderBy('updated_at', 'desc');
        } else {
            // Default sort: Unverified first, then ID
            // $query->orderByRaw("FIELD(current_status, 'PENDING', 'VERIFIED')")->orderBy('id');
        }

        $units = $query->paginate(20)->appends($request->all());

        // Lookup Maps
        // Optimize: verify logic of keys
        $kecNames = \App\Models\Region::where('level', 'KEC')->pluck('name', 'code')->toArray();
        $desaNames = \App\Models\Region::where('level', 'DESA')->pluck('name', 'code')->toArray();

        // Pass full object for dropdown (still needed)
        $kecamatans = \App\Models\Region::where('level', 'KEC')->orderBy('code')->get();

        if ($request->ajax()) {
            return view('units.partials.table_rows', compact('units', 'kecNames', 'desaNames'))->render();
        }

        return view('units.index', compact('units', 'kecamatans', 'kecNames', 'desaNames'));
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Unit $unit)
    {
        try {
            // Update logic
            $oldLat = $unit->latitude;
            $oldLong = $unit->longitude;
            $oldStatus = $unit->status_keberadaan;
            $username = $request->username ?? 'Guest'; // Capture username

            if ($request->has('latitude'))
                $unit->latitude = $request->latitude;
            if ($request->has('longitude'))
                $unit->longitude = $request->longitude;

            if ($request->has('status_keberadaan')) {
                $unit->status_keberadaan = $request->status_keberadaan;
            }

            $unit->current_status = 'VERIFIED';
            $unit->last_updated_by = $username;
            $unit->save();

            // Log
            \App\Models\GroundcheckLog::create([
                'unit_id' => $unit->id,
                'user_id' => $username, // Save username
                'action' => 'UPDATE_DATA',
                'old_values' => ['lat' => $oldLat, 'long' => $oldLong, 'status' => $oldStatus],
                'new_values' => [
                    'lat' => $unit->latitude,
                    'long' => $unit->longitude,
                    'status' => $unit->status_keberadaan
                ]
            ]);

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

            // Re-construct the detail array for frontend update
            $detailData = $unit->raw_data ?? [];
            if (!is_array($detailData)) {
                $detailData = [];
            }
            $detailData['--- UPDATE TERKINI ---'] = '';
            $detailData['Latitude (Baru)'] = $unit->latitude;
            $detailData['Longitude (Baru)'] = $unit->longitude;
            $detailData['Status (Baru)'] = $statuses[$unit->status_keberadaan] ?? $unit->status_keberadaan;
            $detailData['Petugas'] = $unit->last_updated_by;
            $detailData['Waktu Update'] = $unit->updated_at->timezone('Asia/Jakarta')->format('d/m/Y H:i');

            return response()->json([
                'success' => true,
                'last_update' => $unit->updated_at->timezone('Asia/Jakarta')->format('d/m H:i'),
                'user' => $username,
                'detail_json' => $detailData
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function export()
    {
        try {
            $filename = 'Gabungan_Export_' . date('Ymd_His') . '.csv';
            // Use storage_path('app/public') which is standard
            $path = storage_path('app/public/' . $filename);

            \Illuminate\Support\Facades\Artisan::call('app:export-excel', [
                '--output' => $path
            ]);

            if (file_exists($path)) {
                return response()->download($path)->deleteFileAfterSend(true);
            } else {
                return redirect()->back()->with('error', 'Export failed: File not created.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    public function getVillages($kecCode)
    {
        // Return villages where parent_code matches
        $villages = \App\Models\Region::where('level', 'DESA')
            ->where('parent_code', $kecCode)
            ->orderBy('code')
            ->get(['code', 'name']);

        return response()->json($villages);
    }

    public function getUserContributions($username)
    {
        $logs = \App\Models\GroundcheckLog::with('unit')
            ->where('user_id', $username)
            ->orderBy('created_at', 'desc')
            ->take(100) // Limit to last 100 for performance
            ->get();

        return response()->json($logs);
    }

    public function getDailyLogs($date)
    {
        $logs = \App\Models\GroundcheckLog::with('unit')
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($logs);
    }

    public function rekap()
    {
        // Group by kdkec only (not kddesa) for kecamatan-level view
        $results = \App\Models\Unit::select(
            'kdkec',
            \Illuminate\Support\Facades\DB::raw('count(*) as total'),
            \Illuminate\Support\Facades\DB::raw('sum(case when latitude is not null and longitude is not null then 1 else 0 end) as filled')
        )
            ->groupBy('kdkec')
            ->orderBy('kdkec')
            ->get();

        // Get Region Names
        $kecNames = \App\Models\Region::where('level', 'KEC')->pluck('name', 'code')->toArray();

        $rows = [];
        $grandTotal = 0;
        $grandFilled = 0;

        foreach ($results as $row) {
            // Clean Codes (Remove .0)
            $kdkecRaw = str_replace('.0', '', $row->kdkec);

            // Generate Lookup Keys (Standardize to 3 digits for lookup)
            $kCode = sprintf('%03d', (int) $kdkecRaw);

            // Resolve Names
            if ($kdkecRaw === 'UNKNOWN' || $kdkecRaw === '') {
                $kecName = 'TIDAK DIKETAHUI';
            } else {
                $kecName = $kecNames[$kCode] ?? 'UNKNOWN';
            }

            $rows[] = [
                'kdkec' => $kdkecRaw,
                'kec_name' => $kecName,
                'total' => $row->total,
                'filled' => $row->filled,
                'empty' => $row->total - $row->filled,
                'percentage' => $row->total > 0 ? ($row->filled / $row->total) * 100 : 0
            ];
            $grandTotal += $row->total;
            $grandFilled += $row->filled;
        }

        // Status Keberadaan Breakdown
        $statusLabels = [
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

        $statusStats = \App\Models\Unit::select(
            'status_keberadaan',
            \Illuminate\Support\Facades\DB::raw('count(*) as count')
        )
            ->whereNotNull('latitude')->where('latitude', '!=', '')
            ->whereNotNull('longitude')->where('longitude', '!=', '')
            ->whereNotNull('status_keberadaan')
            ->groupBy('status_keberadaan')
            ->get()
            ->keyBy('status_keberadaan');

        $statusBreakdown = [];
        foreach ($statusLabels as $id => $label) {
            $count = $statusStats->get($id)->count ?? 0;
            $statusBreakdown[] = [
                'status_id' => $id,
                'status_name' => $label,
                'count' => $count,
                'percentage' => $grandTotal > 0 ? ($count / $grandTotal) * 100 : 0
            ];
        }

        // Fetch History Logs (Latest 50)
        $logs = \App\Models\GroundcheckLog::with('unit')
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        // Count per User (Only count ACTUAL COORDINATE ADDITIONS)
        // 1. Get IDs of units that originally had coordinates (we ignore these)
        $idsWithOriginalCoords = \App\Models\Unit::where('status_awal', 'HAS_COORD')->pluck('id');

        $userStats = \App\Models\GroundcheckLog::select('user_id', \Illuminate\Support\Facades\DB::raw('count(distinct unit_id) as total'))
            ->whereNotIn('unit_id', $idsWithOriginalCoords)
            ->where(function ($query) {
                // Use COALESCE to handle nulls. JSON_UNQUOTE removed for SQLite compatibility
                // Strictly check only LATITUDE or LONGITUDE changes
                $query->whereRaw("COALESCE(JSON_EXTRACT(old_values, '$.lat'), '') != COALESCE(JSON_EXTRACT(new_values, '$.lat'), '')")
                    ->orWhereRaw("COALESCE(JSON_EXTRACT(old_values, '$.long'), '') != COALESCE(JSON_EXTRACT(new_values, '$.long'), '')");
            })
            ->groupBy('user_id')
            ->orderBy('total', 'desc')
            ->get();

        // Daily Activity Stats (Showing Cumulative Progress from Status Awal)
        // 1. Get Base Count (Data Awal)
        $baseTotalVerified = \App\Models\Unit::where('status_awal', 'HAS_COORD')->count();

        // 2. Get Daily Updates (Only for units that were NOT originally verified, but now have coordinates)
        $dailyNetNew = \App\Models\Unit::select(
            \Illuminate\Support\Facades\DB::raw('DATE(updated_at) as date'),
            \Illuminate\Support\Facades\DB::raw('count(*) as count')
        )
            ->where('status_awal', '!=', 'HAS_COORD')
            ->whereNotNull('latitude')->where('latitude', '!=', '') // Strict Check
            ->whereNotNull('longitude')->where('longitude', '!=', '') // Strict Check
            ->groupBy('date')
            ->orderBy('date', 'asc') // Ascending for cumulative calculation
            ->get();

        // 3. Construct Cumulative Data
        $dailyStats = [];
        $runningTotal = $baseTotalVerified;

        // Add Base Row
        $dailyStats[] = (object) [
            'date' => 'DATA AWAL',
            'total' => $baseTotalVerified, // Represents 'New' for this row
            'cumulative' => $baseTotalVerified
        ];

        foreach ($dailyNetNew as $d) {
            $runningTotal += $d->count;
            $dailyStats[] = (object) [
                'date' => $d->date,
                'total' => $d->count,
                'cumulative' => $runningTotal
            ];
        }

        // If we want to show standard daily activity descending, let's keep it separate or reuse existing variable
        // The view expects $dailyStats to be desc? The Right Sidebar uses $dailyStats.
        // Let's create a NEW variable for the bottom table: $dailyCumulativeStats
        $dailyCumulativeStats = $dailyStats; // Array of objects

        // Re-fetch standard dailyStats for sidebar (Descending, all activity)
        $dailyStats = \App\Models\GroundcheckLog::select(
            \Illuminate\Support\Facades\DB::raw('DATE(created_at) as date'),
            \Illuminate\Support\Facades\DB::raw('count(*) as total')
        )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->take(14)
            ->get();

        return view('units.rekap', compact('rows', 'grandTotal', 'grandFilled', 'logs', 'userStats', 'dailyStats', 'dailyCumulativeStats', 'statusBreakdown'));
    }

    public function getDesaByKecamatan($kdkec)
    {
        // Clean the kdkec parameter
        $kdkecClean = str_replace('.0', '', $kdkec);

        // Query units grouped by desa within this kecamatan
        $results = \App\Models\Unit::select(
            'kddesa',
            'status_keberadaan',
            \Illuminate\Support\Facades\DB::raw('count(*) as total'),
            \Illuminate\Support\Facades\DB::raw('sum(case when latitude is not null and longitude is not null then 1 else 0 end) as filled')
        )
            ->where(function ($q) use ($kdkecClean) {
                $q->where('kdkec', $kdkecClean)
                    ->orWhere('kdkec', $kdkecClean . '.0');
            })
            ->groupBy('kddesa', 'status_keberadaan')
            ->orderBy('kddesa')
            ->get();

        // Get Desa Names
        $kCode = sprintf('%03d', (int) $kdkecClean);
        $desaNames = \App\Models\Region::where('level', 'DESA')
            ->where('parent_code', $kCode)
            ->pluck('name', 'code')
            ->toArray();

        // Aggregate by desa
        $desaData = [];
        foreach ($results as $row) {
            $kddesaRaw = str_replace('.0', '', $row->kddesa);
            $dCode = $kCode . sprintf('%03d', (int) $kddesaRaw);

            if (!isset($desaData[$kddesaRaw])) {
                $desaData[$kddesaRaw] = [
                    'kddesa' => $kddesaRaw,
                    'desa_name' => $desaNames[$dCode] ?? 'TIDAK DIKETAHUI',
                    'total' => 0,
                    'filled' => 0,
                    'status_breakdown' => []
                ];
            }

            $desaData[$kddesaRaw]['total'] += $row->total;
            $desaData[$kddesaRaw]['filled'] += $row->filled;

            if ($row->status_keberadaan) {
                $desaData[$kddesaRaw]['status_breakdown'][$row->status_keberadaan] =
                    ($desaData[$kddesaRaw]['status_breakdown'][$row->status_keberadaan] ?? 0) + $row->total;
            }
        }

        // Calculate percentages and format
        $response = [];
        foreach ($desaData as $desa) {
            $desa['empty'] = $desa['total'] - $desa['filled'];
            $desa['percentage'] = $desa['total'] > 0 ? ($desa['filled'] / $desa['total']) * 100 : 0;
            $response[] = $desa;
        }

        return response()->json($response);
    }

    public function getSlsByDesa($kdkec, $kddesa)
    {
        // Handle messy data formats (e.g. "30.0" vs "30" vs "030")
        $kdkecInt = (int) $kdkec;
        $kddesaInt = (int) $kddesa;

        $units = \App\Models\Unit::where(function ($q) use ($kdkec, $kdkecInt) {
            $q->where('kdkec', $kdkec)
                ->orWhere('kdkec', $kdkecInt)
                ->orWhere('kdkec', $kdkecInt . '.0')
                ->orWhere('kdkec', str_pad($kdkecInt, 2, '0', STR_PAD_LEFT)) // '30'
                ->orWhere('kdkec', str_pad($kdkecInt, 3, '0', STR_PAD_LEFT)); // '030'
        })
            ->where(function ($q) use ($kddesa, $kddesaInt) {
                $q->where('kddesa', $kddesa)
                    ->orWhere('kddesa', $kddesaInt)
                    ->orWhere('kddesa', $kddesaInt . '.0')
                    ->orWhere('kddesa', str_pad($kddesaInt, 3, '0', STR_PAD_LEFT)); // '001'
            })
            ->get();

        $slsData = [];

        foreach ($units as $unit) {
            // raw_data casted to array
            $slsId = $unit->raw_data['sls_idsls'] ?? 'UNKNOWN';
            $slsName = $unit->raw_data['sls_nmsls'] ?? 'TIDAK DIKETAHUI';

            if ($slsId === 'UNKNOWN' && (!isset($unit->raw_data['sls_idsls']) || trim($unit->raw_data['sls_idsls']) === '')) {
                // Try to construct or use a placeholder if data is missing
                $slsId = 'NO_ID';
                $slsName = 'NON-SLS / LUAR SLS';
            }

            if (!isset($slsData[$slsId])) {
                $slsData[$slsId] = [
                    'sls_id' => $slsId,
                    'sls_name' => $slsName,
                    'total' => 0,
                    'filled' => 0,
                    'status_breakdown' => []
                ];
            }

            $slsData[$slsId]['total']++;
            if ($unit->latitude && $unit->longitude) {
                $slsData[$slsId]['filled']++;
            }

            if ($unit->status_keberadaan) {
                $slsData[$slsId]['status_breakdown'][$unit->status_keberadaan] =
                    ($slsData[$slsId]['status_breakdown'][$unit->status_keberadaan] ?? 0) + 1;
            }
        }

        // Format output
        $response = [];
        foreach ($slsData as $sls) {
            $sls['empty'] = $sls['total'] - $sls['filled'];
            $sls['percentage'] = $sls['total'] > 0 ? ($sls['filled'] / $sls['total']) * 100 : 0;
            $response[] = $sls;
        }

        // Sort by name
        usort($response, function ($a, $b) {
            return strcmp($a['sls_name'], $b['sls_name']);
        });

        return response()->json($response);
    }

    public function getRekapSummary()
    {
        // Get kecamatan-level summary
        $results = \App\Models\Unit::select(
            'kdkec',
            \Illuminate\Support\Facades\DB::raw('count(*) as total'),
            \Illuminate\Support\Facades\DB::raw('sum(case when latitude is not null and longitude is not null then 1 else 0 end) as filled')
        )
            ->groupBy('kdkec')
            ->orderBy('kdkec')
            ->get();

        $kecNames = \App\Models\Region::where('level', 'KEC')->pluck('name', 'code')->toArray();

        $rows = [];
        $grandTotal = 0;
        $grandFilled = 0;

        foreach ($results as $row) {
            $kdkecRaw = str_replace('.0', '', $row->kdkec);
            $kCode = sprintf('%03d', (int) $kdkecRaw);
            $kecName = ($kdkecRaw === 'UNKNOWN' || $kdkecRaw === '') ? 'TIDAK DIKETAHUI' : ($kecNames[$kCode] ?? 'UNKNOWN');

            $rows[] = [
                'kdkec' => $kdkecRaw,
                'kec_name' => $kecName,
                'total' => $row->total,
                'filled' => $row->filled,
                'empty' => $row->total - $row->filled,
                'percentage' => $row->total > 0 ? ($row->filled / $row->total) * 100 : 0
            ];
            $grandTotal += $row->total;
            $grandFilled += $row->filled;
        }

        // Get latest update timestamp
        $lastUpdate = \App\Models\Unit::max('updated_at');

        return response()->json([
            'rows' => $rows,
            'grandTotal' => $grandTotal,
            'grandFilled' => $grandFilled,
            'grandEmpty' => $grandTotal - $grandFilled,
            'grandPercentage' => $grandTotal > 0 ? ($grandFilled / $grandTotal) * 100 : 0,
            'lastUpdate' => $lastUpdate,
            'timestamp' => now()->toIso8601String()
        ]);
    }

    public function analysis()
    {
        $path = storage_path('app/public/sipw_analysis.json');
        if (!file_exists($path)) {
            return redirect()->back()->with('error', 'Analysis file not found. Please run the python script first.');
        }

        $json = json_decode(file_get_contents($path), true);

        // Backwards compatibility if JSON structure is old (list vs dict)
        if (isset($json['by_sls'])) {
            $dataSLS = $json['by_sls'];
            $dataKec = $json['by_kecamatan'];
            $stats = $json['stats'] ?? null;
            $allocation = $json['allocation'] ?? null;
        } else {
            // Fallback for old structure
            $dataSLS = $json;
            $dataKec = [];
            $stats = null;
            $allocation = null;
        }

        // Take top 20 for SLS Chart
        $top20SLS = array_slice($dataSLS, 0, 20);

        // Take all for Kecamatan Chart (usually < 20 kecamatans)
        $topKec = $dataKec;

        return view('units.analysis', compact('dataSLS', 'dataKec', 'top20SLS', 'topKec', 'stats', 'allocation'));
    }
    public function getDailyContributors($date)
    {
        // Get units that are "Net New" (status_awal != HAS_COORD) 
        // AND have coordinates 
        // AND were updated on this date
        // Group by user
        if ($date == 'DATA AWAL') {
            return response()->json([
                'msg' => 'Data Awal diambil dari database sumber (non-kontribusi user)'
            ]);
        }

        $contributors = \App\Models\Unit::select(
            'last_updated_by as user',
            \Illuminate\Support\Facades\DB::raw('count(*) as count')
        )
            ->where('status_awal', '!=', 'HAS_COORD')
            ->whereNotNull('latitude')->where('latitude', '!=', '')
            ->whereNotNull('longitude')->where('longitude', '!=', '')
            ->whereDate('updated_at', $date)
            ->whereNotNull('last_updated_by')
            ->groupBy('last_updated_by')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json($contributors);
    }

    public function getMapStats()
    {
        // Fetch all units with valid coordinates
        // Optimize: verify fields are indexed or minimal
        $units = \App\Models\Unit::select('id', 'nama_usaha', 'latitude', 'longitude', 'kdkec', 'kddesa', 'status_keberadaan', 'raw_data')
            ->whereNotNull('latitude')->where('latitude', '!=', '')
            ->whereNotNull('longitude')->where('longitude', '!=', '')
            ->cursor(); // Use cursor to conserve memory during processing

        $data = [];
        foreach ($units as $unit) {
            $slsId = $unit->raw_data['sls_idsls'] ?? null;

            // Clean kdkec/kddesa
            $kdkec = str_replace('.0', '', $unit->kdkec);
            $kddesa = str_replace('.0', '', $unit->kddesa);

            $data[] = [
                'id' => $unit->id,
                'name' => $unit->nama_usaha,
                'lat' => (float) $unit->latitude,
                'lng' => (float) $unit->longitude,
                'kdkec' => $kdkec,
                'kddesa' => $kddesa, // Usually just '001', need full code?
                // Construct full Desa Code: KKK + DDD
                'full_desa_code' => sprintf('%03d%03d', (int) $kdkec, (int) $kddesa),
                'status' => $unit->status_keberadaan,
                'sls_id' => $slsId
            ];
        }

        return response()->json($data);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\GroundcheckLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkUpdateController extends Controller
{
    /**
     * Display the admin interface for Bulk Update
     */
    public function index(Request $request)
    {
        // Simple Auth Check
        if (!$request->session()->has('admin_auth')) {
            return redirect()->route('units.bulk-update.login_page');
        }

        $filterKec = $request->get('filter_kec');
        $filterDesa = $request->get('filter_desa');
        $excludeKec = $request->get('exclude_kec', []);
        $excludeDesa = $request->get('exclude_desa', []);

        if (!is_array($excludeKec)) {
            $excludeKec = [$excludeKec];
        }
        if (!is_array($excludeDesa)) {
            $excludeDesa = [$excludeDesa];
        }

        $query = Unit::where(function ($q) {
            $q->where('current_status', '!=', 'VERIFIED')
                ->orWhereNull('current_status');
        });

        if ($filterKec && $filterKec !== '') {
            if ($filterKec === 'UNKNOWN') {
                $query->where(function ($q) {
                    $q->whereNull('kdkec')
                        ->orWhere('kdkec', '')
                        ->orWhere('kdkec', 'UNKNOWN');
                });
            } else {
                $valFloat = (float) $filterKec;
                $query->where(function ($q) use ($valFloat) {
                    $q->where('kdkec', $valFloat)
                        ->orWhere('kdkec', $valFloat . '.0');
                });
            }
        }
        if ($filterDesa && $filterDesa !== '') {
            if ($filterDesa === 'UNKNOWN') {
                $query->where(function ($q) {
                    $q->whereNull('kddesa')
                        ->orWhere('kddesa', '')
                        ->orWhere('kddesa', 'UNKNOWN');
                });
            } else {
                $villageSuffix = substr($filterDesa, 3, 3);
                $val = (float) $villageSuffix;
                $query->where(function ($q) use ($val) {
                    $q->where('kddesa', $val)
                        ->orWhere('kddesa', $val . '.0');
                });
            }
        }

        $unverifiedUnits = $query->get();

        $stats = [
            'kec_desa' => 0,
            'kec_only' => 0,
            'none' => 0,
            'total' => $unverifiedUnits->count()
        ];

        foreach ($unverifiedUnits as $u) {
            $hasKec = ($u->kdkec !== null && $u->kdkec !== '' && strtoupper($u->kdkec) !== 'UNKNOWN');
            $hasDesa = ($u->kddesa !== null && $u->kddesa !== '' && strtoupper($u->kddesa) !== 'UNKNOWN');

            if ($hasKec && $hasDesa) {
                $stats['kec_desa']++;
            } elseif ($hasKec && !$hasDesa) {
                $stats['kec_only']++;
            } else {
                $stats['none']++;
            }
        }

        // Fetch rollback history (batches)
        $history = GroundcheckLog::select('user_id as batch_id', DB::raw('count(*) as total_units'), DB::raw('max(created_at) as created_at'))
            ->where('action', 'BULK_UPDATE')
            ->groupBy('user_id')
            ->orderBy('created_at', 'desc')
            ->get();
        // Get distinct Kec and Desa for filters from Region
        $kecamatans = \App\Models\Region::where('level', 'KEC')->orderBy('code')->get();

        $desas = collect();
        if ($filterKec && $filterKec !== '' && $filterKec !== 'UNKNOWN') {
            $desas = \App\Models\Region::where('level', 'DESA')->where('parent_code', $filterKec)->orderBy('code')->get();
        }

        // All Desas for exclude filter
        $allDesas = \App\Models\Region::where('level', 'DESA')->orderBy('code')->get();

        return view('units.bulk_update', compact('stats', 'history', 'filterKec', 'filterDesa', 'excludeKec', 'excludeDesa', 'kecamatans', 'desas', 'allDesas'));
    }

    /**
     * Display the login page for Bulk Update
     */
    public function loginPage(Request $request)
    {
        if ($request->session()->has('admin_auth')) {
            return redirect()->route('units.bulk-update');
        }
        return view('units.bulk_update_login');
    }

    /**
     * Process login for Bulk Update
     */
    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required'
        ]);

        if ($request->password === 'admin123') {
            $request->session()->put('admin_auth', true);
            return redirect()->route('units.bulk-update');
        }

        return redirect()->back()->with('error', 'Password salah!');
    }

    /**
     * Execute the bulk update process
     */
    public function execute(Request $request)
    {
        // Simple Auth Check
        if (!$request->session()->has('admin_auth')) {
            return redirect()->route('units.bulk-update.login_page');
        }

        $filterKec = $request->input('filter_kec');
        $filterDesa = $request->input('filter_desa');
        $targetKec = $request->input('target_kec');
        $targetDesa = $request->input('target_desa');
        $targetSls = $request->input('target_sls');
        $excludeKec = $request->input('exclude_kec', []);
        $excludeDesa = $request->input('exclude_desa', []);

        if (!is_array($excludeKec)) {
            $excludeKec = explode(',', $excludeKec);
        }
        if (!is_array($excludeDesa)) {
            $excludeDesa = explode(',', $excludeDesa);
        }

        $batchId = 'Atmint_' . now()->format('Ymd_His');

        $query = Unit::where(function ($q) {
            $q->where('current_status', '!=', 'VERIFIED')
                ->orWhereNull('current_status');
        });

        if ($filterKec && $filterKec !== '') {
            if ($filterKec === 'UNKNOWN') {
                $query->where(function ($q) {
                    $q->whereNull('kdkec')
                        ->orWhere('kdkec', '')
                        ->orWhere('kdkec', 'UNKNOWN');
                });
            } else {
                $valFloat = (float) $filterKec;
                $query->where(function ($q) use ($valFloat) {
                    $q->where('kdkec', $valFloat)
                        ->orWhere('kdkec', $valFloat . '.0');
                });
            }
        }
        if ($filterDesa && $filterDesa !== '') {
            if ($filterDesa === 'UNKNOWN') {
                $query->where(function ($q) {
                    $q->whereNull('kddesa')
                        ->orWhere('kddesa', '')
                        ->orWhere('kddesa', 'UNKNOWN');
                });
            } else {
                $villageSuffix = substr($filterDesa, 3, 3);
                $val = (float) $villageSuffix;
                $query->where(function ($q) use ($val) {
                    $q->where('kddesa', $val)
                        ->orWhere('kddesa', $val . '.0');
                });
            }
        }

        $unverifiedUnits = $query->get();

        if ($unverifiedUnits->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data yang perlu diupdate.');
        }

        $updateMethod = $request->input('update_method', 'with_coord');

        // 1. Load GeoJSON to extract SLS centroids if needed
        $slsData = [];
        if ($updateMethod !== 'no_coord') {
            $slsData = $this->parseGeoJSON();
        }

        DB::beginTransaction();
        try {
            $updatedCount = 0;

            foreach ($unverifiedUnits as $unit) {
                /** @var \App\Models\Unit $unit */
                // Base location on either Target or Original Unit data
                $baseKec = ($targetKec && $targetKec !== '') ? $targetKec : $unit->kdkec;
                $baseDesa = ($targetDesa && $targetDesa !== '') ? $targetDesa : $unit->kddesa;

                $hasKec = ($baseKec !== null && $baseKec !== '' && strtoupper($baseKec) !== 'UNKNOWN');
                $hasDesa = ($baseDesa !== null && $baseDesa !== '' && strtoupper($baseDesa) !== 'UNKNOWN');

                $lat = null;
                $long = null;
                $assignedKec = $baseKec;
                $assignedDesa = $baseDesa;
                $assignedSls = null;

                if ($updateMethod === 'no_coord') {
                    // Do nothing, variables already null
                } elseif ($hasKec && $hasDesa) {
                    if ($targetSls && $targetSls !== '') {
                        $kecKey = $this->normalizeCode($baseKec);
                        $desaKey = $this->normalizeCode($baseDesa);
                        if (isset($slsData[$kecKey][$desaKey][$targetSls])) {
                            $coords = $this->getRandomCoordFromSls($slsData[$kecKey][$desaKey][$targetSls]);
                            if ($coords) {
                                $lat = $coords['lat'];
                                $long = $coords['long'];
                                $assignedSls = $coords;
                            }
                        } else {
                            $coords = $this->getRandomCoordFromDesa($slsData, $baseKec, $baseDesa);
                            if ($coords) {
                                $lat = $coords['lat'];
                                $long = $coords['long'];
                                $assignedSls = $coords;
                            }
                        }
                    } else {
                        $coords = $this->getRandomCoordFromDesa($slsData, $baseKec, $baseDesa);
                        if ($coords) {
                            $lat = $coords['lat'];
                            $long = $coords['long'];
                            $assignedSls = $coords;
                        }
                    }
                } elseif ($hasKec && !$hasDesa) {
                    // Pick random Desa in that Kec
                    $kecKey = $this->normalizeCode($baseKec);
                    if (isset($slsData[$kecKey]) && count($slsData[$kecKey]) > 0) {
                        $randomDesaKey = array_rand($slsData[$kecKey]);
                        $assignedDesa = $randomDesaKey;
                        $coords = $this->getRandomCoordFromDesa($slsData, $baseKec, $randomDesaKey);
                        if ($coords) {
                            $lat = $coords['lat'];
                            $long = $coords['long'];
                            $assignedSls = $coords;
                        }
                    }
                } // else (none) -> lat/long remain null

                // Keep original data for logging
                $oldLat = $unit->latitude;
                $oldLong = $unit->longitude;
                $oldStatus = $unit->status_keberadaan;
                $oldCurrentStatus = $unit->current_status;

                // Update
                $unit->latitude = $lat;
                $unit->longitude = $long;
                $unit->status_keberadaan = 1; // Default to 1. Aktif
                $unit->current_status = 'VERIFIED';
                $unit->last_updated_by = $batchId;

                // --- Inject SLS Information to raw_data to prevent "Tagging diluar kabupaten" Warning ---
                if ($assignedSls && $lat && $long) {
                    $detailData = $unit->raw_data ?? [];
                    if (!is_array($detailData)) {
                        $detailData = [];
                    }

                    $kdkecNorm = $this->normalizeCode($assignedKec);
                    $kddesaNorm = $this->normalizeCode($assignedDesa);

                    $detailData['sls_idsls'] = "1504{$kdkecNorm}{$kddesaNorm}" . ($assignedSls['kdsls'] ?? '0000') . "00";
                    $detailData['sls_nmsls'] = !empty($assignedSls['nmsls']) ? $assignedSls['nmsls'] : "SLS " . ($assignedSls['kdsls'] ?? '');
                    $detailData['kdsubsls'] = "00";
                    $detailData['subsls'] = "00";

                    // Remove old auto lokasi if any
                    if (isset($detailData['[AUTO] Lokasi'])) {
                        unset($detailData['[AUTO] Lokasi']);
                    }

                    $unit->raw_data = $detailData;
                }

                // If it's the first time
                if (empty($unit->first_updated_by)) {
                    $unit->first_updated_by = $batchId;
                }

                $unit->save();

                // Log action
                GroundcheckLog::create([
                    'unit_id' => $unit->id,
                    'user_id' => $batchId,
                    'action' => 'BULK_UPDATE',
                    'old_values' => [
                        'lat' => $oldLat,
                        'long' => $oldLong,
                        'status' => $oldStatus,
                        'status_awal' => $oldCurrentStatus
                    ],
                    'new_values' => [
                        'lat' => $lat,
                        'long' => $long,
                        'status' => 1
                    ]
                ]);

                $updatedCount++;
            }

            DB::commit();

            return redirect()->back()->with('success', "Berhasil melakukan bulk update terhadap {$updatedCount} baris data dengan ID Batch: {$batchId}.");

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Bulk Update failed: " . $e->getMessage());
            return redirect()->back()->with('error', "Terjadi kesalahan saat memproses bulk update: " . $e->getMessage());
        }
    }

    /**
     * Preview the bulk update process for 3 units
     */
    public function preview(Request $request)
    {
        // Simple Auth Check
        if (!$request->session()->has('admin_auth')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized']);
        }

        $filterKec = $request->input('filter_kec');
        $filterDesa = $request->input('filter_desa');
        $targetKec = $request->input('target_kec');
        $targetDesa = $request->input('target_desa');
        $targetSls = $request->input('target_sls');
        $updateMethod = $request->input('update_method', 'with_coord');
        $excludeKec = $request->input('exclude_kec', []);
        $excludeDesa = $request->input('exclude_desa', []);

        if (!is_array($excludeKec)) {
            $excludeKec = explode(',', $excludeKec);
        }
        if (!is_array($excludeDesa)) {
            $excludeDesa = explode(',', $excludeDesa);
        }

        $query = Unit::where(function ($q) {
            $q->where('current_status', '!=', 'VERIFIED')
                ->orWhereNull('current_status');
        });

        if ($filterKec && $filterKec !== '') {
            if ($filterKec === 'UNKNOWN') {
                $query->where(function ($q) {
                    $q->whereNull('kdkec')
                        ->orWhere('kdkec', '')
                        ->orWhere('kdkec', 'UNKNOWN');
                });
            } else {
                $valFloat = (float) $filterKec;
                $query->where(function ($q) use ($valFloat) {
                    $q->where('kdkec', $valFloat)
                        ->orWhere('kdkec', $valFloat . '.0');
                });
            }
        }
        if ($filterDesa && $filterDesa !== '') {
            if ($filterDesa === 'UNKNOWN') {
                $query->where(function ($q) {
                    $q->whereNull('kddesa')
                        ->orWhere('kddesa', '')
                        ->orWhere('kddesa', 'UNKNOWN');
                });
            } else {
                $villageSuffix = substr($filterDesa, 3, 3);
                $val = (float) $villageSuffix;
                $query->where(function ($q) use ($val) {
                    $q->where('kddesa', $val)
                        ->orWhere('kddesa', $val . '.0');
                });
            }
        }

        // Apply Exclusions
        if (!empty($excludeKec) && $excludeKec[0] !== '') {
            $query->whereNotIn('kdkec', $excludeKec)
                ->whereNotIn('kdkec', array_map(function ($k) {
                    return $k . '.0';
                }, $excludeKec));
        }

        if (!empty($excludeDesa) && $excludeDesa[0] !== '') {
            $desiCodes = [];
            foreach ($excludeDesa as $ed) {
                if (strlen($ed) >= 3) {
                    $desiCodes[] = (float) substr($ed, 3, 3);
                    $desiCodes[] = (float) substr($ed, 3, 3) . '.0';
                }
            }
            if (!empty($desiCodes)) {
                $query->whereNotIn('kddesa', $desiCodes);
            }
        }

        $unverifiedUnits = $query->take(3)->get();

        if ($unverifiedUnits->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Tidak ada data untuk dipreview.']);
        }

        $slsData = [];
        if ($updateMethod !== 'no_coord') {
            $slsData = $this->parseGeoJSON();
        }
        $previewData = [];

        foreach ($unverifiedUnits as $unit) {
            $baseKec = ($targetKec && $targetKec !== '') ? $targetKec : $unit->kdkec;
            $baseDesa = ($targetDesa && $targetDesa !== '') ? $targetDesa : $unit->kddesa;

            $assignedLat = null;
            $assignedLong = null;
            $method = 'NO_DATA';

            $hasKec = ($baseKec !== null && $baseKec !== '' && strtoupper($baseKec) !== 'UNKNOWN');
            $hasDesa = ($baseDesa !== null && $baseDesa !== '' && strtoupper($baseDesa) !== 'UNKNOWN');

            if ($updateMethod === 'no_coord') {
                $method = 'KOSONGKAN_KOORDINAT';
            } elseif ($hasKec && $hasDesa) {
                if ($targetSls && $targetSls !== '') {
                    $kecNorm = $this->normalizeCode($baseKec);
                    $desaNorm = $this->normalizeCode($baseDesa);

                    if (isset($slsData[$kecNorm]) && isset($slsData[$kecNorm][$desaNorm]) && isset($slsData[$kecNorm][$desaNorm][$targetSls])) {
                        $targetSlsData = $slsData[$kecNorm][$desaNorm][$targetSls];
                        $coords = $this->getRandomCoordFromSls($targetSlsData);
                        if ($coords) {
                            $assignedLat = $coords['lat'];
                            $assignedLong = $coords['long'];
                            $method = 'SPECIFIC_SLS';
                        }
                    } else {
                        $coords = $this->getRandomCoordFromDesa($slsData, $kecNorm, $desaNorm);
                        if ($coords) {
                            $assignedLat = $coords['lat'];
                            $assignedLong = $coords['long'];
                            $method = 'RANDOM_DESA (Fallback SLS tidak ketemu)';
                        }
                    }
                } else {
                    $kecNorm = $this->normalizeCode($baseKec);
                    $desaNorm = $this->normalizeCode($baseDesa);
                    $coords = $this->getRandomCoordFromDesa($slsData, $kecNorm, $desaNorm);
                    if ($coords) {
                        $assignedLat = $coords['lat'];
                        $assignedLong = $coords['long'];
                        $method = 'RANDOM_DESA';
                    }
                }
            } elseif ($hasKec && !$hasDesa) {
                $kecNorm = $this->normalizeCode($baseKec);
                if (isset($slsData[$kecNorm]) && count($slsData[$kecNorm]) > 0) {
                    $randomDesaKey = array_rand($slsData[$kecNorm]);
                    $coords = $this->getRandomCoordFromDesa($slsData, $kecNorm, $randomDesaKey);

                    if ($coords) {
                        $assignedLat = $coords['lat'];
                        $assignedLong = $coords['long'];
                        $method = 'RANDOM_KECAMATAN';
                    }
                }
            }

            $previewData[] = [
                'idsbr' => $unit->idsbr,
                'nama_usaha' => $unit->nama_usaha,
                'kdkec' => $baseKec,
                'kddesa' => $baseDesa,
                'method' => $method,
                'latitude' => $assignedLat,
                'longitude' => $assignedLong
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $previewData
        ]);
    }

    /**
     * Rollback a specific batch
     */
    public function rollback(Request $request)
    {
        // Simple Auth Check
        if (!$request->session()->has('admin_auth')) {
            return redirect()->route('units.bulk-update.login_page');
        }

        $batchId = $request->input('batch_id');
        $partialKec = $request->input('partial_kec', []);
        $partialDesa = $request->input('partial_desa', []);

        if (!$batchId) {
            return redirect()->back()->with('error', 'Batch ID tidak valid.');
        }

        $logsQuery = GroundcheckLog::where('user_id', $batchId)
            ->where('action', 'BULK_UPDATE');

        // --- Partial Rollback Logic ---
        if (!empty($partialKec) || !empty($partialDesa)) {
            $logsQuery->whereHas('unit', function ($q) use ($partialKec, $partialDesa) {
                $q->where(function ($subQ) use ($partialKec, $partialDesa) {
                    if (!empty($partialKec)) {
                        $kecFloats = array_map(function ($k) {
                            return (float) $k; }, $partialKec);
                        $kecStrings = array_map(function ($k) {
                            return (float) $k . '.0'; }, $partialKec);
                        $allKec = array_merge($kecFloats, $kecStrings);
                        $subQ->orWhereIn('kdkec', $allKec);
                    }
                    if (!empty($partialDesa)) {
                        foreach ($partialDesa as $desaCode) {
                            $cleanCode = str_replace('.', '', $desaCode);
                            if (strlen($cleanCode) >= 6) {
                                $desaKec = (float) substr($cleanCode, 0, 3);
                                $desaFull = (float) substr($cleanCode, 3, 3);
                                $subQ->orWhere(function ($dq) use ($desaKec, $desaFull) {
                                    $dq->where(function ($dqKec) use ($desaKec) {
                                        $dqKec->where('kdkec', $desaKec)->orWhere('kdkec', $desaKec . '.0');
                                    })->where(function ($dqDesa) use ($desaFull) {
                                        $dqDesa->where('kddesa', $desaFull)->orWhere('kddesa', $desaFull . '.0');
                                    });
                                });
                            }
                        }
                    }
                });
            });
        }

        $logs = $logsQuery->get();

        if ($logs->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ditemukan riwayat update untuk Batch ID tersebut.');
        }

        DB::beginTransaction();
        try {
            $rolledBackCount = 0;

            foreach ($logs as $log) {
                $unit = Unit::find($log->unit_id);
                if ($unit) {
                    $oldValues = $log->old_values;

                    $unit->latitude = $oldValues['lat'] ?? null;
                    $unit->longitude = $oldValues['long'] ?? null;
                    $unit->status_keberadaan = $oldValues['status'] ?? null;
                    $unit->current_status = $oldValues['status_awal'] ?? 'PENDING';

                    // Remove batch flag if it matches
                    if ($unit->last_updated_by === $batchId) {
                        $unit->last_updated_by = null;
                    }
                    if ($unit->first_updated_by === $batchId) {
                        $unit->first_updated_by = null;
                    }

                    // Remove SLS data from raw_data to correctly revert
                    $detailData = $unit->raw_data ?? [];
                    if (is_array($detailData)) {
                        $keysToRemove = ['sls_idsls', 'sls_nmsls', 'kdsubsls', 'subsls', '[AUTO] Lokasi'];
                        foreach ($keysToRemove as $k) {
                            unset($detailData[$k]);
                        }
                        $unit->raw_data = $detailData;
                    }

                    $unit->save();

                    // Create rollback log
                    GroundcheckLog::create([
                        'unit_id' => $unit->id,
                        'user_id' => 'System',
                        'action' => 'ROLLBACK_BULK',
                        'old_values' => [
                            'lat' => $unit->latitude,
                            'long' => $unit->longitude,
                            'status' => $unit->status_keberadaan
                        ],
                        'new_values' => [
                            'status' => 'ROLLBACK from ' . $batchId
                        ]
                    ]);

                    $rolledBackCount++;
                }
            }

            // Optionally, delete the original bulk update logs to clean up, 
            // but keeping them with the rollback log is better for auditing.

            DB::commit();

            $msgTag = (empty($partialKec) && empty($partialDesa)) ? "seluruh" : "sebagian";
            return redirect()->back()->with('success', "Berhasil membatalkan (rollback $msgTag) {$rolledBackCount} baris data dari Batch ID: {$batchId}.");

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Rollback failed: " . $e->getMessage());
            return redirect()->back()->with('error', "Terjadi kesalahan saat melakukan rollback: " . $e->getMessage());
        }
    }

    /**
     * API to fetch SLS list for a specific Kec and Desa
     */
    public function getSlsList($kec, $desa)
    {
        $slsData = $this->parseGeoJSON();
        $kec = $this->normalizeCode($kec);
        $desa = $this->normalizeCode($desa);

        if (isset($slsData[$kec]) && isset($slsData[$kec][$desa])) {
            $slsList = [];
            foreach ($slsData[$kec][$desa] as $kdsls => $data) {
                if ($kdsls !== '') {
                    $slsList[] = [
                        'kdsls' => $kdsls,
                        'nmsls' => $data['nmsls'] ? $data['nmsls'] : $kdsls
                    ];
                }
            }
            return response()->json($slsList);
        }

        return response()->json([]);
    }

    /**
     * Helper to parse GeoJSON and build a list of centroids per Kec/Desa
     */
    private function parseGeoJSON()
    {
        $path = public_path('sls_1504.geojson');
        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        $slsMap = []; // format: [kec][desa][] = [lat, long]

        if (isset($json['features'])) {
            foreach ($json['features'] as $feature) {
                if (isset($feature['properties']) && isset($feature['geometry'])) {
                    $props = $feature['properties'];
                    $kdkec = $this->normalizeCode($props['kdkec'] ?? '');
                    $kddesa = $this->normalizeCode($props['kddesa'] ?? '');
                    $kdsls = strval($props['kdsls'] ?? '');
                    $nmsls = $props['nmsls'] ?? '';

                    if ($kdkec === '' || $kddesa === '')
                        continue;

                    // Calculate a simple centroid from the bounding box or first coordinate
                    $coords = $this->extractCentroid($feature['geometry']);
                    if ($coords) {
                        if (!isset($slsMap[$kdkec])) {
                            $slsMap[$kdkec] = [];
                        }
                        if (!isset($slsMap[$kdkec][$kddesa])) {
                            $slsMap[$kdkec][$kddesa] = [];
                        }

                        if ($kdsls !== '') {
                            $slsMap[$kdkec][$kddesa][$kdsls] = [
                                'lat' => $coords['lat'],
                                'long' => $coords['long'],
                                'kdsls' => $kdsls,
                                'nmsls' => $nmsls
                            ];
                        } else {
                            $slsMap[$kdkec][$kddesa][] = [
                                'lat' => $coords['lat'],
                                'long' => $coords['long'],
                                'kdsls' => '',
                                'nmsls' => $nmsls
                            ];
                        }
                    }
                }
            }
        }

        return $slsMap;
    }

    private function normalizeCode($code)
    {
        // Remove .0 suffix and pad to 3 digits if numeric
        $clean = str_replace('.0', '', $code);
        if (is_numeric($clean)) {
            return sprintf('%03d', (int) $clean);
        }
        return $clean;
    }

    private function extractCentroid($geometry)
    {
        $type = $geometry['type'];
        $coords = $geometry['coordinates'];

        if ($type === 'Polygon') {
            $points = $coords[0]; // exterior ring
        } elseif ($type === 'MultiPolygon') {
            $points = $coords[0][0]; // first polygon, exterior ring
        } else {
            return null;
        }

        // Simple bounding box calculation
        $minLng = 999;
        $maxLng = -999;
        $minLat = 999;
        $maxLat = -999;

        foreach ($points as $pt) {
            $lng = $pt[0];
            $lat = $pt[1];
            if ($lng < $minLng)
                $minLng = $lng;
            if ($lng > $maxLng)
                $maxLng = $lng;
            if ($lat < $minLat)
                $minLat = $lat;
            if ($lat > $maxLat)
                $maxLat = $lat;
        }

        $centerLng = ($minLng + $maxLng) / 2;
        $centerLat = ($minLat + $maxLat) / 2;

        return ['lat' => $centerLat, 'long' => $centerLng];
    }

    private function getRandomCoordFromDesa($slsData, $rawKec, $rawDesa)
    {
        $kec = $this->normalizeCode($rawKec);
        $desa = $this->normalizeCode($rawDesa);

        if (!isset($slsData[$kec]) || !isset($slsData[$kec][$desa]) || count($slsData[$kec][$desa]) === 0) {
            return null;
        }

        $slsList = $slsData[$kec][$desa];
        $randomSlsKey = array_rand($slsList);
        $randomSls = $slsList[$randomSlsKey];

        return $this->getRandomCoordFromSls($randomSls);
    }

    private function getRandomCoordFromSls($sls, $unitId = null)
    {
        // 1 degree lat is ~111km. 0.0001 is ~11 meters.
        // We want a jitter radius of about 50 meters (0.00045) to 150 meters (0.00135).

        // To simulate "near a road", we can make the jitter bias along an arbitrary axis
        // We'll create a fake "road angle" for this SLS based on its name or ID
        $angleBase = crc32($sls['nmsls'] ?? 'ROAD') % 360;
        $roadAngleRad = deg2rad($angleBase);

        // The distance along the road
        $distAlongRoad = (mt_rand(-1500, 1500) / 1000000); // +/- 160 meters along road

        // The distance perpendicular to the road (much smaller, simulating "near" the road)
        $distPerpRoad = (mt_rand(-200, 200) / 1000000);   // +/- 20 meters off the road

        // Rotate the distances by the road angle
        $jitterLat = ($distAlongRoad * cos($roadAngleRad)) - ($distPerpRoad * sin($roadAngleRad));
        $jitterLong = ($distAlongRoad * sin($roadAngleRad)) + ($distPerpRoad * cos($roadAngleRad));

        return [
            'lat' => $sls['lat'] + $jitterLat,
            'long' => $sls['long'] + $jitterLong,
            'kdsls' => $sls['kdsls'] ?? '',
            'nmsls' => $sls['nmsls'] ?? ''
        ];
    }
}


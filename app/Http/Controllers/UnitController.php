<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UnitController extends Controller
{
    private function normalizeBusinessName($name)
    {
        $name = strtoupper(trim($name));

        // Remove dots from abbreviations (e.g. "CV." -> "CV", "P.T." -> "PT")
        $name = str_replace('.', ' ', $name);
        $name = preg_replace('/\s+/', ' ', trim($name));

        // Replace & with AND
        $name = str_replace('&', ' AND ', $name);

        // Remove common legal entity prefixes (expanded)
        $prefixes = [
            'PT',
            'CV',
            'UD',
            'PD',
            'TB',
            'TK',
            'TOKO',
            'WARUNG',
            'KEDAI',
            'BENGKEL',
            'APOTEK',
            'KLINIK',
            'SALON',
            'FOTO',
            'DEPOT',
            'GRIYA',
            'RUMAH',
            'KANTOR',
            'PANGKALAN',
            'LAPANGAN',
            'KIOS',
            'AGEN',
            'COUNTER',
            'KONTER',
            'LAUNDRY',
            'PERCETAKAN',
            'RENTAL',
            'STUDIO',
            'CAFE',
            'KAFE',
            'RESTORAN',
            'RESTAURANT',
            'RUMAH MAKAN',
            'RM',
            'USAHA',
            'PERUSAHAAN',
            'YAYASAN',
            'KOPERASI',
            'SMA',
            'SMK',
            'SD',
            'SMP',
            'TK',
            'PAUD',
            'MI',
            'MTS',
            'MA',
            'MASJID',
            'MUSHOLLA',
            'GEREJA',
            'PUSKESMAS',
            'POSYANDU',
            'LPG',
            'GAS',
            'SPBU',
        ];
        // Sort by length descending to match longer ones first
        usort($prefixes, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($prefixes as $p) {
            if (str_starts_with($name, $p . ' ')) {
                $name = trim(substr($name, strlen($p)));
                break;
            }
        }

        // Remove suffix noise words (expanded)
        $suffixes = [
            'JAYA',
            'ABADI',
            'SENTOSA',
            'MAJU',
            'MUNDUR',
            'BERKAH',
            'SUKSES',
            'MAKMUR',
            'SEJAHTERA',
            'MANDIRI',
            'BARU',
            'LAMA',
            'INDAH',
            'MULIA',
            'UTAMA',
            'PRIMA',
            'MAS',
            'TBK',
            'PRATAMA',
            'PERKASA',
        ];
        // Sort by length descending
        usort($suffixes, function ($a, $b) {
            return strlen($b) - strlen($a);
        });
        foreach ($suffixes as $s) {
            if (str_ends_with($name, ' ' . $s)) {
                $name = trim(substr($name, 0, -strlen($s)));
                break;
            }
        }

        // Remove connector/noise words
        $connectors = [' DAN ', ' DI ', ' YANG ', ' ATAU ', ' THE ', ' AND '];
        foreach ($connectors as $c) {
            $name = str_replace($c, ' ', $name);
        }

        // Remove trailing numbers (e.g. "TOKO ABC 2" -> "TOKO ABC")
        $name = preg_replace('/\s+\d+$/', '', $name);

        // Remove symbols and leave only alphanumeric and space
        $name = preg_replace('/[^A-Z0-9\s]/', '', $name);

        // Collapse multiple spaces
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    private function findDuplicates($nama, $kdkec = null, $kddesa = null, $limit = 5)
    {
        if (empty($nama))
            return collect();

        $originalInput = strtoupper(trim($nama));
        $normalizedInput = $this->normalizeBusinessName($nama);
        if (empty($normalizedInput))
            return collect();

        $inputTokens = explode(' ', $normalizedInput);
        sort($inputTokens);

        // Step 1: Build broad search query — cast wider net
        $prefix = substr($normalizedInput, 0, 3); // Shorter prefix (3 chars) to catch more
        $allResults = collect();

        // 1a. Search within same region (kecamatan/desa) — primary check
        $query = \App\Models\Unit::query();
        if ($kdkec) {
            $valFloat = (float) $kdkec;
            $query->where(function ($q) use ($valFloat) {
                $q->where('kdkec', $valFloat)->orWhere('kdkec', $valFloat . '.0');
            });
        }
        if ($kddesa) {
            $villageSuffix = substr($kddesa, -3);
            $val = (float) $villageSuffix;
            $query->where(function ($q) use ($val) {
                $q->where('kddesa', $val)->orWhere('kddesa', $val . '.0');
            });
        }

        // Broad text filter: prefix + each token as LIKE
        $query->where(function ($q) use ($originalInput, $prefix, $inputTokens) {
            $q->where('nama_usaha', 'like', '%' . $originalInput . '%')
                ->orWhere('nama_usaha', 'like', $prefix . '%');
            // Also match each significant token (>2 chars)
            foreach ($inputTokens as $token) {
                if (strlen($token) > 2) {
                    $q->orWhere('nama_usaha', 'like', '%' . $token . '%');
                }
            }
        });

        $regionCandidates = $query->limit(200)->get();
        $allResults = $allResults->merge($regionCandidates);

        // 1b. Cross-region search (kabupaten-wide) — catch duplicates across villages
        // Only search if we have a meaningful name (> 3 chars)
        if (strlen($normalizedInput) > 3) {
            $crossQuery = \App\Models\Unit::query();

            // Exclude already-found IDs
            $existingIds = $allResults->pluck('id')->toArray();
            if (!empty($existingIds)) {
                $crossQuery->whereNotIn('id', $existingIds);
            }

            // Search by exact original name or close prefix across ALL regions
            $crossQuery->where(function ($q) use ($originalInput, $normalizedInput) {
                $q->where('nama_usaha', 'like', '%' . $originalInput . '%')
                    ->orWhere('nama_usaha', 'like', '%' . $normalizedInput . '%');
            });

            $crossCandidates = $crossQuery->limit(100)->get();
            $allResults = $allResults->merge($crossCandidates);
        }

        // Step 2: Score each candidate in PHP for precision
        $duplicates = $allResults->map(function ($unit) use ($normalizedInput, $inputTokens) {
            $normalizedCandidate = $this->normalizeBusinessName($unit->nama_usaha);
            if (empty($normalizedCandidate))
                return null;

            // a. EXACT match (100%)
            if ($normalizedInput === $normalizedCandidate) {
                $unit->similarity_score = 100;
                $unit->match_reason = "Nama Persis";
                return $unit;
            }

            // b. LEVENSHTEIN distance (typo detection)
            $s1 = substr($normalizedInput, 0, 254);
            $s2 = substr($normalizedCandidate, 0, 254);
            $lev = levenshtein($s1, $s2);
            $maxLen = max(strlen($s1), strlen($s2));
            $levScore = $maxLen > 0 ? (1 - ($lev / $maxLen)) * 100 : 0;

            // c. TOKEN matching (reordered words)
            $candidateTokens = explode(' ', $normalizedCandidate);
            sort($candidateTokens);
            $intersect = array_intersect($inputTokens, $candidateTokens);
            $maxTokenCount = max(count($inputTokens), count($candidateTokens));
            $tokenScore = $maxTokenCount > 0 ? (count($intersect) / $maxTokenCount) * 100 : 0;

            // d. CONTAINS check (one name is substring of the other)
            $containsScore = 0;
            if (str_contains($normalizedInput, $normalizedCandidate) || str_contains($normalizedCandidate, $normalizedInput)) {
                $shorter = min(strlen($normalizedInput), strlen($normalizedCandidate));
                $longer = max(strlen($normalizedInput), strlen($normalizedCandidate));
                $containsScore = ($shorter / $longer) * 100;
                // Boost: if one fully contains the other, minimum 80%
                $containsScore = max($containsScore, 80);
            }

            // e. Pick best score
            $finalScore = max($levScore, $tokenScore, $containsScore);

            // Threshold: 75% similarity (stricter than before)
            if ($finalScore >= 75) {
                $unit->similarity_score = round($finalScore, 1);
                if ($containsScore >= $levScore && $containsScore >= $tokenScore) {
                    $unit->match_reason = "Nama Mengandung";
                } elseif ($levScore > $tokenScore) {
                    $unit->match_reason = "Kemiripan Fonetik";
                } else {
                    $unit->match_reason = "Kemiripan Kata";
                }
                return $unit;
            }

            return null;
        })->filter()->unique('id')->sortByDesc('similarity_score');

        return $duplicates->take($limit);
    }

    private function applyFilters($query, $request)
    {
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_usaha', 'like', '%' . $request->search . '%')
                    ->orWhere('idsbr', 'like', '%' . $request->search . '%');
            });
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
            } elseif ($val === 'OUTSIDE_MAP') {
                $query->whereNotNull('latitude')->where('latitude', '!=', '')
                    ->where(function ($q) {
                        $q->whereNull('raw_data->sls_idsls')
                            ->orWhere('raw_data->sls_idsls', '')
                            ->orWhere('raw_data->sls_idsls', 'null');
                    });
            } else {
                $query->where('status_awal', $val);
            }
        }

        if ($request->has('status_keberadaan') && $request->status_keberadaan) {
            $query->where('status_keberadaan', $request->status_keberadaan);
        }

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

        if ($request->has('kddesa') && $request->kddesa) {
            $fullCode = $request->kddesa;
            $villageSuffix = substr($fullCode, 3, 3);
            $val = (float) $villageSuffix;

            $query->where(function ($q) use ($val) {
                $q->where('kddesa', $val)
                    ->orWhere('kddesa', $val . '.0');
            });
        }

        if ($request->has('petugas') && $request->petugas) {
            $query->where('last_updated_by', $request->petugas);
        }

        if ($request->has('updated_at') && $request->updated_at) {
            $query->whereDate('updated_at', $request->updated_at);
        }

        if ($request->has('tipe_usaha') && $request->tipe_usaha) {
            if ($request->tipe_usaha === 'PRELIST') {
                $query->where('idsbr', 'not like', 'tambahan-%');
            } elseif ($request->tipe_usaha === 'TAMBAHAN') {
                $query->where('idsbr', 'like', 'tambahan-%');
            }
        }

        if ($request->has('sort') && $request->sort == 'updated') {
            $query->orderBy('updated_at', 'desc');
        } else {
            // Default sort if needed, or leave to caller
        }

        return $query;
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $query = \App\Models\Unit::query();
        $this->applyFilters($query, $request);

        // Pagination
        $perPage = $request->input('per_page', 20);
        if (!in_array($perPage, [20, 50, 100, 200])) {
            $perPage = 20;
        }

        $units = $query->paginate($perPage)->appends($request->all());

        // Lookup Maps
        $kecNames = \App\Models\Region::where('level', 'KEC')->pluck('name', 'code')->toArray();
        $desaNames = \App\Models\Region::where('level', 'DESA')->pluck('name', 'code')->toArray();
        $kecamatans = \App\Models\Region::where('level', 'KEC')->orderBy('code')->get();

        $allPetugas = \App\Models\Unit::whereNotNull('last_updated_by')
            ->where('last_updated_by', '!=', '')
            ->distinct()
            ->orderBy('last_updated_by')
            ->pluck('last_updated_by');

        if ($request->ajax()) {
            return view('units.partials.table_rows', compact('units', 'kecNames', 'desaNames'))->render();
        }

        return view('units.index', compact('units', 'kecamatans', 'kecNames', 'desaNames', 'allPetugas'));
    }

    public function tambahWilayah(\Illuminate\Http\Request $request)
    {
        $kecNames = \App\Models\Region::where('level', 'KEC')->pluck('name', 'code')->toArray();
        $desaNames = \App\Models\Region::where('level', 'DESA')->pluck('name', 'code')->toArray();
        $kecamatans = \App\Models\Region::where('level', 'KEC')->orderBy('code')->get();

        return view('units.tambah_wilayah', compact('kecamatans', 'kecNames', 'desaNames'));
    }

    public function checkDuplicate(\Illuminate\Http\Request $request)
    {
        $nama = $request->get('nama_usaha');
        $kdkec = $request->get('kdkec');
        $kddesa = $request->get('kddesa');

        if (!$nama) {
            return response()->json(['exists' => false]);
        }

        $duplicates = $this->findDuplicates($nama, $kdkec, $kddesa);

        if ($duplicates->count() > 0) {
            $formattedData = $duplicates->map(function ($dup) {
                return [
                    'idsbr' => $dup->idsbr,
                    'nama_usaha' => $dup->nama_usaha,
                    'alamat' => $dup->alamat ?? ($dup->raw_data['alamat_usaha'] ?? ''),
                    'lokasi' => $dup->raw_data['sls_nmsls'] ?? null,
                    'similarity' => $dup->similarity_score ?? 100,
                    'reason' => $dup->match_reason ?? 'Persis'
                ];
            });
            return response()->json(['exists' => true, 'data' => $formattedData]);
        }
        return response()->json(['exists' => false]);
    }

    public function bulkPreview(\Illuminate\Http\Request $request)
    {
        try {
            $data = $request->json()->all();
            if (!is_array($data)) {
                return response()->json(['success' => false, 'message' => 'Format data tidak valid']);
            }

            $previewData = [];
            $seenNamesInBatch = []; // Track normalized names for inter-row duplicate detection

            foreach ($data as $index => $row) {
                $namaRaw = $row['Nama Usaha'] ?? $row['nama_usaha'] ?? '';
                $nama = trim($namaRaw);

                if (empty($nama)) {
                    continue; // skip
                }

                $kdkec = $row['Kecamatan'] ?? $row['kdkec'] ?? null;
                $kddesa = $row['Desa'] ?? $row['kddesa'] ?? null;

                // 1. Check against database
                $duplicates = $this->findDuplicates($nama, $kdkec, $kddesa, 1);
                $existsInDb = $duplicates->count() > 0;

                // 2. Check inter-row duplicate (within same batch)
                $normalizedName = $this->normalizeBusinessName($nama);
                $existsInBatch = in_array($normalizedName, $seenNamesInBatch);

                $row['is_duplicate'] = $existsInDb || $existsInBatch;
                if ($existsInDb) {
                    $dup = $duplicates->first();
                    $row['duplicate_info'] = $dup->nama_usaha . ' [' . ($dup->similarity_score ?? 100) . '% - DB]';
                } elseif ($existsInBatch) {
                    $row['duplicate_info'] = $nama . ' [Duplikat dalam batch]';
                }
                $row['_original_index'] = $index;

                // Track this name for subsequent rows
                if (!empty($normalizedName)) {
                    $seenNamesInBatch[] = $normalizedName;
                }

                // Auto coordinates logic
                $lat = trim($row['Latitude'] ?? $row['latitude'] ?? '');
                $lng = trim($row['Longitude'] ?? $row['longitude'] ?? '');
                $sls = trim($row['SLS'] ?? $row['sls_idsls'] ?? '');

                if ((empty($lat) || empty($lng)) && !empty($sls)) {
                    $coords = $this->getCoordsBySls($sls);
                    if ($coords) {
                        $row['Latitude'] = $coords['lat'];
                        $row['Longitude'] = $coords['lng'];
                        $row['_auto_coord'] = true; // flag to indicate it was auto-filled
                    }
                }

                $previewData[] = $row;
            }

            return response()->json([
                'success' => true,
                'data' => $previewData
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal preview bulk import: ' . $e->getMessage()]);
        }
    }

    public function bulkImport(\Illuminate\Http\Request $request)
    {
        try {
            $data = $request->json()->all();
            if (!is_array($data)) {
                return response()->json(['success' => false, 'message' => 'Format data tidak valid']);
            }

            $successCount = 0;
            $duplicateCount = 0;
            $interRowDuplicateCount = 0;
            $petugas = session('gc_username', 'Scraper');
            $batchTime = time(); // Use a single batch time for this upload session

            // Track names already inserted in this batch for inter-row duplicate detection
            $insertedNames = [];

            foreach ($data as $row) {
                // Check duplicate by name
                $namaRaw = $row['Nama Usaha'] ?? $row['nama_usaha'] ?? '';
                $nama = trim($namaRaw);

                if (empty($nama)) {
                    continue; // skip
                }

                // Inter-row check: skip if same normalized name already in this batch
                $normalizedForBatch = $this->normalizeBusinessName($nama);
                if (in_array($normalizedForBatch, $insertedNames)) {
                    $interRowDuplicateCount++;
                    continue;
                }

                $kdkec = $row['Kecamatan'] ?? $row['kdkec'] ?? null;
                $kddesa = $row['Desa'] ?? $row['kddesa'] ?? null;
                $duplicates = $this->findDuplicates($nama, $kdkec, $kddesa, 1);
                if ($duplicates->count() > 0) {
                    $duplicateCount++;
                    continue; // Skip
                }

                // Auto coordinates logic
                $lat = trim($row['Latitude'] ?? $row['latitude'] ?? '');
                $lng = trim($row['Longitude'] ?? $row['longitude'] ?? '');
                $sls = trim($row['SLS'] ?? $row['sls_idsls'] ?? '');

                if ((empty($lat) || empty($lng)) && !empty($sls)) {
                    $coords = $this->getCoordsBySls($sls);
                    if ($coords) {
                        $lat = $coords['lat'];
                        $lng = $coords['lng'];
                    }
                }

                // Insert
                $unit = new \App\Models\Unit();
                // Use hyphen to match filtering logic in applyFilters
                $unit->idsbr = 'tambahan-' . $batchTime . '-' . uniqid();
                $unit->nama_usaha = $nama;
                $unit->alamat = $row['Alamat Usaha'] ?? $row['alamat'] ?? '';
                $unit->latitude = $lat ?: null;
                $unit->longitude = $lng ?: null;

                // Standardize to 3-digit regional codes
                $unit->kdkec = (float) substr($row['Kecamatan'] ?? $row['kdkec'] ?? '000', -3);
                $unit->kddesa = (float) substr($row['Desa'] ?? $row['kddesa'] ?? '000', -3);

                $unit->status_awal = ($unit->latitude && $unit->longitude) ? 'HAS_COORD' : 'NO_COORD';
                $unit->current_status = 'VERIFIED';
                $unit->status_keberadaan = 1; // Default "Aktif"
                $unit->last_updated_by = $row['Petugas'] ?? $row['petugas'] ?? $petugas;
                $unit->first_updated_by = $unit->last_updated_by;

                $rawData = [
                    'source' => 'Bulk Excel',
                    'alamat_usaha' => $row['Alamat Usaha'] ?? $row['alamat'] ?? '',
                    'sls_idsls' => $row['SLS'] ?? $row['sls_idsls'] ?? '',
                    'telepon' => $row['Telepon'] ?? $row['telepon'] ?? '',
                    'email' => $row['Email'] ?? $row['email'] ?? '',
                    'kbli_kategori' => $row['KBLI'] ?? $row['kbli_kategori'] ?? '',
                    'skala_usaha' => $row['Skala Usaha'] ?? $row['skala_usaha'] ?? '',
                    'sumber_data' => $row['Sumber Data'] ?? $row['sumber_data'] ?? '',
                    'deskripsi_kegiatan_usaha' => $row['Deskripsi'] ?? $row['deskripsi_kegiatan_usaha'] ?? ''
                ];

                // Automated metadata lookup to fix names and "Tagging diluar kabupaten" warnings
                if ($unit->latitude && $unit->longitude) {
                    $geoInfo = $this->lookupSlsPhp($unit->latitude, $unit->longitude);
                    if ($geoInfo['success']) {
                        $rawData['sls_nmsls'] = $geoInfo['nmsls'];
                        $rawData['sls_nmdesa'] = $geoInfo['nmdesa'];
                        $rawData['sls_nmkec'] = $geoInfo['nmkec'];
                        $rawData['sls_idsls'] = $geoInfo['idsls'];
                        $rawData['[AUTO] Lokasi'] = $geoInfo['nmsls'] . " - " . $geoInfo['nmdesa'] . " - " . $geoInfo['nmkec'];
                    }
                }

                $unit->raw_data = $rawData; // Laravel array cast handles encoding
                $unit->save();

                // Track this name for inter-row duplicate detection
                $insertedNames[] = $normalizedForBatch;
                $successCount++;
            }

            $totalSkipped = $duplicateCount + $interRowDuplicateCount;
            return response()->json([
                'success' => true,
                'message' => "Bulk import selesai. Berhasil: $successCount, Duplikat DB di-skip: $duplicateCount, Duplikat batch di-skip: $interRowDuplicateCount",
                'success_count' => $successCount,
                'duplicate_count' => $totalSkipped
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal bulk import: ' . $e->getMessage()]);
        }
    }

    public function bulkHistory()
    {
        try {
            // Optimization: Use raw SQL to group and count in the database
            // This is MUCH faster than processing records in PHP memory
            // We only show batches with count > 1 to exclude individual additions
            $history = \DB::table('units')
                ->selectRaw("
                    SUBSTR(idsbr, 10, INSTR(SUBSTR(idsbr, 10), '-') - 1) as batch_id,
                    COUNT(*) as count,
                    MAX(first_updated_by) as petugas
                ")
                ->where('idsbr', 'like', 'tambahan-%-%')
                ->groupBy('batch_id')
                ->having('count', '>', 1)
                ->orderBy('batch_id', 'desc')
                ->get();

            $historyList = [];
            foreach ($history as $item) {
                if (is_numeric($item->batch_id)) {
                    $historyList[] = [
                        'batch_id' => $item->batch_id,
                        'date' => date('Y-m-d H:i:s', (int) $item->batch_id),
                        'count' => $item->count,
                        'petugas' => $item->petugas
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $historyList
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil histori bulk: ' . $e->getMessage()
            ]);
        }
    }

    public function bulkCancel(\Illuminate\Http\Request $request)
    {
        try {
            $batchId = $request->input('batch_id');
            if (!$batchId) {
                return response()->json(['success' => false, 'message' => 'Batch ID tidak valid']);
            }

            // Delete all units from this batch
            $deleted = \App\Models\Unit::where('idsbr', 'like', "tambahan-{$batchId}-%")->delete();

            return response()->json([
                'success' => true,
                'message' => "Berhasil menghapus $deleted data dari histori bulk.",
                'deleted_count' => $deleted
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus histori bulk: ' . $e->getMessage()]);
        }
    }

    public function store(\Illuminate\Http\Request $request)
    {
        try {
            // Duplicate check before saving (unless force_save is explicitly set)
            if (!$request->input('force_save')) {
                $nama = trim($request->nama_usaha);
                $kdkec = $request->kdkec;
                $kddesa = $request->kddesa;

                if (!empty($nama)) {
                    $duplicates = $this->findDuplicates($nama, $kdkec, $kddesa, 5);
                    // Filter only high-confidence matches (>= 85%)
                    $highMatches = $duplicates->filter(function ($dup) {
                        return ($dup->similarity_score ?? 0) >= 85;
                    });

                    if ($highMatches->count() > 0) {
                        $formattedData = $highMatches->map(function ($dup) {
                            return [
                                'idsbr' => $dup->idsbr,
                                'nama_usaha' => $dup->nama_usaha,
                                'alamat' => $dup->alamat ?? ($dup->raw_data['alamat_usaha'] ?? ''),
                                'lokasi' => $dup->raw_data['sls_nmsls'] ?? null,
                                'similarity' => $dup->similarity_score ?? 100,
                                'reason' => $dup->match_reason ?? 'Persis'
                            ];
                        });

                        return response()->json([
                            'success' => false,
                            'is_duplicate' => true,
                            'message' => 'Ditemukan ' . $highMatches->count() . ' usaha mirip di database! Apakah Anda yakin ingin tetap menyimpan?',
                            'duplicates' => $formattedData->values()
                        ]);
                    }
                }
            }

            $unit = new \App\Models\Unit();
            // The user requested IDSBR to contain "tambahan"
            $unit->idsbr = $request->idsbr ?? 'tambahan-' . time() . '-' . rand(1000, 9999);
            $unit->nama_usaha = $request->nama_usaha;
            $unit->alamat = $request->alamat_usaha;
            $unit->latitude = $request->latitude;
            $unit->longitude = $request->longitude;
            $unit->status_keberadaan = $request->status_keberadaan ?? '1';
            $unit->kdkec = (float) substr($request->kdkec, -3);
            $unit->kddesa = (float) substr($request->kddesa, -3);
            $unit->status_awal = ($request->latitude && $request->longitude) ? 'HAS_COORD' : 'NO_COORD';
            $unit->current_status = 'VERIFIED'; // Mark as VERIFIED so it shows up correctly
            $unit->last_updated_by = $request->petugas ?? session('gc_username', 'Scraper');
            $unit->first_updated_by = $unit->last_updated_by; // Save creator

            // Build raw_data from default plus any extra optional details
            $rawData = [
                'alamat_usaha' => $request->alamat_usaha,
                'sls_idsls' => $request->sls_idsls,
                'sumber_data' => $request->sumber_data,
                'skala_usaha' => $request->skala_usaha,
                'catatan_profiling' => $request->catatan_profiling,
                'kbli_kategori' => $request->kbli_kategori,
                'deskripsi_kegiatan_usaha' => $request->deskripsi_kegiatan_usaha,
                'telepon' => $request->telepon,
                'email' => $request->email,
                'source' => $request->source ?? 'Manual'
            ];

            // Optional Detail Fields
            if ($request->filled('deskripsi_kegiatan_usaha'))
                $rawData['Deskripsi Kegiatan Usaha'] = $request->deskripsi_kegiatan_usaha;
            if ($request->filled('kbli_kategori'))
                $rawData['KBLI / Kategori'] = $request->kbli_kategori;
            if ($request->filled('skala_usaha'))
                $rawData['Skala Usaha / Bentuk Badan Hukum'] = $request->skala_usaha;
            if ($request->filled('sumber_data'))
                $rawData['Sumber Data'] = $request->sumber_data;
            if ($request->filled('catatan_profiling'))
                $rawData['Catatan Profiling'] = $request->catatan_profiling;

            // Try to lookup SLS metadata to prevent "Tagging diluar kabupaten" and missing names
            if ($unit->latitude && $unit->longitude) {
                $geoInfo = $this->lookupSlsPhp($unit->latitude, $unit->longitude);
                if ($geoInfo['success']) {
                    $rawData['sls_nmsls'] = $geoInfo['nmsls'];
                    $rawData['sls_nmdesa'] = $geoInfo['nmdesa'];
                    $rawData['sls_nmkec'] = $geoInfo['nmkec'];
                    $rawData['sls_idsls'] = $geoInfo['idsls'];
                    $rawData['[AUTO] Lokasi'] = $geoInfo['nmsls'] . " - " . $geoInfo['nmdesa'] . " - " . $geoInfo['nmkec'];
                }
            }

            $unit->raw_data = $rawData;
            $unit->save();

            return response()->json(['success' => true, 'message' => 'Berhasil disimpan!', 'unit' => $unit]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $unit = \App\Models\Unit::findOrFail($id);
            $unit->delete();
            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus data: ' . $e->getMessage()], 500);
        }
    }

    public function downloadVerified(\Illuminate\Http\Request $request)
    {
        $filename = 'Hasil_Verifikasi_' . date('d_M_Y_H_i') . '.csv';

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($request) {
            $handle = fopen('php://output', 'w');

            // Apply Filters
            $query = \App\Models\Unit::query();
            $this->applyFilters($query, $request);

            // Use cursor for memory efficiency
            $units = $query->cursor();

            $headersWritten = false;
            $headerSchema = [];
            $counter = 1;

            foreach ($units as $unit) {
                $data = $unit->raw_data;

                // Handle string data if not automatically cast (prevents crash)
                if (is_string($data)) {
                    $data = json_decode($data, true);
                }

                if (!is_array($data))
                    continue;

                // --- 1. DEFINE SCHEMA (Based on first row logic) ---
                if (!$headersWritten) {
                    $cols = ['nomor', 'nama_usaha', 'alamat_usaha'];
                    foreach (array_keys($data) as $key) {
                        // Avoid duplicates if raw_data already has these names
                        $cleanKey = str_replace('_', ' ', strtolower($key));
                        if (in_array($cleanKey, ['nomor', 'nama_usaha', 'nama usaha', 'alamat_usaha', 'alamat usaha', 'alamat']))
                            continue;

                        $cols[] = $key;
                        if ($key === 'sls_nmsls' || $key === 'nmsls') {
                            $cols[] = 'kode_kec';
                            $cols[] = 'kode_desa';
                            $cols[] = 'Kode_Sub_SLS';
                            $cols[] = 'Latitude';
                            $cols[] = 'Longitude';
                        }
                    }
                    $cols[] = 'Keterangan_Data';
                    $cols[] = 'Petugas_Lapangan';
                    $cols[] = 'Kode_Minggu';

                    fputcsv($handle, $cols);

                    $headerSchema = $cols;
                    $headersWritten = true;
                }

                // --- 2. PREPARE VALUES ---
                $statusLabel = 'ORIGINAL';
                $petugas = '-';
                $weekCode = '';

                if ($unit->current_status == 'VERIFIED') {
                    $tgl = $unit->updated_at;
                    $dateStr = '';
                    if ($tgl) {
                        try {
                            $c = ($tgl instanceof \Carbon\Carbon) ? $tgl : \Carbon\Carbon::parse($tgl);
                            $dateStr = $c->timezone('Asia/Jakarta')->format('d/m/Y H:i');

                            if (empty($unit->last_updated_by)) {
                                $weekCode = 1;
                            } else {
                                $week1Start = \Carbon\Carbon::create(2026, 1, 26, 0, 0, 0, 'Asia/Jakarta');
                                $diffDays = $week1Start->diffInDays($c->timezone('Asia/Jakarta'), false);

                                if ($diffDays >= 0) {
                                    $weekCode = floor($diffDays / 7) + 2;
                                } else {
                                    $weekCode = 1;
                                }
                            }
                        } catch (\Exception $e) {
                            $dateStr = 'ERROR';
                        }
                    }
                    $statusLabel = 'UPDATED (' . $dateStr . ')';
                    $petugas = $unit->last_updated_by ?? '-';
                } else {
                    $statusLabel = ($unit->status_awal == 'HAS_COORD') ? 'ORIGINAL' : 'BELUM UPDATE';
                    $weekCode = '';
                }

                // --- 3. CONSTRUCT ROW ---
                $row = [];
                $schema = $headerSchema ?? $cols ?? [];

                foreach ($schema as $colName) {
                    if ($colName === 'nomor') {
                        $row[] = $counter++;
                    } elseif ($colName === 'nama_usaha') {
                        $row[] = $unit->nama_usaha;
                    } elseif ($colName === 'alamat_usaha') {
                        $row[] = $data['alamat_usaha'] ?? $data['alamat'] ?? $unit->alamat ?? '';
                    } elseif ($colName === 'Latitude') {
                        $row[] = $unit->latitude;
                    } elseif ($colName === 'Longitude') {
                        $row[] = $unit->longitude;
                    } elseif ($colName === 'kode_kec') {
                        $idsls_val = $data['sls_idsls'] ?? $data['idsls'] ?? '';
                        $row[] = strlen($idsls_val) >= 7 ? substr($idsls_val, 4, 3) : '';
                    } elseif ($colName === 'kode_desa') {
                        $idsls_val = $data['sls_idsls'] ?? $data['idsls'] ?? '';
                        $row[] = strlen($idsls_val) >= 10 ? substr($idsls_val, 7, 3) : '';
                    } elseif ($colName === 'Kode_Sub_SLS') {
                        $subC = '';
                        if ($unit->current_status == 'VERIFIED' && $unit->latitude && $unit->longitude) {
                            $rawSub = $data['kdsubsls'] ?? $data['subsls'] ?? null;
                            if ($rawSub !== null && $rawSub !== '') {
                                $subC = $rawSub;
                            } else {
                                $rawId = $data['sls_idsls'] ?? $data['idsls'] ?? '';
                                $cleanId = preg_replace('/[^0-9]/', '', $rawId);
                                if (strlen($cleanId) >= 16) {
                                    $subC = substr($cleanId, -2);
                                } else {
                                    $subC = '00';
                                }
                            }
                        }
                        $row[] = $subC;
                    } elseif ($colName === 'Keterangan_Data') {
                        $row[] = $statusLabel;
                    } elseif ($colName === 'Petugas_Lapangan') {
                        $row[] = $petugas;
                    } elseif ($colName === 'Kode_Minggu') {
                        $row[] = $weekCode;
                    } else {
                        $val = $data[$colName] ?? '';
                        if ($colName === 'sls_idsls' || $colName === 'idsls') {
                            $val = '="' . $val . '"';
                        }
                        if ($unit->current_status == 'VERIFIED') {
                            if (stripos($colName, 'lat') !== false && $unit->latitude)
                                $val = $unit->latitude;
                            if (stripos($colName, 'long') !== false && $unit->longitude)
                                $val = $unit->longitude;
                            if (stripos($colName, 'keberadaan') !== false && $unit->status_keberadaan)
                                $val = $unit->status_keberadaan;
                        }
                        $row[] = $val;
                    }
                }
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Unit $unit)
    {
        try {
            // 1. Optimistic Locking Check
            if ($request->has('last_updated_at')) {
                $clientUpdatedAt = $request->last_updated_at;
                $dbUpdatedAt = $unit->updated_at->toIso8601String();

                // Allow some small drift or just exact match if we use ISO string
                if ($clientUpdatedAt !== $dbUpdatedAt) {
                    return response()->json([
                        'message' => 'Conflict: Data ini sudah diupdate oleh orang lain beberapa saat lalu. Silakan refresh halaman.',
                        'current_data' => $unit
                    ], 409);
                }
            }

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

            // 2. Persistent Ownership (First Updated By)
            if (empty($unit->first_updated_by)) {
                $unit->first_updated_by = $username;
            }

            // Check if any attributes are dirty before saving
            $isDirty = $unit->isDirty();
            $unit->save();

            // If no attributes were changed, force update the timestamp
            // Note: isDirty() is cleared after save(), so we use wasChanged() check logic or our pre-check
            if (!$isDirty) {
                $unit->touch();
            }

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

            // --- Sub-SLS Logic (Sync with Frontend) ---
            $subC = '';
            // Since we just updated/verified, lat/long are present
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

            // Insert specific key order not strictly required for JSON dependent frontend (unless object iteration order matters)
            // But let's add it clearly.
            // We will do simple merge here, frontend order might differ unless sorted.
            // Actually, JS iterates object keys. Object key order is preserved in modern JS/Browsers.
            // Let's try to insert it nicely if possible, or just prepend/append.
            // User asked for specific position in BLADE, but via AJAX it replaces content.
            // So we should try to position it well here too.

            // --- Auto Keyword SLS Lookup ---
            $slsDebug = [];
            if ($unit->latitude && $unit->longitude) {
                try {
                    // Use native PHP lookup instead of Python shell_exec
                    $result = $this->lookupSlsPhp($unit->latitude, $unit->longitude);

                    // Add debug info
                    $slsDebug['source'] = 'PHP Native';
                    if (!$result['success']) {
                        $slsDebug['error'] = $result['message'];
                    } else {
                        $slsDebug['info'] = "Found: " . ($result['idsls'] ?? 'N/A');
                    }

                    if ($result && isset($result['success']) && $result['success']) {
                        // Update Raw Data
                        $detailData = $unit->raw_data ?? [];
                        if (!is_array($detailData))
                            $detailData = [];

                        $detailData['sls_idsls'] = $result['idsls'];
                        $detailData['sls_nmsls'] = $result['nmsls'];
                        $detailData['kdsubsls'] = $result['kdsubsls'];
                        $detailData['subsls'] = $result['kdsubsls']; // Redundant safety

                        // Force update subC for immediate feedback
                        $subC = $result['kdsubsls'];

                        $kdkec = sprintf('%03d', (int) $result['full_data']['kdkec']);
                        $kddesa = sprintf('%03d', (int) $result['full_data']['kddesa']);

                        // Resolve Names
                        $desaName = $result['nmdesa'] ?? 'UNKNOWN';
                        $kecName = 'UNKNOWN';

                        // Lookup Kec
                        $kecRegion = \App\Models\Region::where('level', 'KEC')->where('code', $kdkec)->first();
                        if ($kecRegion)
                            $kecName = $kecRegion->name;

                        // Lookup Desa if unknown or missing from GeoJSON
                        if ($desaName === 'UNKNOWN' || $desaName === '') {
                            $fullCode = $kdkec . $kddesa;
                            $desaRegion = \App\Models\Region::where('level', 'DESA')->where('code', $fullCode)->first();
                            if ($desaRegion)
                                $desaName = $desaRegion->name;
                        }
                        $detailData['nmdesa'] = $desaName; // Legacy support

                        // Store structured data for flexible display
                        $detailData['sls_nmkec'] = $kecName;
                        $detailData['sls_nmdesa'] = $desaName;

                        // Simple SLS Name for "Lokasi" lines
                        $detailData['sls_nmsls'] = ($result['nmsls'] !== 'UNKNOWN') ? $result['nmsls'] : 'SLS ' . $result['full_data']['kdsls'];

                        // Remove old [AUTO] Lokasi key if exists to force new display logic
                        if (isset($detailData['[AUTO] Lokasi'])) {
                            unset($detailData['[AUTO] Lokasi']);
                        }

                        // Optional: Update Unit Core Columns if needed (kdkec/kddesa)
                        if (isset($result['full_data']['kdkec']))
                            $unit->kdkec = (int) $result['full_data']['kdkec'];
                        if (isset($result['full_data']['kddesa']))
                            $unit->kddesa = (int) $result['full_data']['kddesa'];

                        $unit->raw_data = $detailData;
                        $unit->save(); // Save again with new raw_data
                    }
                } catch (\Exception $e) {
                    // Log error but DO NOT fail the request.
                    // The primary update (lat/long) has already succeeded above.
                    \Illuminate\Support\Facades\Log::warning("SLS Lookup Failed: " . $e->getMessage());
                    // Optionally append a warning to the response if needed, 
                    // but better to just let it pass so the user sees "Success" (at least for the coordinate update).
                }
            }

            // Re-construct for response
            $detailData = $unit->raw_data ?? [];

            // Ensure Sub-SLS is correct in display
            $subC = $detailData['kdsubsls'] ?? $subC;

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
                $newDetail = array_merge(['Kode Sub-SLS' => $subC], $newDetail);
            }
            $detailData = $newDetail;
            $detailData['--- UPDATE TERKINI ---'] = '';
            $detailData['Latitude (Baru)'] = $unit->latitude;
            $detailData['Longitude (Baru)'] = $unit->longitude;
            $detailData['Status (Baru)'] = $statuses[$unit->status_keberadaan] ?? $unit->status_keberadaan;
            $detailData['Petugas'] = $unit->last_updated_by;
            $detailData['Waktu Update'] = $unit->updated_at->timezone('Asia/Jakarta')->format('d/m/Y H:i');

            // Add Detected Address to Response
            if (isset($detailData['sls_nmsls'])) {
                $detailData['[AUTO] Lokasi SLS'] = $detailData['sls_nmsls'];
            }

            return response()->json([
                'success' => true,
                'last_update' => $unit->updated_at->timezone('Asia/Jakarta')->format('d/m H:i'),
                'new_timestamp' => $unit->updated_at->toIso8601String(),
                'user' => $username,
                'detail_json' => $detailData,
                'debug_sls' => $slsDebug
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function cancel(\Illuminate\Http\Request $request, \App\Models\Unit $unit)
    {
        try {
            // Log the cancellation before clearing data
            \App\Models\GroundcheckLog::create([
                'unit_id' => $unit->id,
                'user_id' => $request->username ?? 'Admin',
                'action' => 'CANCEL_UPDATE',
                'old_values' => [
                    'lat' => $unit->latitude,
                    'long' => $unit->longitude,
                    'status' => $unit->status_keberadaan,
                    'status_awal' => $unit->current_status
                ],
                'new_values' => ['status' => 'CLEARED']
            ]);

            // Clear Data
            $unit->latitude = null;
            $unit->longitude = null;
            $unit->status_keberadaan = null;
            $unit->current_status = 'PENDING'; // Revert to PENDING

            // Remove User assignment
            $unit->last_updated_by = null;

            // Clear SLS Data (Yellow Label Info) from raw_data
            if (is_array($unit->raw_data)) {
                $rawData = $unit->raw_data;
                $keysToRemove = [
                    'sls_idsls',
                    'sls_nmsls',
                    'kdsubsls',
                    'subsls',
                    'sls_nmkec',
                    'sls_nmdesa',
                    'sls_nmkab',
                    'sls_nmprov',
                    '[AUTO] Lokasi SLS',
                    '[AUTO] Lokasi'
                ];
                foreach ($keysToRemove as $key) {
                    unset($rawData[$key]);
                }
                $unit->raw_data = $rawData;
            }

            // Should we reset first_updated_by? Probably not if we want to trace history, 
            // but "cancel" implies undoing the work.
            // Let's keep first_updated_by unless requested, or maybe clear it if it was the same user.
            if ($unit->first_updated_by === $unit->last_updated_by) {
                // $unit->first_updated_by = null; // Optional
            }

            $unit->save();

            return response()->json([
                'success' => true,
                'message' => 'Update cancelled and data reset.'
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

        // Filter unique by (idsbr, nama_usaha, action)
        // If multiple logs have same IDSBR + Nama Usaha + Perubahan, keep only the latest
        $uniqueLogs = $logs->unique(function ($log) {
            $unit = $log->unit;
            $idsbr = $unit ? $unit->idsbr : 'null';
            $namaUsaha = $unit ? $unit->nama_usaha : 'null';
            $action = $log->action ?? 'null';

            // Create unique key from combination
            return $idsbr . '_' . $namaUsaha . '_' . $action;
        });

        // Re-index array (remove gaps from unique filter)
        return response()->json($uniqueLogs->values());
    }

    public function getDailyLogs(\Illuminate\Http\Request $request, $date)
    {
        $query = \App\Models\GroundcheckLog::with('unit')
            ->whereDate('created_at', $date);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('user_id', 'like', "%{$search}%")
                    ->orWhereHas('unit', function ($qUnit) use ($search) {
                        $qUnit->where('nama_usaha', 'like', "%{$search}%")
                            ->orWhere('idsbr', 'like', "%{$search}%");
                    });
            });
        }

        // Paginate by 50 to support infinite scroll smoothly
        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json($logs);
    }

    public function rekap()
    {
        // Group by kdkec only (not kddesa) for kecamatan-level view
        $results = \App\Models\Unit::select(
            'kdkec',
            \Illuminate\Support\Facades\DB::raw('count(*) as total'),
            \Illuminate\Support\Facades\DB::raw('sum(case when current_status = "VERIFIED" then 1 else 0 end) as groundchecked'),
            \Illuminate\Support\Facades\DB::raw('sum(case when current_status = "VERIFIED" and latitude is not null and latitude != "" and longitude is not null and longitude != "" then 1 else 0 end) as with_coord'),
            \Illuminate\Support\Facades\DB::raw('sum(case when current_status = "VERIFIED" and (latitude is null or latitude = "" or longitude is null or longitude = "") then 1 else 0 end) as no_coord')
        )
            ->groupBy('kdkec')
            ->orderBy('kdkec')
            ->get();

        // Get Region Names
        $kecNames = \App\Models\Region::where('level', 'KEC')->pluck('name', 'code')->toArray();

        // MERGE DUPLICATE KECAMATAN: Combine entries like "11" into "11.0"
        $mergedResults = [];

        foreach ($results as $row) {
            // Normalize key: remove .0 from kdkec
            $normalizedKey = str_replace('.0', '', $row->kdkec);

            if (!isset($mergedResults[$normalizedKey])) {
                $mergedResults[$normalizedKey] = [
                    'kdkec' => $row->kdkec, // Keep original format from first occurrence
                    'total' => 0,
                    'groundchecked' => 0,
                    'with_coord' => 0,
                    'no_coord' => 0
                ];
            }

            // Merge data
            $mergedResults[$normalizedKey]['total'] += $row->total;
            $mergedResults[$normalizedKey]['groundchecked'] += $row->groundchecked;
            $mergedResults[$normalizedKey]['with_coord'] += $row->with_coord;
            $mergedResults[$normalizedKey]['no_coord'] += $row->no_coord;

            // Prefer .0 format if we encounter it
            if (strpos($row->kdkec, '.0') !== false) {
                $mergedResults[$normalizedKey]['kdkec'] = $row->kdkec;
            }
        }

        $rows = [];
        $grandTotal = 0;
        $grandGroundchecked = 0;
        $grandWithCoord = 0;
        $grandNoCoord = 0;

        foreach ($mergedResults as $normalizedKey => $data) {
            // Clean Codes (Remove .0 for display)
            $kdkecRaw = str_replace('.0', '', $data['kdkec']);

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
                'total' => $data['total'],
                'groundchecked' => $data['groundchecked'],
                'with_coord' => $data['with_coord'],
                'no_coord' => $data['no_coord'],
                'empty' => $data['total'] - $data['groundchecked'],
                'percentage' => $data['total'] > 0 ? ($data['groundchecked'] / $data['total']) * 100 : 0
            ];
            $grandTotal += $data['total'];
            $grandGroundchecked += $data['groundchecked'];
            $grandWithCoord += $data['with_coord'];
            $grandNoCoord += $data['no_coord'];
        }

        // --- TAMBAHAN DATA CALCULATIONS ---
        $resultsTambahan = \App\Models\Unit::select(
            'kdkec',
            \Illuminate\Support\Facades\DB::raw('count(*) as total'),
            \Illuminate\Support\Facades\DB::raw('sum(case when current_status = "VERIFIED" then 1 else 0 end) as groundchecked'),
            \Illuminate\Support\Facades\DB::raw('sum(case when current_status = "VERIFIED" and latitude is not null and latitude != "" and longitude is not null and longitude != "" then 1 else 0 end) as with_coord'),
            \Illuminate\Support\Facades\DB::raw('sum(case when current_status = "VERIFIED" and (latitude is null or latitude = "" or longitude is null or longitude = "") then 1 else 0 end) as no_coord')
        )
            ->where(function ($q) {
                $q->where('idsbr', 'like', 'tambahan-%')
                    ->orWhere('idsbr', 'like', 'tambahan_%');
            })
            ->groupBy('kdkec')
            ->orderBy('kdkec')
            ->get();

        $mergedTambahan = [];
        foreach ($resultsTambahan as $row) {
            $normalizedKey = str_replace('.0', '', $row->kdkec);
            if (!isset($mergedTambahan[$normalizedKey])) {
                $mergedTambahan[$normalizedKey] = [
                    'kdkec' => $row->kdkec,
                    'total' => 0,
                    'groundchecked' => 0,
                    'with_coord' => 0,
                    'no_coord' => 0
                ];
            }
            $mergedTambahan[$normalizedKey]['total'] += $row->total;
            $mergedTambahan[$normalizedKey]['groundchecked'] += $row->groundchecked;
            $mergedTambahan[$normalizedKey]['with_coord'] += $row->with_coord;
            $mergedTambahan[$normalizedKey]['no_coord'] += $row->no_coord;
            if (strpos($row->kdkec, '.0') !== false) {
                $mergedTambahan[$normalizedKey]['kdkec'] = $row->kdkec;
            }
        }

        $tambahanRows = [];
        $tambahanGrandTotal = 0;
        $tambahanGrandGroundchecked = 0;
        $tambahanGrandWithCoord = 0;
        $tambahanGrandNoCoord = 0;

        foreach ($mergedTambahan as $normalizedKey => $data) {
            $kdkecRaw = str_replace('.0', '', $data['kdkec']);
            $kCode = sprintf('%03d', (int) $kdkecRaw);
            if ($kdkecRaw === 'UNKNOWN' || $kdkecRaw === '') {
                $kecName = 'TIDAK DIKETAHUI';
            } else {
                $kecName = $kecNames[$kCode] ?? 'UNKNOWN';
            }

            $tambahanRows[] = [
                'kdkec' => $kdkecRaw,
                'kec_name' => $kecName,
                'total' => $data['total'],
                'groundchecked' => $data['groundchecked'],
                'with_coord' => $data['with_coord'],
                'no_coord' => $data['no_coord'],
                'empty' => $data['total'] - $data['groundchecked'],
                'percentage' => $data['total'] > 0 ? ($data['groundchecked'] / $data['total']) * 100 : 0
            ];
            $tambahanGrandTotal += $data['total'];
            $tambahanGrandGroundchecked += $data['groundchecked'];
            $tambahanGrandWithCoord += $data['with_coord'];
            $tambahanGrandNoCoord += $data['no_coord'];
        }
        // --- END TAMBAHAN DATA CALCULATIONS ---

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
            ->whereNotNull('status_keberadaan')->where('status_keberadaan', '!=', '')
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

        // Count per User (Top Kontributor)
        // Use units.last_updated_by - stores actual user name
        // Each unit counted only ONCE (units table has 1 row per unit)
        // Match logic with main page filter
        $updatedUnits = \App\Models\Unit::select('id', 'last_updated_by')
            ->whereNotNull('last_updated_by')
            ->where('last_updated_by', '!=', '')
            ->get();

        // Group by user and count units
        // LOGIC BARU: Normalisasi User (Trim + Lowercase) untuk agregasi
        $rawUserStats = [];

        foreach ($updatedUnits as $unit) {
            $originalName = $unit->last_updated_by;
            $normalizedKey = strtolower(trim($originalName));

            if (!isset($rawUserStats[$normalizedKey])) {
                $rawUserStats[$normalizedKey] = [
                    'total' => 0,
                    'variants' => []
                ];
            }

            $rawUserStats[$normalizedKey]['total']++;

            // Track variant counts to pick dominant name later
            if (!isset($rawUserStats[$normalizedKey]['variants'][$originalName])) {
                $rawUserStats[$normalizedKey]['variants'][$originalName] = 0;
            }
            $rawUserStats[$normalizedKey]['variants'][$originalName]++;
        }

        // LOGIC MERGE: Gabungkan jika nama pendek adalah bagian dari nama panjang
        // Sort keys by length descending to process "Angger Halim Ismail" before "Angger"
        $keys = array_keys($rawUserStats);
        usort($keys, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        $finalUserStats = [];
        $processedKeys = [];

        foreach ($keys as $key) {
            if (isset($processedKeys[$key]))
                continue;

            // This long key becomes a parent entry
            $finalUserStats[$key] = $rawUserStats[$key];
            $processedKeys[$key] = true;

            // Look for shorter keys that are substrings of this key
            foreach ($keys as $candidate) {
                if ($key === $candidate || isset($processedKeys[$candidate]))
                    continue;

                // Check if candidate is a substring of key
                if (strpos($key, $candidate) !== false) {
                    // Merge candidate down to key
                    $finalUserStats[$key]['total'] += $rawUserStats[$candidate]['total'];

                    // Merge variants
                    foreach ($rawUserStats[$candidate]['variants'] as $vName => $vCount) {
                        if (!isset($finalUserStats[$key]['variants'][$vName])) {
                            $finalUserStats[$key]['variants'][$vName] = 0;
                        }
                        $finalUserStats[$key]['variants'][$vName] += $vCount;
                    }

                    $processedKeys[$candidate] = true;
                }
            }
        }

        // Format for view
        $userStats = [];
        foreach ($finalUserStats as $key => $data) {
            // Find dominant name variant
            $bestName = $key;
            $maxVariantCount = -1;

            foreach ($data['variants'] as $variantName => $vCount) {
                if ($vCount > $maxVariantCount) {
                    $maxVariantCount = $vCount;
                    $bestName = $variantName;
                }
            }

            $userStats[] = (object) [
                'user_id' => $bestName, // Use the most frequent original casing
                'total' => $data['total']
            ];
        }

        // Sort by total descending
        usort($userStats, function ($a, $b) {
            return $b->total - $a->total;
        });

        // 2. Daily Cumulative Stats (Showing Cumulative Progress from Status Awal)
        // 1. Get Base Count (Data Awal) - STRICT CHECK: Must currently be VERIFIED
        $baseTotalVerified = \App\Models\Unit::where('status_awal', 'HAS_COORD')
            ->where('current_status', 'VERIFIED')
            ->count();

        // 2. Get Daily Updates (All Verified Units)
        $dailyNetNew = \App\Models\Unit::select(
            \Illuminate\Support\Facades\DB::raw('DATE(updated_at) as date'),
            \Illuminate\Support\Facades\DB::raw('count(*) as count')
        )
            ->where('status_awal', '!=', 'HAS_COORD')
            ->where('current_status', 'VERIFIED')
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

        // --- TAMBAHAN SUMMARY STATS (Top Kontributor, Progress, Aktivitas) ---
        // 1. Tambahan Top Kontributor
        $updatedTambahanUnits = \App\Models\Unit::select('id', 'last_updated_by')
            ->whereNotNull('last_updated_by')
            ->where('last_updated_by', '!=', '')
            ->where(function ($q) {
                $q->where('idsbr', 'like', 'tambahan-%')
                    ->orWhere('idsbr', 'like', 'tambahan_%');
            })
            ->get();

        $rawTUserStats = [];
        foreach ($updatedTambahanUnits as $unit) {
            $originalName = $unit->last_updated_by;
            $normalizedKey = strtolower(trim($originalName));
            if (!isset($rawTUserStats[$normalizedKey])) {
                $rawTUserStats[$normalizedKey] = ['total' => 0, 'variants' => []];
            }
            $rawTUserStats[$normalizedKey]['total']++;
            if (!isset($rawTUserStats[$normalizedKey]['variants'][$originalName])) {
                $rawTUserStats[$normalizedKey]['variants'][$originalName] = 0;
            }
            $rawTUserStats[$normalizedKey]['variants'][$originalName]++;
        }

        $tKeys = array_keys($rawTUserStats);
        usort($tKeys, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        $finalTUserStats = [];
        $processedTKeys = [];
        foreach ($tKeys as $key) {
            if (isset($processedTKeys[$key]))
                continue;
            $finalTUserStats[$key] = $rawTUserStats[$key];
            $processedTKeys[$key] = true;
            foreach ($tKeys as $candidate) {
                if ($key === $candidate || isset($processedTKeys[$candidate]))
                    continue;
                if (strpos($key, $candidate) !== false) {
                    $finalTUserStats[$key]['total'] += $rawTUserStats[$candidate]['total'];
                    foreach ($rawTUserStats[$candidate]['variants'] as $vName => $vCount) {
                        if (!isset($finalTUserStats[$key]['variants'][$vName])) {
                            $finalTUserStats[$key]['variants'][$vName] = 0;
                        }
                        $finalTUserStats[$key]['variants'][$vName] += $vCount;
                    }
                    $processedTKeys[$candidate] = true;
                }
            }
        }

        $tambahanUserStats = [];
        foreach ($finalTUserStats as $key => $data) {
            // Find dominant name variant
            $bestName = $key;
            $maxVariantCount = -1;
            foreach ($data['variants'] as $variantName => $vCount) {
                if ($vCount > $maxVariantCount) {
                    $maxVariantCount = $vCount;
                    $bestName = $variantName;
                }
            }
            $tambahanUserStats[] = (object) [
                'user_id' => $bestName,
                'total' => $data['total']
            ];
        }
        usort($tambahanUserStats, function ($a, $b) {
            return $b->total - $a->total;
        });

        // 2. Tambahan Progress Akumulasi
        $tBaseTotalVerified = \App\Models\Unit::where('status_awal', 'HAS_COORD')
            ->where('current_status', 'VERIFIED')
            ->where(function ($q) {
                $q->where('idsbr', 'like', 'tambahan-%')
                    ->orWhere('idsbr', 'like', 'tambahan_%');
            })
            ->count();

        $tDailyNetNew = \App\Models\Unit::select(
            \Illuminate\Support\Facades\DB::raw('DATE(updated_at) as date'),
            \Illuminate\Support\Facades\DB::raw('count(*) as count')
        )
            ->where('status_awal', '!=', 'HAS_COORD')
            ->where('current_status', 'VERIFIED')
            ->where(function ($q) {
                $q->where('idsbr', 'like', 'tambahan-%')
                    ->orWhere('idsbr', 'like', 'tambahan_%');
            })
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $tambahanDailyCumulativeStats = [];
        $tRunningTotal = $tBaseTotalVerified;
        $tambahanDailyCumulativeStats[] = (object) [
            'date' => 'DATA AWAL',
            'total' => $tBaseTotalVerified,
            'cumulative' => $tBaseTotalVerified
        ];
        foreach ($tDailyNetNew as $d) {
            $tRunningTotal += $d->count;
            $tambahanDailyCumulativeStats[] = (object) [
                'date' => $d->date,
                'total' => $d->count,
                'cumulative' => $tRunningTotal
            ];
        }

        // 3. Tambahan Aktivitas Terakhir
        $tambahanLogs = \App\Models\GroundcheckLog::with('unit')
            ->whereHas('unit', function ($q) {
                $q->where('idsbr', 'like', 'tambahan-%')
                    ->orWhere('idsbr', 'like', 'tambahan_%');
            })
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        return view('units.rekap', compact(
            'rows',
            'grandTotal',
            'grandGroundchecked',
            'grandWithCoord',
            'grandNoCoord',
            'tambahanRows',
            'tambahanGrandTotal',
            'tambahanGrandGroundchecked',
            'tambahanGrandWithCoord',
            'tambahanGrandNoCoord',
            'logs',
            'userStats',
            'dailyStats',
            'dailyCumulativeStats',
            'statusBreakdown',
            'tambahanUserStats',
            'tambahanDailyCumulativeStats',
            'tambahanLogs'
        ));
    }

    public function getDesaByKecamatan($kdkec)
    {
        // Clean the kdkec parameter
        $kdkecClean = str_replace('.0', '', $kdkec);

        // Query units grouped by desa within this kecamatan
        $results = \App\Models\Unit::select(
            'kddesa',
            'status_keberadaan',
            'latitude',
            'longitude',
            \Illuminate\Support\Facades\DB::raw('count(*) as total'),
            \Illuminate\Support\Facades\DB::raw('sum(case when current_status = "VERIFIED" then 1 else 0 end) as groundchecked'),
            \Illuminate\Support\Facades\DB::raw('sum(case when current_status = "VERIFIED" and latitude is not null and latitude != "" and longitude is not null and longitude != "" then 1 else 0 end) as with_coord'),
            \Illuminate\Support\Facades\DB::raw('sum(case when current_status = "VERIFIED" and (latitude is null or latitude = "" or longitude is null or longitude = "") then 1 else 0 end) as no_coord')
        )
            ->where(function ($q) use ($kdkecClean) {
                if ($kdkecClean === 'UNKNOWN') {
                    $q->whereNull('kdkec')
                        ->orWhere('kdkec', '')
                        ->orWhere('kdkec', 'UNKNOWN');
                } else {
                    $q->where('kdkec', $kdkecClean)
                        ->orWhere('kdkec', $kdkecClean . '.0');
                }
            })
            ->groupBy('kddesa', 'status_keberadaan', 'latitude', 'longitude')
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
                    'groundchecked' => 0,
                    'with_coord' => 0,
                    'no_coord' => 0,
                    'status_breakdown' => []
                ];
            }

            $desaData[$kddesaRaw]['total'] += $row->total;
            $desaData[$kddesaRaw]['groundchecked'] += $row->groundchecked;
            $desaData[$kddesaRaw]['with_coord'] += $row->with_coord;
            $desaData[$kddesaRaw]['no_coord'] += $row->no_coord;

            if ($row->status_keberadaan) {
                $desaData[$kddesaRaw]['status_breakdown'][$row->status_keberadaan] =
                    ($desaData[$kddesaRaw]['status_breakdown'][$row->status_keberadaan] ?? 0) + $row->total;
            }
        }

        // Calculate percentages and format
        $response = [];
        foreach ($desaData as $desa) {
            $desa['empty'] = $desa['total'] - $desa['groundchecked'];
            $desa['percentage'] = $desa['total'] > 0 ? ($desa['groundchecked'] / $desa['total']) * 100 : 0;
            $response[] = $desa;
        }

        return response()->json($response);
    }

    public function mapDashboard()
    {
        // Pass necessary data for filters
        $kecNames = \App\Models\Region::where('level', 'KEC')->pluck('name', 'code')->toArray();
        $desaNames = \App\Models\Region::where('level', 'DESA')->pluck('name', 'code')->toArray();

        return view('units.map_dashboard', compact('kecNames', 'desaNames'));
    }

    public function getMapData(Request $request)
    {
        $query = \App\Models\Unit::query();

        // Reuse filter logic (Simplified version for map performance)
        // We only want units WITH coordinates mainly, but let's allow filtering
        if ($request->has('kdkec') && $request->kdkec) {
            $val = $request->kdkec;
            $valFloat = (float) $val;
            $query->where(function ($q) use ($valFloat) {
                $q->where('kdkec', $valFloat)
                    ->orWhere('kdkec', $valFloat . '.0');
            });
        }

        if ($request->has('kddesa') && $request->kddesa) {
            $fullCode = $request->kddesa;
            $villageSuffix = substr($fullCode, 3, 3);
            $val = (float) $villageSuffix;
            $query->where(function ($q) use ($val) {
                $q->where('kddesa', $val)
                    ->orWhere('kddesa', $val . '.0');
            });
        }

        // Only fetch units with coordinates for the map markers
        $query->whereNotNull('latitude')->where('latitude', '!=', '')
            ->whereNotNull('longitude')->where('longitude', '!=', '');

        // Select only necessary columns to keep JSON light
        $units = $query->select('id', 'nama_usaha', 'latitude', 'longitude', 'status_keberadaan', 'current_status', 'raw_data')->get();

        $features = [];
        foreach ($units as $unit) {
            $color = 'red'; // Default Pending/Issue
            if ($unit->current_status == 'VERIFIED') {
                $color = 'green';
                if ($unit->status_keberadaan == 6 || $unit->status_keberadaan == 4) { // Tidak Ditemukan / Tutup
                    $color = 'orange'; // Warning color
                }
            }

            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $unit->longitude, (float) $unit->latitude]
                ],
                'properties' => [
                    'id' => $unit->id,
                    'name' => $unit->nama_usaha,
                    'status' => $unit->current_status,
                    'condition' => $unit->status_keberadaan,
                    'color' => $color,
                    'address' => $unit->raw_data['alamat_detail'] ?? $unit->raw_data['alamat'] ?? ''
                ]
            ];
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features
        ]);
    }

    public function getSlsByDesa($kdkec, $kddesa)
    {
        // Handle messy data formats (e.g. "30.0" vs "30" vs "030")
        $kdkecInt = (int) $kdkec;
        $kddesaInt = (int) $kddesa;

        $units = \App\Models\Unit::where(function ($q) use ($kdkec, $kdkecInt) {
            if ($kdkec === 'UNKNOWN') {
                $q->whereNull('kdkec')
                    ->orWhere('kdkec', '')
                    ->orWhere('kdkec', 'UNKNOWN');
            } else {
                $q->where('kdkec', $kdkec)
                    ->orWhere('kdkec', $kdkecInt)
                    ->orWhere('kdkec', $kdkecInt . '.0')
                    ->orWhere('kdkec', str_pad($kdkecInt, 2, '0', STR_PAD_LEFT)) // '30'
                    ->orWhere('kdkec', str_pad($kdkecInt, 3, '0', STR_PAD_LEFT)); // '030'
            }
        })
            ->where(function ($q) use ($kddesa, $kddesaInt) {
                if ($kddesa === 'UNKNOWN') {
                    $q->whereNull('kddesa')
                        ->orWhere('kddesa', '')
                        ->orWhere('kddesa', 'UNKNOWN');
                } else {
                    $q->where('kddesa', $kddesa)
                        ->orWhere('kddesa', $kddesaInt)
                        ->orWhere('kddesa', $kddesaInt . '.0')
                        ->orWhere('kddesa', str_pad($kddesaInt, 3, '0', STR_PAD_LEFT)); // '001'
                }
            })
            ->get();

        $slsData = [];

        // Parse GeoJSON to get ALL SLS including empty ones
        $geoJsonPath = public_path('sls_1504.geojson');
        if (file_exists($geoJsonPath)) {
            $geoData = json_decode(file_get_contents($geoJsonPath), true);
            if (isset($geoData['features'])) {
                $targetKec = str_pad($kdkecInt, 3, '0', STR_PAD_LEFT);
                $targetDesa = str_pad($kddesaInt, 3, '0', STR_PAD_LEFT);
                foreach ($geoData['features'] as $feature) {
                    $props = $feature['properties'] ?? [];
                    $fKec = str_pad((int) ($props['kdkec'] ?? 0), 3, '0', STR_PAD_LEFT);
                    $fDesa = str_pad((int) ($props['kddesa'] ?? 0), 3, '0', STR_PAD_LEFT);
                    if ($fKec === $targetKec && $fDesa === $targetDesa) {
                        $kdsls = strval($props['kdsls'] ?? '');
                        if ($kdsls !== '') {
                            $fullId = '1504' . $targetKec . $targetDesa . str_pad($kdsls, 4, '0', STR_PAD_LEFT);
                            $nmsls = $props['nmsls'] ?? $kdsls;

                            $slsData[$fullId] = [
                                'sls_id' => $fullId,
                                'sls_name' => $nmsls,
                                'total' => 0,
                                'groundchecked' => 0,
                                'with_coord' => 0,
                                'no_coord' => 0,
                                'status_breakdown' => []
                            ];
                        }
                    }
                }
            }
        }
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
                    'groundchecked' => 0,
                    'with_coord' => 0,
                    'no_coord' => 0,
                    'status_breakdown' => []
                ];
            }

            $slsData[$slsId]['total']++;

            $isGroundchecked = ($unit->current_status == 'VERIFIED');

            $hasCoord = ($unit->latitude && $unit->latitude != '' && $unit->longitude && $unit->longitude != '');

            if ($isGroundchecked) {
                $slsData[$slsId]['groundchecked']++;
                if ($hasCoord) {
                    $slsData[$slsId]['with_coord']++;
                } else {
                    $slsData[$slsId]['no_coord']++;
                }

                if ($unit->status_keberadaan) {
                    $slsData[$slsId]['status_breakdown'][$unit->status_keberadaan] =
                        ($slsData[$slsId]['status_breakdown'][$unit->status_keberadaan] ?? 0) + 1;
                }
            }
        }

        // Format output
        $response = [];
        foreach ($slsData as $sls) {
            $sls['empty'] = $sls['total'] - $sls['groundchecked'];
            $sls['percentage'] = $sls['total'] > 0 ? ($sls['groundchecked'] / $sls['total']) * 100 : 0;
            $response[] = $sls;
        }

        // Sort by name
        usort($response, function ($a, $b) {
            return strcmp($a['sls_name'], $b['sls_name']);
        });

        return response()->json($response);
    }

    public function getDesaTambahanByKecamatan($kdkec)
    {
        $kdkecClean = str_replace('.0', '', $kdkec);

        $results = \App\Models\Unit::select(
            'kddesa',
            'status_keberadaan',
            'latitude',
            'longitude',
            \Illuminate\Support\Facades\DB::raw('count(*) as total'),
            \Illuminate\Support\Facades\DB::raw('sum(case when current_status = "VERIFIED" then 1 else 0 end) as groundchecked'),
            \Illuminate\Support\Facades\DB::raw('sum(case when current_status = "VERIFIED" and latitude is not null and latitude != "" and longitude is not null and longitude != "" then 1 else 0 end) as with_coord'),
            \Illuminate\Support\Facades\DB::raw('sum(case when current_status = "VERIFIED" and (latitude is null or latitude = "" or longitude is null or longitude = "") then 1 else 0 end) as no_coord')
        )
            ->where('idsbr', 'like', 'tambahan-%')
            ->where(function ($q) use ($kdkecClean) {
                if ($kdkecClean === 'UNKNOWN') {
                    $q->whereNull('kdkec')
                        ->orWhere('kdkec', '')
                        ->orWhere('kdkec', 'UNKNOWN');
                } else {
                    $q->where('kdkec', $kdkecClean)
                        ->orWhere('kdkec', $kdkecClean . '.0');
                }
            })
            ->groupBy('kddesa', 'status_keberadaan', 'latitude', 'longitude')
            ->orderBy('kddesa')
            ->get();

        $kCode = sprintf('%03d', (int) $kdkecClean);
        $desaNames = \App\Models\Region::where('level', 'DESA')
            ->where('parent_code', $kCode)
            ->pluck('name', 'code')
            ->toArray();

        $desaData = [];
        foreach ($results as $row) {
            $kddesaRaw = str_replace('.0', '', $row->kddesa);
            $dCode = $kCode . sprintf('%03d', (int) $kddesaRaw);

            if (!isset($desaData[$kddesaRaw])) {
                $desaData[$kddesaRaw] = [
                    'kddesa' => $kddesaRaw,
                    'desa_name' => $desaNames[$dCode] ?? 'TIDAK DIKETAHUI',
                    'total' => 0,
                    'groundchecked' => 0,
                    'with_coord' => 0,
                    'no_coord' => 0,
                    'status_breakdown' => []
                ];
            }

            $desaData[$kddesaRaw]['total'] += $row->total;
            $desaData[$kddesaRaw]['groundchecked'] += $row->groundchecked;
            $desaData[$kddesaRaw]['with_coord'] += $row->with_coord;
            $desaData[$kddesaRaw]['no_coord'] += $row->no_coord;

            if ($row->status_keberadaan) {
                $desaData[$kddesaRaw]['status_breakdown'][$row->status_keberadaan] =
                    ($desaData[$kddesaRaw]['status_breakdown'][$row->status_keberadaan] ?? 0) + $row->total;
            }
        }

        $response = [];
        foreach ($desaData as $desa) {
            $desa['empty'] = $desa['total'] - $desa['groundchecked'];
            $desa['percentage'] = $desa['total'] > 0 ? ($desa['groundchecked'] / $desa['total']) * 100 : 0;
            $response[] = $desa;
        }

        return response()->json($response);
    }

    public function getSlsTambahanByDesa($kdkec, $kddesa)
    {
        // Handle messy data formats
        $kdkecInt = (int) $kdkec;
        $kddesaInt = (int) $kddesa;

        $units = \App\Models\Unit::where(function ($q) {
            $q->where('idsbr', 'like', 'tambahan-%')
                ->orWhere('idsbr', 'like', 'tambahan_%');
        })
            ->where(function ($q) use ($kdkec, $kdkecInt) {
                if ($kdkec === 'UNKNOWN') {
                    $q->whereNull('kdkec')
                        ->orWhere('kdkec', '')
                        ->orWhere('kdkec', 'UNKNOWN');
                } else {
                    $q->where('kdkec', $kdkec)
                        ->orWhere('kdkec', $kdkecInt)
                        ->orWhere('kdkec', $kdkecInt . '.0')
                        ->orWhere('kdkec', str_pad($kdkecInt, 2, '0', STR_PAD_LEFT)) // '30'
                        ->orWhere('kdkec', str_pad($kdkecInt, 3, '0', STR_PAD_LEFT)); // '030'
                }
            })
            ->where(function ($q) use ($kddesa, $kddesaInt) {
                if ($kddesa === 'UNKNOWN') {
                    $q->whereNull('kddesa')
                        ->orWhere('kddesa', '')
                        ->orWhere('kddesa', 'UNKNOWN');
                } else {
                    $q->where('kddesa', $kddesa)
                        ->orWhere('kddesa', $kddesaInt)
                        ->orWhere('kddesa', $kddesaInt . '.0')
                        ->orWhere('kddesa', str_pad($kddesaInt, 3, '0', STR_PAD_LEFT)); // '001'
                }
            })
            ->get();

        $slsData = [];

        // Parse GeoJSON to get ALL SLS including empty ones
        $geoJsonPath = public_path('sls_1504.geojson');
        if (file_exists($geoJsonPath)) {
            $geoData = json_decode(file_get_contents($geoJsonPath), true);
            if (isset($geoData['features'])) {
                $targetKec = str_pad($kdkecInt, 3, '0', STR_PAD_LEFT);
                $targetDesa = str_pad($kddesaInt, 3, '0', STR_PAD_LEFT);
                foreach ($geoData['features'] as $feature) {
                    $props = $feature['properties'] ?? [];
                    $fKec = str_pad((int) ($props['kdkec'] ?? 0), 3, '0', STR_PAD_LEFT);
                    $fDesa = str_pad((int) ($props['kddesa'] ?? 0), 3, '0', STR_PAD_LEFT);
                    if ($fKec === $targetKec && $fDesa === $targetDesa) {
                        $kdsls = strval($props['kdsls'] ?? '');
                        if ($kdsls !== '') {
                            $fullId = '1504' . $targetKec . $targetDesa . str_pad($kdsls, 4, '0', STR_PAD_LEFT);
                            $nmsls = $props['nmsls'] ?? $kdsls;

                            // Only include SLS if it's referenced by a 'tambahan' unit, otherwise we'll show empty generic SLSs.
                            // For 'Tambahan' mode, we might want to skip pre-filling empty SLS if they don't have Tambahan.
                            // But let's keep consistency with the main function, just show them as 0.
                            $slsData[$fullId] = [
                                'sls_id' => $fullId,
                                'sls_name' => $nmsls,
                                'total' => 0,
                                'groundchecked' => 0,
                                'with_coord' => 0,
                                'no_coord' => 0,
                                'status_breakdown' => []
                            ];
                        }
                    }
                }
            }
        }

        foreach ($units as $unit) {
            $slsId = $unit->raw_data['sls_idsls'] ?? 'UNKNOWN';
            $slsName = $unit->raw_data['sls_nmsls'] ?? 'TIDAK DIKETAHUI';

            if ($slsId === 'UNKNOWN' && (!isset($unit->raw_data['sls_idsls']) || trim($unit->raw_data['sls_idsls']) === '')) {
                $slsId = 'NO_ID';
                $slsName = 'NON-SLS / LUAR SLS';
            }

            if (!isset($slsData[$slsId])) {
                $slsData[$slsId] = [
                    'sls_id' => $slsId,
                    'sls_name' => $slsName,
                    'total' => 0,
                    'groundchecked' => 0,
                    'with_coord' => 0,
                    'no_coord' => 0,
                    'status_breakdown' => []
                ];
            }

            $slsData[$slsId]['total']++;
            $isGroundchecked = ($unit->current_status == 'VERIFIED');
            $hasCoord = ($unit->latitude && $unit->latitude != '' && $unit->longitude && $unit->longitude != '');

            if ($isGroundchecked) {
                $slsData[$slsId]['groundchecked']++;
                if ($hasCoord) {
                    $slsData[$slsId]['with_coord']++;
                } else {
                    $slsData[$slsId]['no_coord']++;
                }

                if ($unit->status_keberadaan) {
                    $slsData[$slsId]['status_breakdown'][$unit->status_keberadaan] =
                        ($slsData[$slsId]['status_breakdown'][$unit->status_keberadaan] ?? 0) + 1;
                }
            }
        }

        // Format output
        $response = [];
        foreach ($slsData as $sls) {
            // Filter out SLS that have absolutely 0 target in 'Usaha Tambahan', otherwise the table is flooded with 0s.
            if ($sls['total'] > 0 || $sls['sls_id'] === 'NO_ID') {
                $sls['empty'] = $sls['total'] - $sls['groundchecked'];
                $sls['percentage'] = $sls['total'] > 0 ? ($sls['groundchecked'] / $sls['total']) * 100 : 0;
                $response[] = $sls;
            }
        }

        // Sort by name
        usort($response, function ($a, $b) {
            return strcmp($a['sls_name'], $b['sls_name']);
        });

        return response()->json($response);
    }

    public function getRekapSummary()
    {
        // 1. Get Kecamatan Level Summary
        $results = \App\Models\Unit::select(
            'kdkec',
            \Illuminate\Support\Facades\DB::raw('count(*) as total'),
            \Illuminate\Support\Facades\DB::raw('sum(case when current_status = "VERIFIED" then 1 else 0 end) as groundchecked'),
            \Illuminate\Support\Facades\DB::raw('sum(case when current_status = "VERIFIED" and latitude is not null and latitude != "" and longitude is not null and longitude != "" then 1 else 0 end) as with_coord'),
            \Illuminate\Support\Facades\DB::raw('sum(case when current_status = "VERIFIED" and (latitude is null or latitude = "" or longitude is null or longitude = "") then 1 else 0 end) as no_coord')
        )
            ->groupBy('kdkec')
            ->orderBy('kdkec')
            ->get();

        $kecNames = \App\Models\Region::where('level', 'KEC')->pluck('name', 'code')->toArray();

        $rows = [];
        $grandTotal = 0;
        $grandGroundchecked = 0;
        $grandWithCoord = 0;
        $grandNoCoord = 0;

        // Note: We need to replicate merged logic if we want consistency with index
        $mergedResults = [];
        foreach ($results as $row) {
            $normalizedKey = str_replace('.0', '', $row->kdkec);
            if (!isset($mergedResults[$normalizedKey])) {
                $mergedResults[$normalizedKey] = [
                    'kdkec' => $row->kdkec,
                    'total' => 0,
                    'groundchecked' => 0,
                    'with_coord' => 0,
                    'no_coord' => 0
                ];
            }
            $mergedResults[$normalizedKey]['total'] += $row->total;
            $mergedResults[$normalizedKey]['groundchecked'] += $row->groundchecked;
            $mergedResults[$normalizedKey]['with_coord'] += $row->with_coord;
            $mergedResults[$normalizedKey]['no_coord'] += $row->no_coord;

            if (strpos($row->kdkec, '.0') !== false) {
                $mergedResults[$normalizedKey]['kdkec'] = $row->kdkec;
            }
        }

        foreach ($mergedResults as $normalizedKey => $data) {
            $kdkecRaw = str_replace('.0', '', $data['kdkec']);
            $kCode = sprintf('%03d', (int) $kdkecRaw);
            $kecName = ($kdkecRaw === 'UNKNOWN' || $kdkecRaw === '') ? 'TIDAK DIKETAHUI' : ($kecNames[$kCode] ?? 'UNKNOWN');

            $rows[] = [
                'kdkec' => $kdkecRaw,
                'kec_name' => $kecName,
                'total' => $data['total'],
                'groundchecked' => $data['groundchecked'],
                'with_coord' => $data['with_coord'],
                'no_coord' => $data['no_coord'],
                'empty' => $data['total'] - $data['groundchecked'],
                'percentage' => $data['total'] > 0 ? ($data['groundchecked'] / $data['total']) * 100 : 0
            ];
            $grandTotal += $data['total'];
            $grandGroundchecked += $data['groundchecked'];
            $grandWithCoord += $data['with_coord'];
            $grandNoCoord += $data['no_coord'];
        }

        // 2. Daily Cumulative Stats
        $baseTotalVerified = \App\Models\Unit::where('status_awal', 'HAS_COORD')
            ->where('current_status', 'VERIFIED')
            ->count();
        $dailyNetNew = \App\Models\Unit::select(
            \Illuminate\Support\Facades\DB::raw('DATE(updated_at) as date'),
            \Illuminate\Support\Facades\DB::raw('count(*) as count')
        )
            ->where('status_awal', '!=', 'HAS_COORD')
            ->where('current_status', 'VERIFIED')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $dailyCumulativeStats = [];
        $runningTotal = $baseTotalVerified;
        $dailyCumulativeStats[] = [
            'date' => 'DATA AWAL',
            'total' => $baseTotalVerified,
            'cumulative' => $baseTotalVerified
        ];
        foreach ($dailyNetNew as $d) {
            $runningTotal += $d->count;
            $dailyCumulativeStats[] = [
                'date' => $d->date,
                'total' => $d->count,
                'cumulative' => $runningTotal
            ];
        }

        // 3. User Stats (Top Contributors)
        $updatedUnits = \App\Models\Unit::select('last_updated_by')
            ->whereNotNull('last_updated_by')
            ->where('last_updated_by', '!=', '')
            ->get();

        $userUnitsMap = [];
        foreach ($updatedUnits as $unit) {
            $userName = $unit->last_updated_by;
            if (!isset($userUnitsMap[$userName]))
                $userUnitsMap[$userName] = 0;
            $userUnitsMap[$userName]++;
        }
        $userStats = [];
        foreach ($userUnitsMap as $userName => $total) {
            $userStats[] = ['user_id' => $userName, 'total' => $total];
        }
        usort($userStats, function ($a, $b) {
            return $b['total'] - $a['total'];
        });

        // 4. Recent Activity (Daily Logs Grouped)
        $dailyStats = \App\Models\GroundcheckLog::select(
            \Illuminate\Support\Facades\DB::raw('DATE(created_at) as date'),
            \Illuminate\Support\Facades\DB::raw('count(*) as total')
        )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->take(14)
            ->get();

        // Get latest update timestamp
        $lastUpdate = \App\Models\Unit::max('updated_at');

        return response()->json([
            'rows' => $rows,
            'grandTotal' => $grandTotal,
            'grandGroundchecked' => $grandGroundchecked,
            'grandWithCoord' => $grandWithCoord,
            'grandNoCoord' => $grandNoCoord,
            'grandEmpty' => $grandTotal - $grandGroundchecked,
            'grandPercentage' => $grandTotal > 0 ? ($grandGroundchecked / $grandTotal) * 100 : 0,
            'dailyCumulativeStats' => $dailyCumulativeStats,
            'userStats' => $userStats,
            'dailyStats' => $dailyStats,
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
        $units = \App\Models\Unit::select('id', 'nama_usaha', 'idsbr', 'latitude', 'longitude', 'kdkec', 'kddesa', 'status_keberadaan', 'raw_data')
            ->whereNotNull('latitude')->where('latitude', '!=', '')
            ->whereNotNull('longitude')->where('longitude', '!=', '')
            ->cursor(); // Use cursor to conserve memory during processing

        // Get region names for lookup
        $kecNames = \App\Models\Region::where('level', 'KEC')->pluck('name', 'code')->toArray();
        $desaNames = \App\Models\Region::where('level', 'DESA')->pluck('name', 'code')->toArray();

        $data = [];
        foreach ($units as $unit) {
            $slsId = $unit->raw_data['sls_idsls'] ?? null;
            if ($slsId === 'UNKNOWN' || $slsId === null || trim((string) $slsId) === '') {
                $slsId = 'NO_ID';
            }


            // Clean kdkec/kddesa
            $kdkec = str_replace('.0', '', $unit->kdkec);
            $kddesa = str_replace('.0', '', $unit->kddesa);

            // Lookup names
            $kCode = sprintf('%03d', (int) $kdkec);
            $dCode = sprintf('%03d%03d', (int) $kdkec, (int) $kddesa);

            $kecName = $kecNames[$kCode] ?? 'Unknown';
            $desaName = $desaNames[$dCode] ?? 'Unknown';

            $data[] = [
                'id' => $unit->id,
                'name' => $unit->nama_usaha,
                'idsbr' => $unit->idsbr,
                'lat' => (float) $unit->latitude,
                'lng' => (float) $unit->longitude,
                'kdkec' => $kdkec,
                'kddesa' => $kddesa,
                'kec_name' => $kecName,
                'desa_name' => $desaName,
                'full_desa_code' => sprintf('%03d%03d', (int) $kdkec, (int) $kddesa),
                'status' => $unit->status_keberadaan,
                'sls_id' => $slsId
            ];
        }

        return response()->json($data);
    }

    public function gapAnalysis()
    {
        // ... (existing implementation) ...
        // Get ground-check data grouped by SLS
        // Get ground-check data grouped by SLS
        $units = \App\Models\Unit::select('raw_data')
            ->whereNotNull('latitude')->where('latitude', '!=', '')
            ->whereNotNull('longitude')->where('longitude', '!=', '')
            ->get();

        $groundCheckData = $units->groupBy(function ($unit) {
            return $unit->raw_data['sls_nmsls'] ?? 'TIDAK DIKETAHUI';
        });

        // Build Name -> ID Map
        $slsIdMap = [];
        foreach ($units as $u) {
            $name = $u->raw_data['sls_nmsls'] ?? null;
            $id = $u->raw_data['sls_idsls'] ?? null;
            if ($name && $id) {
                $slsIdMap[$name] = $id;
            }
        }

        // Get SIPW data
        $sipwData = \App\Models\SipwData::all()->keyBy('sls_name');

        // Combine and calculate gaps
        $allSls = collect($sipwData->keys())->merge($groundCheckData->keys())->unique();

        $gapData = [];
        foreach ($allSls as $slsName) {
            $sipwCount = $sipwData->get($slsName)?->business_count ?? 0;
            $sipwCount = $sipwData->get($slsName)?->business_count ?? 0;
            // $groundCheckData values are now Collections, so we use count()
            $groundCount = isset($groundCheckData[$slsName]) ? $groundCheckData[$slsName]->count() : 0;

            $gap = $sipwCount - $groundCount;
            $gapPercent = $sipwCount > 0 ? ($gap / $sipwCount) * 100 : 0;

            $slsId = $slsIdMap[$slsName] ?? '';

            $gapData[] = [
                'sls_name' => $slsName,
                'sls_id' => $slsId,
                'sipw_count' => $sipwCount,
                'ground_count' => $groundCount,
                'gap' => $gap,
                'gap_percent' => round($gapPercent, 2),
                'status' => $gapPercent < 10 ? 'good' : ($gapPercent < 30 ? 'warning' : 'danger')
            ];
        }

        // Sort by gap (largest first)
        usort($gapData, function ($a, $b) {
            return $b['gap'] <=> $a['gap'];
        });

        // Calculate summary
        $totalGap = array_sum(array_column($gapData, 'gap'));
        $avgGapPercent = count($gapData) > 0 ? array_sum(array_column($gapData, 'gap_percent')) / count($gapData) : 0;
        $totalSipw = array_sum(array_column($gapData, 'sipw_count'));
        $totalGroundCheck = array_sum(array_column($gapData, 'ground_count'));

        return view('units.gap_analysis', [
            'gapData' => $gapData,
            'summary' => [
                'total_sls' => count($gapData),
                'total_gap' => $totalGap,
                'avg_gap_percent' => round($avgGapPercent, 2),
                'total_sipw' => $totalSipw,
                'total_ground_check' => $totalGroundCheck
            ]
        ]);
    }

    public function sipwVisualization()
    {
        // Cache logic to avoid reading file on every request (60 minutes)
        // Ensure cache key is unique or we clear it during dev
        $data = \Illuminate\Support\Facades\Cache::remember('sipw_viz_data_v8', 60, function () {

            // Priority 1: Check Excel (User Request: "visualisasikan pake data excel export_sipw")
            $path = base_path('export_sipw.xlsx');
            if (file_exists($path)) {
                try {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $rows = [];
                    $headers = [];

                    // Init indices variables outside loop to ensure scope persistence
                    $idxNmsls = $idxJenis = $idxUsaha = $idxDominan = false;
                    $idxKdKec = $idxKdDesa = $idxKdSls = $idxNmKec = $idxNmDesa = false;

                    foreach ($worksheet->getRowIterator() as $index => $row) {
                        $cellIterator = $row->getCellIterator();
                        $cellIterator->setIterateOnlyExistingCells(false);

                        $cells = [];
                        foreach ($cellIterator as $cell) {
                            $cells[] = $cell->getValue();
                        }

                        if ($index == 1) {
                            // Normalize Headers
                            $headers = array_map(function ($h) {
                                return strtolower(trim((string) $h));
                            }, $cells);

                            // Find Dynamic Indices
                            $idxNmsls = array_search('nmsls', $headers);
                            $idxJenis = array_search('jenis', $headers);
                            $idxUsaha = array_search('usaha', $headers);
                            $idxDominan = array_search('dominan', $headers);

                            $idxKdKec = array_search('kdkec', $headers);
                            $idxKdDesa = array_search('kddesa', $headers);
                            $idxKdSls = array_search('kdsls', $headers);
                            $idxNmKec = array_search('nmkec', $headers);
                            $idxNmDesa = array_search('nmdesa', $headers);

                        } else {
                            $rowAssoc = [];
                            foreach ($headers as $i => $h) {
                                $key = !empty($h) ? $h : "col_$i";
                                $rowAssoc[$key] = $cells[$i] ?? null;
                            }

                            // Logic Maps with Fallbacks
                            $rowAssoc['_logic_nmsls'] = ($idxNmsls !== false) ? ($cells[$idxNmsls] ?? '-') : ($cells[3] ?? '-');
                            $rowAssoc['_logic_jenis'] = ($idxJenis !== false) ? ($cells[$idxJenis] ?? '-') : ($cells[5] ?? '-');
                            $rowAssoc['_logic_usaha'] = ($idxUsaha !== false) ? (int) ($cells[$idxUsaha] ?? 0) : (int) ($cells[22] ?? 0);
                            $rowAssoc['_logic_dominan'] = ($idxDominan !== false) ? ($cells[$idxDominan] ?? null) : ($cells[28] ?? null);

                            $rowAssoc['_logic_kdkec'] = ($idxKdKec !== false) ? ($cells[$idxKdKec] ?? 'UNKNOWN') : ($cells[8] ?? 'UNKNOWN');
                            $rowAssoc['_logic_kddesa'] = ($idxKdDesa !== false) ? ($cells[$idxKdDesa] ?? 'UNKNOWN') : ($cells[9] ?? 'UNKNOWN');
                            $rowAssoc['_logic_kdsls'] = ($idxKdSls !== false) ? ($cells[$idxKdSls] ?? '') : ($cells[10] ?? '');

                            $rowAssoc['_logic_nmkec'] = ($idxNmKec !== false) ? ($cells[$idxNmKec] ?? '-') : ($cells[15] ?? '-');
                            $rowAssoc['_logic_nmdesa'] = ($idxNmDesa !== false) ? ($cells[$idxNmDesa] ?? '-') : ($cells[16] ?? '-');

                            $rows[] = $rowAssoc;
                        }
                    }
                    return $rows;
                } catch (\Exception $e) {
                    // Fallback to CSV if Excel fails
                }
            }

            // Priority 2: Check CSV (Fallback)
            $csvPath = base_path('sipw_export.csv');
            if (file_exists($csvPath)) {
                $rows = [];
                if (($handle = fopen($csvPath, "r")) !== FALSE) {
                    // CSV has NO headers usually, but if it does we read them.
                    // Previous check showed NO headers in CSV (RT 001...).
                    // So we generate generic headers: Column 1, Column 2...

                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        $rowAssoc = [];
                        foreach ($data as $i => $val) {
                            $rowAssoc["Column " . ($i + 1)] = $val;
                        }

                        // Logic Maps
                        $rowAssoc['_logic_nmsls'] = $data[0] ?? '-'; // Index 0
                        $rowAssoc['_logic_jenis'] = $data[5] ?? '-'; // Index 5
                        $rowAssoc['_logic_usaha'] = (int) ($data[22] ?? 0); // Index 22

                        $rows[] = $rowAssoc;
                    }
                    fclose($handle);
                }
                return $rows;
            }

            return [];
        });

        if ($data === null || empty($data)) {
            $data = [];
        }

        // Process Stats
        $slsCounts = [];
        $totalBusinesses = 0;
        $typeCounts = [];
        $dominantCounts = [];

        // Hierarchy Structure: KecCode -> [name, desa_count, desas -> [DesaCode -> [name, sls_count, nonsls_count]]]
        $hierarchy = [];

        // Mapping Dominan (Col AC)
        $dominanMap = [
            1 => 'Permukiman Biasa',
            2 => 'Permukiman mewah/elite',
            3 => 'Permukiman Kumuh',
            4 => 'Apartemen/kondominium',
            5 => 'Kos-kosan/kontrakan',
            6 => 'Pesantren/barak/asrama',
            8 => 'Mall/Pertokoan/Pasar',
            9 => 'Kawasan Industri',
            10 => 'Hotel/Tempat Rekreasi',
            11 => 'Tidak Berpenghuni',
            12 => 'Perkantoran',
            13 => 'Pelabuhan/Bandara/Terminal'
        ];

        foreach ($data as $row) {
            // 1. Basic Stats
            $slsName = $row['_logic_nmsls'] ?? 'UNKNOWN';
            if ($slsName === '' || $slsName === null)
                $slsName = 'TIDAK DIKETAHUI';

            if (!isset($slsCounts[$slsName]))
                $slsCounts[$slsName] = 0;
            $slsCounts[$slsName]++;

            $val = isset($row['_logic_usaha']) ? (int) $row['_logic_usaha'] : 0;
            $totalBusinesses += $val;

            $type = $row['_logic_jenis'] ?? 'LAINNYA';
            if ($type === '' || $type === null)
                $type = 'LAINNYA';
            if (!isset($typeCounts[$type]))
                $typeCounts[$type] = 0;
            $typeCounts[$type]++;

            // 2. Dominant Load Stats
            $domCode = isset($row['_logic_dominan']) ? (int) $row['_logic_dominan'] : 0;
            if ($domCode > 0) {
                $domLabel = $dominanMap[$domCode] ?? 'Lainnya/Tidak Diketahui';
                if (!isset($dominantCounts[$domLabel]))
                    $dominantCounts[$domLabel] = 0;
                $dominantCounts[$domLabel]++;
            }

            // 3. Hierarchy Stats
            $kc = $row['_logic_kdkec'] ?? 'UNKNOWN';
            $dc = $row['_logic_kddesa'] ?? 'UNKNOWN';
            $slsCode = $row['_logic_kdsls'] ?? '';
            $nmKec = $row['_logic_nmkec'] ?? '-';
            $nmDesa = $row['_logic_nmdesa'] ?? '-';

            // Init Kec
            if (!isset($hierarchy[$kc])) {
                $hierarchy[$kc] = [
                    'name' => $nmKec,
                    'desas' => []
                ];
            }
            // Init Desa
            if (!isset($hierarchy[$kc]['desas'][$dc])) {
                $hierarchy[$kc]['desas'][$dc] = [
                    'name' => $nmDesa,
                    'sls_count' => 0,
                    'non_sls_count' => 0
                ];
            }

            // Determine if SLS or Non-SLS
            // Assumption: Non-SLS if Name contains 'NON SLS' or Code is '0000'
            $isNonSls = false;
            // Clean strings
            $checkName = strtoupper($slsName);
            $checkCode = trim((string) $slsCode);

            if (strpos($checkName, 'NON SLS') !== false || $checkCode === '0000') {
                $isNonSls = true;
            }

            if ($isNonSls) {
                $hierarchy[$kc]['desas'][$dc]['non_sls_count']++;
            } else {
                $hierarchy[$kc]['desas'][$dc]['sls_count']++;
            }
        }

        // --- Final Processing ---

        // Sort Top 5 SLS
        $slsBusinessCounts = [];
        foreach ($data as $row) {
            $name = $row['_logic_nmsls'] ?? 'UNKNOWN';
            $val = isset($row['_logic_usaha']) ? (int) $row['_logic_usaha'] : 0;
            if (!isset($slsBusinessCounts[$name]))
                $slsBusinessCounts[$name] = 0;
            $slsBusinessCounts[$name] += $val;
        }
        arsort($slsBusinessCounts);
        $top20SLS = array_slice($slsBusinessCounts, 0, 5);

        // Sort Types
        arsort($typeCounts);
        // Sort Dominant
        arsort($dominantCounts);

        $stats = [
            'total_sls' => count($data),
            'total_businesses' => $totalBusinesses,
            'topSLS' => $top20SLS,
            'types' => $typeCounts,
            'dominant' => $dominantCounts
        ];

        return view('units.sipw_viz', compact('data', 'stats', 'hierarchy'));
    }

    // --- PHP GeoJSON Lookup Helper ---

    private function pointInPolygon($point, $polygon)
    {
        // Ray casting algorithm
        $x = $point[0];
        $y = $point[1];
        $inside = false;
        $n = count($polygon);

        // P1
        $p1 = $polygon[0];
        $p1x = $p1[0];
        $p1y = $p1[1];

        for ($i = 0; $i <= $n; $i++) {
            // P2
            $p2 = $polygon[$i % $n];
            $p2x = $p2[0];
            $p2y = $p2[1];

            if ($y > min($p1y, $p2y)) {
                if ($y <= max($p1y, $p2y)) {
                    if ($x <= max($p1x, $p2x)) {
                        if ($p1y != $p2y) {
                            $xinters = ($y - $p1y) * ($p2x - $p1x) / ($p2y - $p1y) + $p1x;
                        } else {
                            $xinters = $p1x; // fallback
                        }

                        if ($p1x == $p2x || $x <= $xinters) {
                            $inside = !$inside;
                        }
                    }
                }
            }
            $p1x = $p2x;
            $p1y = $p2y;
        }

        return $inside;
    }

    public function checkSls(\Illuminate\Http\Request $request)
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        if (!$lat || !$lng) {
            return response()->json(['success' => false, 'message' => 'Lat/Lng required']);
        }

        try {
            $result = $this->lookupSlsPhp($lat, $lng);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function lookupSlsPhp($lat, $lng)
    {
        $path = base_path('sls_1504.geojson');
        if (!file_exists($path)) {
            $path = public_path('sls_1504.geojson');
            if (!file_exists($path)) {
                return ['success' => false, 'message' => 'GeoJSON file not found in root or public'];
            }
        }

        $jsonContent = file_get_contents($path);
        // Use associative array for faster parsing
        $data = json_decode($jsonContent, true);
        if (!$data || !isset($data['features'])) {
            return ['success' => false, 'message' => 'Invalid GeoJSON'];
        }

        // Optimize: Check bounding box first if available, but for now iterate
        foreach ($data['features'] as $feature) {
            $geom = $feature['geometry'];
            $type = $geom['type'];
            $coords = $geom['coordinates'];
            $match = false;

            if ($type === 'Polygon') {
                foreach ($coords as $poly) {
                    if ($this->pointInPolygon([$lng, $lat], $poly)) {
                        $match = true;
                        break;
                    }
                }
            } elseif ($type === 'MultiPolygon') {
                foreach ($coords as $multipoly) {
                    foreach ($multipoly as $poly) {
                        if ($this->pointInPolygon([$lng, $lat], $poly)) {
                            $match = true;
                            break 2;
                        }
                    }
                }
            }

            if ($match) {
                $props = $feature['properties'];
                $idsls = ($props['kdprov'] ?? '') .
                    ($props['kdkab'] ?? '') .
                    ($props['kdkec'] ?? '') .
                    ($props['kddesa'] ?? '') .
                    ($props['kdsls'] ?? '') .
                    ($props['kdsubsls'] ?? '');

                return [
                    'success' => true,
                    'idsls' => $idsls,
                    'nmsls' => $props['nmsls'] ?? 'UNKNOWN',
                    'nmdesa' => $props['nmdesa'] ?? 'UNKNOWN',
                    'nmkec' => $props['nmkec'] ?? 'UNKNOWN',
                    'kdsubsls' => $props['kdsubsls'] ?? '00',
                    'geometry' => $feature['geometry'], // Include Geometry from feature
                    'full_data' => $props
                ];
            }
        }

        return ['success' => false, 'message' => 'Not found in any SLS'];
    }
    public function getDesaGeometry($kec, $desa)
    {
        // Reuse lookup logic style but filter for entire Desa
        $path = base_path('sls_1504.geojson');
        if (!file_exists($path)) {
            $path = public_path('sls_1504.geojson');
            if (!file_exists($path)) {
                return response()->json(['success' => false, 'message' => 'GeoJSON not found']);
            }
        }

        $jsonContent = file_get_contents($path);
        $data = json_decode($jsonContent, true);

        if (!$data || !isset($data['features'])) {
            return response()->json(['success' => false, 'message' => 'Invalid GeoJSON']);
        }

        $features = [];
        $kdkec = sprintf('%03d', (int) $kec);
        $kddesa = sprintf('%03d', (int) $desa);

        foreach ($data['features'] as $feature) {
            $props = $feature['properties'];
            // Check Kec & Desa match
            if (
                isset($props['kdkec']) && sprintf('%03d', (int) $props['kdkec']) === $kdkec &&
                isset($props['kddesa']) && sprintf('%03d', (int) $props['kddesa']) === $kddesa
            ) {
                // Add useful ID
                $feature['properties']['idsls'] = ($props['kdprov'] ?? '') .
                    ($props['kdkab'] ?? '') .
                    ($props['kdkec'] ?? '') .
                    ($props['kddesa'] ?? '') .
                    ($props['kdsls'] ?? '') .
                    ($props['kdsubsls'] ?? '');
                $features[] = $feature;
            }
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features
        ]);
    }
    private function getCoordsBySls($idsls)
    {
        if (empty($idsls))
            return null;

        static $geoData = null;
        if ($geoData === null) {
            $path = base_path('sls_1504.geojson');
            if (!file_exists($path)) {
                $path = public_path('sls_1504.geojson');
                if (!file_exists($path))
                    return null;
            }
            $geoData = json_decode(file_get_contents($path), true);
        }

        if (!$geoData || !isset($geoData['features']))
            return null;

        $targetId = str_pad($idsls, 16, '0', STR_PAD_RIGHT);

        foreach ($geoData['features'] as $feature) {
            $props = $feature['properties'];
            $featId = ($props['kdprov'] ?? '') .
                ($props['kdkab'] ?? '') .
                ($props['kdkec'] ?? '') .
                ($props['kddesa'] ?? '') .
                ($props['kdsls'] ?? '') .
                ($props['kdsubsls'] ?? '00');

            if (strpos($featId, $idsls) === 0 || $featId === $targetId) {
                if (isset($props['posisi'])) {
                    $parts = explode(',', $props['posisi']);
                    if (count($parts) >= 2) {
                        return [
                            'lng' => trim($parts[0]),
                            'lat' => trim($parts[1])
                        ];
                    }
                }
                break;
            }
        }
        return null;
    }

    // --- DETEKSI ALAMAT (CODE GENERATOR) ---

    public function showGenerateKodePage()
    {
        return view('units.generate_kode');
    }

    public function detectAddresses(\Illuminate\Http\Request $request)
    {
        $addresses = $request->input('addresses', []);

        if (empty($addresses)) {
            return response()->json(['error' => 'No addresses provided'], 400);
        }

        static $geoData = null;
        if ($geoData === null) {
            $path = base_path('sls_1504.geojson');
            if (!file_exists($path)) {
                $path = public_path('sls_1504.geojson');
            }
            if (file_exists($path)) {
                $geoData = json_decode(file_get_contents($path), true);
            }
        }

        if (!$geoData || !isset($geoData['features'])) {
            return response()->json(['error' => 'GeoJSON data not available'], 500);
        }

        $results = [];

        foreach ($addresses as $rawAddress) {
            $address = strtoupper(trim($rawAddress));
            $foundDesa = null;
            $foundSls = null;
            $foundKec = null;

            // Extract RT number if present (e.g., "RT.01", "RT 04", "RT02")
            $rtNumber = null;
            if (preg_match('/RT[\.\s]*0*(\d{1,3})/i', $address, $matches)) {
                $rtInt = (int) $matches[1];
                $rtNumber = str_pad($rtInt, 2, '0', STR_PAD_LEFT);
            }

            // Phase 1: Perfect Match (Village + RT)
            foreach ($geoData['features'] as $feature) {
                $props = $feature['properties'] ?? [];
                $nmdesa = strtoupper(trim($props['nmdesa'] ?? ''));
                $nmsls = strtoupper(trim($props['nmsls'] ?? ''));

                if ($nmdesa && strpos($address, $nmdesa) !== false) {
                    if ($rtNumber && strpos($nmsls, 'RT ' . $rtNumber) !== false) {
                        $foundKec = str_pad((int) ($props['kdkec'] ?? 0), 3, '0', STR_PAD_LEFT);
                        $foundDesa = str_pad((int) ($props['kddesa'] ?? 0), 3, '0', STR_PAD_LEFT);
                        $kdsls = str_pad($props['kdsls'] ?? '', 4, '0', STR_PAD_LEFT);
                        $foundSls = '1504' . $foundKec . $foundDesa . $kdsls;
                        break;
                    }
                }
            }

            // Phase 2: Fallback (Just Village, or Just RT)
            if (!$foundSls) {
                foreach ($geoData['features'] as $feature) {
                    $props = $feature['properties'] ?? [];
                    $nmdesa = strtoupper(trim($props['nmdesa'] ?? ''));
                    $nmsls = strtoupper(trim($props['nmsls'] ?? ''));

                    // Match by Village alone
                    if ($nmdesa && strpos($address, $nmdesa) !== false) {
                        $foundKec = str_pad((int) ($props['kdkec'] ?? 0), 3, '0', STR_PAD_LEFT);
                        $foundDesa = str_pad((int) ($props['kddesa'] ?? 0), 3, '0', STR_PAD_LEFT);
                        // Don't set foundSls, just Desa and Kec
                    }
                }
            }

            $results[] = [
                'original' => $rawAddress,
                'kode_kecamatan' => $foundKec ? '1504' . $foundKec : '',
                'kode_desa' => $foundDesa ? '1504' . $foundKec . $foundDesa : '',
                'kode_sls' => $foundSls ?? ''
            ];
        }

        return response()->json(['data' => $results]);
    }
}

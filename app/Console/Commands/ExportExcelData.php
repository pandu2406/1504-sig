<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Unit;
use Carbon\Carbon;

class ExportExcelData extends Command
{
    protected $signature = 'app:export-excel {--output=}';
    protected $description = 'Export units to CSV using raw_data and applying updates';

    public function handle()
    {
        $outputFile = $this->option('output');
        if (!$outputFile) {
            $outputFile = storage_path('app/public/Gabungan_Export_' . date('Ymd_His') . '.csv');
        }

        $this->info("Starting Export to $outputFile...");

        $dir = dirname($outputFile);
        if (!is_dir($dir))
            mkdir($dir, 0755, true);

        // Instantiate GeoService
        $geoService = new \App\Services\GeoService();

        $handle = fopen($outputFile, 'w');

        // FIXED: Tanpa select() sama sekali agar Updated At terbawa otomatis
        $query = Unit::query();

        $count = 0;
        $headersWritten = false;

        // Dynamic Index for Kode_Minggu
        $idxKodeMinggu = -1;

        $query->chunk(500, function ($units) use ($handle, &$headersWritten, &$count, $geoService) {
            // Static cache for the Key Name across rows (in this chunk scope at least)
            static $keyKodeMinggu = null;

            foreach ($units as $unit) {
                if (empty($unit->raw_data))
                    continue;

                $data = $unit->raw_data;
                $keys = array_keys($data);

                // Detect Key Name for Kode_Minggu ONCE (or if not yet found)
                if ($keyKodeMinggu === null) {
                    foreach ($keys as $k) {
                        if (stripos($k, 'minggu') !== false) {
                            $keyKodeMinggu = $k;
                            break;
                        }
                    }
                    // Fallback to AP (index 41) if structure matches Excel
                    if ($keyKodeMinggu === null && isset($keys[41])) {
                        $keyKodeMinggu = $keys[41];
                    }
                }

                if (!$headersWritten) {
                    $headers = $keys;
                    $headers[] = 'Keterangan_Data';
                    $headers[] = 'Petugas_Lapangan';
                    fputcsv($handle, $headers);
                    $headersWritten = true;
                }

                $statusLabel = 'ORIGINAL';
                $petugas = '-';

                try {
                    // Check Geofencing for ALL verified units (or any unit with coords)
                    // Requirement: "jika ada usaha yang Lat/Long nya berada diluar jangkauan" -> add 99 to Kode_Minggu
                    if ($unit->latitude && $unit->longitude) {
                        // Optimasi: GeoService is now cached, so calling checkPosition is cheap.
                        // We must double check because previous attempts might have failed or data is legacy.
                        // Requirement: "dicek ulang taggingnya" implies verifying current coords.

                        $res = $geoService->checkPosition($unit->latitude, $unit->longitude);

                        if (!$res['success']) {
                            // It is OUTSIDE
                            // Set Kode_Minggu to 99
                            // Use key name to be safe
                            if ($keyKodeMinggu && array_key_exists($keyKodeMinggu, $data)) {
                                $data[$keyKodeMinggu] = 99;
                                // Log for debugging
                                // echo "Updated unit {$unit->id} (Outside) -> 99. Key: $keyKodeMinggu\n";
                            } else {
                                // Force add if key exists in $keys but not in $data (shouldn't happen with array_keys($data))
                                // BUT if $data doesn't have the key set yet (sparse), we might need to add it.
                                if ($keyKodeMinggu) {
                                    $data[$keyKodeMinggu] = 99;
                                } else {
                                    // Fallback: try column 41 directly by index? 
                                    // No, $data is associative. We need the KEY.
                                    // If we can't find key, we can't update.
                                    // echo "FAILED to update unit {$unit->id} (Outside). Key not found.\n";
                                }
                            }
                        }
                    }

                    if ($unit->current_status == 'VERIFIED') {
                        // Update Data with latest values
                        // Map specific columns if known, or rely on raw_data being updated?
                        // Usually update() updates raw_data too. 
                        // But let's override with Model attributes to be sure.

                        // We need to know indices or keys for Lat/Long/Status if we want to update them in CSV.
                        // Assuming standard structure or relying on raw_data being fresh.
                        // UnitController@update updates raw_data['sls_idsls'] etc.

                        // But Lat/Long in columns 10 and 11?
                        if (isset($keys[10]))
                            $data[$keys[10]] = $unit->latitude;
                        if (isset($keys[11]))
                            $data[$keys[11]] = $unit->longitude;
                        if (isset($keys[12]) && $unit->status_keberadaan) {
                            $data[$keys[12]] = $unit->status_keberadaan;
                        }

                        // FIXED LOGIC TANGGAL
                        $dateStr = 'ERROR-DATE';
                        if ($unit->updated_at) {
                            $carbon = ($unit->updated_at instanceof Carbon) ? $unit->updated_at : Carbon::parse($unit->updated_at);
                            $dateStr = $carbon->timezone('Asia/Jakarta')->format('d/m/Y H:i');
                        } else {
                            $dateStr = 'NULL-DATE';
                        }

                        $statusLabel = 'UPDATED-FIXED (' . $dateStr . ')';
                        $petugas = $unit->last_updated_by ?? '-';
                    } else {
                        if ($unit->status_awal == 'HAS_COORD') {
                            $statusLabel = 'ORIGINAL';
                        } else {
                            $statusLabel = 'BELUM UPDATE';
                        }
                    }
                } catch (\Exception $e) {
                    $statusLabel = 'ERROR: ' . $e->getMessage();
                }

                $values = array_values($data);
                $values[] = $statusLabel;
                $values[] = $petugas;

                fputcsv($handle, $values);
                $count++;
            }
            $this->info("Processed $count rows...");
        });

        fclose($handle);
        $this->info("Export Complete! Total $count rows.");
    }
}
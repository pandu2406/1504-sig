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
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $handle = fopen($outputFile, 'w');

        // FIXED: Tanpa select() sama sekali agar Updated At terbawa otomatis
        $query = Unit::query();

        $count = 0;
        $headersWritten = false;

        $query->chunk(500, function ($units) use ($handle, &$headersWritten, &$count) {
            foreach ($units as $unit) {
                if (empty($unit->raw_data)) continue;

                $data = $unit->raw_data;

                if (!$headersWritten) {
                    $headers = array_keys($data);
                    $headers[] = 'Keterangan_Data';
                    $headers[] = 'Petugas_Lapangan';
                    fputcsv($handle, $headers);
                    $headersWritten = true;
                }

                $keys = array_keys($data);
                $statusLabel = 'ORIGINAL';
                $petugas = '-';

                try {
                    if ($unit->current_status == 'VERIFIED') {
                        // Update Data
                        if (isset($keys[10])) $data[$keys[10]] = $unit->latitude;
                        if (isset($keys[11])) $data[$keys[11]] = $unit->longitude;
                        if (isset($keys[12]) && $unit->status_keberadaan) {
                            $data[$keys[12]] = $unit->status_keberadaan;
                        }

                        // FIXED LOGIC TANGGAL
                        $dateStr = 'ERROR-DATE';
                        if ($unit->updated_at) {
                             $carbon = ($unit->updated_at instanceof Carbon) ? $unit->updated_at : Carbon::parse($unit->updated_at);
                             $dateStr = $carbon->timezone('Asia/Jakarta')->format('d/m/Y H:i');
                        } else {
                             $dateStr = 'NULL-DATE'; // Debugging jika kosong
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
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Unit;

class ImportExcelData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-excel-check {--output=}';
    protected $description = 'Export units to CSV using raw_data and applying updates';

    public function handle()
    {
        $outputFile = $this->option('output');
        if (!$outputFile) {
            // Default to storage path if not provided
            $outputFile = storage_path('app/public/Gabungan_Export_' . date('Ymd_His') . '.csv');
        }

        $this->info("Starting Export to $outputFile...");

        // Ensure directory exists
        $dir = dirname($outputFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $handle = fopen($outputFile, 'w');

        // Fetch all units chunked to save memory
        // We need 'raw_data' and the columns to update
        $query = Unit::query()->select([
            'id',
            'idsbr',
            'latitude',
            'longitude',
            'status_keberadaan',
            'current_status',
            'raw_data',
            'last_updated_by'
        ]);

        $count = 0;
        $headersWritten = false;

        // Use chunking
        $query->chunk(500, function ($units) use ($handle, &$headersWritten, &$count) {
            foreach ($units as $unit) {
                if (empty($unit->raw_data)) {
                    continue; // Skip if no raw data (should not happen for imported rows)
                }

                // Decode raw_data (Associative Array)
                $data = $unit->raw_data;

                // If this is the very first row, write headers
                if (!$headersWritten) {
                    $headers = array_keys($data);
                    // Add Custom Headers
                    $headers[] = 'Keterangan_Data';
                    $headers[] = 'Petugas_Lapangan';

                    fputcsv($handle, $headers);
                    $headersWritten = true;
                }

                // Get Keys to access by Index (safe for K, L, M mapping)
                $keys = array_keys($data);

                // Column Indices (0-based) from import logic:
                // K = 10 (Latitude)
                // L = 11 (Longitude)
                // M = 12 (Status Keberadaan)

                // Apply Updates if Verified
                $statusLabel = 'ORIGINAL';
                $petugas = '-';

                if ($unit->current_status == 'VERIFIED') {
                    // Update Lat/Long
                    if (isset($keys[10]))
                        $data[$keys[10]] = $unit->latitude;
                    if (isset($keys[11]))
                        $data[$keys[11]] = $unit->longitude;

                    // Update Status (Col M)
                    if (isset($keys[12]) && $unit->status_keberadaan) {
                        $data[$keys[12]] = $unit->status_keberadaan;
                    }

                    $dateStr = $unit->updated_at ? $unit->updated_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') : now()->format('d/m/Y H:i');
                    $statusLabel = 'UPDATED (' . $dateStr . ')';
                    $petugas = $unit->last_updated_by ?? '-';
                } else {
                    // If not verified, but imported, check if it had coords originally
                    if ($unit->status_awal == 'HAS_COORD') {
                        $statusLabel = 'ORIGINAL';
                    } else {
                        $statusLabel = 'BELUM UPDATE';
                    }
                }

                // Append Columns
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

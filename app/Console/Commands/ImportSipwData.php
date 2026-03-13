<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SipwData;

class ImportSipwData extends Command
{
    protected $signature = 'app:import-sipw';
    protected $description = 'Import SIPW data from CSV (converted from export_sipw.xlsx)';

    public function handle()
    {
        // Check if CSV file exists
        $csvPath = base_path('sipw_export.csv');

        if (!file_exists($csvPath)) {
            $this->error("CSV file not found: $csvPath");
            $this->info("\n=== CARA EXPORT CSV YANG BENAR ===");
            $this->info("1. Buka export_sipw.xlsx");
            $this->info("2. Pastikan kolom yang akan diexport:");
            $this->info("   - Kolom D: SLS Name (BUKAN Sub-SLS!)");
            $this->info("   - Kolom W: Business Count");
            $this->info("3. Buat sheet baru, copy HANYA 2 kolom tersebut");
            $this->info("4. Di sheet baru:");
            $this->info("   - Kolom A: SLS Name (dari kolom D sheet asli)");
            $this->info("   - Kolom B: Business Count (dari kolom W sheet asli)");
            $this->info("5. Save sheet baru as CSV: sipw_export.csv");
            $this->info("6. Taruh file di folder project root");
            return 1;
        }

        $this->info("Loading CSV file...");

        // Clear existing data
        SipwData::truncate();
        $this->info("Cleared existing SIPW data.");

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            $this->error("Failed to open CSV file");
            return 1;
        }

        // Skip header row
        $header = fgetcsv($handle);
        $this->info("CSV Header: " . implode(', ', $header));

        $imported = 0;
        $skipped = 0;
        $this->info("Processing rows...");

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                $skipped++;
                continue; // Skip invalid rows
            }

            // CSV should have exactly 2 columns: SLS Name, Business Count
            $slsName = trim($row[0] ?? '');
            $businessCount = (int) ($row[1] ?? 0);

            // Validate SLS name (must not be empty and should not contain "SUB" keyword)
            if (empty($slsName)) {
                $skipped++;
                continue;
            }

            // Skip if it looks like a Sub-SLS (contains specific keywords)
            if (stripos($slsName, 'SUB SLS') !== false || stripos($slsName, 'SUBSLS') !== false) {
                $this->warn("Skipping Sub-SLS: $slsName");
                $skipped++;
                continue;
            }

            SipwData::updateOrCreate(
                ['sls_name' => $slsName],
                ['business_count' => $businessCount]
            );

            $imported++;

            // Show progress every 100 rows
            if ($imported % 100 === 0) {
                $this->info("Processed $imported rows...");
            }
        }

        fclose($handle);

        $this->info("✓ Done! Imported $imported SLS records.");
        if ($skipped > 0) {
            $this->warn("⚠ Skipped $skipped invalid/Sub-SLS rows.");
        }

        return 0;
    }
}

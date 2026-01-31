<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $kecCsv = 'e:\Ngoding\gc-sbr1504\kode_kecamatan.csv';
        $desCsv = 'e:\Ngoding\gc-sbr1504\kode_keldes.csv';

        // Helper to read CSV via Python (lazy but robust against formatting issues)
        // Actually, for small reference files, PHP native is fine if files are simple.
        // Let's check format again. They are quoted: "010","MERSAM"

        $this->seedKecamatan($kecCsv);
        $this->seedDesa($desCsv);
    }

    private function seedKecamatan($file)
    {
        $handle = fopen($file, "r");
        if ($handle) {
            fgetcsv($handle); // Header
            while (($row = fgetcsv($handle)) !== false) {
                // "010", "MERSAM"
                \App\Models\Region::create([
                    'code' => $row[0],
                    'name' => $row[1],
                    'level' => 'KEC'
                ]);
            }
            fclose($handle);
        }
    }

    private function seedDesa($file)
    {
        $handle = fopen($file, "r");
        if ($handle) {
            fgetcsv($handle); // Header
            while (($row = fgetcsv($handle)) !== false) {
                // "010009", "SENGKATI..."
                $fullCode = $row[0];
                $name = $row[1];
                $kdkec = substr($fullCode, 0, 3);

                \App\Models\Region::create([
                    'code' => $fullCode,
                    'name' => $name,
                    'parent_code' => $kdkec,
                    'level' => 'DESA'
                ]);
            }
            fclose($handle);
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class RecapVillageData extends Command
{
    protected $signature = 'app:rekap-desa';
    protected $description = 'Recap data count by Village (Desa) including Unknown';

    public function handle()
    {
        $this->info("Generating Recap by Village...");

        // Group by kdkec and kddesa
        $results = Unit::select('kdkec', 'kddesa', DB::raw('count(*) as total'))
            ->groupBy('kdkec', 'kddesa')
            ->orderBy('kdkec')
            ->orderBy('kddesa')
            ->get();

        $rows = [];
        $grandTotal = 0;

        // Get Region Names for better display
        $kecNames = \App\Models\Region::where('level', 'KEC')->pluck('name', 'code')->toArray();
        $desaNames = \App\Models\Region::where('level', 'DESA')->pluck('name', 'code')->toArray();

        foreach ($results as $row) {
            $kCode = sprintf('%03d', (int) $row->kdkec);
            $dCode = $kCode . sprintf('%03d', (int) $row->kddesa);

            $kecName = $kecNames[$kCode] ?? 'UNKNOWN';
            $desaName = $desaNames[$dCode] ?? 'UNKNOWN';

            // Special handling for UNKNOWN kdkec
            if ($row->kdkec === 'UNKNOWN') {
                $kecName = 'TIDAK DIKETAHUI';
                $desaName = '-';
            }

            $rows[] = [
                'Kecamatan' => "{$row->kdkec} - $kecName",
                'Desa' => "{$row->kddesa} - $desaName",
                'Jumlah' => $row->total
            ];
            $grandTotal += $row->total;
        }

        $this->table(['Kecamatan', 'Desa', 'Jumlah Data'], $rows);

        $this->info("Grand Total: $grandTotal");
    }
}

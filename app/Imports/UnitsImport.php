<?php

namespace App\Imports;

use App\Models\Unit;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class UnitsImport implements ToModel, WithStartRow, WithChunkReading, WithBatchInserts
{
    public function startRow(): int
    {
        return 3;
    }

    public function model(array $row)
    {
        // Adjust indices based on verification
        // Default assumption: S=18, T=19, K=10, L=11
        // Name usually Column B (1) or F? User didn't specify Name column. 
        // I will assume Name is Column B (1) for now based on previous python output "1: nama_usaha"
        // But I will update this after checking python output.

        // Return null if no kdkec/kddesa to skip empty rows
        if (!isset($row[18])) {
            return null;
        }

        return new Unit([
            'kdkec' => $row[18] ?? '', // S
            'kddesa' => $row[19] ?? '', // T
            'nama_usaha' => $row[1] ?? 'Unknown', // B - Checking this.
            'alamat' => $row[2] ?? null, // C - Checking this.
            'latitude' => $row[10] ?? null, // K
            'longitude' => $row[11] ?? null, // L
            'status_awal' => (!empty($row[10]) && !empty($row[11])) ? 'HAS_COORD' : 'NO_COORD',
            'current_status' => (!empty($row[10]) && !empty($row[11])) ? 'VERIFIED' : 'PENDING',
        ]);
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }
}

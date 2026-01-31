<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UnitController;

Route::get('/', [UnitController::class, 'index'])->name('units.index');
Route::post('/units/{unit}/update', [UnitController::class, 'update'])->name('units.update');
Route::get('/units/export', [UnitController::class, 'export'])->name('units.export');
Route::get('/units/analysis', [App\Http\Controllers\UnitController::class, 'analysis'])->name('units.analysis');
Route::get('/units/rekap', [UnitController::class, 'rekap'])->name('units.rekap');
Route::get('/units/rekap/summary', [UnitController::class, 'getRekapSummary'])->name('units.rekap.summary');
Route::get('/units/rekap/desa/{kdkec}', [UnitController::class, 'getDesaByKecamatan'])->name('units.rekap.desa');
Route::get('/units/rekap/sls/{kdkec}/{kddesa}', [UnitController::class, 'getSlsByDesa'])->name('units.rekap.sls');
Route::get('/api/map-stats', [UnitController::class, 'getMapStats'])->name('api.map_stats');
Route::get('/api/villages/{kecCode}', [UnitController::class, 'getVillages'])->name('api.villages');
Route::get('/units/contributions/{username}', [UnitController::class, 'getUserContributions'])->name('units.contributions');
Route::get('/units/daily/{date}', [UnitController::class, 'getDailyLogs'])->name('units.daily');
Route::get('/units/daily-contributors/{date}', [UnitController::class, 'getDailyContributors'])->name('units.daily_contributors');

Route::get('/download-hasil-verifikasi', function () {
    $filename = 'Hasil_Verifikasi_' . date('d_M_Y_H_i') . '.csv';

    // Header supaya browser langsung download file CSV
    $headers = [
        "Content-type" => "text/csv",
        "Content-Disposition" => "attachment; filename=$filename",
        "Pragma" => "no-cache",
        "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
        "Expires" => "0"
    ];

    $callback = function () {
        $handle = fopen('php://output', 'w');

        // AMBIL SEMUA DATA (Tanpa Filter Kolom)
        // Pakai cursor() supaya RAM hemat
        $units = \App\Models\Unit::cursor();

        $headersWritten = false;

        foreach ($units as $unit) {
            if (empty($unit->raw_data))
                continue;

            $data = $unit->raw_data;

            // Judul Kolom (Header)
            if (!$headersWritten) {
                $cols = array_keys($data);
                $cols[] = 'Keterangan_Data';  // Kolom AG
                $cols[] = 'Petugas_Lapangan'; // Kolom AH
                fputcsv($handle, $cols);
                $headersWritten = true;
            }

            $keys = array_keys($data);
            $statusLabel = 'ORIGINAL';
            $petugas = '-';

            // LOGIKA MENENTUKAN ISI KOLOM
            if ($unit->current_status == 'VERIFIED') {
                // 1. Update Koordinat di CSV sesuai Database
                if (isset($keys[10]))
                    $data[$keys[10]] = $unit->latitude;
                if (isset($keys[11]))
                    $data[$keys[11]] = $unit->longitude;
                // 2. Update Status Keberadaan
                if (isset($keys[12]) && $unit->status_keberadaan) {
                    $data[$keys[12]] = $unit->status_keberadaan;
                }

                // 3. AMBIL TANGGAL UPDATE (PASTI SAMA DENGAN DASHBOARD)
                $tgl = $unit->updated_at;
                $dateStr = '';
                if ($tgl) {
                    try {
                        // Pastikan formatnya benar
                        $c = ($tgl instanceof \Carbon\Carbon) ? $tgl : \Carbon\Carbon::parse($tgl);
                        $dateStr = $c->timezone('Asia/Jakarta')->format('d/m/Y H:i');
                    } catch (\Exception $e) {
                        $dateStr = 'ERROR';
                    }
                } else {
                    $dateStr = 'TGL-KOSONG'; // Harusnya tidak terjadi
                }

                // Isi Kolom AG (Keterangan_Data)
                // Format: UPDATED (28/01/2026 17:00)
                $statusLabel = 'UPDATED (' . $dateStr . ')';

                // Isi Kolom AH (Petugas)
                $petugas = $unit->last_updated_by ?? '-';
            } else {
                // Logika untuk yang belum update
                $statusLabel = ($unit->status_awal == 'HAS_COORD') ? 'ORIGINAL' : 'BELUM UPDATE';
            }

            // Gabungkan data asli dengan 2 kolom tambahan
            $row = array_values($data);
            $row[] = $statusLabel;
            $row[] = $petugas;

            fputcsv($handle, $row);
        }
        fclose($handle);
    };

    return response()->stream($callback, 200, $headers);
});

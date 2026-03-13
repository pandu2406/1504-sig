<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Unit;
use Illuminate\Support\Facades\DB;

$mappings = [
    // format: 'Nama Lama (Oknum)' => 'Nama Baru (Benar)'
    'simon' => 'Simon',
    'DWI' => 'Dwi',
    'Muhammad Nabil Fikri' => 'Nabil',
];

echo "=== Memulai Pembersihan Nama Petugas ===\n";

foreach ($mappings as $oldName => $newName) {
    // 1. Update last_updated_by
    $countLast = Unit::where('last_updated_by', $oldName)->update(['last_updated_by' => $newName]);

    // 2. Update first_updated_by
    $countFirst = Unit::where('first_updated_by', $oldName)->update(['first_updated_by' => $newName]);

    if ($countLast > 0 || $countFirst > 0) {
        echo "[MATCH] Mengubah '$oldName' menjadi '$newName' ($countLast Last, $countFirst First)\n";
    }
}

// Tambahan: Handle Case Sensitivity (kecuali yang sudah di mapping khusus)
$allNames = Unit::whereNotNull('last_updated_by')->distinct()->pluck('last_updated_by')->toArray();
foreach ($allNames as $name) {
    // Jika ada yang sama persis secara lowercase tapi beda penulisan
    $normalized = trim($name);
    if ($normalized !== $name) {
        Unit::where('last_updated_by', $name)->update(['last_updated_by' => $normalized]);
        Unit::where('first_updated_by', $name)->update(['first_updated_by' => $normalized]);
        echo "[FIX] Trim spasi untuk '$name'\n";
    }
}

echo "\nSelesai! Poin petugas sudah menyatu.\n";

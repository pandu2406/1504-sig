<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Unit;

$names = Unit::whereNotNull('latitude')
    ->where('latitude', '!=', '')
    ->whereNotNull('last_updated_by')
    ->where('last_updated_by', '!=', '')
    ->distinct()
    ->pluck('last_updated_by')
    ->toArray();

echo "=== Total Petugas Unik: " . count($names) . " ===\n\n";

$potentialDuplicates = [];

foreach ($names as $i => $name1) {
    foreach ($names as $j => $name2) {
        if ($i >= $j)
            continue;

        $n1 = strtolower(trim($name1));
        $n2 = strtolower(trim($name2));

        // Substring match
        if (strpos($n1, $n2) !== false || strpos($n2, $n1) !== false) {
            $potentialDuplicates[] = [$name1, $name2, 'Kemiripan Nama (Substring)'];
            continue;
        }

        // Levenshtein distance for typos (threshold 3)
        $lev = levenshtein($n1, $n2);
        if ($lev > 0 && $lev <= 3) {
            $potentialDuplicates[] = [$name1, $name2, "Beda Tipis ($lev karakter)"];
        }
    }
}

if (empty($potentialDuplicates)) {
    echo "Tidak ditemukan nama yang mirip secara otomatis.\n";
} else {
    echo "Daftar Potensi Nama Ganda:\n";
    echo str_repeat("-", 60) . "\n";
    printf("%-25s | %-25s | %s\n", "Nama A", "Nama B", "Alasan");
    echo str_repeat("-", 60) . "\n";
    foreach ($potentialDuplicates as $pair) {
        printf("%-25s | %-25s | %s\n", $pair[0], $pair[1], $pair[2]);
    }
}

echo "\nSemua Nama Unik Petugas:\n";
sort($names);
foreach (array_chunk($names, 3) as $chunk) {
    echo implode(" | ", array_map(fn($n) => str_pad($n, 25), $chunk)) . "\n";
}

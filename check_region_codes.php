<?php
// Load Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check Region Codes
echo "Checking Region Codes...\n";
$kec = \App\Models\Region::where('level', 'KEC')->take(5)->get();
foreach ($kec as $k) {
    echo "KEC: " . $k->code . " - " . $k->name . "\n";
}

$desa = \App\Models\Region::where('level', 'DESA')->take(5)->get();
foreach ($desa as $d) {
    echo "DESA: " . $d->code . " - " . $d->name . " (Parent: " . $d->parent_code . ")\n";
}

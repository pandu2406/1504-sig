<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Searching for specific regions...\n";

// Target: Kec 042, Desa 004
$kecCode = '042';
$desaSuffix = '004';

// Check Kecamatan
$kec = \App\Models\Region::where('level', 'KEC')->where('code', 'like', "%$kecCode%")->first();
if ($kec) {
    echo "Found KEC: {$kec->code} - {$kec->name}\n";

    // Check Desa with precise parent
    $desaCode = $kec->code . $desaSuffix;
    echo "Looking for DESA with code: $desaCode\n";
    $desa = \App\Models\Region::where('level', 'DESA')->where('code', $desaCode)->first();
    if ($desa) {
        echo "Found DESA: {$desa->code} - {$desa->name}\n";
    } else {
        echo "DESA not found via direct concatenation.\n";
        // Check children
        $children = \App\Models\Region::where('level', 'DESA')->where('parent_code', $kec->code)->get();
        echo "Children of {$kec->code} count: " . $children->count() . "\n";
        foreach ($children as $c) {
            echo "- {$c->code} : {$c->name}\n";
        }
    }
} else {
    echo "KEC $kecCode not found.\n";
    // List all Kec to be sure
    $allKec = \App\Models\Region::where('level', 'KEC')->pluck('code', 'name');
    print_r($allKec);
}

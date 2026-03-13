<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$unit = \App\Models\Unit::whereNotNull('latitude')->first();
if ($unit) {
    echo "LAT: " . $unit->latitude . "\nLONG: " . $unit->longitude . "\n";
    // Also try to call the controller directly if possible, or just print the URL to test.
    echo "TEST URL: http://localhost:8001/api/check-sls?lat=" . $unit->latitude . "&lng=" . $unit->longitude . "\n";
} else {
    echo "No unit with coordinates found.\n";
}

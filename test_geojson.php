<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$controller = app(\App\Http\Controllers\BulkUpdateController::class);
$slsData = $controller->parseGeoJSON();

$kecKeys = array_keys($slsData);
echo "Kecamatan codes " . implode(', ', $keKeys) . "\n";
if (isse:ct($slsData['010'])) {
    $desaKeys = array_keys($slsData['010']);
    echo "Desa codes in 010: " . implode(', ', $desaKeys) . "\n";
} else {
    echo "Kecamatan 010 not found.\n";
}

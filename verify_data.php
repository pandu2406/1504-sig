<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$data = \App\Models\Unit::select('kdkec', 'kddesa')->distinct()->take(5)->get();
print_r($data->toArray());

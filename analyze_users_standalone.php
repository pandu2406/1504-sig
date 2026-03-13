<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

// --- LOGIC ---
$units = \App\Models\Unit::whereNotNull('last_updated_by')->where('last_updated_by', '!=', '')->get();

$stats = [];
foreach ($units as $u) {
    if (!$u->last_updated_by)
        continue;

    // Normalize string (trim + lowercase)
    $original = trim($u->last_updated_by);
    $normalized = strtolower($original);

    if (!isset($stats[$normalized])) {
        $stats[$normalized] = [
            'total' => 0,
            'variants' => []
        ];
    }

    $stats[$normalized]['total']++;

    if (!isset($stats[$normalized]['variants'][$original])) {
        $stats[$normalized]['variants'][$original] = 0;
    }
    $stats[$normalized]['variants'][$original]++;
}

// Sort by Total Descending
uasort($stats, function ($a, $b) {
    return $b['total'] <=> $a['total'];
});

// Output Markdown Table
echo "\n### Tabel Analisis Duplikasi User (Case-Insensitive)\n";
echo "| No | Normalized Name | Total Breakdown | Variants (Count) |\n";
echo "|---:| :--- | :--- | :--- |\n";

$i = 1;
foreach ($stats as $normKey => $data) {
    // Only show if duplicates exist OR run for all top contributors?
    // User requested "identifikasi pada tabel ... user yang memiliki keyword sama"
    // So let's prioritize showing merged entries, but maybe top contributors generally too.

    $variantStrings = [];
    foreach ($data['variants'] as $vName => $vCount) {
        $variantStrings[] = "$vName ($vCount)";
    }
    $vStr = implode(", ", $variantStrings);

    $isMerged = count($data['variants']) > 1 ? "⚠️ **MERGED**" : "";

    // Show top 20 or merged ones specifically
    if ($i <= 20 || count($data['variants']) > 1) {
        echo "| $i | **$normKey** $isMerged | **{$data['total']}** | $vStr |\n";
    }
    $i++;
}
echo "\n";

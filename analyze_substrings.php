<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Get all unique users and their counts
$units = \App\Models\Unit::whereNotNull('last_updated_by')->where('last_updated_by', '!=', '')->get();

$userCounts = [];
foreach ($units as $u) {
    if (!$u->last_updated_by)
        continue;

    // Normalize logic: Lowercase + Trim
    $original = trim($u->last_updated_by);
    $normalized = strtolower($original);

    if (!isset($userCounts[$normalized])) {
        $userCounts[$normalized] = [
            'count' => 0,
            'originals' => []
        ];
    }
    $userCounts[$normalized]['count']++;
    $userCounts[$normalized]['originals'][] = $original; // Just to keep track of variations
}

// 2. Identify Substring Matches
// Sort by length to efficiently check if short is in long
$keys = array_keys($userCounts);
sort($keys); // Alphabetical sort first
// Custom sort by length desc (Longest first, so we don't match "Am" in "Administrator" needlessly, or maybe shortest first?)
// Actually, I want to find if 'angger' is in 'angger halim'.
// Let's iterate all vs all (O(N^2) but N is small, < 100 users probably).

$potentialMerges = [];

foreach ($userCounts as $nameA => $dataA) {
    foreach ($userCounts as $nameB => $dataB) {
        if ($nameA === $nameB)
            continue;

        // Check if A is a substring of B (and A is reasonably long enough, > 3 chars to avoid 'dwi' matching everything?)
        // User example: "Angger" (6 chars).

        if (strlen($nameA) < 4)
            continue; // Skip very short names to avoid false positives? Or maybe list them but mark warning?

        // Exact word match check might be safer? 
        // "Angger" in "Angger Halim" -> Yes.
        // "Angger" in "Tanggerang" -> No (ideally).
        // Let's use simple strpos first.

        if (strpos($nameB, $nameA) !== false) {
            // A is inside B
            // Example: A="angger", B="angger halim ismail"

            // Check word boundaries generally? 
            // If I look for "angger", "mangger" should not match?
            // " $nameB " contains " $nameA "?
            // Let's just output raw matches for user to decide.

            $key = "$nameA -> $nameB";
            if (!isset($potentialMerges[$nameA])) {
                $potentialMerges[$nameA] = [];
            }
            $potentialMerges[$nameA][] = $nameB;
        }
    }
}

// 3. Output Table
echo "\n### Potensi Duplikasi (Substring Match)\n";
echo "| No | Short Name (Count) | Found In Long Name (Count) |\n";
echo "|---:| :--- | :--- |\n";

$i = 1;
foreach ($potentialMerges as $short => $longs) {
    $shortCount = $userCounts[$short]['count'];
    $shortDisplay = "**$short** ($shortCount)";

    $longDisplays = [];
    foreach ($longs as $long) {
        $longCount = $userCounts[$long]['count'];
        $longDisplays[] = "$long ($longCount)";
    }
    $longStr = implode(", ", $longDisplays);

    echo "| $i | $shortDisplay | $longStr |\n";
    $i++;
}

if ($i === 1) {
    echo "| - | Tidak ditemukan match substring yang signifikan | - |\n";
}
echo "\n";

$units = \App\Models\Unit::whereNotNull('last_updated_by')->where('last_updated_by', '!=', '')->get();
$stats = [];
foreach ($units as $u) {
if (!$u->last_updated_by) continue;
$original = $u->last_updated_by;
$normalized = strtolower(trim($original));

if (!isset($stats[$normalized])) {
$stats[$normalized] = ['total' => 0, 'variants' => []];
}

$stats[$normalized]['total']++;
if (!isset($stats[$normalized]['variants'][$original])) {
$stats[$normalized]['variants'][$original] = 0;
}
$stats[$normalized]['variants'][$original]++;
}

// Filter only those with duplicates (multiple variants) OR just list all sorted?
// User said "identifikasi ... jika ada keyword User yang sama".
// I will list those where count($variants) > 1 OR just list top ones.
// Let's sort by Total desc
uasort($stats, function($a, $b) {
return $b['total'] <=> $a['total'];
    });

    echo "\n--- START REPORT ---\n";
    echo "| No | Normalized Name | Total | Variants (Count) |\n";
    echo "|--- | --- | --- | --- |\n";
    $i = 1;
    foreach ($stats as $key => $data) {
    // Only show if interesting? Or all? User said "Top Kontributor", so listing all is safer or top 20.
    // If list is huge, I'll limit. But probably not huge.
    // Check if there are variants to highlight "keyword sama" issues.
    $variantStrings = [];
    foreach ($data['variants'] as $name => $cnt) {
    $variantStrings[] = "$name ($cnt)";
    }
    $vStr = implode(", ", $variantStrings);

    // Highlight if merged
    $isMerged = count($data['variants']) > 1 ? "**MERGED**" : "";

    if ($i <= 50) { // Limit to top 50 echo "| $i | $key $isMerged | {$data['total']} | $vStr |\n" ; } $i++; }
        echo "--- END REPORT ---\n" ; exit();
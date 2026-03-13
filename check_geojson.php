<?php
$path = 'public/sls_1504.geojson';
if (!file_exists($path)) {
    die("File not found at $path\n");
}
$json = file_get_contents($path);
$data = json_decode($json, true);
if (isset($data['features'][0])) {
    echo "Properties: " . json_encode($data['features'][0]['properties'], JSON_PRETTY_PRINT) . "\n";
    echo "Geometry Type: " . $data['features'][0]['geometry']['type'] . "\n";
    // Check first coordinate
    $geom = $data['features'][0]['geometry'];
    if ($geom['type'] === 'Polygon') {
        echo "Example Coord: " . json_encode($geom['coordinates'][0][0]) . "\n";
    }
} else {
    echo "No features found\n";
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Unit;
use Illuminate\Support\Facades\File;

class EnrichSlsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:enrich-sls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enrich Unit data with SLS info from sls_1504.geojson based on coordinates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $geojsonPath = base_path('sls_1504.geojson');
        if (!File::exists($geojsonPath)) {
            $this->error("File not found: $geojsonPath");
            return 1;
        }

        $this->info("Loading GeoJSON...");
        $json = json_decode(File::get($geojsonPath), true);

        if (!$json || !isset($json['features'])) {
            $this->error("Invalid GeoJSON format.");
            return 1;
        }

        $features = $json['features'];
        $this->info("Loaded " . count($features) . " features.");

        // Pre-calculate Bounding Boxes
        $this->info("Pre-calculating Bounding Boxes...");
        foreach ($features as &$feature) {
            $feature['bbox'] = $this->calculateBBox($feature['geometry']);
        }
        unset($feature); // Break reference

        $this->info("Fetching Units with coordinates...");
        // Get units that have coordinates
        $units = Unit::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $this->info("Found " . $units->count() . " units to process.");

        $bar = $this->output->createProgressBar($units->count());
        $bar->start();

        $updatedCount = 0;

        foreach ($units as $unit) {
            $lat = (float) $unit->latitude;
            $lon = (float) $unit->longitude;

            // Simple validation
            if ($lat == 0 || $lon == 0) {
                $bar->advance();
                continue;
            }

            $matchedFeature = null;

            foreach ($features as $feature) {
                // 1. Check Bounding Box first (Fast)
                if (!$this->isInsideBBox($lat, $lon, $feature['bbox'])) {
                    continue;
                }

                // 2. Ray Casting (Precise)
                if ($this->isPointInPolygon($lon, $lat, $feature['geometry'])) {
                    $matchedFeature = $feature;
                    break;
                }
            }

            if ($matchedFeature) {
                $props = $matchedFeature['properties'];

                $rawData = $unit->raw_data ?? [];

                // Add SLS Info
                $rawData['sls_idsls'] = $props['idsls'] ?? null;
                $rawData['sls_nmsls'] = $props['nmsls'] ?? null;
                $rawData['sls_kddesa'] = $props['kddesa'] ?? null;
                $rawData['sls_nmdesa'] = $props['nmdesa'] ?? null;
                $rawData['sls_nmkec'] = $props['nmkec'] ?? null;
                $rawData['sls_kdsls'] = $props['kdsls'] ?? null;

                $unit->raw_data = $rawData;

                // IMPORTANT: Do not update timestamps
                $unit->timestamps = false;
                $unit->save();

                $updatedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done! Updated $updatedCount units with SLS info.");
    }

    private function calculateBBox($geometry)
    {
        $minLat = 90;
        $maxLat = -90;
        $minLon = 180;
        $maxLon = -180;

        $coords = $this->extractCoordinates($geometry);

        foreach ($coords as $coord) {
            $lon = $coord[0];
            $lat = $coord[1];

            if ($lat < $minLat)
                $minLat = $lat;
            if ($lat > $maxLat)
                $maxLat = $lat;
            if ($lon < $minLon)
                $minLon = $lon;
            if ($lon > $maxLon)
                $maxLon = $lon;
        }

        return [
            'min_lat' => $minLat,
            'max_lat' => $maxLat,
            'min_lon' => $minLon,
            'max_lon' => $maxLon,
        ];
    }

    private function isInsideBBox($lat, $lon, $bbox)
    {
        return $lat >= $bbox['min_lat'] && $lat <= $bbox['max_lat'] &&
            $lon >= $bbox['min_lon'] && $lon <= $bbox['max_lon'];
    }

    private function isPointInPolygon($lon, $lat, $geometry)
    {
        // Extract all polygons (Handle Polygon and MultiPolygon)
        $polygons = [];
        if ($geometry['type'] === 'Polygon') {
            $polygons[] = $geometry['coordinates'];
        } elseif ($geometry['type'] === 'MultiPolygon') {
            $polygons = $geometry['coordinates'];
        }

        $inside = false;

        foreach ($polygons as $polygon) {
            // Usually index 0 is the outer ring, others are holes
            // For simplicity, we check if it is inside the outer ring
            // Standard GeoJSON Polygon: [ [outer_ring], [hole1], ... ]

            $ring = $polygon[0];

            // Ray Casting Algorithm
            $count = count($ring);
            $j = $count - 1;

            for ($i = 0; $i < $count; $i++) {
                $xi = $ring[$i][0];
                $yi = $ring[$i][1];
                $xj = $ring[$j][0];
                $yj = $ring[$j][1];

                $intersect = (($yi > $lat) != ($yj > $lat)) &&
                    ($lon < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi);

                if ($intersect) {
                    $inside = !$inside;
                }
                $j = $i;
            }

            // If inside one polygon part of a MultiPolygon, return true immediately causes issues if there are holes? 
            // GeoJSON basic compliance: simple assumption "union of polygons" for MultiPolygon.
            // Valid for this specific task context.
            if ($inside)
                return true;
        }

        return false;
    }

    private function extractCoordinates($geometry)
    {
        // Flatten nested arrays to get list of [lon, lat]
        $flat = [];
        array_walk_recursive($geometry['coordinates'], function ($item) use (&$flat) {
            $flat[] = $item;
        });

        // array_walk_recursive flattens everything to scalars. We need pairs.
        // Re-approach: specific traversal
        $points = [];
        if ($geometry['type'] === 'Polygon') {
            foreach ($geometry['coordinates'][0] as $p)
                $points[] = $p;
        } elseif ($geometry['type'] === 'MultiPolygon') {
            foreach ($geometry['coordinates'] as $poly) {
                foreach ($poly[0] as $p)
                    $points[] = $p;
            }
        }
        return $points;
    }
}

<?php

namespace App\Services;

class GeoService
{
    protected static $geoJsonData = null;

    public function pointInPolygon($point, $polygon)
    {
        // Ray casting algorithm
        $x = $point[0];
        $y = $point[1];
        $inside = false;
        $n = count($polygon);

        // P1
        $p1 = $polygon[0];
        $p1x = $p1[0];
        $p1y = $p1[1];

        for ($i = 0; $i <= $n; $i++) {
            // P2
            $p2 = $polygon[$i % $n];
            $p2x = $p2[0];
            $p2y = $p2[1];

            if ($y > min($p1y, $p2y)) {
                if ($y <= max($p1y, $p2y)) {
                    if ($x <= max($p1x, $p2x)) {
                        if ($p1y != $p2y) {
                            $xinters = ($y - $p1y) * ($p2x - $p1x) / ($p2y - $p1y) + $p1x;
                        } else {
                            $xinters = $p1x; // fallback
                        }

                        if ($p1x == $p2x || $x <= $xinters) {
                            $inside = !$inside;
                        }
                    }
                }
            }
            $p1x = $p2x;
            $p1y = $p2y;
        }

        return $inside;
    }

    public function checkPosition($lat, $lng)
    {
        if (self::$geoJsonData === null) {
            $path = base_path('sls_1504.geojson');
            if (!file_exists($path)) {
                $path = public_path('sls_1504.geojson');
                if (!file_exists($path)) {
                    return ['success' => false, 'message' => 'GeoJSON file not found'];
                }
            }

            $jsonContent = file_get_contents($path);
            self::$geoJsonData = json_decode($jsonContent, true);
        }

        $data = self::$geoJsonData;
        if (!$data || !isset($data['features'])) {
            return ['success' => false, 'message' => 'Invalid GeoJSON'];
        }

        foreach ($data['features'] as $feature) {
            $geom = $feature['geometry'];
            $type = $geom['type'];
            $coords = $geom['coordinates'];
            $match = false;

            if ($type === 'Polygon') {
                foreach ($coords as $poly) {
                    if ($this->pointInPolygon([$lng, $lat], $poly)) {
                        $match = true;
                        break;
                    }
                }
            } elseif ($type === 'MultiPolygon') {
                foreach ($coords as $multipoly) {
                    foreach ($multipoly as $poly) {
                        if ($this->pointInPolygon([$lng, $lat], $poly)) {
                            $match = true;
                            break 2;
                        }
                    }
                }
            }

            if ($match) {
                $props = $feature['properties'];
                $idsls = ($props['kdprov'] ?? '') .
                    ($props['kdkab'] ?? '') .
                    ($props['kdkec'] ?? '') .
                    ($props['kddesa'] ?? '') .
                    ($props['kdsls'] ?? '') .
                    ($props['kdsubsls'] ?? '');

                return [
                    'success' => true,
                    'idsls' => $idsls,
                    'nmsls' => $props['nmsls'] ?? 'UNKNOWN',
                    'nmdesa' => $props['nmdesa'] ?? 'UNKNOWN',
                    'nmkec' => $props['nmkec'] ?? 'UNKNOWN',
                    'kdsubsls' => $props['kdsubsls'] ?? '00',
                    'full_data' => $props
                ];
            }
        }

        return ['success' => false, 'message' => 'Not found in any SLS'];
    }
}

import sys
import json
import os

def point_in_polygon(point, polygon):
    # Ray casting algorithm for manual check if shapely is missing
    x, y = point
    inside = False
    n = len(polygon)
    p1x, p1y = polygon[0][:2]
    for i in range(n + 1):
        p2x, p2y = polygon[i % n][:2]
        if y > min(p1y, p2y):
            if y <= max(p1y, p2y):
                if x <= max(p1x, p2x):
                    if p1y != p2y:
                        xinters = (y - p1y) * (p2x - p1x) / (p2y - p1y) + p1x
                    if p1x == p2x or x <= xinters:
                        inside = not inside
        p1x, p1y = p2x, p2y
    return inside

def check_feature(feature, lon, lat):
    geom = feature.get('geometry', {})
    gtype = geom.get('type')
    coords = geom.get('coordinates', [])
    
    if gtype == 'Polygon':
        for poly in coords:
            # GeoJSON polygons are lists of rings
            if point_in_polygon((lon, lat), poly):
                return True
    elif gtype == 'MultiPolygon':
        for multipoly in coords:
            for poly in multipoly:
                 if point_in_polygon((lon, lat), poly):
                    return True
    return False

def main():
    if len(sys.argv) < 3:
        print(json.dumps({"error": "Usage: python lookup_sls.py <lat> <long>"}))
        sys.exit(1)

    try:
        lat = float(sys.argv[1])
        lon = float(sys.argv[2])
    except ValueError:
        print(json.dumps({"error": "Invalid coordinates"}))
        sys.exit(1)

    # Try using shapely for robustness if available
    use_shapely = False
    try:
        from shapely.geometry import shape, Point
        point = Point(lon, lat)
        use_shapely = True
    except ImportError:
        pass

    path = os.path.join(os.path.dirname(__file__), 'sls_1504.geojson')
    if not os.path.exists(path):
        print(json.dumps({"error": "GeoJSON file not found"}))
        sys.exit(1)

    with open(path, 'r', encoding='utf-8') as f:
        data = json.load(f)

    for feature in data.get('features', []):
        match = False
        if use_shapely:
            try:
                polygon = shape(feature['geometry'])
                if polygon.contains(point):
                    match = True
            except Exception:
                continue
        else:
            match = check_feature(feature, lon, lat)

        if match:
            props = feature.get('properties', {})
            # Standardize output
            result = {
                "success": True,
                "idsls": "".join([
                    props.get('kdprov', ''),
                    props.get('kdkab', ''),
                    props.get('kdkec', ''),
                    props.get('kddesa', ''),
                    props.get('kdsls', ''),
                    props.get('kdsubsls', '')
                ]),
                "nmsls": props.get('nmsls', 'UNKNOWN'),
                "nmdesa": props.get('nmdesa', 'UNKNOWN'),
                "nmkec": props.get('nmkec', 'UNKNOWN'),
                "kdsubsls": props.get('kdsubsls', '00'),
                "full_data": props
            }
            print(json.dumps(result))
            sys.exit(0)

    print(json.dumps({"success": False, "message": "Location not inside any SLS"}))

if __name__ == "__main__":
    main()

import sys
import json
import os

# Copy of point_in_polygon function
def point_in_polygon(point, polygon):
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
            if point_in_polygon((lon, lat), poly):
                return True
    elif gtype == 'MultiPolygon':
        for multipoly in coords:
            for poly in multipoly:
                 if point_in_polygon((lon, lat), poly):
                    return True
    return False

def main():
    lat = -1.57303
    lon = 103.114405
    
    path = os.path.join(os.path.dirname(__file__), 'sls_1504.geojson')
    with open(path, 'r', encoding='utf-8') as f:
        data = json.load(f)

    for feature in data.get('features', []):
        if check_feature(feature, lon, lat):
            print(json.dumps(feature['properties'], indent=2))
            return

    print("No match found")

if __name__ == "__main__":
    main()

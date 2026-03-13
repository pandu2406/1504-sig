import json

with open('sls_1504.geojson', 'r') as f:
    data = json.load(f)
    if 'features' in data and len(data['features']) > 0:
        props = data['features'][0]['properties']
        print(list(props.keys()))
    else:
        print("No features found")

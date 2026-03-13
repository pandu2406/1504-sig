import pandas as pd
import json

file_path = 'export_sipw.xlsx'

try:
    df = pd.read_excel(file_path, nrows=5)
    print(json.dumps(df.columns.tolist(), indent=2))
    print(json.dumps(df.iloc[0].astype(str).to_dict(), indent=2))
except Exception as e:
    print(f"Error reading Excel: {e}")

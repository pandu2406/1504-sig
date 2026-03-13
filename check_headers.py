import pandas as pd
import string

file_path = 'sipw_export.csv'

try:
    df = pd.read_csv(file_path, nrows=0)
    headers = df.columns.tolist()
    
    print("HEADERS MAPPING:")
    for i, h in enumerate(headers):
        # Generate column letter (A, B, ... Z, AA, AB)
        col_letter = ""
        n = i + 1
        while n > 0:
            n, remainder = divmod(n - 1, 26)
            col_letter = chr(65 + remainder) + col_letter
            
        print(f"{col_letter} (Index {i}): {h}")
        
except Exception as e:
    print(f"Error: {e}")

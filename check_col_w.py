import pandas as pd
import string

def col2num(col):
    num = 0
    for c in col:
        if c in string.ascii_letters:
            num = num * 26 + (ord(c.upper()) - ord('A')) + 1
    return num - 1

file_path = 'sipw_export.csv'

try:
    # Read first row to get headers
    df = pd.read_csv(file_path, nrows=0)
    headers = df.columns.tolist()
    
    col_w_index = col2num('W')
    
    print(f"Index for Column W: {col_w_index}")
    if col_w_index < len(headers):
        print(f"Header at Column W: {headers[col_w_index]}")
    else:
        print("Column W is out of bounds")
        
    print("\nAll Headers:")
    for i, h in enumerate(headers):
        print(f"{i} ({chr(65+i) if i<26 else '?' }): {h}")
        
except Exception as e:
    print(f"Error: {e}")

import pandas as pd

file_path = 'export_sipw.xlsx'

try:
    df = pd.read_excel(file_path, nrows=0)
    columns = df.columns.tolist()
    
    # Excel Column W is the 23rd column (Index 22)
    target_index = 22 
    
    print(f"Total Columns: {len(columns)}")
    if target_index < len(columns):
        print(f"Header at Index {target_index} (Column W): '{columns[target_index]}'")
    else:
        print(f"Index {target_index} is out of bounds.")

    # Also print surrounding columns to be safe
    start = max(0, target_index - 2)
    end = min(len(columns), target_index + 3)
    print("Surrounding headers:")
    for i in range(start, end):
        print(f"{i}: {columns[i]}")

except Exception as e:
    print(f"Error: {e}")

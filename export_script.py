
import pandas as pd
import numpy as np

# Load Original
# header=1 means Row 2 is header
df = pd.read_excel(r'e:\Ngoding\gc-sbr1504\Gabungan.xlsx', header=1)

# Load Updates
updates = pd.read_csv(r'e:\Ngoding\groundcheck_app\temp_updates.csv', dtype={'idsbr': str})

# Ensure match column is string
df['idsbr'] = df['idsbr'].astype(str)
updates['idsbr'] = updates['idsbr'].astype(str)

# Set index for easy mapping
df.set_index('idsbr', inplace=True)
updates.set_index('idsbr', inplace=True)

# Update values
df.update(updates.rename(columns={'lat_new': 'latitude', 'long_new': 'longitude'}))

# Add Status Column using Map
# We map from updates['status_label'].
# Note: indices must match.
df['Keterangan_Data'] = df.index.map(updates['status_label'])
df['Petugas_Lapangan'] = df.index.map(updates['petugas'])

# Reset index to write back
df.reset_index(inplace=True)

# Save
df.to_excel(r'e:\Ngoding\gc-sbr1504\Gabungan_Export_20260127_075917.xlsx', index=False)
print('Export success')

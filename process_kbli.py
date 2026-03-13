import pandas as pd
import json

df = pd.read_excel('legalitas.xlsx')

df_odd = df[['odd', 'odd 2']].rename(columns={'odd': 'kbli', 'odd 2': 'deskripsi'}).dropna(subset=['kbli'])
df_even = df[['even', 'even 2']].rename(columns={'even': 'kbli', 'even 2': 'deskripsi'}).dropna(subset=['kbli'])

kbli_df = pd.concat([df_odd, df_even], ignore_index=True)

kbli_df['kbli'] = kbli_df['kbli'].astype(str).str.strip()
# remove any floating point .0 if it read as float
kbli_df['kbli'] = kbli_df['kbli'].apply(lambda x: x[:-2] if x.endswith('.0') else x)
kbli_df['deskripsi'] = kbli_df['deskripsi'].astype(str).str.strip()

# Sort by kbli
kbli_df = kbli_df.sort_values('kbli')

kbli_list = kbli_df.to_dict('records')

with open('public/kbli.json', 'w', encoding='utf-8') as f:
    json.dump(kbli_list, f, ensure_ascii=False)

print(f"Saved {len(kbli_list)} KBLI records to public/kbli.json")
# print first 5 to verify
print(kbli_list[:5])


import pandas as pd
import sys

try:
    file_path = r'c:\xampp\htdocs\zb-bancosystem\NATIONAL ROUTE FREQUENCY.xlsx'
    df = pd.read_excel(file_path)
    print("Columns:", df.columns.tolist())
    print("First 20 rows:")
    print(df.head(20).to_string())
except Exception as e:
    print(f"Error reading excel: {e}")

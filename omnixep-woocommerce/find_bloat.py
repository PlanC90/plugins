import os

file_path = r'c:\Users\Ceyhun\Desktop\Projelerim\xepmarket\xepmarket\app\public\wp-content\plugins\omnixep-woocommerce\omnixep-woocommerce.php'

with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
    for i, line in enumerate(f, 1):
        if len(line) > 100000:
            safe_start = "".join([c if ord(c) < 128 else '?' for c in line[:100]])
            print(f"Line {i}: Length {len(line)} - Content start: {safe_start}")

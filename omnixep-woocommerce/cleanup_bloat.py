import os

file_path = r'c:\Users\Ceyhun\Desktop\Projelerim\xepmarket\xepmarket\app\public\wp-content\plugins\omnixep-woocommerce\omnixep-woocommerce.php'
temp_path = file_path + '.tmp'

target_396_start = "        if (strpos($reason, 'ÃƒÆ’"
target_1076_start = "        'jurisdiction_accepted' => 'Republic of TÃƒÆ’"

with open(file_path, 'r', encoding='utf-8', errors='ignore') as f, open(temp_path, 'w', encoding='utf-8') as out:
    for line in f:
        if line.startswith(target_396_start) and len(line) > 1000:
            out.write("        if (strpos($reason, 'Müşteri Şikayeti Üzerine Panelden Kapatıldı') !== false) {\n")
            print("Fixed line 396 (bloat).")
        elif line.startswith(target_1076_start) and len(line) > 1000:
            out.write("        'jurisdiction_accepted' => 'Republic of Türkiye',\n")
            print("Fixed line 1076 (bloat).")
        else:
            out.write(line)

os.replace(temp_path, file_path)
print("Cleanup complete.")

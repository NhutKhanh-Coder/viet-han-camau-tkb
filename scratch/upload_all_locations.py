import ftplib
import os

ftp = ftplib.FTP('ftpupload.net', timeout=15)
ftp.login('if0_41796593', 'T5v3vJeuvOxCI')

# Also create a runner script to execute SQL delete directly on the live server
cleaner_php = """<?php
require_once __DIR__ . '/config.php';
$db = getDB();
$db->query("DELETE FROM mmo_coupons WHERE code IN ('VKC20', 'SINHVIEN10')");
echo json_encode(['status' => 'success', 'deleted' => true]);
"""

local_file = r'c:\xampp\htdocs\tkb\teacher\quanly_ai_accounts.php'
local_sql = r'c:\xampp\htdocs\tkb\api\db_sync_data.sql'

with open(r'c:\xampp\htdocs\tkb\scratch\clean_remote.php', 'w', encoding='utf-8') as f:
    f.write(cleaner_php)

locations = [
    'htdocs/tkb/teacher',
    'viethan.free.nf/htdocs/tkb/teacher',
    'viethan.free.nf/teacher',
]

for loc in locations:
    try:
        ftp.cwd('/')
        ftp.cwd(loc)
        print(f"Uploading to {loc}...")
        with open(local_file, 'rb') as f:
            ftp.storbinary('STOR quanly_ai_accounts.php', f)
        print(f"Success uploaded quanly_ai_accounts.php to {loc}")
    except Exception as e:
        print(f"Failed to upload to {loc}: {e}")

# Upload clean_remote.php to tkb root in all domains
root_locations = [
    'htdocs/tkb',
    'viethan.free.nf/htdocs/tkb',
    'htdocs',
    'viethan.free.nf/htdocs'
]

for rloc in root_locations:
    try:
        ftp.cwd('/')
        ftp.cwd(rloc)
        print(f"Uploading clean_remote.php to {rloc}...")
        with open(r'c:\xampp\htdocs\tkb\scratch\clean_remote.php', 'rb') as f:
            ftp.storbinary('STOR clean_remote.php', f)
        print(f"Success uploaded clean_remote.php to {rloc}")
    except Exception as e:
        print(f"Failed root {rloc}: {e}")

ftp.quit()
print("FTP COMPLETE!")

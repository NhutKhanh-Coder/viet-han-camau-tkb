import ftplib

ftp = ftplib.FTP('ftpupload.net', timeout=15)
ftp.login('if0_41796593', 'T5v3vJeuvOxCI')

inspector_code = '''<?php
require_once __DIR__ . '/config.php';
$db = getDB();

echo "=== MON_HOC ===\\n";
$r = $db->query("SELECT * FROM mon_hoc");
if ($r) {
    while ($row = $r->fetch_assoc()) echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\\n";
} else {
    echo "Error: " . $db->error . "\\n";
}

echo "\\n=== THOI_KHOA_BIEU ===\\n";
$r = $db->query("SELECT * FROM thoi_khoa_bieu");
if ($r) {
    while ($row = $r->fetch_assoc()) echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\\n";
} else {
    echo "Error: " . $db->error . "\\n";
}

echo "\\n=== LESSONS ===\\n";
$r = $db->query("SELECT * FROM lessons");
if ($r) {
    while ($row = $r->fetch_assoc()) echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\\n";
} else {
    echo "Error: " . $db->error . "\\n";
}

echo "\\n=== TAI_LIEU ===\\n";
$r = $db->query("SELECT * FROM tai_lieu");
if ($r) {
    while ($row = $r->fetch_assoc()) echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\\n";
} else {
    echo "Error: " . $db->error . "\\n";
}
'''

with open(r'c:\xampp\htdocs\tkb\scratch\remote_inspect_all.php', 'w', encoding='utf-8') as f:
    f.write(inspector_code)

for loc in ['htdocs/tkb', 'viethan.free.nf/htdocs/tkb']:
    try:
        ftp.cwd('/')
        ftp.cwd(loc)
        with open(r'c:\xampp\htdocs\tkb\scratch\remote_inspect_all.php', 'rb') as f:
            ftp.storbinary('STOR remote_inspect_all.php', f)
        print(f"Uploaded to {loc}")
    except Exception as e:
        print(f"Failed {loc}: {e}")

ftp.quit()
print("Uploaded inspector.")

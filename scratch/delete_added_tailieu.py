import sys
import ftplib
sys.stdout.reconfigure(encoding='utf-8')
from run_remote import run_remote_php

# 1. Delete rows from tai_lieu table on remote database
db_cleanup_code = """<?php
require_once __DIR__ . '/config.php';
$db = getDB();

echo "--- BEFORE DELETION ---\\n";
$r1 = $db->query("SELECT id, ten_tai_lieu FROM tai_lieu");
while ($row = $r1->fetch_assoc()) {
    echo "Doc ID " . $row['id'] . ": " . $row['ten_tai_lieu'] . "\\n";
}

$del = $db->query("DELETE FROM tai_lieu WHERE ten_tai_lieu LIKE '%KỊCH BẢN VIDEO%' OR id IN (5, 6, 8)");
if ($del) {
    echo "Deleted rows count: " . $db->affected_rows . "\\n";
} else {
    echo "Delete error: " . $db->error . "\\n";
}

echo "--- AFTER DELETION ---\\n";
$r2 = $db->query("SELECT id, ten_tai_lieu FROM tai_lieu");
if ($r2 && $r2->num_rows > 0) {
    while ($row = $r2->fetch_assoc()) {
        echo "Doc ID " . $row['id'] . ": " . $row['ten_tai_lieu'] . "\\n";
    }
} else {
    echo "No documents remaining in tai_lieu table.\\n";
}
"""

print("Executing DB deletion on remote server...")
result = run_remote_php(db_cleanup_code, 'exec_delete_tailieu.php')
print(result)

# 2. Delete the docx files on FTP
print("\nDeleting docx files via FTP...")
ftp = ftplib.FTP('ftpupload.net', timeout=15)
ftp.login('if0_41796593', 'T5v3vJeuvOxCI')

remote_dirs = [
    'viethan.free.nf/htdocs/tkb/assets/uploads/documents',
    'htdocs/tkb/assets/uploads/documents'
]
files_to_delete = [
    '1789441087_K___CH_B___N_VIDEO_THUY___T_TR__NH_D______N.docx',
    '1789442373_K___CH_B___N_VIDEO_THUY___T_TR__NH_D______N.docx',
    '1789442374_K___CH_B___N_VIDEO_THUY___T_TR__NH_D______N.docx'
]

for rdir in remote_dirs:
    try:
        ftp.cwd('/')
        ftp.cwd(rdir)
        print(f"Checking in {rdir}:")
        file_list = ftp.nlst()
        for fname in files_to_delete:
            if fname in file_list:
                ftp.delete(fname)
                print(f"  Deleted: {fname}")
            else:
                print(f"  Not found: {fname}")
    except Exception as e:
        print(f"Directory {rdir}: {e}")

ftp.quit()
print("\nAll target documents deleted from remote server.")

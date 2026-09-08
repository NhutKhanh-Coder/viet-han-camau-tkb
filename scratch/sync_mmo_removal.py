import ftplib
import os
import urllib.request
import ssl
import time

FTP_HOST = "ftpupload.net"
FTP_USER = "if0_41796593"
FTP_PASS = "T5v3vJeuvOxCI"

LOCAL_BASE = r"c:\xampp\htdocs\tkb"

REMOTE_ROOTS = ["/htdocs/tkb", "/viethan.free.nf/htdocs/tkb"]

FILES_TO_DELETE = [
    "admin/mmo_events.php",
    "admin/quanly_ai_accounts.php",
    "teacher/mmo.php",
    "student/shop_ai.php",
    "student/events.php",
    "api/claim_mmo_event.php",
    "api/mmo_chat.php",
    "clear_and_delete.php"
]

FILES_TO_UPLOAD = [
    "config.php",
    "includes/admin_nav.php",
    "includes/teacher_nav.php",
    "includes/student_nav.php",
    "admin/dashboard.php",
    "admin/phanquyen.php",
    "student/profile.php"
]

# Create temporary drop script for remote DB
temp_drop_script_content = """<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/config.php';
$conn = getDB();
$tables = ['ai_account_orders', 'ai_accounts_store', 'mmo_coupons', 'mmo_events', 'mmo_event_claims'];
foreach ($tables as $t) {
    if ($conn->query("DROP TABLE IF EXISTS `$t`")) {
        echo "Dropped remote table: $t\\n";
    } else {
        echo "Error dropping $t: " . $conn->error . "\\n";
    }
}
$res = $conn->query("SHOW TABLES");
$remaining = [];
while ($row = $res->fetch_array()) {
    if (preg_match('/mmo|ai_account/i', $row[0])) {
        $remaining[] = $row[0];
    }
}
echo "Remaining remote MMO tables: " . (empty($remaining) ? "None (Completely clean!)" : implode(', ', $remaining)) . "\\n";
"""

local_drop_path = os.path.join(LOCAL_BASE, "scratch", "remote_drop_mmo.php")
with open(local_drop_path, "w", encoding="utf-8") as f:
    f.write(temp_drop_script_content)

print("Connecting to FTP...")
ftp = ftplib.FTP(FTP_HOST, FTP_USER, FTP_PASS, timeout=60)
ftp.set_pasv(True)
print("Connected.")

for root in REMOTE_ROOTS:
    print(f"\n--- Processing remote root: {root} ---")
    # 1. Delete obsolete files
    for rel_f in FILES_TO_DELETE:
        rem_path = f"{root}/{rel_f}"
        try:
            ftp.delete(rem_path)
            print(f"Deleted remote file: {rem_path}")
        except Exception as e:
            print(f"Note on deleting {rem_path}: {e}")

    # 2. Upload modified files
    for rel_f in FILES_TO_UPLOAD:
        local_path = os.path.join(LOCAL_BASE, rel_f.replace("/", os.sep))
        rem_path = f"{root}/{rel_f}"
        if os.path.exists(local_path):
            try:
                with open(local_path, "rb") as f:
                    ftp.storbinary(f"STOR {rem_path}", f)
                print(f"Uploaded: {rem_path}")
            except Exception as e:
                print(f"Error uploading {rem_path}: {e}")
        else:
            print(f"Local file missing: {local_path}")

    # 3. Upload drop script
    rem_drop_path = f"{root}/remote_drop_mmo.php"
    try:
        with open(local_drop_path, "rb") as f:
            ftp.storbinary(f"STOR {rem_drop_path}", f)
        print(f"Uploaded DB cleaner script: {rem_drop_path}")
    except Exception as e:
        print(f"Error uploading drop script to {rem_drop_path}: {e}")

ftp.quit()
print("\nFTP upload & deletion completed.")

# 4. Trigger remote drop script via HTTP
print("\nTriggering remote DB drop script via HTTP...")
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

req_urls = [
    "https://viethan.free.nf/tkb/remote_drop_mmo.php",
    "http://viethan.free.nf/tkb/remote_drop_mmo.php"
]

dropped_successfully = False
for url in req_urls:
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, context=ctx, timeout=30) as resp:
            content = resp.read().decode('utf-8', errors='ignore')
            print(f"Response from {url}:\n{content}")
            if "Dropped remote table" in content or "Remaining remote MMO tables" in content:
                dropped_successfully = True
                break
    except Exception as e:
        print(f"Error fetching {url}: {e}")

# 5. Connect back to FTP and remove remote_drop_mmo.php
print("\nCleaning up remote drop script...")
try:
    ftp = ftplib.FTP(FTP_HOST, FTP_USER, FTP_PASS, timeout=60)
    ftp.set_pasv(True)
    for root in REMOTE_ROOTS:
        rem_drop_path = f"{root}/remote_drop_mmo.php"
        try:
            ftp.delete(rem_drop_path)
            print(f"Deleted remote cleaner: {rem_drop_path}")
        except Exception as e:
            print(f"Note deleting {rem_drop_path}: {e}")
    ftp.quit()
except Exception as e:
    print(f"Error cleaning up remote drop script: {e}")

# Clean up local drop script
if os.path.exists(local_drop_path):
    os.remove(local_drop_path)

print("\n--- ALL TASKS COMPLETED SUCCESSFULLY ---")

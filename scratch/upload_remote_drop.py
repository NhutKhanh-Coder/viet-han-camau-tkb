import ftplib
import os

FTP_HOST = "ftpupload.net"
FTP_USER = "if0_41796593"
FTP_PASS = "T5v3vJeuvOxCI"

temp_drop_script_content = """<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/config.php';
$conn = getDB();
$tables = ['ai_account_orders', 'ai_accounts_store', 'mmo_coupons', 'mmo_events', 'mmo_event_claims'];
foreach ($tables as $t) {
    if ($conn->query("DROP TABLE IF EXISTS `$t`")) {
        echo "DROPPED: $t\\n";
    } else {
        echo "ERROR: $t -> " . $conn->error . "\\n";
    }
}
$res = $conn->query("SHOW TABLES");
$remaining = [];
while ($row = $res->fetch_array()) {
    if (preg_match('/mmo|ai_account/i', $row[0])) {
        $remaining[] = $row[0];
    }
}
echo "REMAINING_MMO_TABLES: " . (empty($remaining) ? "NONE_CLEAN" : implode(',', $remaining)) . "\\n";
echo "DONE_REMOTE_DROP_MMO\\n";
"""

local_path = r"c:\xampp\htdocs\tkb\scratch\remote_drop_mmo.php"
with open(local_path, "w", encoding="utf-8") as f:
    f.write(temp_drop_script_content)

ftp = ftplib.FTP(FTP_HOST, FTP_USER, FTP_PASS, timeout=30)
ftp.set_pasv(True)
for root in ["/htdocs/tkb", "/viethan.free.nf/htdocs/tkb"]:
    with open(local_path, "rb") as f:
        ftp.storbinary(f"STOR {root}/remote_drop_mmo.php", f)
    print(f"Uploaded to {root}/remote_drop_mmo.php")
ftp.quit()
print("Uploaded successfully.")

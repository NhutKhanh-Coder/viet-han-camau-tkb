import sys
sys.stdout.reconfigure(encoding='utf-8')
from run_remote import run_remote_php

code = """<?php
require_once __DIR__ . '/config.php';
$db = getDB();
echo "=== LESSONS ON REMOTE ===\\n";
$res = $db->query("SELECT id, giang_vien_id, mon_hoc_id, tieu_de, video_url, created_at FROM lessons");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\\n";
    }
}
"""

print(run_remote_php(code, 'check_remote_lessons.php'))

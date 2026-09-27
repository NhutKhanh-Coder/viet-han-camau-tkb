import sys
sys.stdout.reconfigure(encoding='utf-8')
from run_remote import run_remote_php

code = """<?php
require_once __DIR__ . '/config.php';
$db = getDB();
echo "=== TAI_LIEU ON REMOTE ===\\n";
$res = $db->query("SELECT * FROM tai_lieu");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\\n";
    }
} else {
    echo "Error: " . $db->error . "\\n";
}
"""

print(run_remote_php(code, 'check_remote_tailieu.php'))

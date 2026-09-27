import sys
sys.stdout.reconfigure(encoding='utf-8')
from run_remote import run_remote_php

code = """<?php
require_once __DIR__ . '/config.php';
$db = getDB();
$r = $db->query("SELECT COUNT(*) as c FROM tai_lieu");
echo "Remote tai_lieu count: " . ($r ? $r->fetch_assoc()['c'] : 'error');
"""

print(run_remote_php(code, 'verify_count.php'))

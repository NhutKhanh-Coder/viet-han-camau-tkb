<?php
require_once __DIR__ . '/config.php';
$db = getDB();
$r = $db->query("SELECT COUNT(*) as c FROM tai_lieu");
echo "Remote tai_lieu count: " . ($r ? $r->fetch_assoc()['c'] : 'error');

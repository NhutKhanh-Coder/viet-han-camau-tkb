<?php
require_once __DIR__ . '/config.php';
$db = getDB();

echo "=== GIANG_VIEN ===\n";
$r = $db->query("SELECT id, ma_gv, ho_ten, khoa FROM giang_vien");
while ($row = $r->fetch_assoc()) echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";

<?php
require_once __DIR__ . '/config.php';
$db = getDB();

echo "=== THOI_KHOA_BIEU ===\n";
$res = $db->query("SELECT * FROM thoi_khoa_bieu LIMIT 10");
if ($res) {
    while ($r = $res->fetch_assoc()) echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== ALL MON_HOC ===\n";
$res2 = $db->query("SELECT * FROM mon_hoc");
if ($res2) {
    while ($r = $res2->fetch_assoc()) echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

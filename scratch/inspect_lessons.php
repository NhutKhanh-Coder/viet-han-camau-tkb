<?php
require_once __DIR__ . '/config.php';
$db = getDB();

echo "=== TAI_LIEU TABLE ===\n";
$res1 = $db->query("SELECT t.*, m.ten_mon FROM tai_lieu t LEFT JOIN mon_hoc m ON t.mon_hoc_id = m.id LIMIT 10");
if ($res1) {
    echo "Found " . $res1->num_rows . " rows:\n";
    while ($r = $res1->fetch_assoc()) echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "Query error: " . $db->error . "\n";
}

echo "\n=== LESSONS TABLE ===\n";
$res2 = $db->query("SELECT l.*, m.ten_mon FROM lessons l LEFT JOIN mon_hoc m ON l.mon_hoc_id = m.id LIMIT 10");
if ($res2) {
    echo "Found " . $res2->num_rows . " rows:\n";
    while ($r = $res2->fetch_assoc()) echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "Query error: " . $db->error . "\n";
}

echo "\n=== ALL MON_HOC ===\n";
$res3 = $db->query("SELECT id, ma_mon, ten_mon, khoa FROM mon_hoc LIMIT 10");
if ($res3) {
    while ($r = $res3->fetch_assoc()) echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

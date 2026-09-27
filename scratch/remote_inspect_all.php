<?php
require_once __DIR__ . '/config.php';
$db = getDB();

echo "=== MON_HOC ===\n";
$r = $db->query("SELECT * FROM mon_hoc");
if ($r) {
    while ($row = $r->fetch_assoc()) echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "Error: " . $db->error . "\n";
}

echo "\n=== THOI_KHOA_BIEU ===\n";
$r = $db->query("SELECT * FROM thoi_khoa_bieu");
if ($r) {
    while ($row = $r->fetch_assoc()) echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "Error: " . $db->error . "\n";
}

echo "\n=== LESSONS ===\n";
$r = $db->query("SELECT * FROM lessons");
if ($r) {
    while ($row = $r->fetch_assoc()) echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "Error: " . $db->error . "\n";
}

echo "\n=== TAI_LIEU ===\n";
$r = $db->query("SELECT * FROM tai_lieu");
if ($r) {
    while ($row = $r->fetch_assoc()) echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "Error: " . $db->error . "\n";
}

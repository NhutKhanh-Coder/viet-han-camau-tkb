<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

echo "=== STUDENTS TABLE ===\n";
$res = $db->query("SELECT id, user_id, ma_sv, banner FROM students");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
}

echo "=== SINH_VIEN TABLE ===\n";
$res2 = $db->query("SELECT id, user_id, ma_sv, banner FROM sinh_vien");
if ($res2) {
    while ($row2 = $res2->fetch_assoc()) {
        print_r($row2);
    }
}

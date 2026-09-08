<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

echo "=== CHECKING student_code_storage ===\n";
$res = $db->query("SELECT id, student_id, ten_du_an, ngon_ngu, la_cong_khai FROM student_code_storage");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "ID: {$row['id']} | StudentID: {$row['student_id']} | Name: {$row['ten_du_an']} | Lang: {$row['ngon_ngu']} | Public: {$row['la_cong_khai']}\n";
    }
} else {
    echo "Query error: " . $db->error . "\n";
}

echo "\n=== CHECKING students ===\n";
$sres = $db->query("SELECT id, ho_ten, masv, lop FROM students LIMIT 10");
if ($sres) {
    while ($row = $sres->fetch_assoc()) {
        echo "ID: {$row['id']} | Masv: {$row['masv']} | Name: {$row['ho_ten']} | Lop: {$row['lop']}\n";
    }
}

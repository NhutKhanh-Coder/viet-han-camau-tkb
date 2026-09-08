<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

$res = $db->query("SELECT id, ten_du_an, student_id, mo_ta FROM student_code_storage");
echo "Current projects in DB:\n";
while ($r = $res->fetch_assoc()) {
    echo "- ID: {$r['id']}, Title: {$r['ten_du_an']}, StudentID: {$r['student_id']}\n";
}

$delRes = $db->query("DELETE FROM student_code_storage WHERE ten_du_an LIKE '%keria%' OR mo_ta LIKE '%keria%'");
echo "Deleted keria: " . ($delRes ? "OK (affected: " . $db->affected_rows . ")" : "FAIL: " . $db->error) . "\n";

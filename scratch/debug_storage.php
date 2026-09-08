<?php
require_once __DIR__ . '/../config.php';
$db = getDB();
$res = $db->query('SELECT id, student_id, ten_du_an, ngon_ngu, la_cong_khai FROM student_code_storage');
$rows = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
}
echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

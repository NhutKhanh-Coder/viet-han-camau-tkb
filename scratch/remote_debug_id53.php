<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$db = getDB();
$id = 53;

$res = $db->query("SELECT id, ten_du_an, student_id, la_cong_khai, LENGTH(ma_nguon) as len_ma, SUBSTRING(ma_nguon, 1, 500) as sample FROM student_code_storage WHERE id = $id");
$data = [];
if ($res && $row = $res->fetch_assoc()) {
    $data['storage_row'] = $row;
    $data['json_decode_test'] = @json_decode($row['sample'], true) ? 'SUCCESS' : 'FAILED';
} else {
    $data['storage_row'] = 'NOT FOUND';
}

$fres = $db->query("SELECT file_path, LENGTH(file_content) as file_len FROM student_code_files WHERE storage_id = $id LIMIT 20");
$files = [];
if ($fres) {
    while ($r = $fres->fetch_assoc()) {
        $files[] = $r;
    }
}
$data['student_code_files'] = $files;

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

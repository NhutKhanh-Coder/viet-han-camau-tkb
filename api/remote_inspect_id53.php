<?php
require_once __DIR__ . '/../config.php';
$db = getDB();
$id = 53;

$res = $db->query("SELECT id, ten_du_an, student_id, la_cong_khai, LENGTH(ma_nguon) as len_ma, SUBSTRING(ma_nguon, 1, 500) as sample FROM student_code_storage WHERE id = $id");
$data = [];
if ($res && ($row = $res->fetch_assoc())) {
    $data['storage_row'] = $row;
} else {
    $data['storage_row'] = 'NOT FOUND';
}

$fres = $db->query("SELECT id, file_path, LENGTH(file_content) as file_len, SUBSTRING(file_content, 1, 100) as sample_content FROM student_code_files WHERE storage_id = $id");
$files = [];
if ($fres) {
    while ($r = $fres->fetch_assoc()) {
        $files[] = $r;
    }
}
$data['file_count_in_db_table'] = count($files);
$data['files'] = array_slice($files, 0, 10);

$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents(__DIR__ . '/../scratch/id53_data.txt', $json);
echo "OK";

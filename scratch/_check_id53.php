<?php
require 'c:/xampp/htdocs/tkb/config.php';
$db = getDB();
$res = $db->query('SELECT id, ten_du_an, student_id, la_cong_khai, LENGTH(ma_nguon) as len_ma, SUBSTRING(ma_nguon, 1, 300) as sample FROM student_code_storage WHERE id = 53');
if ($res && $row = $res->fetch_assoc()) {
    print_r($row);
} else {
    echo "Not found ID 53 in local DB\n";
    $res2 = $db->query('SELECT id, ten_du_an FROM student_code_storage ORDER BY id DESC LIMIT 10');
    while ($row2 = $res2->fetch_assoc()) {
        print_r($row2);
    }
}
$fres = $db->query('SELECT COUNT(*) as count_files FROM student_code_files WHERE storage_id = 53');
if ($fres && $frow = $fres->fetch_assoc()) {
    echo "Files in student_code_files for id 53: " . $frow['count_files'] . "\n";
}

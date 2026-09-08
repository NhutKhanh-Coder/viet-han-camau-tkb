<?php
require_once '../config.php';
$db = getDB();
$res = $db->query("SELECT file_path FROM student_code_files WHERE storage_id = 58");
$files = [];
while ($r = $res->fetch_assoc()) {
    $files[] = $r['file_path'];
}
echo "FILES IN DB for 58:\n";
print_r($files);

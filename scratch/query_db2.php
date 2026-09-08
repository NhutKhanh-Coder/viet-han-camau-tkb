<?php
require 'c:/xampp/htdocs/tkb/config.php';
$db = getDB();
$res = $db->query("SELECT file_path FROM student_code_files WHERE storage_id=58");
if ($res) {
    while($r = $res->fetch_assoc()) {
        echo $r['file_path'] . "\n";
    }
}

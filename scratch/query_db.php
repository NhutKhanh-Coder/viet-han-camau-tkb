<?php
require 'c:/xampp/htdocs/tkb/config.php';
$conn = getDB();
$res = $conn->query("SELECT file_path, LENGTH(file_content) as len FROM student_code_files WHERE storage_id=58");
while($r = $res->fetch_assoc()) {
    print_r($r);
}

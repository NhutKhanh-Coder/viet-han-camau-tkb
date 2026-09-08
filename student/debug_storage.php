<?php
require_once '../config.php';

$db = getDB();

ob_start();

echo "=== SESSION INFO ===\n";
print_r($_SESSION);

echo "\n=== ALL ROWS IN student_code_storage ===\n";
$res = $db->query("SELECT id, student_id, ten_du_an, ngon_ngu, la_cong_khai FROM student_code_storage ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Query failed: " . $db->error . "\n";
}

echo "\n=== CHECK TABLE SCHEMA ===\n";
$res2 = $db->query("DESCRIBE student_code_storage");
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        print_r($row);
    }
}

$output = ob_get_clean();

// Write to a text file that can be downloaded via FTP
file_put_contents('debug_output.txt', $output);

echo "OK. Wrote to debug_output.txt";

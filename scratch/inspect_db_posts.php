<?php
require_once 'config.php';
$db = getDB();

echo "=== TABLES ===\n";
$res = $db->query('SHOW TABLES');
while ($r = $res->fetch_array()) {
    echo $r[0] . "\n";
}

echo "\n=== ASSIGNMENTS (BAI TAP) ===\n";
$res = $db->query('SELECT * FROM assignments LIMIT 10');
if ($res) {
    while ($r = $res->fetch_assoc()) {
        print_r($r);
    }
}

echo "\n=== TAI LIEU / DOCUMENTS ===\n";
$tables = ['tai_lieu', 'mon_hoc_tai_lieu', 'documents', 'thong_bao', 'practice_sessions', 'quizzes', 'do_an'];
foreach ($tables as $t) {
    $chk = $db->query("SHOW TABLES LIKE '$t'");
    if ($chk && $chk->num_rows > 0) {
        $cnt = $db->query("SELECT COUNT(*) as c FROM $t")->fetch_assoc()['c'];
        echo "Table $t has $cnt rows\n";
    }
}

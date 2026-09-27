<?php
require_once __DIR__ . '/../config.php';
$db = getDB();
$res = $db->query('SELECT id, tieu_de FROM quizzes ORDER BY id DESC LIMIT 15');
if ($res) {
    while ($r = $res->fetch_assoc()) {
        echo $r['id'] . ' | ' . $r['tieu_de'] . PHP_EOL;
    }
} else {
    echo "Query error: " . $db->error;
}

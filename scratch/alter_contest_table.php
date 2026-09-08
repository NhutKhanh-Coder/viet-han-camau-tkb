<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

echo "<pre>\n=== ALTER TABLE mmo_contest_submissions ===\n";

$cols = [];
$resCols = $db->query("SHOW COLUMNS FROM mmo_contest_submissions");
while ($c = $resCols->fetch_assoc()) {
    $cols[] = $c['Field'];
}

if (!in_array('current_round', $cols)) {
    $db->query("ALTER TABLE mmo_contest_submissions ADD COLUMN current_round INT DEFAULT 1 AFTER student_code");
    echo "Added current_round\n";
}
if (!in_array('total_time_seconds', $cols)) {
    $db->query("ALTER TABLE mmo_contest_submissions ADD COLUMN total_time_seconds INT DEFAULT 0 AFTER submitted_at");
    echo "Added total_time_seconds\n";
}

echo "=== COLUMNS AFTER ALTER ===\n";
$res2 = $db->query("SHOW COLUMNS FROM mmo_contest_submissions");
while ($c = $res2->fetch_assoc()) {
    echo " - " . $c['Field'] . " (" . $c['Type'] . ")\n";
}

echo "=== DONE ===\n</pre>";

<?php
require_once __DIR__ . '/../config.php';

$conn = getDbConnection();
if (!$conn) {
    echo "DB connection failed\n";
    exit;
}

echo "Database tables:\n";
$tables = [];
$res = $conn->query("SHOW TABLES");
while ($r = $res->fetch_array()) {
    $tables[] = $r[0];
    echo "- " . $r[0] . "\n";
}

echo "\n--- Searching all tables for 'mmo' ---\n";
foreach ($tables as $t) {
    $colsRes = $conn->query("SHOW COLUMNS FROM `$t`");
    $textCols = [];
    while ($c = $colsRes->fetch_assoc()) {
        $type = strtolower($c['Type']);
        if (strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
            $textCols[] = $c['Field'];
        }
    }
    if (!empty($textCols)) {
        $where = [];
        foreach ($textCols as $col) {
            $where[] = "`$col` LIKE '%mmo%'";
        }
        $sql = "SELECT * FROM `$t` WHERE " . implode(" OR ", $where);
        $searchRes = $conn->query($sql);
        if ($searchRes && $searchRes->num_rows > 0) {
            echo "Table $t has " . $searchRes->num_rows . " rows matching 'mmo':\n";
            while ($row = $searchRes->fetch_assoc()) {
                print_r($row);
            }
        }
    }
}

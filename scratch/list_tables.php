<?php
require_once __DIR__ . '/../config.php';
$db = getDB();
$r = $db->query("SHOW TABLES");
$tables = [];
while ($row = $r->fetch_row()) {
    $tables[] = $row[0];
}
echo "TABLES: " . implode(', ', $tables) . PHP_EOL;

foreach ($tables as $t) {
    if (strpos($t, 'bai') !== false || strpos($t, 'post') !== false || strpos($t, 'assign') !== false || strpos($t, 'thong') !== false || strpos($t, 'tai') !== false) {
        echo "TABLE $t: ";
        $c = $db->query("DESCRIBE $t");
        $cols = [];
        while ($cr = $c->fetch_assoc()) $cols[] = $cr['Field'];
        echo implode(', ', $cols) . PHP_EOL;
    }
}

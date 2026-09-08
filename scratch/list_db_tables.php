<?php
require_once __DIR__ . '/../config.php';
$db = getDB();
$r = $db->query('SHOW TABLES');
echo "All tables in database:\n";
while ($row = $r->fetch_array()) {
    echo "- {$row[0]}\n";
}

<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

echo "=== LOCAL TAI_LIEU TABLE ===\n";
$res = $db->query("SELECT * FROM tai_lieu");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "Error: " . $db->error . "\n";
}

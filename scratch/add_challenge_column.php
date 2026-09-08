<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

echo "<pre>\n=== ADDING challenge_data TO mmo_events ===\n";

$cols = [];
$res = $db->query("SHOW COLUMNS FROM mmo_events");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $cols[] = $r['Field'];
    }
}

if (!in_array('challenge_data', $cols)) {
    echo "Adding challenge_data column...\n";
    $ok = $db->query("ALTER TABLE mmo_events ADD COLUMN challenge_data TEXT NULL AFTER custom_gift_info");
    echo $ok ? "SUCCESS: Added challenge_data column!\n" : "ERROR: " . $db->error . "\n";
} else {
    echo "Column challenge_data ALREADY EXISTS.\n";
}

echo "=== MIGRATION FINISHED ===\n</pre>";

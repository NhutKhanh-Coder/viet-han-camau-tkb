<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

echo "<h3>COLUMNS IN mmo_events:</h3><pre>";
$res = $db->query("SHOW COLUMNS FROM mmo_events");
while ($r = $res->fetch_assoc()) {
    echo $r['Field'] . " (" . $r['Type'] . ")\n";
}
echo "</pre>";

echo "<h3>COLUMNS IN ai_account_orders:</h3><pre>";
$resO = $db->query("SHOW COLUMNS FROM ai_account_orders");
if ($resO) {
    while ($r = $resO->fetch_assoc()) {
        echo $r['Field'] . " (" . $r['Type'] . ")\n";
    }
} else {
    echo "TABLE ai_account_orders DOES NOT EXIST or error: " . $db->error . "\n";
}
echo "</pre>";

echo "<h3>DATA IN mmo_event_claims:</h3><pre>";
$resC2 = $db->query("SELECT * FROM mmo_event_claims LIMIT 10");
while ($r = $resC2->fetch_assoc()) {
    print_r($r);
}
echo "</pre>";

echo "<h3>DATA IN mmo_events:</h3><pre>";
$res2 = $db->query("SELECT * FROM mmo_events");
while ($r2 = $res2->fetch_assoc()) {
    print_r($r2);
}
echo "</pre>";

<?php
require_once __DIR__ . '/../config.php';
$db = getDB();
$res = $db->query("SELECT id, title, claim_type, event_type, secret_code, status FROM mmo_events");
echo "LOCAL DB:\n";
while ($r = $res->fetch_assoc()) {
    print_r($r);
}

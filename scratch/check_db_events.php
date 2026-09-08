<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

echo "<pre>\n=== MMO EVENTS IN DATABASE ===\n";
$res = $db->query("SELECT id, title, status, event_type, claim_type, total_gifts, claimed_count, SUBSTRING(challenge_data, 1, 60) as ch_sample FROM mmo_events ORDER BY id DESC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        print_r($r);
    }
} else {
    echo "Query error: " . $db->error . "\n";
}

echo "\n=== STUDENT CLAIMS ===\n";
$res2 = $db->query("SELECT * FROM mmo_event_claims ORDER BY id DESC LIMIT 10");
if ($res2) {
    while ($r = $res2->fetch_assoc()) {
        print_r($r);
    }
}
echo "</pre>";

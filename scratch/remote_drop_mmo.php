<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/config.php';
$conn = getDB();
$tables = ['ai_account_orders', 'ai_accounts_store', 'mmo_coupons', 'mmo_events', 'mmo_event_claims'];
foreach ($tables as $t) {
    if ($conn->query("DROP TABLE IF EXISTS `$t`")) {
        echo "DROPPED: $t\n";
    } else {
        echo "ERROR: $t -> " . $conn->error . "\n";
    }
}
$res = $conn->query("SHOW TABLES");
$remaining = [];
while ($row = $res->fetch_array()) {
    if (preg_match('/mmo|ai_account/i', $row[0])) {
        $remaining[] = $row[0];
    }
}
echo "REMAINING_MMO_TABLES: " . (empty($remaining) ? "NONE_CLEAN" : implode(',', $remaining)) . "\n";
echo "DONE_REMOTE_DROP_MMO\n";

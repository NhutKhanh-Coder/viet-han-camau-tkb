<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once __DIR__ . '/../config.php';

$conn = getDB();
$tables = ['ai_account_orders', 'ai_accounts_store', 'mmo_coupons', 'mmo_events', 'mmo_event_claims'];
foreach ($tables as $t) {
    $conn->query("DROP TABLE IF EXISTS `$t`");
    echo "Dropped $t\n";
}

$res = $conn->query("SHOW TABLES");
$remaining = [];
while ($row = $res->fetch_array()) {
    if (preg_match('/mmo|ai_account/i', $row[0])) {
        $remaining[] = $row[0];
    }
}
echo "Remaining matching tables: " . (empty($remaining) ? "None (clean!)" : implode(', ', $remaining)) . "\n";

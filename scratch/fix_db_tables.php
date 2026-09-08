<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

echo "<pre>\n=== MIGRATING DATABASE FOR MMO EVENTS ===\n";

// 1. Kiểm tra và bổ sung cột cho mmo_event_claims
$claimsCols = [];
$resC = $db->query("SHOW COLUMNS FROM mmo_event_claims");
if ($resC) {
    while ($r = $resC->fetch_assoc()) {
        $claimsCols[] = $r['Field'];
    }
}

if (!in_array('gift_received', $claimsCols)) {
    echo "Adding column gift_received to mmo_event_claims...\n";
    $db->query("ALTER TABLE mmo_event_claims ADD COLUMN gift_received VARCHAR(255) NULL AFTER student_code");
}
if (!in_array('gift_details', $claimsCols)) {
    echo "Adding column gift_details to mmo_event_claims...\n";
    $db->query("ALTER TABLE mmo_event_claims ADD COLUMN gift_details TEXT NULL AFTER gift_received");
}
if (!in_array('reward_name', $claimsCols)) {
    echo "Adding column reward_name to mmo_event_claims...\n";
    $db->query("ALTER TABLE mmo_event_claims ADD COLUMN reward_name VARCHAR(255) NULL AFTER gift_details");
}
if (!in_array('reward_info', $claimsCols)) {
    echo "Adding column reward_info to mmo_event_claims...\n";
    $db->query("ALTER TABLE mmo_event_claims ADD COLUMN reward_info TEXT NULL AFTER reward_name");
}
if (!in_array('order_id', $claimsCols)) {
    echo "Adding column order_id to mmo_event_claims...\n";
    $db->query("ALTER TABLE mmo_event_claims ADD COLUMN order_id INT(11) DEFAULT 0 AFTER reward_info");
}

// 2. Đồng bộ dữ liệu mmo_event_claims
$db->query("UPDATE mmo_event_claims SET gift_received = reward_name WHERE (gift_received IS NULL OR gift_received = '') AND reward_name IS NOT NULL AND reward_name != ''");
$db->query("UPDATE mmo_event_claims SET gift_details = reward_info WHERE (gift_details IS NULL OR gift_details = '') AND reward_info IS NOT NULL AND reward_info != ''");
$db->query("UPDATE mmo_event_claims SET reward_name = gift_received WHERE (reward_name IS NULL OR reward_name = '') AND gift_received IS NOT NULL AND gift_received != ''");
$db->query("UPDATE mmo_event_claims SET reward_info = gift_details WHERE (reward_info IS NULL OR reward_info = '') AND gift_details IS NOT NULL AND gift_details != ''");
echo "Synced mmo_event_claims columns!\n";

// 3. Chuẩn hóa sự kiện trong mmo_events
$db->query("UPDATE mmo_events SET claim_type = 'wheel' WHERE event_type = 'wheel'");
$db->query("UPDATE mmo_events SET claim_type = 'secret_code' WHERE event_type = 'code' OR (secret_code IS NOT NULL AND secret_code != '')");
$db->query("UPDATE mmo_events SET event_type = 'wheel' WHERE claim_type = 'wheel'");
$db->query("UPDATE mmo_events SET event_type = 'code' WHERE claim_type IN ('code', 'secret_code')");
$db->query("UPDATE mmo_events SET event_type = 'direct' WHERE (claim_type IN ('free', 'direct') OR claim_type IS NULL) AND (event_type IS NULL OR event_type = '' OR event_type = 'free')");

// Đồng bộ tên quà và dữ liệu quà
$db->query("UPDATE mmo_events SET gift_name = reward_name WHERE (gift_name IS NULL OR gift_name = '' OR gift_name = 'Quà Tặng MMO VIP') AND reward_name IS NOT NULL AND reward_name != ''");
$db->query("UPDATE mmo_events SET reward_name = gift_name WHERE (reward_name IS NULL OR reward_name = '') AND gift_name IS NOT NULL AND gift_name != ''");
$db->query("UPDATE mmo_events SET custom_gift_info = reward_data WHERE (custom_gift_info IS NULL OR custom_gift_info = '') AND reward_data IS NOT NULL AND reward_data != ''");
$db->query("UPDATE mmo_events SET reward_data = custom_gift_info WHERE (reward_data IS NULL OR reward_data = '') AND custom_gift_info IS NOT NULL AND custom_gift_info != ''");
$db->query("UPDATE mmo_events SET total_gifts = max_claims WHERE (total_gifts IS NULL OR total_gifts <= 0) AND max_claims > 0");
$db->query("UPDATE mmo_events SET max_claims = total_gifts WHERE (max_claims IS NULL OR max_claims <= 0) AND total_gifts > 0");
echo "Synced mmo_events table rows!\n";

// 4. In ra dữ liệu mmo_events sau khi cập nhật
$chkEv = $db->query("SELECT id, title, event_type, claim_type, secret_code, gift_name, reward_name FROM mmo_events");
echo "\n--- MMO EVENTS AFTER UPDATE ---\n";
while ($row = $chkEv->fetch_assoc()) {
    print_r($row);
}

// 5. In ra columns mmo_event_claims
echo "\n--- MMO EVENT CLAIMS COLUMNS ---\n";
$resCols2 = $db->query("SHOW COLUMNS FROM mmo_event_claims");
while ($r = $resCols2->fetch_assoc()) {
    echo "{$r['Field']} ({$r['Type']})\n";
}

echo "=== DONE ===\n</pre>";

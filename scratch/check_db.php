<?php
require_once __DIR__ . '/../config.php';
$db = getDB();
if (!$db) {
    echo "DB conn failed\n";
    exit;
}
echo "=== USERS TABLE ===\n";
$res = $db->query("DESCRIBE users");
while ($r = $res->fetch_assoc()) {
    echo $r['Field'] . " | " . $r['Type'] . " | " . $r['Null'] . " | " . $r['Default'] . "\n";
}

echo "\n=== CURRENT ADMIN / USERS ===\n";
$res2 = $db->query("SELECT id, username, ho_ten, role, email, sdt FROM users");
while ($r2 = $res2->fetch_assoc()) {
    echo "ID: {$r2['id']} | User: {$r2['username']} | Name: {$r2['ho_ten']} | Role: {$r2['role']} | Email: {$r2['email']}\n";
}

<?php
require_once __DIR__ . '/../config.php';
$db = getDB();
$res = $db->query("SELECT id, username, role, ho_ten FROM users");
while ($r = $res->fetch_assoc()) {
    echo "ID: {$r['id']} | User: {$r['username']} | Role: {$r['role']} | Name: {$r['ho_ten']}\n";
}

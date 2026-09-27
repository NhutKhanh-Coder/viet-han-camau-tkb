<?php
require_once 'config.php';
$db = getDB();
$res = $db->query("SELECT id, username, ho_ten, role FROM users WHERE role='admin'");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | User: {$row['username']} | Name: {$row['ho_ten']}\n";
}

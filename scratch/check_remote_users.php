<?php
$conn = @new mysqli('sql308.infinityfree.com', 'if0_41796593', 'T5v3vJeuvOxCI', 'if0_41796593_truong_caodang');
if ($conn->connect_error) {
    die("Remote DB Error: " . $conn->connect_error . "\n");
}
$res = $conn->query("SELECT id, username, role, ho_ten FROM users");
while ($r = $res->fetch_assoc()) {
    echo "ID: {$r['id']} | User: {$r['username']} | Role: {$r['role']} | Name: {$r['ho_ten']}\n";
}

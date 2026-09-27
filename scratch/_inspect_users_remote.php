<?php
require_once "config.php";
$db = getDB();
$res = $db->query("SELECT id, username, ho_ten, role FROM users WHERE role='admin'");
while ($r = $res->fetch_assoc()) {
    echo "ID: " . $r["id"] . " | User: " . $r["username"] . " | Name: " . $r["ho_ten"] . "<br>";
}
unlink(__FILE__);

<?php
require_once __DIR__ . "/config.php";
$db = getDB();
$res = $db->query("SELECT id, username, ho_ten, role FROM users");
$out = [];
while ($r = $res->fetch_assoc()) {
    $out[] = $r;
}
file_put_contents(__DIR__ . "/_users_dump.json", json_encode($out, JSON_PRETTY_PRINT));
echo "DONE";

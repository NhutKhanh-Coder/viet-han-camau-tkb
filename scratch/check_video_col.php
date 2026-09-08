<?php
require_once __DIR__ . '/../config.php';
$conn = getDB();

$res = $conn->query("SHOW COLUMNS FROM sinh_vien LIKE 'tiktok_video'");
if ($res && $res->num_rows > 0) {
    echo "COL_EXISTS\n";
} else {
    $conn->query("ALTER TABLE sinh_vien ADD COLUMN tiktok_video TEXT NULL AFTER banner");
    echo "COL_ADDED\n";
}
?>

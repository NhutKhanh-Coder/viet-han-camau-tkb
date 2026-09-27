<?php
require_once __DIR__ . '/config.php';
$db = getDB();

echo "--- BEFORE DELETION ---\n";
$r1 = $db->query("SELECT id, ten_tai_lieu FROM tai_lieu");
while ($row = $r1->fetch_assoc()) {
    echo "Doc ID " . $row['id'] . ": " . $row['ten_tai_lieu'] . "\n";
}

$del = $db->query("DELETE FROM tai_lieu WHERE ten_tai_lieu LIKE '%KỊCH BẢN VIDEO%' OR id IN (5, 6, 8)");
if ($del) {
    echo "Deleted rows count: " . $db->affected_rows . "\n";
} else {
    echo "Delete error: " . $db->error . "\n";
}

echo "--- AFTER DELETION ---\n";
$r2 = $db->query("SELECT id, ten_tai_lieu FROM tai_lieu");
if ($r2 && $r2->num_rows > 0) {
    while ($row = $r2->fetch_assoc()) {
        echo "Doc ID " . $row['id'] . ": " . $row['ten_tai_lieu'] . "\n";
    }
} else {
    echo "No documents remaining in tai_lieu table.\n";
}

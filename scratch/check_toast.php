<?php
$content = file_get_contents(__DIR__ . '/../admin/mmo_events.php');
if (strpos($content, 'showToast') !== false) {
    echo "showToast found in admin/mmo_events.php\n";
} else {
    echo "showToast NOT found in admin/mmo_events.php\n";
}

<?php
$content = file_get_contents(__DIR__ . '/../admin/mmo_events.php');
if (preg_match('/function\s+openAddModal\(\)[^{]*\{[^}]*\}/', $content, $m)) {
    echo $m[0] . "\n";
}
if (preg_match('/function\s+closeModal[^{]*\{[^}]*\}/', $content, $m2)) {
    echo $m2[0] . "\n";
}
// check all modal CSS
preg_match_all('/\.modal[^{]*\{[^}]*\}/', $content, $m3);
print_r($m3[0]);

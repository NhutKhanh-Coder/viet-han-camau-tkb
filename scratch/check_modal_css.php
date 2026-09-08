<?php
$content = file_get_contents(__DIR__ . '/../admin/mmo_events.php');
if (preg_match_all('/\.modal-overlay[^{]*\{[^}]*\}/', $content, $m)) {
    print_r($m[0]);
}

<?php
require_once __DIR__ . '/test_all_40_fixed.php';

foreach ($matches as $idx => $m) {
    $qNum = $idx + 1;
    $found = false;
    foreach ($parsed as $p) {
        if ($p['stt'] == $qNum) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "Missing question #$qNum:\n";
        print_r($m);
    }
}

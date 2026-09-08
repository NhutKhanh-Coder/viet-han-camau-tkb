<?php
require_once __DIR__ . '/test_40_perfect.php';

$stts = array_column($parsed, 'stt');
for ($i = 1; $i <= 40; $i++) {
    if (!in_array($i, $stts)) {
        echo "Missing #$i\n";
    }
}

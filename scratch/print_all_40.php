<?php
require_once __DIR__ . '/test_40_perfect.php';

echo "Parsed total count: " . count($parsed) . "\n";
foreach ($parsed as $p) {
    echo "Q" . $p['stt'] . ": " . mb_substr($p['cau_hoi'], 0, 40) . " | " . $p['dap_an_a'] . " | " . $p['dap_an_dung'] . "\n";
}

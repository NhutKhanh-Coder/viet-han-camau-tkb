<?php
require 'scratch/test_enhanced.php';

echo "=== ALL 40 ANSWERS ===\n";
foreach ($res as $i => $q) {
    echo ($i+1) . "." . $q['dap_an_dung'] . " ";
    if (($i+1) % 10 == 0) echo "\n";
}
echo "\n";



<?php
$content = file_get_contents('c:/xampp/htdocs/tkb/scratch/populate_quiz57_40q.php');
$start = strpos($content, '$questions = array (');
$end = strpos($content, ');', $start) + 2;
eval(substr($content, $start, $end - $start));


echo "=== 40 QUESTIONS ANSWER KEY FROM populate_quiz57_40q.php ===\n";
foreach ($questions as $i => $q) {
    echo "Q" . ($i + 1) . ": " . $q['cau_hoi'] . "\n";
    echo "   Ans: " . $q['dap_an_dung'] . " -> " . $q['dap_an_' . strtolower($q['dap_an_dung'])] . "\n";
}

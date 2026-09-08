<?php
require_once __DIR__ . '/test_40_perfect.php';

$pos23 = strpos($docText, 'Câu 23:');
$pos25 = strpos($docText, 'Câu 25:');
echo substr($docText, $pos23, $pos25 - $pos23 + 200);

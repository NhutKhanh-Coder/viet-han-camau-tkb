<?php
require_once __DIR__ . '/test_40_perfect.php';

$qPattern = '/(?:^|\n)\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*(\d+)|\b(\d{1,3})[\.\:\)\/]\s+)\s*[\.\:\)\-\s]*(.*?)(?=(?:\n\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*\d+|\b\d{1,3}[\.\:\)\/]\s+))|$)/su';

preg_match_all($qPattern, $docText, $mAll, PREG_SET_ORDER);
foreach ($mAll as $idx => $m) {
    if (($m[1] ?: $m[2]) == 24) {
        echo "Found Q24 in match index $idx:\n";
        print_r($m);
    }
}

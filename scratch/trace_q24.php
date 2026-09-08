<?php
$block = "Cú pháp để truy cập phần tử thứ 3 của mảng trong VB.Net là gì?
a) arrayName(2)
b) arrayName(3)
c) arrayName[3]
d) arrayName[2]
Đáp án: a";

$blockNorm = preg_replace('/(?:^|\n|\s+)(?:A[\.\:\)]|a[\.\:\)]|\[A\]|\(A\))\s+/u', "\n[OPT_A] ", $block);
echo "After OPT_A:\n$blockNorm\n\n";

$blockNorm = preg_replace('/(?:^|\n|\s+)(?:B[\.\:\)]|b[\.\:\)]|\[B\]|\(B\))\s+/u', "\n[OPT_B] ", $blockNorm);
echo "After OPT_B:\n$blockNorm\n\n";

$blockNorm = preg_replace('/(?:^|\n|\s+)(?:C[\.\:\)]|c[\.\:\)]|\[C\]|\(C\))\s+/u', "\n[OPT_C] ", $blockNorm);
echo "After OPT_C:\n$blockNorm\n\n";

$blockNorm = preg_replace('/(?:^|\n|\s+)(?:D[\.\:\)]|d[\.\:\)]|\[D\]|\(D\))\s+/u', "\n[OPT_D] ", $blockNorm);
echo "After OPT_D:\n$blockNorm\n\n";

$posA = strpos($blockNorm, '[OPT_A]');
$posB = strpos($blockNorm, '[OPT_B]');
$posC = strpos($blockNorm, '[OPT_C]');
$posD = strpos($blockNorm, '[OPT_D]');
echo "posA: $posA, posB: $posB, posC: $posC, posD: $posD\n";

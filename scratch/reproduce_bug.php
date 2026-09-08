<?php
// Suppose the text had no newlines between questions or Word non-breaking spaces
$rawUserText = "Câu 1: Visual Studio .Net là :
a) Hệ điều hành mới của Microsoft
b) Trình soạn thảo văn bản
c) Môi trường phát triển tích hợp (IDE)
d) Trình duyệt web Câu 2 : Phiên bản nào của Visual Studio .Net hỗ trợ phát triển ứng dụng đa nền tảng ?
A. Visual Studio 2010
B. Visual Studio 2013
C. Visual Studio 2017
D. Visual Studio 2015
Câu 2: Mảng trong VB.Net là gì?
A. Một tập hợp các biến có kiểu dữ liệu khác nhau
B. Một tập hợp các biến có cùng kiểu dữ liệu
C. Một hàm chứa nhiều giá trị
D. Một đối tượng lưu trữ duy nhất một giá trị Câu 3: Cú pháp để khai báo mảng trong VB.Net là gì?
A. Dim arrayName As datatype()
B. Var arrayName = datatype()
C. Define arrayName As datatype()
D. Declare arrayName As datatype()";

// 1. Normalize line breaks and spaces
$t = str_replace(["\r\n", "\r", "\xc2\xa0", "\u{00A0}", "&nbsp;"], ["\n", "\n", " ", " ", " "], $rawUserText);

// Look at what happened: if line 105 in quiz.php had:
// $t = preg_replace('/(?<!\n)(?=\s*(?:\[BOLD\]|\*)*\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*\d+|\b\d{1,3}[\.:\/)]\s*))/u', "\n", $t);
// When "Câu 2 :" has space before colon or is inline after text:
// Notice: (?<!\n) before "Câu 2" matches! BUT lookahead in qPat requires (?:\n\s*...)
$t_old = preg_replace('/(?<!\n)(?=\s*(?:\[BOLD\]|\*)*\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*\d+|\b\d{1,3}[\.:\/)]\s*))/u', "\n", $t);

$qPat_old = '/(?:^|\n)\s*(?:\[BOLD\]|\*)*\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*(\d+)|\b(\d{1,3})[\.:\/)]\s*)\s*(?:\[\/BOLD\]|\*)*[\s\.:\)\-]*(.*?)(?=(?:\n\s*(?:\[BOLD\]|\*)*\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*\d+|\b\d{1,3}[\.:\/)]\s*))|$)/su';

preg_match_all($qPat_old, $t_old, $m_old, PREG_SET_ORDER);
echo "Count with old regex: " . count($m_old) . "\n";
foreach ($m_old as $i => $m) {
    echo "Q" . ($i+1) . " (Num {$m[1]}):\n";
    $block = trim($m[3]);
    $norm = $block;
    foreach (['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd'] as $upper => $lower) {
        $norm = preg_replace('/(?:^|\n|\s{2,}|\t|\s+)(?:\[BOLD\]|\*)*\s*(?:' . $upper . '[\.:\)\/\-]|' . $lower . '[\.:\)\/\-]|\[' . $upper . '\]|\(' . $upper . '\)|\[' . $lower . '\]|\(' . $lower . '\))\s*(?:\[\/BOLD\]|\*)*/u', "\n[OPT_$upper] ", $norm);
    }
    $posA = strpos($norm, '[OPT_A]');
    $posB = strpos($norm, '[OPT_B]');
    $posC = strpos($norm, '[OPT_C]');
    $posD = strpos($norm, '[OPT_D]');
    $qTitle = trim(substr($norm, 0, $posA));
    $optA = trim(substr($norm, $posA + 7, $posB - ($posA + 7)));
    $optB = trim(substr($norm, $posB + 7, $posC - ($posB + 7)));
    $optC = trim(substr($norm, $posC + 7, $posD - ($posC + 7)));
    $optD = trim(substr($norm, $posD + 7));
    echo "  Title: $qTitle\n";
    echo "  A: $optA\n";
    echo "  B: $optB\n";
    echo "  C: $optC\n";
    echo "  D: $optD\n\n";
}

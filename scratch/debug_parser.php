<?php
$text = "Câu 1: Visual Studio .Net là : a) Hệ điều hành mới của Microsoft b) Trình soạn thảo văn bản c) Môi trường phát triển tích hợp (IDE) d) Trình duyệt web Câu 2 : Phiên bản nào của Visual Studio .Net hỗ trợ phát triển ứng dụng đa nền tảng ? A. Visual Studio 2010 B. Visual Studio 2013 C. Visual Studio 2017 D. Visual Studio 2015 Câu 3: Mảng trong VB.Net là gì? A. Một tập hợp các biến có kiểu dữ liệu khác nhau B. Một tập hợp các biến có cùng kiểu dữ liệu C. Một hàm chứa nhiều giá trị D. Một đối tượng lưu trữ duy nhất một giá trị Câu 4: Cú pháp để khai báo mảng trong VB.Net là gì? A. Dim arrayName As datatype() B. Var arrayName = datatype() C. Define arrayName As datatype() D. Declare arrayName As datatype()";

// 1. Normalize line breaks and spaces
$t = str_replace(["\r\n", "\r", "\xc2\xa0", "\u{00A0}", "&nbsp;"], ["\n", "\n", " ", " ", " "], $text);

// 2. Ensure newlines before ANY Question pattern
// Notice: If "Câu 2" is preceded by a word or space, put \n
$t = preg_replace('/(?:\s+|\n)*(?=(?:\[BOLD\]|\*)*\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*\d+|\b\d{1,3}\s*[\.:\/)]\s*))/iu', "\n", $t);

$qPat = '/(?:^|\n)\s*(?:\[BOLD\]|\*)*\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*(\d+)|\b(\d{1,3})\s*[\.:\/)]\s*)\s*(?:\[\/BOLD\]|\*)*[\s\.:\)\-]*(.*?)(?=(?:\n\s*(?:\[BOLD\]|\*)*\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*\d+|\b\d{1,3}\s*[\.:\/)]\s*))|$)/siu';

preg_match_all($qPat, $t, $matches, PREG_SET_ORDER);

echo "Total matches on single-line text: " . count($matches) . "\n";
foreach ($matches as $i => $m) {
    echo "--- Q" . ($i+1) . " ---\n" . trim($m[3]) . "\n";
}

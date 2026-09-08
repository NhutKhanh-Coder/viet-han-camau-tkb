<?php
$text = "Câu 1: Visual Studio .Net là:
A. Hệ điều hành mới của Microsoft
B. Trình soạn thảo văn bản
C. Môi trường phát triển tích hợp (IDE)
D. Trình duyệt web

Câu 2: Phiên bản nào của Visual Studio .Net hỗ trợ phát triển ứng dụng đa nền tảng?
A. Visual Studio 2010
B. Visual Studio 2013
C. Visual Studio 2017
D. Visual Studio 2015 Câu 3: Trong quá trình cài đặt Visual Studio .Net, bước nào sau đây là cần thiết?
A. Chọn các ngôn ngữ lập trình và công cụ phát triển
B. Cài đặt thư viện Python
C. Cấu hình trình duyệt web
D. Cập nhật trình điều khiển đồ họa";

// Current logic:
$t = str_replace(["\r\n", "\r"], ["\n", "\n"], $text);
$t = preg_replace('/(?<!\n)(?=\s*(?:\[BOLD\]|\*)*\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*\d+|\b\d{1,3}[\.:\/)]\s*))/u', "\n", $t);

$qPat = '/(?:^|\n)\s*(?:\[BOLD\]|\*)*\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*(\d+)|\b(\d{1,3})[\.:\/)]\s*)\s*(?:\[\/BOLD\]|\*)*[\s\.:\)\-]*(.*?)(?=(?:\n\s*(?:\[BOLD\]|\*)*\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*\d+|\b\d{1,3}[\.:\/)]\s*))|$)/su';
preg_match_all($qPat, $t, $m, PREG_SET_ORDER);

echo "Found " . count($m) . " questions\n";
foreach ($m as $idx => $item) {
    echo "--- Q" . ($idx+1) . " (Num=" . ($item[1] ?: $item[2]) . ") ---\n";
    echo substr($item[3], 0, 100) . "...\n";
}

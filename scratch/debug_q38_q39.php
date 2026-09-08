<?php
$text = "Câu 37: Từ khóa nào trong Select Case được sử dụng để kiểm tra trường hợp mặc định khi không có điều kiện nào thỏa mãn?
a) Else
b) Otherwise
c) Case Else
d) Default
Đáp án: c

Câu 38: Trong vòng lặp For i = 1 To 5, giá trị ban đầu của biến đếm là:
a) 1
b) 5
c) 0
d) -1
Đáp án: a

Câu 39: Trong câu lệnh For i = 1 To 10 Step 2, giá trị của i tăng mỗi lần lặp là:
a) 1
b) 2
c) 3
d) 4
Đáp án: b

Câu 40: Lệnh For i = 10 To 1 Step -1 sẽ:
a) Lặp từ 1 đến 10
b) Lặp từ 10 đến 1
c) Lặp từ 1 đến 10 với bước nhảy 1
d) Lặp từ 1 đến 10 với bước nhảy -1
Đáp án: b";

// Test current qPattern in teacher/quiz.php:
$qPattern = '/(?:^|\n)\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*(\d+)|\b(\d{1,3})[\.\:\/]\s+|\b(\d{1,3})\)\s+(?=[A-ZÀ-Ỹ]))\s*[\.\:\)\-\s]*(.*?)(?=(?:\n\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*\d+|\b\d{1,3}[\.\:\/]\s+|\b\d{1,3}\)\s+(?=[A-ZÀ-Ỹ])))|$)/su';

preg_match_all($qPattern, $text, $matches, PREG_SET_ORDER);
echo "Matches count: " . count($matches) . "\n";
foreach ($matches as $idx => $m) {
    echo "Match $idx -> Q" . ($m[1] ?: $m[2] ?: $m[3]) . ":\n" . $m[4] . "\n-------------------\n";
}

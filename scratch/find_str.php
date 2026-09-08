<?php
$lines = file(__DIR__ . '/../student/ai.php');
foreach ($lines as $num => $line) {
    if (stripos($line, 'chưa thể tạo bản sửa an toàn') !== false || stripos($line, 'không khớp duy nhất') !== false) {
        echo ($num + 1) . ": " . trim($line) . "\n";
    }
}

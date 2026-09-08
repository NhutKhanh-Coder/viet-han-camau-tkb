<?php
$q3 = "[BOLD]Câu 3:[/BOLD] Trong quá trình cài đặt Visual Studio .Net, bước nào sau đây là cần thiết?\n"
    . "[BOLD]A.[/BOLD] Chọn các ngôn ngữ lập trình và công cụ phát triển\n"
    . "[BOLD]B.[/BOLD] Cài đặt thư viện Python\n"
    . "[BOLD]C.[/BOLD] Cấu hình trình duyệt web\n"
    . "[BOLD]D.[/BOLD] Cập nhật trình điều khiển đồ họa\n";

$norm = $q3;
echo "Original:\n$norm\n\n";

foreach (['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd'] as $upper => $lower) {
    $norm = preg_replace('/(?:^|\n|\s{2,}|\t|\s+)(?:\[BOLD\]|\*)*\s*(?:' . $upper . '[\.:\)\/\-]|' . $lower . '[\.:\)\/\-]|\[' . $upper . '\]|\(' . $upper . '\)|\[' . $lower . '\]|\(' . $lower . '\))\s*(?:\[\/BOLD\]|\*)*/u', "\n[OPT_$upper] ", $norm);
    echo "After replacing $upper/$lower:\n$norm\n\n";
}

$posA = strpos($norm, '[OPT_A]');
$posB = strpos($norm, '[OPT_B]');
$posC = strpos($norm, '[OPT_C]');
$posD = strpos($norm, '[OPT_D]');
echo "posA: $posA, posB: $posB, posC: $posC, posD: $posD\n";
echo "optA raw: '" . substr($norm, $posA + 7, $posB - ($posA + 7)) . "'\n";

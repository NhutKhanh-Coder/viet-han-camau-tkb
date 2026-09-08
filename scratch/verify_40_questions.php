<?php
// Load all 40 questions from db_sync_data.sql
$sql = file_get_contents('c:/xampp/htdocs/tkb/api/db_sync_data.sql');
preg_match_all("/INSERT INTO `quiz_questions` .*? VALUES \('\d+', '\d+', '(.*?)', '(.*?)', '(.*?)', '(.*?)', '(.*?)', '(.*?)'\);/", $sql, $matches, PREG_SET_ORDER);

echo "Found " . count($matches) . " questions in SQL dump\n";

// Convert to formatted Word text (simulating realistic teacher document)
$docText = "";
foreach ($matches as $i => $m) {
    $qNum = $i + 1;
    $cauHoi = stripcslashes($m[1]);
    $optA = stripcslashes($m[2]);
    $optB = stripcslashes($m[3]);
    $optC = stripcslashes($m[4]);
    $optD = stripcslashes($m[5]);
    $dung = $m[6];
    
    $docText .= "[BOLD]Câu $qNum:[/BOLD] $cauHoi\n";
    $docText .= "[BOLD]A.[/BOLD] $optA\n";
    $docText .= "[BOLD]B.[/BOLD] $optB\n";
    $docText .= "[BOLD]C.[/BOLD] $optC\n";
    $docText .= "[BOLD]D.[/BOLD] $optD\n";
    $docText .= "Đáp án: $dung\n\n";
}

// Universal parser with the fix
function parseQuestionsFull($text) {
    if (empty(trim($text))) return [];
    $t = str_replace(["\r\n", "\r", "\xc2\xa0", "\u{00A0}", "&nbsp;"], ["\n", "\n", " ", " ", " "], $text);
    
    // Global answer key table
    $ansMap = [];
    if (preg_match('/(?:BẢNG\s*ĐÁP\s*ÁN|ĐÁP\s*ÁN|HƯỚNG\s*DẪN\s*CHẤM|ANSWER\s*KEY|KEY|Đ\/A|ĐA)\s*[:\n\r](.*?)$/isu', $t, $sec)) {
        if (preg_match_all('/(?:Câu\s*|\b)(\d{1,3})\s*[\.:\)\/\-\s]*\s*([A-Da-d])\b/u', $sec[1], $p, PREG_SET_ORDER)) {
            foreach ($p as $m) $ansMap[(int)$m[1]] = strtoupper($m[2]);
        }
    }

    // Newline before question numbers
    $t = preg_replace('/(?<!\n)(?=\s*(?:\[BOLD\]|\*)*\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*\d+|\b\d{1,3}[\.:\/)]\s*))/u', "\n", $t);
    $qPat = '/(?:^|\n)\s*(?:\[BOLD\]|\*)*\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*(\d+)|\b(\d{1,3})[\.:\/)]\s*)\s*(?:\[\/BOLD\]|\*)*[\s\.:\)\-]*(.*?)(?=(?:\n\s*(?:\[BOLD\]|\*)*\s*(?:(?:Câu|CÂU|Question|QUESTION|Bài|BÀI)\s*\d+|\b\d{1,3}[\.:\/)]\s*))|$)/su';

    if (!preg_match_all($qPat, $t, $matches, PREG_SET_ORDER)) return [];

    $questions = [];
    $autoNum = 1;

    foreach ($matches as $m) {
        $qNum = (int)($m[1] ?: $m[2] ?: $autoNum);
        $block = trim($m[3]);
        if (mb_strlen($block) < 4) continue;

        $norm = $block;
        foreach (['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd'] as $upper => $lower) {
            $norm = preg_replace('/(?:^|\n|\s{2,}|\t|\s+)(?:\[BOLD\]|\*)*\s*(?:' . $upper . '[\.:\)\/\-]|' . $lower . '[\.:\)\/\-]|\[' . $upper . '\]|\(' . $upper . '\)|\[' . $lower . '\]|\(' . $lower . '\))\s*(?:\[\/BOLD\]|\*)*/u', "\n[OPT_$upper] ", $norm);
        }

        $posA = strpos($norm, '[OPT_A]');
        $posB = strpos($norm, '[OPT_B]');
        $posC = strpos($norm, '[OPT_C]');
        $posD = strpos($norm, '[OPT_D]');

        $qTitle = '';
        $optA = $optB = $optC = $optD = '';

        if ($posA !== false && $posB !== false) {
            $qTitle = trim(substr($norm, 0, $posA)) ?: "Câu hỏi $qNum";
            $optA = trim(substr($norm, $posA + 7, $posB - ($posA + 7)));
            if ($posC !== false) {
                $optB = trim(substr($norm, $posB + 7, $posC - ($posB + 7)));
                if ($posD !== false) {
                    $optC = trim(substr($norm, $posC + 7, $posD - ($posC + 7)));
                    $optD = trim(substr($norm, $posD + 7));
                } else {
                    $optC = trim(substr($norm, $posC + 7));
                    $optD = '';
                }
            } else {
                $optB = trim(substr($norm, $posB + 7));
                $optC = $optD = '';
            }
        } else {
            $lines = array_values(array_filter(array_map('trim', explode("\n", $block))));
            if (count($lines) >= 3) {
                $qTitle = $lines[0];
                $optA = $lines[1] ?? 'Lựa chọn A';
                $optB = $lines[2] ?? 'Lựa chọn B';
                $optC = $lines[3] ?? 'Lựa chọn C';
                $optD = $lines[4] ?? 'Lựa chọn D';
            } else {
                continue;
            }
        }

        $correct = $ansMap[$qNum] ?? '';

        if (empty($correct) && preg_match('/(?:(?:Đáp\s*án\s*đúng(?:\s*là)?|Đáp\s*án|Answer|ĐA|Đ\/A|Key|Đáp\s*số|Chọn|Phương\s*án)\s*[:\.\-]?|=>|->)\s*([A-Da-d])\b/iu', $block, $mk)) {
            $correct = strtoupper($mk[1]);
        }

        if (empty($correct)) {
            foreach (['A', 'B', 'C', 'D'] as $letter) {
                $lcLetter = strtolower($letter);
                if (stripos($block, "[BOLD]$lcLetter") !== false || stripos($block, "[BOLD]$letter") !== false) {
                    $correct = $letter;
                    break;
                }
            }
        }

        if (empty($correct)) {
            foreach (['A', 'B', 'C', 'D'] as $letter) {
                $lc = strtolower($letter);
                if (preg_match('/(?:\*\s*[' . $letter . $lc . '][\.:\)]|[' . $letter . $lc . '][\.:\)]\s*\*)/u', $block)) {
                    $correct = $letter;
                    break;
                }
            }
        }

        $stripPat = '/\s*(?:(?:Đáp\s*án\s*đúng(?:\s*là)?|Đáp\s*án|Answer|ĐA|Đ\/A|Key|Đáp\s*số|Chọn|Phương\s*án)\s*[:\.\-]?|=>|->)\s*([A-Da-d])(?:\s*[\.\)]|\s*$|\s*\n).*/isu';
        $clean = fn($s) => trim(str_replace(['[BOLD]', '[/BOLD]', '*'], '', preg_replace($stripPat, '', $s)));

        if (!in_array($correct, ['A','B','C','D'])) $correct = 'A';

        $questions[] = [
            'stt' => $qNum,
            'cau_hoi' => $clean($qTitle),
            'dap_an_a' => $clean($optA) ?: 'Lựa chọn A',
            'dap_an_b' => $clean($optB) ?: 'Lựa chọn B',
            'dap_an_c' => $clean($optC) ?: 'Lựa chọn C',
            'dap_an_d' => $clean($optD) ?: 'Lựa chọn D',
            'dap_an_dung' => $correct,
        ];
        $autoNum++;
    }

    return $questions;
}

$results = parseQuestionsFull($docText);
echo "Parsed " . count($results) . " / " . count($matches) . " questions perfectly!\n";

$correctCount = 0;
foreach ($results as $idx => $r) {
    $expected = $matches[$idx][6];
    if ($r['dap_an_dung'] === $expected) $correctCount++;
    else {
        echo "Mismatch at Q" . ($idx+1) . ": Got {$r['dap_an_dung']}, Expected $expected\n";
    }
}
echo "Answer Match Rate: $correctCount / " . count($results) . "\n";

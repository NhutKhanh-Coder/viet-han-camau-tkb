<?php
require_once __DIR__ . '/test_parser.php';

$sql = file_get_contents('c:/xampp/htdocs/tkb/api/db_sync_data.sql');

// Extract the 40 questions inserted into quiz_questions for quiz 14
preg_match_all("/INSERT INTO `quiz_questions` .*? VALUES \('\d+', '14', '(.*?)', '(.*?)', '(.*?)', '(.*?)', '(.*?)', '([A-D])'\);/u", $sql, $matches, PREG_SET_ORDER);

echo "Found " . count($matches) . " raw questions in SQL:\n";

// Reconstruct simulated text document as it appears in a Word doc
$docText = "ĐỀ THI TRẮC NGHIỆM MÔN LẬP TRÌNH VB.NET\n\n";
foreach ($matches as $idx => $m) {
    $qNum = $idx + 1;
    $docText .= "Câu $qNum: " . $m[1] . "\n";
    $docText .= "a) " . $m[2] . "\n";
    $docText .= "b) " . $m[3] . "\n";
    $docText .= "c) " . $m[4] . "\n";
    $docText .= "d) " . $m[5] . "\n";
    $docText .= "Đáp án: " . strtolower($m[6]) . "\n\n";
}

$parsed = parseQuestionsUniversal($docText);
echo "Parsed " . count($parsed) . " questions from simulated Word text.\n";

$missing = [];
$parsedTitles = array_map(fn($p) => mb_substr(trim($p['cau_hoi']), 0, 30), $parsed);

foreach ($matches as $idx => $m) {
    $qNum = $idx + 1;
    $expectedTitle = mb_substr(trim($m[1]), 0, 30);
    $found = false;
    foreach ($parsed as $p) {
        if (mb_stripos($p['cau_hoi'], $expectedTitle) !== false || mb_stripos($expectedTitle, mb_substr(trim($p['cau_hoi']), 0, 20)) !== false) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "MISSING Q$qNum: " . $m[1] . "\n";
        echo "   Opt A: " . $m[2] . "\n";
        echo "   Opt B: " . $m[3] . "\n";
        echo "   Opt C: " . $m[4] . "\n";
        echo "   Opt D: " . $m[5] . "\n\n";
    }
}

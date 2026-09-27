<?php
$teacherQuiz = file_get_contents('c:/xampp/htdocs/tkb/teacher/quiz.php');
$start = strpos($teacherQuiz, 'function parseQuestionsFromTextContent($text)');
$end = strpos($teacherQuiz, 'function analyzeQuestionsAnswersViaAI', $start);
$funcCode = substr($teacherQuiz, $start, $end - $start);
eval($funcCode);

$sql = file_get_contents('c:/xampp/htdocs/tkb/api/db_sync_data.sql');
preg_match_all("/INSERT INTO `quiz_questions` .*? VALUES \('\d+', '14', '(.*?)', '(.*?)', '(.*?)', '(.*?)', '(.*?)', '([A-D])'\);/u", $sql, $matches, PREG_SET_ORDER);

$txt2 = "";
foreach ($matches as $i => $m) {
    $num = $i + 1;
    $txt2 .= "Câu $num: {$m[1]}\n";
    $txt2 .= "A. {$m[2]}\n";
    $txt2 .= "B. {$m[3]}\n";
    $txt2 .= "C. {$m[4]}\n";
    $txt2 .= "D. {$m[5]}\n\n";
}
$p2 = parseQuestionsFromTextContent($txt2);

echo "Total input questions: " . count($matches) . "\n";
echo "Parsed: " . count($p2) . "\n";

$foundNumbers = array_map(fn($q) => $q['stt'], $p2);
for ($n = 1; $n <= 40; $n++) {
    if (!in_array($n, $foundNumbers)) {
        echo "Missing question number: $n -> {$matches[$n-1][1]}\n";
    }
}

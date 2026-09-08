<?php
require_once __DIR__ . '/test_parser.php';

$sql = file_get_contents('c:/xampp/htdocs/tkb/api/db_sync_data.sql');
preg_match_all("/INSERT INTO `quiz_questions` .*? VALUES \('\d+', '14', '(.*?)', '(.*?)', '(.*?)', '(.*?)', '(.*?)', '([A-D])'\);/u", $sql, $matches, PREG_SET_ORDER);

$q24_text = "Câu 24: " . $matches[23][1] . "\n";
$q24_text .= "a) " . $matches[23][2] . "\n";
$q24_text .= "b) " . $matches[23][3] . "\n";
$q24_text .= "c) " . $matches[23][4] . "\n";
$q24_text .= "d) " . $matches[23][5] . "\n";
$q24_text .= "Đáp án: " . $matches[23][6] . "\n";

echo "--- Q24 text ---\n$q24_text\n";
$parsed = parseQuestionsUniversal($q24_text);
print_r($parsed);

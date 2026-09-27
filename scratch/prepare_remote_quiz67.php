<?php
require 'scratch/test_v3.php';

$teacherQuiz = file_get_contents('c:/xampp/htdocs/tkb/teacher/quiz.php');
$start = strpos($teacherQuiz, 'function analyzeQuestionsAnswersViaAI(&$questions)');
$end = strpos($teacherQuiz, 'function singleQuestionSolveViaAI', $start);
$funcCode = substr($teacherQuiz, $start, $end - $start);
eval($funcCode);

// AI solve the 4 questions
analyzeQuestionsAnswersViaAI($parsed);

// Generate SQL or PHP script to update remote Quiz 67
$phpCode = '<?php' . "\n";
$phpCode .= 'ini_set("display_errors", 1);' . "\n";
$phpCode .= 'require_once __DIR__ . "/config.php";' . "\n";
$phpCode .= 'header("Content-Type: text/plain; charset=utf-8");' . "\n";
$phpCode .= '$db = getDB();' . "\n";
$phpCode .= '$targetQuizId = 67;' . "\n";
$phpCode .= '$db->query("DELETE FROM quiz_questions WHERE quiz_id = $targetQuizId");' . "\n";
$phpCode .= '$db->query("DELETE FROM quiz_attempts WHERE quiz_id = $targetQuizId");' . "\n";
$phpCode .= '$db->query("DELETE FROM quiz_exam_codes WHERE quiz_id = $targetQuizId");' . "\n";
$phpCode .= '$stmt = $db->prepare("INSERT INTO quiz_questions (quiz_id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung) VALUES (?, ?, ?, ?, ?, ?, ?)");' . "\n";
$phpCode .= '$inserted = 0;' . "\n";
$phpCode .= '$questions = ' . var_export($parsed, true) . ';' . "\n";
$phpCode .= 'foreach ($questions as $q) {' . "\n";
$phpCode .= '    $c = trim($q["cau_hoi"]);' . "\n";
$phpCode .= '    $a = trim($q["dap_an_a"]);' . "\n";
$phpCode .= '    $b = trim($q["dap_an_b"]);' . "\n";
$phpCode .= '    $cc = trim($q["dap_an_c"]);' . "\n";
$phpCode .= '    $d = trim($q["dap_an_d"]);' . "\n";
$phpCode .= '    $ans = trim($q["dap_an_dung"]);' . "\n";
$phpCode .= '    $stmt->bind_param("issssss", $targetQuizId, $c, $a, $b, $cc, $d, $ans);' . "\n";
$phpCode .= '    if ($stmt->execute()) $inserted++;' . "\n";
$phpCode .= '}' . "\n";
$phpCode .= '$stmt->close();' . "\n";
$phpCode .= 'echo "Successfully inserted $inserted questions into Quiz $targetQuizId\n";' . "\n";

file_put_contents('c:/xampp/htdocs/tkb/scratch/update_remote_quiz67.php', $phpCode);
echo "Generated update_remote_quiz67.php with " . count($parsed) . " questions\n";

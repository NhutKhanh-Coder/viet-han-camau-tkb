<?php
$teacherQuiz = file_get_contents('c:/xampp/htdocs/tkb/teacher/quiz.php');
$start = strpos($teacherQuiz, 'function extractTextFromUploadedFile($tmp_name, $ext)');
$end = strpos($teacherQuiz, 'function ensureDbConnection', $start);
$funcCode = substr($teacherQuiz, $start, $end - $start);
eval($funcCode);

$docx = 'C:/Users/Lê Nhựt Khánh/Downloads/KTHS1.docx';
$txt = extractTextFromUploadedFile($docx, 'docx');

echo "=== FIRST 2000 CHARS OF EXTRACTED TEXT ===\n";
echo substr($txt, 0, 2000);

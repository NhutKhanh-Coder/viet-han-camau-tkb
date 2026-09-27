<?php
$sql = file_get_contents(__DIR__ . '/../api/db_sync_data.sql');
preg_match_all("/INSERT INTO `quiz_questions` .*? VALUES \('(?:[0-9]+)', '14', '(.*?)', '(.*?)', '(.*?)', '(.*?)', '(.*?)', '(.*?)'\);/u", $sql, $matches, PREG_SET_ORDER);

$questions = [];
foreach ($matches as $m) {
    $questions[] = [
        'cau_hoi'     => stripslashes($m[1]),
        'dap_an_a'    => stripslashes($m[2]),
        'dap_an_b'    => stripslashes($m[3]),
        'dap_an_c'    => stripslashes($m[4]),
        'dap_an_d'    => stripslashes($m[5]),
        'dap_an_dung' => $m[6],
    ];
}

$code = '<?php' . "\n";
$code .= "require_once __DIR__ . '/../config.php';\n";
$code .= "\$db = getDB();\n";
$code .= "header('Content-Type: text/plain; charset=utf-8');\n";
$code .= "echo \"=== RE-POPULATING QUIZ 57 WITH 40 QUESTIONS ===\\n\";\n\n";

$code .= "\$qCheck = \$db->query(\"SELECT id, tieu_de FROM quizzes WHERE id = 57\");\n";
$code .= "if (!\$qCheck || \$qCheck->num_rows === 0) {\n";
$code .= "    die(\"ERROR: Quiz 57 not found in database!\\n\");\n";
$code .= "}\n";
$code .= "\$quizRow = \$qCheck->fetch_assoc();\n";
$code .= "echo \"Target Quiz: ID {\$quizRow['id']} - {\$quizRow['tieu_de']}\\n\";\n\n";

$code .= "// Clear existing questions and attempts\n";
$code .= "\$db->query(\"DELETE FROM quiz_questions WHERE quiz_id = 57\");\n";
$code .= "echo \"Cleared old questions. Affected: \" . \$db->affected_rows . \"\\n\";\n";
$code .= "\$db->query(\"DELETE FROM quiz_attempts WHERE quiz_id = 57\");\n";
$code .= "echo \"Cleared old attempts. Affected: \" . \$db->affected_rows . \"\\n\";\n\n";

$code .= "\$questions = " . var_export($questions, true) . ";\n\n";

$code .= "\$stmt = \$db->prepare(\"INSERT INTO quiz_questions (quiz_id, cau_hoi, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung) VALUES (?, ?, ?, ?, ?, ?, ?)\");\n";
$code .= "\$inserted = 0;\n";
$code .= "foreach (\$questions as \$q) {\n";
$code .= "    \$qid = 57;\n";
$code .= "    \$stmt->bind_param('issssss', \$qid, \$q['cau_hoi'], \$q['dap_an_a'], \$q['dap_an_b'], \$q['dap_an_c'], \$q['dap_an_d'], \$q['dap_an_dung']);\n";
$code .= "    if (\$stmt->execute()) {\n";
$code .= "        \$inserted++;\n";
$code .= "    } else {\n";
$code .= "        echo \"Error inserting question: \" . \$stmt->error . \"\\n\";\n";
$code .= "    }\n";
$code .= "}\n";
$code .= "\$stmt->close();\n\n";

$code .= "echo \"Total questions inserted: \$inserted / \" . count(\$questions) . \"\\n\";\n";
$code .= "\$cntRes = \$db->query(\"SELECT count(*) as c FROM quiz_questions WHERE quiz_id = 57\");\n";
$code .= "\$cntRow = \$cntRes->fetch_assoc();\n";
$code .= "echo \"VERIFIED IN DATABASE: Quiz 57 now has \" . \$cntRow['c'] . \" questions!\\n\";\n";

file_put_contents(__DIR__ . '/populate_quiz57_40q.php', $code);
echo "Successfully generated populate_quiz57_40q.php (" . strlen($code) . " bytes)\n";

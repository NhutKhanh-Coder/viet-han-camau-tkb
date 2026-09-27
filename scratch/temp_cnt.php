<?php
require_once __DIR__ . "/config.php";
$db = getDB();
$r = $db->query("SELECT COUNT(*) as c FROM quiz_questions WHERE quiz_id = 67");
$row = $r->fetch_assoc();
echo "QUIZ 67 QUESTIONS COUNT: " . $row["c"] . "\n";

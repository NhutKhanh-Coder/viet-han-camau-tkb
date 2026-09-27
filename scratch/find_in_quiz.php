<?php
$f = file('teacher/quiz.php');
foreach ($f as $i => $l) {
    if (preg_match('/(#0f172a|#1e293b|#334155|#ffffff|background:\s*#fff)/i', $l)) {
        if ($i > 1560) {
            echo ($i + 1) . ": " . trim($l) . "\n";
        }
    }
}

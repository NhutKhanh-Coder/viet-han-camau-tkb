<?php
$content = file_get_contents('c:/xampp/htdocs/tkb/student/dashboard.php');
$pos = strpos($content, 'updateLhLiveClock');
if ($pos !== false) {
    $line = substr_count(substr($content, 0, $pos), "\n") + 1;
    echo "Found updateLhLiveClock at line: $line\n";
    $lines = explode("\n", $content);
    for ($i = max(0, $line - 15); $i < min(count($lines), $line + 25); $i++) {
        echo ($i + 1) . ": " . $lines[$i] . "\n";
    }
} else {
    echo "Not found\n";
}

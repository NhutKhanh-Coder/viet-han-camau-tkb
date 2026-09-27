<?php
$content = file_get_contents('c:/xampp/htdocs/tkb/student/dashboard.php');
$no_php = preg_replace('/<\?php.*?\?>/s', '""', $content);
$no_php = preg_replace('/<\?=.*?\?>/s', '""', $no_php);
preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $no_php, $matches);
foreach ($matches[1] as $idx => $js) {
    $f = "c:/xampp/htdocs/tkb/scratch/dashboard_script_{$idx}.js";
    file_put_contents($f, $js);
    $out = shell_exec("node -c \"$f\" 2>&1");
    echo "Block $idx: " . ($out ? trim($out) : "OK") . "\n";
}

<?php
$content = file_get_contents(__DIR__ . '/../student/ai.php');
preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $content, $matches);

foreach ($matches[1] as $idx => $js) {
    if (trim($js) === '') continue;
    $file = __DIR__ . "/extracted_$idx.js";
    file_put_contents($file, $js);
    $out = shell_exec("node -c " . escapeshellarg($file) . " 2>&1");
    echo "Script $idx: " . ($out ? $out : "OK (No syntax errors)\n");
}

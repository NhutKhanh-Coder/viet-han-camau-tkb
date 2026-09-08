<?php
$pythonCmd = 'C:\\Users\\LNHTKH~1\\AppData\\Local\\Programs\\Python\\Python312\\python.exe';
$scriptPath = 'c:/xampp/htdocs/tkb/api/tts_engine.py';
$text = 'Xin chào, đây là giọng nam Tuấn Hùng kiểm tra ngắn';
$voiceId = 'vi_tuanhung';
$pitch = 1.0;
$rate = 1.0;
$cacheFile = 'c:/xampp/htdocs/tkb/uploads/tts_cache/short_test_tuanhung.mp3';

$cmd = sprintf(
    '%s %s --text %s --voice %s --pitch %s --rate %s --output %s 2>&1',
    $pythonCmd,
    escapeshellarg($scriptPath),
    escapeshellarg($text),
    escapeshellarg($voiceId),
    escapeshellarg((string)$pitch),
    escapeshellarg((string)$rate),
    escapeshellarg($cacheFile)
);

echo "Running: $cmd\n";
$t0 = microtime(true);
$output = shell_exec($cmd);
$dt = round(microtime(true) - $t0, 2);
echo "Time: {$dt}s\n";
echo "Output: $output\n";
echo "File: " . (file_exists($cacheFile) ? 'EXISTS (size: ' . filesize($cacheFile) . ')' : 'MISSING') . "\n";

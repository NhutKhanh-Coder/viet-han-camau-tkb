<?php
$models = json_decode(file_get_contents(__DIR__ . '/formatted_87_models.json'), true);

$jsArray = "        // ===== ALL 87 xKiro MODELS DATASET =====\n        const ALL_MODELS = " . json_encode($models, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ";\n";

$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$pattern = '/\/\/ ===== (?:WORKING|ALL 87) xKiro MODELS DATASET.*?\/\/ ===== AUTHENTIC BRAND SVG LOGOS/s';
$replacement = $jsArray . "\n        // ===== AUTHENTIC BRAND SVG LOGOS";

$newAiPhp = preg_replace($pattern, $replacement, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $newAiPhp);
echo "Successfully updated student/ai.php with " . count($models) . " models!\n";

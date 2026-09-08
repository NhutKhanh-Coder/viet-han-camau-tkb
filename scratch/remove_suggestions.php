<?php
$aiPhp = file_get_contents('c:/xampp/htdocs/tkb/student/ai.php');

$pattern = '/<div class="ai-hero-card" onclick="suggestChat.*?<\/div>\s*<div class="ai-hero-card"/s';
// Actually, I can just use a regex that matches ALL the div hero cards inside the grid up to the label.

$gridPattern = '/<div class="ai-hero-grid">.*?<label for="folderInput" class="ai-hero-card"/s';
$replacement = '<div class="ai-hero-grid">
                            <label for="folderInput" class="ai-hero-card"';

$aiPhp = preg_replace($gridPattern, $replacement, $aiPhp);

file_put_contents('c:/xampp/htdocs/tkb/student/ai.php', $aiPhp);
echo "Removed chat suggestions!\n";

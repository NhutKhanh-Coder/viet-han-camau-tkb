<?php
$aiPhp = file_get_contents('c:/xampp/htdocs/tkb/student/ai.php');

$pattern = '/<div class="ai-hero-grid">.*?<\/div>\s*<\/div>/s';
// Wait, the grid contains a label now:
// <div class="ai-hero-grid">
//    <label for="folderInput" class="ai-hero-card" ...>
//        ...
//    </label>
// </div>
$gridPattern = '/<div class="ai-hero-grid">.*?<\/div>\s*<!-- Messages container -->/s';
$replacement = '<!-- Messages container -->';

$aiPhp = preg_replace($gridPattern, $replacement, $aiPhp);

file_put_contents('c:/xampp/htdocs/tkb/student/ai.php', $aiPhp);
echo "Removed ai-hero-grid entirely!\n";

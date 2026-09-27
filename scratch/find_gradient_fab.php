<?php
$dir = new RecursiveDirectoryIterator('c:/xampp/htdocs/tkb');
$ite = new RecursiveIteratorIterator($dir);
foreach ($ite as $file) {
    if (!$file->isFile()) continue;
    $ext = $file->getExtension();
    if (!in_array($ext, ['php', 'css', 'js', 'html'])) continue;
    $path = $file->getPathname();
    if (strpos($path, '.git') !== false) continue;
    $content = file_get_contents($path);
    if (stripos($content, 'cfab') !== false || stripos($content, 'cToggle') !== false || stripos($content, 'cbox') !== false) {
        echo "Found chat FAB in: $path\n";
    }
}

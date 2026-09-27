<?php
$files = glob('c:/xampp/htdocs/tkb/admin/*.php');
$files[] = 'c:/xampp/htdocs/tkb/teacher/baocao_tien_do.php';

$search = "(!isset(\$_COOKIE['adm_theme']) || \$_COOKIE['adm_theme'] === 'light') ? 'adm-light-mode' : ''";
$replace = "(isset(\$_COOKIE['adm_theme']) && \$_COOKIE['adm_theme'] === 'light') ? 'adm-light-mode' : ''";

$count = 0;
foreach ($files as $f) {
    $content = file_get_contents($f);
    if (strpos($content, $search) !== false) {
        $content = str_replace($search, $replace, $content);
        file_put_contents($f, $content);
        echo "Updated: " . basename($f) . "\n";
        $count++;
    }
}
echo "Total files updated: $count\n";

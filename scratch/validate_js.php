<?php
$html = file_get_contents('c:/xampp/htdocs/tkb/admin/ai_studio.php');
preg_match('/<script>(.+?)<\/script>/s', $html, $matches);
if (!empty($matches[1])) {
    // Replace any PHP short tags inside JS
    $js = preg_replace('/<\?=.+?\?>/', '"test"', $matches[1]);
    file_put_contents('c:/xampp/htdocs/tkb/scratch/extracted.js', $js);
    echo "Extracted JS length: " . strlen($js) . " bytes\n";
    $output = shell_exec('node -c "c:\\xampp\\htdocs\\tkb\\scratch\\extracted.js" 2>&1');
    echo "Node check result:\n" . ($output ?: "Clean syntax! No errors.\n");
} else {
    echo "No script tag found\n";
}

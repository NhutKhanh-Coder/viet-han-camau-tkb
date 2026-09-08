<?php
$file = 'c:/xampp/htdocs/keria/index.html';
if (!file_exists($file)) {
    die("File not found: $file\n");
}

$content = file_get_contents($file);
echo "Original size: " . strlen($content) . "\n";

// Replace PHAN THỊ NHẬT AN / LƯƠNG THỊ ANH THƯ with LÊ ANH THƯ
$modified = str_replace('PHAN THỊ NHẬT AN', 'LÊ ANH THƯ', $content);
$modified = str_replace('Phan Thị Nhật An', 'Lê Anh Thư', $modified);
$modified = str_replace('phan thị nhật an', 'lê anh thư', $modified);
$modified = str_replace('LƯƠNG THỊ ANH THƯ', 'LÊ ANH THƯ', $modified);

if ($modified !== $content) {
    file_put_contents($file, $modified);
    echo "SUCCESS: c:/xampp/htdocs/keria/index.html updated with LÊ ANH THƯ!\n";
} else {
    echo "No changes made, content might already be updated.\n";
}

// Show line 626
$lines = explode("\n", file_get_contents($file));
echo "Line 626: " . trim($lines[625] ?? '') . "\n";

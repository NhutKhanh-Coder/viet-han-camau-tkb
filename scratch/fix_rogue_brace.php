<?php
$aiPhp = file_get_contents('c:/xampp/htdocs/tkb/student/ai.php');

// The problematic code in ai.php looks like this right now:
//         });
// 
//         }
// 
//         async function getFilesRecursivelyFromHandle

$pattern = '/\}\);\s*\}\s*async function getFilesRecursivelyFromHandle/s';
$replacement = "});\n\n        async function getFilesRecursivelyFromHandle";

$aiPhp = preg_replace($pattern, $replacement, $aiPhp);

file_put_contents('c:/xampp/htdocs/tkb/student/ai.php', $aiPhp);
echo "Removed rogue } successfully.\n";

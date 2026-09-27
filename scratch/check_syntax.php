<?php
$files = glob('c:/xampp/htdocs/tkb/admin/*.php');
$files[] = 'c:/xampp/htdocs/tkb/includes/admin_nav.php';
$files[] = 'c:/xampp/htdocs/tkb/teacher/baocao_tien_do.php';

$errors = 0;
foreach ($files as $f) {
    exec("c:\\xampp\\php\\php.exe -l \"$f\"", $output, $return_var);
    if ($return_var !== 0) {
        echo "SYNTAX ERROR in $f: " . implode("\n", $output) . "\n";
        $errors++;
    }
}
if ($errors === 0) {
    echo "ALL " . count($files) . " FILES PASSED PHP SYNTAX CHECK!\n";
}

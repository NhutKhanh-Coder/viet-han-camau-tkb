<?php
$content = file_get_contents('c:/xampp/htdocs/tkb/student/dashboard.php');
preg_match_all('/id=[\'"]([^\'"]*cbox[^\'"]*)[\'"]/i', $content, $m);
print_r($m[1]);

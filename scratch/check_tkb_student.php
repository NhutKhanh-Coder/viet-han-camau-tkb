<?php
$ftp = ftp_connect('ftpupload.net', 21, 30);
if ($ftp && ftp_login($ftp, 'if0_41796593', 'T5v3vJeuvOxCI')) {
    ftp_pasv($ftp, true);
    
    echo "=== Listing /htdocs/tkb/student ===\n";
    $list = ftp_nlist($ftp, 'htdocs/tkb/student');
    print_r($list);
    
    $size = ftp_size($ftp, 'htdocs/tkb/student/ai.php');
    echo "Size of htdocs/tkb/student/ai.php: $size bytes\n";
    
    ftp_close($ftp);
}

<?php
$ftp = ftp_connect('ftpupload.net', 21, 30);
if ($ftp && ftp_login($ftp, 'if0_41796593', 'T5v3vJeuvOxCI')) {
    ftp_pasv($ftp, true);
    
    echo "=== Listing /htdocs ===\n";
    $list1 = ftp_nlist($ftp, 'htdocs');
    print_r($list1);
    
    echo "\n=== Listing /htdocs/tkb ===\n";
    $list2 = ftp_nlist($ftp, 'htdocs/tkb');
    print_r($list2);
    
    echo "\n=== Listing /htdocs/tkb/student ===\n";
    $list3 = ftp_nlist($ftp, 'htdocs/student');
    print_r($list3);
    
    ftp_close($ftp);
}

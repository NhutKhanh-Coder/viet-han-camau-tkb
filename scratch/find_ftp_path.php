<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn = @ftp_connect($ftp_server, 21, 20);
if (!$conn) die("Cannot connect to FTP\n");

if (@ftp_login($conn, $ftp_user, $ftp_pass)) {
    @ftp_pasv($conn, true);
    echo "FTP Connected!\n";

    // List root to find correct path
    echo "=== Root listing ===\n";
    $list = ftp_nlist($conn, '.');
    print_r($list);
    
    echo "\n=== Trying viethan.free.nf ===\n";
    $list2 = ftp_nlist($conn, 'viethan.free.nf');
    if ($list2) print_r($list2);
    else echo "Not found\n";
    
    echo "\n=== Trying viethan.free.nf/htdocs ===\n";
    $list3 = ftp_nlist($conn, 'viethan.free.nf/htdocs');
    if ($list3) print_r($list3);
    else echo "Not found\n";

    echo "\n=== Trying viethan.free.nf/htdocs/tkb ===\n";
    $list4 = ftp_nlist($conn, 'viethan.free.nf/htdocs/tkb');
    if ($list4) print_r(array_slice($list4, 0, 10));
    else echo "Not found\n";

    @ftp_close($conn);
} else {
    echo "FTP Login failed!\n";
}

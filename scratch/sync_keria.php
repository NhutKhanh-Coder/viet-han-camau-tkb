<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn = @ftp_connect($ftp_server, 21, 20);
if (!$conn) die("Cannot connect to FTP\n");

if (@ftp_login($conn, $ftp_user, $ftp_pass)) {
    @ftp_pasv($conn, true);
    echo "FTP Connected!\n";

    @ftp_mkdir($conn, 'viethan.free.nf/htdocs/keria');

    $local = 'c:/xampp/htdocs/keria/index.html';
    $remote = 'viethan.free.nf/htdocs/keria/index.html';

    if (file_exists($local)) {
        if (@ftp_put($conn, $remote, $local, FTP_BINARY)) {
            echo "OK: keria/index.html uploaded successfully!\n";
        } else {
            echo "FAIL: keria/index.html upload failed\n";
        }
    }
    @ftp_close($conn);
    echo "Done!\n";
} else {
    echo "FTP Login failed!\n";
}

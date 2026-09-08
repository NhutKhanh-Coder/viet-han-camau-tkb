<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn = @ftp_connect($ftp_server, 21, 20);
if (!$conn) die("Cannot connect to FTP\n");

if (@ftp_login($conn, $ftp_user, $ftp_pass)) {
    @ftp_pasv($conn, true);
    echo "FTP Connected!\n";

    $local = 'c:/xampp/htdocs/tkb/test_folder_pick.php';
    $remote = 'htdocs/tkb/test_folder_pick.php';
    
    if (@ftp_put($conn, $remote, $local, FTP_BINARY)) {
        echo "Successfully uploaded test_folder_pick.php\n";
    } else {
        echo "Failed to upload\n";
    }
    @ftp_close($conn);
} else {
    echo "FTP Login failed!\n";
}

<?php
set_time_limit(60);
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn_id = @ftp_connect($ftp_server, 21, 30);
if (!$conn_id || !@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("ERROR: FTP login failed!\n");
}
@ftp_pasv($conn_id, true);

$dirs = ['/htdocs/tkb/student', '/viethan.free.nf/htdocs/tkb/student'];
foreach ($dirs as $d) {
    echo "Listing $d:\n";
    $list = @ftp_nlist($conn_id, $d);
    if ($list === false) {
        echo "  Cannot list or dir does not exist.\n";
    } else {
        print_r($list);
    }
}

ftp_close($conn_id);

<?php
set_time_limit(180);
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

echo "Connecting to FTP $ftp_server...\n";
$conn_id = @ftp_connect($ftp_server, 21, 30);
if (!$conn_id || !@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("ERROR: FTP connection/login failed!\n");
}
@ftp_pasv($conn_id, true);

$local_file = 'c:/xampp/htdocs/tkb/student/dashboard.php';
$remote_bases = [
    '/htdocs/tkb/student/dashboard.php',
    '/viethan.free.nf/htdocs/tkb/student/dashboard.php'
];

foreach ($remote_bases as $r) {
    if (@ftp_put($conn_id, $r, $local_file, FTP_BINARY)) {
        echo "SUCCESS: student/dashboard.php -> $r\n";
    } else {
        echo "FAILED: student/dashboard.php -> $r\n";
    }
}

ftp_close($conn_id);
echo "Sync dashboard.php finished!\n";

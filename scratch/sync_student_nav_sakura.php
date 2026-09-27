<?php
set_time_limit(180);
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn_id = @ftp_connect($ftp_server, 21, 30);
if (!$conn_id || !@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("ERROR: FTP login failed!\n");
}
@ftp_pasv($conn_id, true);

$local = 'c:/xampp/htdocs/tkb/includes/student_nav.php';
$remotes = [
    '/htdocs/tkb/includes/student_nav.php',
    '/viethan.free.nf/htdocs/tkb/includes/student_nav.php'
];

foreach ($remotes as $r) {
    if (@ftp_put($conn_id, $r, $local, FTP_BINARY)) {
        echo "SUCCESS: student_nav.php -> $r\n";
    } else {
        echo "FAILED: student_nav.php -> $r\n";
    }
}
ftp_close($conn_id);

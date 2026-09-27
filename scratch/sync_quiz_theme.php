<?php
set_time_limit(120);
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

echo "Connecting to FTP $ftp_server...\n";
$conn_id = @ftp_connect($ftp_server, 21, 30);
if (!$conn_id || !@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("ERROR: FTP connection/login failed!\n");
}
@ftp_pasv($conn_id, true);

$local_file = 'c:/xampp/htdocs/tkb/teacher/quiz.php';
$remotes = [
    '/htdocs/tkb/teacher/quiz.php',
    '/viethan.free.nf/htdocs/tkb/teacher/quiz.php'
];

foreach ($remotes as $remote) {
    if (@ftp_put($conn_id, $remote, $local_file, FTP_BINARY)) {
        echo "SUCCESS: $local_file -> $remote\n";
    } else {
        echo "FAILED: $local_file -> $remote\n";
    }
}
ftp_close($conn_id);
echo "Finished syncing quiz.php!\n";

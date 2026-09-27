<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn = @ftp_connect($ftp_server, 21, 30);
if (!$conn || !@ftp_login($conn, $ftp_user, $ftp_pass)) {
    die("FTP Connection failed\n");
}
@ftp_pasv($conn, true);

$local_file = 'c:/xampp/htdocs/tkb/scratch/inspect_quiz57.php';
@ftp_put($conn, '/htdocs/tkb/student/inspect_quiz57.php', $local_file, FTP_BINARY);
@ftp_put($conn, '/viethan.free.nf/htdocs/tkb/student/inspect_quiz57.php', $local_file, FTP_BINARY);
ftp_close($conn);
echo "Uploaded inspect_quiz57.php!\n";

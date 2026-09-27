<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn = @ftp_connect($ftp_server, 21, 30);
if (!$conn || !@ftp_login($conn, $ftp_user, $ftp_pass)) {
    die("FTP Connection failed\n");
}
@ftp_pasv($conn, true);

$candidates = [
    '/htdocs/tkb/student/cleanup_0_score.php',
    '/viethan.free.nf/htdocs/tkb/student/cleanup_0_score.php',
    '/htdocs/student/cleanup_0_score.php',
    '/viethan.free.nf/htdocs/student/cleanup_0_score.php'
];

foreach ($candidates as $c) {
    @ftp_delete($conn, $c);
}

ftp_close($conn);
echo "Cleanup scripts removed from server!\n";

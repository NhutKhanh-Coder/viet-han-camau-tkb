<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn = @ftp_connect($ftp_server, 21, 30);
if (!$conn || !@ftp_login($conn, $ftp_user, $ftp_pass)) {
    die("FTP Connection failed\n");
}
@ftp_pasv($conn, true);
echo "FTP Connected successfully!\n";

$candidates = [
    '/htdocs/tkb',
    '/viethan.free.nf/htdocs/tkb',
    '/htdocs',
    '/viethan.free.nf/htdocs'
];

$local_file = 'c:/xampp/htdocs/tkb/student/quiz.php';
if (!file_exists($local_file)) {
    die("Local file not found: $local_file\n");
}

foreach ($candidates as $cand) {
    $remote = $cand . '/student/quiz.php';
    $chk = @ftp_size($conn, $remote);
    if ($chk !== -1) {
        echo "FOUND student/quiz.php in: $cand (old size: $chk bytes)\n";
        if (@ftp_put($conn, $remote, $local_file, FTP_BINARY)) {
            echo ">>> SUCCESS: Uploaded updated quiz.php to: $remote\n";
        } else {
            echo ">>> FAILED to upload to: $remote\n";
        }
    } else {
        echo "Not found in $cand\n";
    }
}

ftp_close($conn);
echo "Done!\n";

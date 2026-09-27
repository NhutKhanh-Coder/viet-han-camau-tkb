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

$local_quiz = 'c:/xampp/htdocs/tkb/student/quiz.php';

foreach ($candidates as $cand) {
    $remote_quiz = $cand . '/student/quiz.php';
    $chk = @ftp_size($conn, $remote_quiz);
    if ($chk !== -1) {
        echo "Found: $remote_quiz (size: $chk bytes)\n";
        if (@ftp_put($conn, $remote_quiz, $local_quiz, FTP_BINARY)) {
            echo ">>> SUCCESS: Uploaded updated student/quiz.php to: $remote_quiz (" . filesize($local_quiz) . " bytes)\n";
        } else {
            echo ">>> FAILED to upload to: $remote_quiz\n";
        }
    }
}

ftp_close($conn);
echo "\nDeployment finished!\n";

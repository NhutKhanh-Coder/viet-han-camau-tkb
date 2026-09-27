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

$local_populator = 'c:/xampp/htdocs/tkb/scratch/populate_quiz57_40q.php';

foreach ($candidates as $cand) {
    $remote_runner = $cand . '/student/populate_quiz57_40q.php';
    if (@ftp_put($conn, $remote_runner, $local_populator, FTP_BINARY)) {
        echo ">>> Uploaded populator runner to: $remote_runner\n";
    }
}

ftp_close($conn);
echo "Ready for browser invocation!\n";

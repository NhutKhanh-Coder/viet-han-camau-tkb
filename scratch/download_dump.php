<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn = @ftp_connect($ftp_server, 21, 30);
if (!$conn || !@ftp_login($conn, $ftp_user, $ftp_pass)) {
    die("FTP Connection failed\n");
}
@ftp_pasv($conn, true);

$remote = '/htdocs/tkb/student/dump_quiz_data.txt';
$local = 'c:/xampp/htdocs/tkb/scratch/dump_quiz_data.txt';

if (@ftp_get($conn, $local, $remote, FTP_BINARY)) {
    echo "Downloaded dump_quiz_data.txt successfully!\n";
} else {
    // Try other path
    $remote = '/viethan.free.nf/htdocs/tkb/student/dump_quiz_data.txt';
    if (@ftp_get($conn, $local, $remote, FTP_BINARY)) {
        echo "Downloaded from viethan.free.nf path!\n";
    } else {
        echo "Failed to download\n";
    }
}

// Clean up
@ftp_delete($conn, '/htdocs/tkb/student/run_dump_txt.php');
@ftp_delete($conn, '/htdocs/tkb/student/dump_quiz_data.txt');
@ftp_delete($conn, '/viethan.free.nf/htdocs/tkb/student/run_dump_txt.php');
@ftp_delete($conn, '/viethan.free.nf/htdocs/tkb/student/dump_quiz_data.txt');

ftp_close($conn);

<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';
$conn = @ftp_connect($ftp_server, 21, 30);
if (!$conn || !@ftp_login($conn, $ftp_user, $ftp_pass)) die("FTP fail\n");
@ftp_pasv($conn, true);
@ftp_put($conn, '/htdocs/tkb/scratch_check.php', 'c:/xampp/htdocs/tkb/scratch/check_remote_attempt.php', FTP_BINARY);
@ftp_put($conn, '/viethan.free.nf/htdocs/tkb/scratch_check.php', 'c:/xampp/htdocs/tkb/scratch/check_remote_attempt.php', FTP_BINARY);
ftp_close($conn);
echo "Uploaded scratch_check.php!\n";

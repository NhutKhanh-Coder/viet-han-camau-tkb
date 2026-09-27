<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';
$conn = @ftp_connect($ftp_server, 21, 30);
if (!$conn || !@ftp_login($conn, $ftp_user, $ftp_pass)) die("FTP fail\n");
@ftp_pasv($conn, true);
@ftp_put($conn, '/htdocs/tkb/student/debug_dump.php', 'c:/xampp/htdocs/tkb/scratch/test_db_direct.php', FTP_BINARY);
@ftp_put($conn, '/viethan.free.nf/htdocs/tkb/student/debug_dump.php', 'c:/xampp/htdocs/tkb/scratch/test_db_direct.php', FTP_BINARY);
ftp_close($conn);
echo "Uploaded debug_dump.php to FTP!\n";

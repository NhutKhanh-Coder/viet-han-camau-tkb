<?php
$conn = @ftp_connect('ftpupload.net', 21, 30);
if (!$conn || !@ftp_login($conn, 'if0_41796593', 'T5v3vJeuvOxCI')) die("FTP fail\n");
@ftp_pasv($conn, true);

$local  = 'c:/xampp/htdocs/tkb/admin/ai_studio.php';
$remote = 'viethan.free.nf/htdocs/tkb/admin/ai_studio.php';

if (@ftp_put($conn, $remote, $local, FTP_BINARY)) {
    echo "OK: ai_studio.php synced!\n";
} else {
    echo "FAIL: Could not upload ai_studio.php\n";
}
@ftp_close($conn);

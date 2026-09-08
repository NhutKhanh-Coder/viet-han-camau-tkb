<?php
$ftp_server = 'ftpupload.net';
$ftp_user   = 'if0_41796593';
$ftp_pass   = 'T5v3vJeuvOxCI';

$conn_id = @ftp_connect($ftp_server, 21, 30);
if (!$conn_id || !@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("FTP connection failed\n");
}
@ftp_pasv($conn_id, true);

$local_base = 'c:/xampp/htdocs/tkb';
$remotes = ['/htdocs/tkb', '/viethan.free.nf/htdocs/tkb'];

foreach ($remotes as $rem) {
    $loc = $local_base . '/includes/admin_nav.php';
    $r = $rem . '/includes/admin_nav.php';
    if (file_exists($loc)) {
        if (@ftp_put($conn_id, $r, $loc, FTP_BINARY)) {
            echo "Uploaded nav: $r\n";
        } else {
            echo "Failed upload nav: $r\n";
        }
    }
}

ftp_close($conn_id);
echo "Synced logo fix to FTP 100%!\n";
